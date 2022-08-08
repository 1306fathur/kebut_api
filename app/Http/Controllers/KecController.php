<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KecController extends Controller
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
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama_kec';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        $where = array('kecamatan.deleted_at' => null);
        if ((int)$request->id_city > 0) $where += array('kecamatan.id_city' => $request->id_city);
        $count = 0;
        $_data = array();
        $data = null;
        if (!empty($keyword)) {
            $_data = DB::table('kecamatan')->where($where)->select('kecamatan.*', 'nama_provinsi', 'nama_city')
                ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                ->whereRaw("LOWER(nama_kec) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('kecamatan')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('kecamatan')->where($where)->select('kecamatan.*', 'nama_provinsi', 'nama_city')
                ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                ->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }
		$provinsi = '';
		$city = '';
		if($count == 0){
			$data_city = DB::table('kota')->where(array('id_kota' => $request->id_city))->first();
			$data_prov = DB::table('provinsi')->where(array('id_provinsi' => $data_city->id_prov))->first();
			$provinsi = $data_prov->nama_provinsi;
			$city = $data_city->nama_city;
		}
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'provinsi'      => $provinsi,
            'city'          => $city,
            'data'          => null
        );
        if ($count > 0) {
            $provinsi = $_data[0]->nama_provinsi;
            $city = $_data[0]->nama_city;
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
                'err_msg'       => 'ok',
                'total_data'    => $count,
                'provinsi'      => $provinsi,
                'city'          => $city,
                'data'          => $data
            );
        }
        return response($result);
    }

    function store(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $data = array();
        $id = (int)$request->id_kec > 0 ? (int)$request->id_kec : 0;
		$kode_kec = $request->has('kode_kec')  ? $request->kode_kec : '';
        $data = array(
            'nama_kec' => $request->nama_kec,
            'kode_kec' => $kode_kec
        );
        if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('kecamatan')->where('id_kec', $id)->update($data);
        } else {
            $data += array(
                "id_city"    => (int)$request->id_city,
                "created_at" => $tgl,
                "created_by" => $request->id_operator
            );
            $id = DB::table('kecamatan')->insertGetId($data, "id_kec");
        }
        $result = array();
        if ($id > 0) {
            $data += array('id_kec' => $id);
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
        $id = (int)$request->id_kec > 0 ? (int)$request->id_kec : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => $request->id_operator);
        DB::table('kecamatan')->where('id_kec', $id)->update($data);
		$where = array('deleted_at' => null, 'id_kec' => $id);
        DB::table('kelurahan')->where($where)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }
}
