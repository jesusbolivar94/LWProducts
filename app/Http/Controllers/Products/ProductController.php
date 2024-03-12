<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSeedRequest;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function seed(UploadSeedRequest $request)
    {
        // Set the default character set for the script
        mb_internal_encoding('UTF-8');

        $tables = [
            '\App\Models\Architecture',
            '\App\Models\Location',
            '\App\Models\Currency',
            '\App\Models\CharacteristicsUnit',
            '\App\Models\Characteristic',
            '\App\Models\Product'
        ];

        // Clean tables ignore foreign key constraints
        foreach ($tables as $table) {
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $table::truncate();
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $seed = $request->file('file');

        // Reed the file using the `PhpSpreadsheet` library.
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($seed);

        // Get the first sheet.
        $sheet = $spreadsheet->getActiveSheet();

        // Get the highest row and column.
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Check that B1 is equal to 'RAM'
        if ($sheet->getCell('B1')->getValue() !== 'RAM') {
            return response()->json(['message' => 'The file is not valid.'], 400);
        }

        // Check that C1 is equal to 'HDD'
        if ($sheet->getCell('C1')->getValue() !== 'HDD') {
            return response()->json(['message' => 'The file is not valid.'], 400);
        }

        // Check that D1 is equal to 'Location'
        if ($sheet->getCell('D1')->getValue() !== 'Location') {
            return response()->json(['message' => 'The file is not valid.'], 400);
        }

        // Check that E1 is equal to 'Price'
        if ($sheet->getCell('E1')->getValue() !== 'Price') {
            return response()->json(['message' => 'The file is not valid.'], 400);
        }

        // Fill architectures table with the data from the file: Column B contains a string, extract DDR version and fill architectures table with it. Example: 16GBDDR3
        for ($row = 2; $row <= $highestRow; $row++) {
            $ram = $sheet->getCell('B' . $row)->getValue();
            // Define a regular expression to capture the DDR version and size
            $pattern = '/\d+GB([A-Za-z]+)/';

            if (preg_match($pattern, $ram, $matches)) {
                // Extract architecture name and version
                $architecture_name = $matches[1];
                $architecture_version = null;
                // If the version is available, extract it
                if (preg_match('/(\d+)$/', $ram, $version_matches)) {
                    $architecture_version = $version_matches[1];
                }
                \App\Models\Architecture::firstOrCreate(['name' => $architecture_name, 'version' => $architecture_version]);
            }
        }

        // Fill architectures table with the data from the file: Column C contains a string, extract HDD version and fill architectures table with it. Example: 2x2TBSATA2, 4x480GBSSD, 8x300GBSAS. Valid values are: SATA2, SSD, SAS.
        for ($row = 2; $row <= $highestRow; $row++) {
            $hdd = $sheet->getCell('C' . $row)->getValue();
            $pattern = '/(\d+x\d+GB)(SATA|SSD|SAS)(\d*)/i';

            if (preg_match($pattern, $hdd, $matches)) {
                $version = is_numeric($matches[3]) ? $matches[3] : '';
                \App\Models\Architecture::firstOrCreate([
                    'name' => strtoupper($matches[2]), // Convert to uppercase for consistency
                    'version' => $version,
                ]);
            }
        }

        // Fill Locations table with the data from the file.
        for ($row = 2; $row <= $highestRow; $row++) {
            $location = $sheet->getCell('D' . $row)->getValue();
            \App\Models\Location::firstOrCreate(['name' => $location]);
        }

        // Fill currencies table with the data from the file. Column E contains a price, the first character is the currency symbol. Store ISO 4217 currency code in the currencies table.
        for ($row = 2; $row <= $highestRow; $row++) {
            $price = $sheet->getCell('E' . $row)->getValue();

            $pattern = '/^(\D+)(\w+)/';

            if (preg_match($pattern, $price, $matches)) {
                // Remove any weird characters from the symbol
                $symbol = mb_substr($matches[1], 0, 1);

                $currencyMap = [
                    '$' => 'USD',
                    '€' => 'EUR',
                    'S' => 'SGD',
                ];

                if ( isset($currencyMap[$symbol]) ) {
                    \App\Models\Currency::firstOrCreate(['name' => $currencyMap[$symbol]]);
                }
            }
        }

        // Fill Units table with the data from the file. Column B and Column C contains strings, possible formats are something like: 16GBDDR3 on B and 2x2TBSATA2 on C. Where GB and TB are the units.
        for ($row = 2; $row <= $highestRow; $row++) {
            $ram = $sheet->getCell('B' . $row)->getValue();
            $hdd = $sheet->getCell('C' . $row)->getValue();

            $pattern = '/(\d+)(GB|TB)(DDR3|DDR4|SATA|SSD|SAS)/';

            if (preg_match($pattern, $ram, $matches)) {
                \App\Models\CharacteristicsUnit::firstOrCreate(['name' => $matches[2]]);
            }

            if (preg_match($pattern, $hdd, $matches)) {
                \App\Models\CharacteristicsUnit::firstOrCreate(['name' => $matches[2]]);
            }
        }

        /**
         * Fill Characteristics table with the data from the file. And also fill characteristic_architecture_id field with the id of the architecture, and characteristic_unit_id with the id of the unit.
         * And fill capacity field, for example: in 16GBDDR3, 16 is the capacity. And in 2x2TBSATA2, 2TB is the capacity but only save 2.
         */
        for ($row = 2; $row <= $highestRow; $row++) {
            $ram = $sheet->getCell('B' . $row)->getValue();
            $hdd = $sheet->getCell('C' . $row)->getValue();

            $pattern = '/(\d+)(GB|TB)(DDR|SATA|SSD|SAS)/';

            if (preg_match($pattern, $ram, $matches)) {
                $capacity = $matches[1];
                $unit = \App\Models\CharacteristicsUnit::where('name', $matches[2])->first();
                $architecture = \App\Models\Architecture::where('name', $matches[3])->first();

                if ( is_null($unit) ) dd($matches[2]);

                \App\Models\Characteristic::firstOrCreate([
                    'capacity' => $capacity,
                    'characteristic_architecture_id' => $architecture->id,
                    'characteristic_unit_id' => $unit->id,
                ]);
            }

            if (preg_match($pattern, $hdd, $matches)) {
                $capacity = $matches[1];
                $unit = \App\Models\CharacteristicsUnit::where('name', $matches[2])->first();
                $architecture = \App\Models\Architecture::where('name', $matches[3])->first();

                \App\Models\Characteristic::firstOrCreate([
                    'capacity' => $capacity,
                    'characteristic_architecture_id' => $architecture->id,
                    'characteristic_unit_id' => $unit->id,
                ]);
            }
        }

        /**
         * Fill the products table with the data from the file.
         * Column A contains a string, the product name.
         * Column D contains a string, the location name. Use the location name to get the location id and fill the location_id field.
         * Column E contains a price, the first character is the currency symbol. Use the symbol to identify ISO 4217 currency code, get the currency id and fill the currency_id field.
         * Use the rest of the price to fill the price field.
         */
        for ($row = 2; $row <= $highestRow; $row++) {
            $name = $sheet->getCell('A' . $row)->getValue();
            $location = $sheet->getCell('D' . $row)->getValue();
            $price = $sheet->getCell('E' . $row)->getValue();

            $location_id = \App\Models\Location::where('name', $location)->first()->id;

            $pattern = '/^(\D+)(\w+)/';
            $currency_id = null;
            if (preg_match($pattern, $price, $matches)) {
                // Remove any weird characters from the symbol
                $symbol = mb_substr($matches[1], 0, 1);

                $currencyMap = [
                    '$' => 'USD',
                    '€' => 'EUR',
                    'S' => 'SGD',
                ];

                // Singapure dollar prices should not contain $ symbol
                if ($symbol === 'S') {
                    $price = mb_substr($price, 1);
                }

                if ( isset($currencyMap[$symbol]) ) {
                    $currency_id = \App\Models\Currency::where('name', $currencyMap[$symbol])->first()->id;
                }
            }

            $price = mb_substr($price, 1);

            $product = \App\Models\Product::firstOrCreate([
                'name' => $name,
                'location_id' => $location_id,
                'currency_id' => $currency_id,
                'price' => $price,
            ]);
        }

        return response()->json(['message' => 'File uploaded successfully.']);
    }
}
