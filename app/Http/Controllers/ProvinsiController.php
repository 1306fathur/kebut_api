<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProvinsiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //

    public function index(Request $request)
    {
        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;		
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama_provinsi';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
		$is_wh = (int)$request->is_wh > 0 ? (int)$request->is_wh : 0;
        $where = array('deleted_at' => null);
		if($is_wh > 0) $where += array('id_wh' => 0);
        $count = 0;
        $_data = array();
        $data = null;
        if (!empty($keyword)) {
            $_data = DB::table('provinsi')->where($where)->whereRaw("LOWER(nama_provinsi) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('provinsi')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('provinsi')->where($where)->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ($count > 0) {
            foreach ($_data as $d) {
                unset($d->created_by);
                unset($d->updated_by);
                unset($d->deleted_by);
                unset($d->created_at);
                unset($d->updated_at);
                unset($d->deleted_at);
                $data[] = $d;
            }
            $result = array(
                'err_code'      => '00',
                'err_msg'          => 'ok',
                'total_data'    => $count,
                'data'          => $data
            );
        }
        return response($result);
    }

    function store(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $data = array();
        $id = (int)$request->id_provinsi > 0 ? (int)$request->id_provinsi : 0;
		$kode_provinsi = $request->has('kode_provinsi')  ? $request->kode_provinsi : '';
        $data = array(
            'nama_provinsi' => $request->nama_provinsi,
            'kode_provinsi'	=> $kode_provinsi
        );

        if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('provinsi')->where('id_provinsi', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl, "created_by" => $request->id_operator);
            $id = DB::table('provinsi')->insertGetId($data, "id_provinsi");
        }
        $result = array();
        if ($id > 0) {
            $data += array('id_provinsi' => $id);
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        } else {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'insert has problem',
                'data'      => null
            );
        }
        return response($result);
    }

    function proses_delete(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id = (int)$request->id_provinsi > 0 ? (int)$request->id_provinsi : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => (int)$request->id_operator);
        DB::table('provinsi')->where('id_provinsi', $id)->update($data);
        $where = array('deleted_at' => null, 'id_prov' => $id);        
        DB::table('kota')->where($where)->update($data);        
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }
		
}
