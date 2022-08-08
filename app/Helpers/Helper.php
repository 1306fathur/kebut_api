<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Helper
{
	
	

   static function last_login($id_member = 0)
    {
        $tgl = date('Y-m-d H:i:s');
        DB::table('members')->where('id_member', $id_member)->update(['last_login' => $tgl]);
        return $id_member;
    }	
	

    static function send_fcm($id_member=0,$data_fcm=array(),$notif_fcm=array()){
		$url = 'https://fcm.googleapis.com/fcm/send';		
		$server_key = env('FCM_KEY');
		$fields = array();		
		$result = array();		
		$fields['data'] = $data_fcm;
		$fields['notification'] = $notif_fcm;
		$where = array('id_member'=>$id_member);
		$fcm_token = DB::table('fcm_token')->where($where)->get(); 
		$target = array();
		if(!empty($fcm_token)){
			foreach($fcm_token as $dt){
				array_push($target ,$dt->token_fcm);
			}
			$fields['registration_ids'] = $target;
			$headers = array(
				'Content-Type:application/json',
				'Authorization:key='.$server_key
			);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('FCM Send Error: ' . curl_error($ch));
			}
			curl_close($ch);
		}	
		Log::info("push notif :".$id_member);
		Log::info($result);
		return $result;
		
	}
	
	static function iPay88_signature($source){
	   return base64_encode(hex2bin(sha1($source)));
	}
	
	static function hex2bin($hexSource){
		for ($i=0;$i<strlen($hexSource);$i=$i+2)
		{
		  $bin .= chr(hexdec(substr($hexSource,$i,2)));
		}
	  return $bin;
	}   
	
	
}
