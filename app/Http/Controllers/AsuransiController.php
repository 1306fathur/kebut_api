<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class AsuransiController extends Controller
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
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nilai_asuransi';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        $id_cargo = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;
        
		if($sort_column == 'id_asuransi') $sort_column = "ABS(id_asuransi)";
		$sort_column .=' '.$sort_order;
        $where = array('deleted_at' => null,'id_cargo'=>$id_cargo);
        $count = 0;
        $_data = array();
        $data = array();
        if (!empty($keyword)) {
            $_data = DB::table('asuransi')->select('asuransi.*')                
                ->where($where)->whereRaw("LOWER(nilai_asuransi) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('asuransi')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('asuransi')->select('asuransi.*')                
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
                unset($d->created_by);
                unset($d->updated_by);
                unset($d->deleted_by);
                unset($d->created_at);
                unset($d->updated_at);
                unset($d->deleted_at);
                unset($d->img);                
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
        $id = (int)$request->id_asuransi > 0 ? (int)$request->id_asuransi : 0;   
		$id_cargo = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;
        $data = array(            
            'nilai_asuransi'   	=> !empty($request->nilai_asuransi) ? $request->nilai_asuransi : '',
            'biaya'   			=> !empty($request->biaya) ? str_replace(',', '', $request->biaya) : '',
        );       
        
        if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('asuransi')->where('id_asuransi', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl, "created_by" => $request->id_operator,"id_cargo"=>$id_cargo);
            $id = DB::table('asuransi')->insertGetId($data, "id_asuransi");
        }

        if ($id > 0) {            
			$_data = DB::table('asuransi')->where(array('id_asuransi' => $id))->first();		
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
        $id = (int)$request->id_asuransi > 0 ? (int)$request->id_asuransi : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => $request->id_operator);
        DB::table('asuransi')->where('id_asuransi', $id)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }

    
}
