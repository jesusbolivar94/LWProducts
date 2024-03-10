<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSeedRequest;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function seed(UploadSeedRequest $request)
    {
        dd($request->files);
    }
}
