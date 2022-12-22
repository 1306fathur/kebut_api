<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CargoController extends Controller
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
        $panjang = (int)$request->panjang;
        $lebar = (int)$request->lebar;
        $tinggi = (int)$request->tinggi;
        $volume = (int)$request->volume;

        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama_cargo';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;

        if ($sort_column == 'id_ac') $sort_column = "ABS(id_ac)";
        $sort_column .= ' ' . $sort_order;
        $where = array('deleted_at' => null);
        $count = 0;
        $_data = array();
        $data = array();
        if (!empty($keyword)) {
            $_data = DB::table('armada_cargo')->select('armada_cargo.*')
                ->where('panjang', '>=', $panjang)
                ->where('lebar', '>=', $lebar)
                ->where('tinggi', '>=', $tinggi)
                ->where('volume', '>=', $volume)
                ->where($where)->whereRaw("LOWER(nama_cargo) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('armada_cargo')
                ->where('panjang', '>=', $panjang)
                ->where('lebar', '>=', $lebar)
                ->where('tinggi', '>=', $tinggi)
                ->where('volume', '>=', $volume)
                ->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('armada_cargo')->select('armada_cargo.*')
                ->where('panjang', '>=', $panjang)
                ->where('lebar', '>=', $lebar)
                ->where('tinggi', '>=', $tinggi)
                ->where('volume', '>=', $volume)
                ->where($where)->offset($offset)->limit($per_page)->orderByRaw($sort_column)->get();
        }
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ($count > 0) {
            foreach ($_data as $d) {
                $path_img = null;
                $path_img  = !empty($d->img) ? env('PUBLIC_URL') . '/uploads/armada_cargo/' . $d->img : null;
                unset($d->created_by);
                unset($d->updated_by);
                unset($d->deleted_by);
                unset($d->created_at);
                unset($d->updated_at);
                unset($d->deleted_at);
                unset($d->img);
                $d->img = $path_img;
                $data[] = $d;
            }
            $result = array(
                'err_code'      => '00',
                'err_msg'          => 'ok',
                // 'app_key'		=> env('APP_KEY'),
                'total_data'    => $count,
                'data'          => $data
            );
        }
        return response($result);
    }

    function store(Request $request)
    {
        $result = array();
        $tgl = date('Y-m-d H:i:s');
        $_tgl = date('YmdHi');
        $data = array();
        $id = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;

        $path_img = $request->file("img");
        $data = array(
            'nama_cargo'           => $request->nama_cargo,
            'panjang'           => $request->has('panjang') && !empty($request->panjang) ? $request->panjang : '',
            'lebar'               => $request->has('lebar') && !empty($request->lebar) ? $request->lebar : '',
            'tinggi'               => $request->has('tinggi') && !empty($request->tinggi) ? $request->tinggi : '',
            'volume'               => $request->has('volume') && !empty($request->volume) ? $request->volume : '',
            'kap'               => $request->has('kap') && !empty($request->kap) ? $request->kap : '',
            'golongan_tol'       => $request->has('golongan_tol') && !empty($request->golongan_tol) ? $request->golongan_tol : '',
            'golongan_ferry'       => $request->has('golongan_ferry') && !empty($request->golongan_ferry) ? $request->golongan_ferry : '',
            'free_bongkar_muat'       => $request->has('free_bongkar_muat') && !empty($request->free_bongkar_muat) ? $request->free_bongkar_muat : '',
        );

        if (!empty($path_img)) {
            $randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
            $nama_file = base64_encode($_tgl . "" . $randomletter);
            $fileSize = $path_img->getSize();
            $extension = $path_img->getClientOriginalExtension();
            $imageName = $nama_file . '.' . $extension;
            $tujuan_upload = 'uploads/armada_cargo';

            $_extension = array('png', 'jpg', 'jpeg');
            if ($fileSize > 2099200) { // satuan bytes
                $result = array(
                    'err_code'  => '07',
                    'err_msg'   => 'file size over 2048',
                    'data'      => $fileSize
                );
                return response($result);
                return false;
            }
            if (!in_array($extension, $_extension)) {
                $result = array(
                    'err_code'  => '07',
                    'err_msg'   => 'file extension not valid',
                    'data'      => null
                );
                return response($result);
                return false;
            }
            $path_img->move($tujuan_upload, $imageName);
            $data += array("img" => $imageName);
        }
        if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('armada_cargo')->where('id_ac', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl, "created_by" => $request->id_operator);
            $id = DB::table('armada_cargo')->insertGetId($data, "id_ac");
        }

        if ($id > 0) {
            $_data = DB::table('armada_cargo')->where(array('id_ac' => $id))->first();
            $path_img = null;
            $path_img  = !empty($_data->img) ? env('PUBLIC_URL') . '/uploads/armada_cargo/' . $_data->img : null;
            $_data->img = $path_img;
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $_data
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
        $id = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => $request->id_operator);
        DB::table('armada_cargo')->where('id_ac', $id)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }

    function detail(Request $request)
    {
        $id = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;
        $where = array('deleted_at' => null, 'id_ac' => $id);
        $count = 0;
        $count = DB::table('armada_cargo')->where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => $id
        );
        if ($count > 0) {
            $data = DB::table('armada_cargo')->where($where)->first();
            $photo = '';
            $photo = !empty($data->img) ? env('PUBLIC_URL') . '/uploads/armada_cargo/' . $data->img : '';
            unset($data->img);
            $data->img = $photo;

            $sort_column = 'id_bi';
            if ($sort_column == 'id_bi') $sort_column = "ABS(id_bi)";
            $sort_column .= ' ASC';

            $data_bi = DB::table('biaya_inap')->select('id_bi', 'jml', 'biaya')->where($where)->orderByRaw($sort_column)->get();

            $sort_column = 'id_asuransi';
            if ($sort_column == 'id_asuransi') $sort_column = "ABS(id_asuransi)";
            $sort_column .= ' ASC';
            $where = array('deleted_at' => null, 'id_cargo' => $id);
            $data_asuransi = DB::table('asuransi')->select('id_asuransi', 'nilai_asuransi', 'biaya')->where($where)->orderByRaw($sort_column)->get();

            $sort_column = 'id_bm';
            if ($sort_column == 'id_bm') $sort_column = "ABS(id_bm)";
            $sort_column .= ' ASC';

            $data_bm = DB::table('bongkar_muat')->select('id_bm', 'lama_bongkar_muat', 'free_bongkar_muat', 'tambahan_biaya_per_jam')->where($where)->orderByRaw($sort_column)->get();
            $data->asuransi = count($data_asuransi) > 0 ? $data_asuransi : null;
            $data->bongkar_muat = count($data_bm) > 0 ? $data_bm : null;
            $data->biaya_inap = count($data_bi) > 0 ? $data_bi : null;
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        }

        return response($result);
    }
}
