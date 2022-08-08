<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class MasterController extends Controller
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
        $cms = (int)$request->cms > 0 ? (int)$request->cms : 0;
        $setting = DB::table('setting')->get()->toArray();
        $out = array();
        if (!empty($setting)) {
            foreach ($setting as $val) {
                $out[$val->setting_key] = $val->setting_val;
            }
        }
        if ($cms == 0) {
            unset($out['mail_pass']);
            unset($out['send_mail']);           
            unset($out['content_forgotPass']);           
            unset($out['content_forgotPin']);
            unset($out['about_us']);                  
        }
		
		unset($out['content_reg']);
		unset($out['subj_email_register']);
        unset($out['subj_email_forgot']);   
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $out
        );
        $id_member = (int)$request->id_member > 0 ? Helper::last_login((int)$request->id_member) : 0;
        return response($result);
    }

    function upd_setting(Request $request){
		$input  = $request->all();
		foreach($input as $key=>$val){
			$where = array();
			$dt = array();
			if($key == 'hrg_instant' || $key == 'hrg_sameday_6km' || $key == 'hrg_sameday_15km' || $key == 'hrg_sameday_diatas_15km' || $key == 'min_perhitungan_jarak'){
				$val = str_replace(',','',$val);
			}
			$where = array("setting_key"=>"$key");
			$dt = ["setting_val" => "$val"];
			DB::table('setting')->where($where)->update($dt);
		}
		$result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $input
        );
		return response($result);
	}
	
	
	
	
}
