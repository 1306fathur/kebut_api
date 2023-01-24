<?php

namespace App\Http\Controllers;

use App\Imports\Mapping_origin_destination;
use Illuminate\Http\Request;
//use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function ongkir(Request $request)
    {
        //use Maatwebsite\Excel\Facades\Excel;
        $file = $request->file('file');
        //add parameter id_ac to import function
        $id_ac = $request->id_ac;
        Excel::import(new Mapping_origin_destination($id_ac), $file);
        return response()->json([
            'message' => 'success'
        ], 200);
    }
}
