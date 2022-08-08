<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OutletController extends Controller
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
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama_outlet';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        
		if($sort_column == 'id_outlet') $sort_column = "ABS(id_outlet)";
		$sort_column .=' '.$sort_order;
        $where = array('outlet.deleted_at' => null);
        $count = 0;
        $_data = array();
        $data = array();
        if (!empty($keyword)) {
            $_data = DB::table('outlet')->select('outlet.*')                
                ->where($where)->whereRaw("LOWER(nama_outlet) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('outlet')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('outlet')->select('outlet.*')                
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
                $path_img  = !empty($d->img) ? env('PUBLIC_URL') . '/uploads/outlets/' . $d->img : null;
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
        $id = (int)$request->id_outlet > 0 ? (int)$request->id_outlet : 0;
		
        $path_img = $request->file("img");
        $data = array(            
            'nama_outlet'   => $request->nama_outlet,
            'alamat'   		=> $request->has('alamat') && !empty($request->alamat) ? $request->alamat : '',
            'phone'   		=> $request->has('phone') && !empty($request->phone) ? $request->phone : '',
            'telp'   		=> $request->has('telp') && !empty($request->telp) ? $request->telp : '',
            'longitude'   	=> $request->has('longitude') && !empty($request->longitude) ? $request->longitude : '',
            'latitude'   	=> $request->has('latitude') && !empty($request->latitude) ? $request->latitude : '',
        );
		
       
        if (!empty($path_img)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $path_img->getSize();
            $extension = $path_img->getClientOriginalExtension();
            $imageName = $nama_file . '.' . $extension;            
            $tujuan_upload = 'uploads/outlets';
			
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
            DB::table('outlet')->where('id_outlet', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl, "created_by" => $request->id_operator);
            $id = DB::table('outlet')->insertGetId($data, "id_outlet");
        }

        if ($id > 0) {            
			$_data = DB::table('outlet')->where(array('id_outlet' => $id))->first();     
			$path_img = null;
			$path_img  = !empty($_data->img) ? env('PUBLIC_URL') . '/uploads/outlets/' . $_data->img : null;
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
        $id = (int)$request->id_outlet > 0 ? (int)$request->id_outlet : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => $request->id_operator);
        DB::table('outlet')->where('id_outlet', $id)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }

	function detail(Request $request){
		$id = (int)$request->id_outlet > 0 ? (int)$request->id_outlet : 0;
		$where = array('deleted_at' => null, 'id_outlet' => $id);        
		$count = 0;		
        $count = DB::table('outlet')->where($where)->count();	
		$result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => $id
        );
		if($count > 0){
			$data = DB::table('outlet')->where($where)->first();
			$photo = '';
            $photo = !empty($data->img) ? env('PUBLIC_URL') . '/uploads/outlets/' . $data->img : '';
            unset($data->img);			
			$data->img = $photo;			
			$result = array(
				'err_code'  => '00',
				'err_msg'   => 'ok',
				'data'      => $data
			);
		}
		
		return response($result);
	}
    
}
