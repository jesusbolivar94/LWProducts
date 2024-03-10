<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSeedRequest;
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
        ];

        // Clean tables
        foreach ($tables as $table) {
            $table::query()->delete();
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
            $architecture = substr($ram, -4);
            \App\Models\Architecture::firstOrCreate(['name' => $architecture]);
        }

        // Fill architectures table with the data from the file: Column C contains a string, extract HDD version and fill architectures table with it. Example: 2x2TBSATA2, 4x480GBSSD, 8x300GBSAS. Valid values are: SATA2, SSD, SAS.
        for ($row = 2; $row <= $highestRow; $row++) {
            $hdd = $sheet->getCell('C' . $row)->getValue();
            $pattern = '/(\d+x\d+GB)(SATA|SSD|SAS)/';

            if (preg_match($pattern, $hdd, $matches)) {
                \App\Models\Architecture::firstOrCreate(['name' => $matches[2]]);
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
                ];

                if ( isset($currencyMap[$symbol]) ) {
                    \App\Models\Currency::firstOrCreate(['name' => $currencyMap[$symbol]]);
                }
            }
        }

        return response()->json(['message' => 'File uploaded successfully.']);
    }
}
