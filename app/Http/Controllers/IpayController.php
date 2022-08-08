<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\Helper;

class IpayController extends Controller
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

    public function redirect(Request $request)
    {
		$input  = $request->all();
		$tgl = date('Y-m-d H:i:s');
        $MerchantCode = $request->MerchantCode;
        $PaymentId = $request->PaymentId;
        $RefNo = $request->RefNo;
        $Amount = $request->Amount;
        $Currency = $request->Currency;
        $Remark = $request->Remark;
        $id_transaksi = $request->RefNo;
        $AuthCode = $request->AuthCode;
        $estatus = (int)$request->Status;
        $ErrDesc = $request->ErrDesc;
        $Signature = $request->Signature;
        $TransId = $request->TransId;
		$log_payment = serialize($input);
		$AmountFormat = number_format(substr($Amount, 0, -2));
		Log::info($input);
		$result = array();
		$where = array('id_transaksi'=>$id_transaksi,'status'=>1);
		$no_va = $request->VirtualAccountAssigned;
        if($estatus==1){
            $status=4;
			$data = array('status'=>4,'tgl_payment_ipay88'=> $tgl,'log_payment'=>$log_payment,'no_va'=>$no_va,'transid_ipay'=>$TransId);
			
			DB::table('transaksi')->where($where)->update($data);
			
			Log::info((DB::getQueryLog()));
            $message="Payment Successful";
			echo '<script>console.log(\'RECEIVEOK\');</script>';
			echo "RECEIVEOK";
			print_r("RECEIVEOK");
			
			exit;
        }else if($estatus == 6){
			
			$payment_name = '';
			if($PaymentId == 34) $payment_name = 'Credit Card';
			if($PaymentId == 26) $payment_name = 'BNI VA';
			if($PaymentId == 17) $payment_name = 'Mandiri VA';
			if($PaymentId == 9) $payment_name = 'Maybank VA';
			if($PaymentId == 31) $payment_name = 'Permata VA';
			 $message="LANJUTKAN PEMBAYARAN";
			echo '<script>console.log(\'LANJUTKAN PEMBAYARAN\');</script>';
			echo "LANJUTKAN PEMBAYARAN";
			print_r("LANJUTKAN PEMBAYARAN");
// 			echo 'PaymentId pakai variable $PaymentId : '.$PaymentId."\n";
// 			echo 'Amount pakai variable $Amount : '.$Amount."\n";
// 			echo 'No.VA pakai variable $no_va : '.$no_va."\n";
// 			echo 'Transaction VA Pending';
			exit;
		}else{
            $status=0;
            $message="Payment Failed";
			$data = array('log_payment'=>$log_payment);
			// DB::enableQueryLog();
			DB::table('transaksi')->where($where)->update($data);
			Log::info((DB::getQueryLog()));
			echo '<script>console.log(\'RECEIVEFALSE\');</script>';		
			echo "RECEIVEFALSE";
            print_r("RECEIVEFALSE");
           
            exit;
        }
		
        //return response($res);
		// Log::info($status);
		 return response($result);
    }

    function notify(Request $request)
    {
        $input  = $request->all();
		$tgl = date('Y-m-d H:i:s');
        $MerchantCode = $request->MerchantCode;
        $PaymentId = $request->PaymentId;
        $RefNo = $request->RefNo;
        $Amount = $request->Amount;
        $Currency = $request->Currency;
        $Remark = $request->Remark;
        $id_transaksi = $request->RefNo;
        $AuthCode = $request->AuthCode;
        $Status = $request->Status;
        $ErrDesc = $request->ErrDesc;
        $estatus = $request->Signature;
		$log_payment = serialize($input);
		
        $MerchantKey_response 	= env('MERCHANT_KEY');
		$MerchantCode_response 	= $MerchantCode;
		$PaymentId_response	= $PaymentId;
		$RefNo_response         = $RefNo;
		$HashAmount_response 	= $Amount;
		$Currency_response 	= $Currency;
		$Status_response 	= $estatus;
		Log::info($input);
		$merchant_signature	= "";		
		$merchant_encrypt	= sha1($MerchantKey_response.$MerchantCode_response.$PaymentId_response.$RefNo_response.$HashAmount_response.$Currency_response.$Status_response);		
		// Log::info($input);
		for ($i=0; $i<strlen($merchant_encrypt); $i=$i+2){	
			$merchant_signature .= chr(hexdec(substr($merchant_encrypt,$i,2)));
		}     	
		$merchant_signature_check = base64_encode($merchant_signature);
		if($estatus=='1' && $merchant_signature_check==$signature){
            $status=4;
			$data = array('status'=>4,'tgl_payment_ipay88'=> $tgl,'log_payment'=>$log_payment);
			// DB::enableQueryLog();
			DB::table('transaksi')->where('id_transaksi', $id_transaksi)->update($data);
			// Log::info((DB::getQueryLog()));
            $message="Payment Successful";
			// echo '<script>console.log(\'RECEIVEOK\');</script>';
			// print_r("RECEIVEOK");exit;
			 echo "RECEIVEOK";
        }else{
            $status=0;
            $message="Payment Failed";
			// echo '<script>console.log(\'RECEIVEFALSE\');</script>';		
            // print_r("RECEIVEFALSE");exit; 
			 echo "RECEIVEFALSE";
		} 
		// Log::info($status);		
    }
	
	
}
