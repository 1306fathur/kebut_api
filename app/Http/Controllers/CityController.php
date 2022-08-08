<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
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
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama_city';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        $where = array('kota.deleted_at' => null);
        if ((int)$request->id_provinsi > 0) $where += array('kota.id_prov' => $request->id_provinsi);
        $count = 0;
        $_data = array();
        $data = null;
        if (!empty($keyword)) {
            $_data = DB::table('kota')->where($where)->select('kota.*', 'nama_provinsi')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                ->whereRaw("LOWER(nama_city) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('kota')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('kota')->where($where)->select('kota.*', 'nama_provinsi')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                ->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }
        $provinsi = '';
        if ($count == 0) {
            $data_prov = DB::table('provinsi')->where(array('id_provinsi' => $request->id_provinsi))->first();
            $provinsi = $data_prov->nama_provinsi;
        }
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'provinsi'      => $provinsi,
            'data'          => null
        );
        if ($count > 0) {
            $provinsi = $_data[0]->nama_provinsi;
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
                'provinsi'      => $provinsi,
                'data'          => $data
            );
        }
        return response($result);
    }

    function store(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $data = array();
        $id = (int)$request->id_kota > 0 ? (int)$request->id_kota : 0;
        $kode_city = $request->has('kode_city')  ? $request->kode_city : '';
        $data = array(
            'nama_city' => $request->nama_city,
            'kode_city' => $kode_city,
        );
        if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('kota')->where('id_kota', $id)->update($data);
        } else {
            $data += array(
                "id_prov"    => (int)$request->id_provinsi,
                "created_at" => $tgl,
                "created_by" => $request->id_operator
            );
            $id = DB::table('kota')->insertGetId($data, "id_kota");
        }
        $result = array();
        if ($id > 0) {
            $data += array('id_kota' => $id);
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
        $id = (int)$request->id_kota > 0 ? (int)$request->id_kota : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => (int)$request->id_operator);
        DB::table('kota')->where('id_kota', $id)->update($data);
        $where = array('deleted_at' => null, 'id_city' => $request->id);
        DB::table('kecamatan')->where($where)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }
}
