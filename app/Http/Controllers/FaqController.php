<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
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

    public function cust(Request $request)
    {
        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'id_faq';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
		if($sort_column == 'priority_number') $sort_column = "ABS(id_faq)";
        $where = array('deleted_at' => null, 'tipe'=>1);
        $count = 0;
        $_data = array();
        $data = null;
        if (!empty($keyword)) {
            $_data = DB::table('faq')->where($where)->whereRaw("LOWER(question) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('faq')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('faq')->where($where)->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ($count > 0) {
            
            $result = array(
                'err_code'      => '00',
                'err_msg'          => 'ok',
                'total_data'    => $count,
                'data'          => $_data
            );
        }
        return response($result);
    }
	
	public function driver(Request $request)
    {
        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'id_faq';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
		if($sort_column == 'priority_number') $sort_column = "ABS(id_faq)";
        $where = array('deleted_at' => null, 'tipe'=>2);
        $count = 0;
        $_data = array();
        $data = null;
        if (!empty($keyword)) {
            $_data = DB::table('faq')->where($where)->whereRaw("LOWER(question) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('faq')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('faq')->where($where)->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ($count > 0) {
            
            $result = array(
                'err_code'      => '00',
                'err_msg'          => 'ok',
                'total_data'    => $count,
                'data'          => $_data
            );
        }
        return response($result);
    }

    
    function store(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $_tgl = date('YmdHi');
        $data = array();
        $id = (int)$request->id_faq > 0 ? (int)$request->id_faq : 0;
        
        $data = array(
            'question' 	=> $request->question,
            'answer' 	=> $request->answer,            
        );
       
        if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('faq')->where('id_faq', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl, "created_by" => $request->id_operator,'tipe' => (int)$request->tipe > 0 ? (int)$request->tipe : 1);
            $id = DB::table('faq')->insertGetId($data, "id_faq");
        }
        $result = array();
        if ($id > 0) {
            $data += array('id_faq' => $id);
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
	
	function detail(Request $request){
		$id = (int)$request->id_faq > 0 ? (int)$request->id_faq : 0;
		$data = '';
		$data = DB::table('faq')->where(array('id_faq' => $id))->first();
		$result = array();
		$result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $data
        );
		return response($result);
	}

    function proses_delete(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id = (int)$request->id_faq > 0 ? (int)$request->id_faq : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => $request->id_operator);
        DB::table('faq')->where('id_faq', $id)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }
}
