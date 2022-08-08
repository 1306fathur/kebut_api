<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DriverController extends Controller
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

    public function index(Request $request)
    {
        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $type = (int)$request->type > 0 ? (int)$request->type : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        $where = array();
        $where = array('deleted_at' => null);
        if ((int)$type > 0) {
            $where += array('type' => $type);
        }
        $count = 0;
        $data = null;
        if (!empty($keyword)) {
			$data = DB::table('driver')->select('driver.*')                
                ->where($where)->whereRaw("LOWER(nama) like '%" . $keyword . "%'")->get();
            $count = count($data);
        } else {
           $count = DB::table('driver')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $data = DB::table('driver')->select('driver.*')                
                ->where($where)->offset($offset)->limit($per_page)->orderByRaw($sort_column)->get();
        }
        $result = array();
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ((int)$count > 0) {
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'total_data'    => $count,
                'data'      => $data
            );
        }
        return response($result);
    }

    function detail(Request $request)
    {
        $id_driver  = (int)$request->id_driver ;
        $id_token = (int)$request->id_token_fcm > 0 ? (int)$request->id_token_fcm : 0;
        $where = ['deleted_at' => null, 'id_driver' => $id_driver];
		$count = DB::table('driver')->where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => $id_driver
        );
        if ((int)$count > 0) {
            $data = DB::table('driver')->where($where)->first();
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        }
        return response($result);
    }

    function reg(Request $request)
    {
		$ptn = "/^0/";
        $rpltxt = "62";
        $tgl = date('Y-m-d H:i:s');
        $_tgl = date('YmdHi');
		$title = $request->has('title') ? $request->title : '';
		$no_npwp = $request->has('no_npwp') ? $request->no_npwp : '';
		$no_ktp = $request->has('no_ktp') ? $request->no_ktp : '';
		$nama = $request->has('nama') ? $request->nama : '';
		$phone = $request->has('phone') ? preg_replace($ptn, $rpltxt, $request->phone) : '';
		$email = $request->has('email') ? $request->email : '';
		$pass = $request->has('pass') ? Crypt::encryptString(strtolower($request->pass)) : '';
		$alamat = $request->has('alamat') ? $request->alamat : '';
		$id_kota = $request->has('id_kota') ? (int)$request->id_kota : '';
		$bank = $request->has('bank') ? $request->bank : '';
		$no_rek = $request->has('no_rek') ? $request->no_rek : '';
		$nama_rek = $request->has('nama_rek') ? $request->nama_rek : '';
		$no_pol = $request->has('no_pol') ? $request->no_pol : '';
		$merk_kendaraan = $request->has('merk_kendaraan') ? $request->merk_kendaraan : '';
		$tipe_kendaraan = $request->has('tipe_kendaraan') ? $request->tipe_kendaraan : '';
		$thn_kendaraan = $request->has('thn_kendaraan') ? $request->thn_kendaraan : '';
		$nama_pemilik_kendaraan = $request->has('nama_pemilik_kendaraan') ? $request->nama_pemilik_kendaraan : '';		
        
		$img_ktp = $request->file("img_ktp");
		$img_npwp = $request->file("img_npwp");
		$img_buku_tabungan = $request->file("img_buku_tabungan");
		$img_profile = $request->file("img_profile");
		$img_stnk = $request->file("img_stnk");
		$img_sim = $request->file("img_sim");
		$img_kendaraan = $request->file("img_kendaraan");
		
        $data = array();
        $result = array();
		
        if (empty($email)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'email is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result = array(
                'err_code'    => '06',
                'err_msg'    => 'email invalid format',
                'data'      => null
            );
            return response($result);
            return false;
        }

        if (empty($phone)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'phone is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $count = 0;		
        $where = ['deleted_at' => null, 'email' => $email];
        $count = DB::table('driver')->where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'email already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }    
		
		$data = array(
			'title'=> $title,
			'no_npwp'=> $no_npwp,
			'no_ktp'=> $no_ktp,
			'nama'=> $nama,
			'phone'=> $phone,
			'email'=> $email,
			'pass'=> $pass,
			'alamat'=> $alamat,
			'id_kota'=> $id_kota,
			'bank'=> $bank,
			'no_rek'=> $no_rek,
			'nama_rek'=> $nama_rek,
			'no_pol'=> $no_pol,
			'merk_kendaraan'=> $merk_kendaraan,
			'tipe_kendaraan'=> $tipe_kendaraan,
			'thn_kendaraan'=> $thn_kendaraan,
			'nama_pemilik_kendaraan'=> $nama_pemilik_kendaraan,
			'created_at'=> $tgl,
		);
		
		if (!empty($img_ktp)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $img_ktp->getSize();
            $extension = $img_ktp->getClientOriginalExtension();
            $imageName = $nama_file . 'ktp.' . $extension;   
			$imageName = str_replace("=","",$imageName);
            $tujuan_upload = 'uploads/drivers';
			
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
            $img_ktp->move($tujuan_upload, $imageName);
            $data += array("img_ktp" => env('PUBLIC_URL') . '/uploads/driver/'.$imageName);
        }

		if (!empty($img_npwp)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $img_npwp->getSize();
            $extension = $img_npwp->getClientOriginalExtension();
            $imageName = $nama_file . 'npwp.' . $extension; 
			$imageName = str_replace("=","",$imageName);
            $tujuan_upload = 'uploads/drivers';
			
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
            $img_npwp->move($tujuan_upload, $imageName);
            $data += array("img_npwp" => env('PUBLIC_URL') . '/uploads/driver/'.$imageName);
        }
		if (!empty($img_buku_tabungan)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $img_buku_tabungan->getSize();
            $extension = $img_buku_tabungan->getClientOriginalExtension();
            $imageName = $nama_file . 'bktbgn.' . $extension;  
			$imageName = str_replace("=","",$imageName);
            $tujuan_upload = 'uploads/drivers';
			
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
            $img_buku_tabungan->move($tujuan_upload, $imageName);
            $data += array("img_buku_tabungan" => env('PUBLIC_URL') . '/uploads/driver/'.$imageName);
        }
		if (!empty($img_profile)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $img_profile->getSize();
            $extension = $img_profile->getClientOriginalExtension();
            $imageName = $nama_file . 'pp.' . $extension;    
			$imageName = str_replace("=","",$imageName);
            $tujuan_upload = 'uploads/drivers';
			
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
            $img_profile->move($tujuan_upload, $imageName);
            $data += array("img_profile" => env('PUBLIC_URL') . '/uploads/driver/'.$imageName);
        }
		
		if (!empty($img_stnk)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $img_stnk->getSize();
            $extension = $img_stnk->getClientOriginalExtension();
            $imageName = $nama_file . 'stnk.' . $extension; 
			$imageName = str_replace("=","",$imageName);
            $tujuan_upload = 'uploads/drivers';
			
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
            $img_stnk->move($tujuan_upload, $imageName);
            $data += array("img_stnk" => env('PUBLIC_URL') . '/uploads/driver/'.$imageName);
        }
		
		if (!empty($img_sim)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $img_sim->getSize();
            $extension = $img_sim->getClientOriginalExtension();
            $imageName = $nama_file . 'sim.' . $extension; 
			$imageName = str_replace("=","",$imageName);
            $tujuan_upload = 'uploads/drivers';
			
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
            $img_sim->move($tujuan_upload, $imageName);
            $data += array("img_sim" => env('PUBLIC_URL') . '/uploads/driver/'.$imageName);
        }
		
		if (!empty($img_kendaraan)) {
			$randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
			$nama_file = base64_encode($_tgl."".$randomletter);            
            $fileSize = $img_kendaraan->getSize();
            $extension = $img_kendaraan->getClientOriginalExtension();
            $imageName = $nama_file . 'kend.' . $extension;       
			$imageName = str_replace("=","",$imageName);
            $tujuan_upload = 'uploads/drivers';
			
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
            $img_kendaraan->move($tujuan_upload, $imageName);
            $data += array("img_kendaraan" => env('PUBLIC_URL') . '/uploads/driver/'.$imageName);
        }
        $id = DB::table('driver')->insertGetId($data, "id_driver");
		$data += array('id_driver'=>$id);
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $data
        );
        return response($result);
    }
	
	
	
	
	

    function login(Request $request)
    {
        $count = 0;
        $email = $request->email;
       
        $pass = strtolower($request->pass);
        $result = array();
        if (empty($email)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'Email is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (empty($pass)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'password is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
		 if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result = array(
                'err_code'    => '06',
                'err_msg'    => 'email invalid format',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $where = ['deleted_at' => null, 'email' => $email];        

        $count = DB::table('driver')->where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => null
        );
        if ($count > 0) {
            $data = DB::table('driver')->where($where)->first();
            $password = Crypt::decryptString($data->pass);
            if ($pass == $password) {
				
                $result = array(
                    'err_code'  => '00',
                    'err_msg'   => 'ok',
                    'data'      => $data
                );
            } else {
                $result = array(
                    'err_code'  => '03',
                    'err_msg'   => 'password not match',
                    'data'      => null
                );
            }
            // if ((int)$data->status != 1) {
                // $result = array();
                // $result = array(
                    // 'err_code'  => '05',
                    // 'err_msg'   => 'Status inactive',
                    // 'data'      => null
                // );
            // }
			// if ((int)$data->verify_phone != 1) {
                // $result = array();
                // $result = array(
                    // 'err_code'  => '05',
                    // 'err_msg'   => 'Phone belum diverifikasi',
                    // 'data'      => null
                // );
            // }
        }
        return response($result);
    }

    function change_pass(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member;
        Helper::last_login($id_member);
        $new_pass = $request->new_pass;
        $old_pass = $request->old_pass;
        $result = array();
        if (empty($new_pass)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'new_pass is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (empty($old_pass)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'old_pass is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if ($id_member > 0) {
            $data = Members::where('id_member', $id_member)->first();
            $password = Crypt::decryptString($data->pass);
            $old_pass = strtolower($old_pass);
            if ($password != $old_pass) {
                $result = array(
                    'err_code'  => '03',
                    'err_msg'   => 'old_pass not match',
                    'data'      => null
                );
                return response($result);
                return false;
            }
            $new_pass = strtolower($new_pass);
            if ($password == $new_pass) {
                $result = array(
                    'err_code'  => '02',
                    'err_msg'   => 'new_pass sama dengan password sebelumnya',
                    'data'      => null
                );
                return response($result);
                return false;
            }
            $data->pass = Crypt::encryptString($new_pass);
            $data->updated_at = $tgl;
            $data->updated_by = $id_member;
            $data->save();
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        } else {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'id_member required',
                'data'      => $id_member
            );
        }
        return response($result);
    }

    


    function forgot_pass(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $email = $request->email;
		$result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => null
        );
        if (!empty($email)) {
			$where = ['deleted_at' => null, 'email' => $email];
			$count = DB::table('driver')->where($where)->count();
			if ($count > 0) {
				$data = DB::table('driver')->where($where)->first();
			   
				$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
				$pass = array(); //remember to declare $pass as an array
				$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
				for ($i = 0; $i < 8; $i++) {
					$n = rand(0, $alphaLength);
					$pass[] = $alphabet[$n];
				}
				$new_pass = implode($pass);
				$pass = Crypt::encryptString(strtolower($new_pass));
				
				$data_upd = array(
					"pass"	=> $pass
					
				);
				DB::table('driver')->where('id_driver', $data->id_driver)->update($data_upd);
				// $setting = DB::table('setting')->get()->toArray();
				// $out = array();
				// if (!empty($setting)) {
					// foreach ($setting as $val) {
						// $out[$val->setting_key] = $val->setting_val;
					// }
				// }
				// $content_member = $out['content_forgotPass'];
				// $content = str_replace('[#name#]', $data->nama, $content_member);
				// $content = str_replace('[#email#]', $data->email, $content);
				// $content = str_replace('[#new_pass#]', $new_pass, $content);
				// $data->content = $content;
				// Mail::send([], ['users' => $data], function ($message) use ($data) {
					// $message->to($data->email, $data->nama)->subject('Forgot Password')->setBody($data->content, 'text/html');
				// });
				$data = DB::table('driver')->where($where)->first();
				$result = array(
					'err_code'  => '00',
					'err_msg'   => 'ok',
					'pass'      => $new_pass,
					'data'      => $data
				);
			}
        } else {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'email required',
                'data'      => null
            );
        }
        return response($result);
	}	
	
	function status_work(){
		$id_driver  = (int)$request->id_driver ;
        $status_work = (int)$request->status_work > 0 ? (int)$request->status_work : 0;
        $where = ['deleted_at' => null, 'id_driver' => $id_driver];
		$count = DB::table('driver')->where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => $id_driver
        );
        if ((int)$count > 0) {
			DB::table('kota')->where($where)->update(array('status_work' => $status_work));
            $data = DB::table('driver')->where($where)->first();
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        }
        return response($result);
	}
	
	function ambil_job(Request $request)
    {
		$tgl = date('Y-m-d H:i:s');
        $id_driver  = (int)$request->id_driver ;
        $id_transaksi = (int)$request->id_transaksi > 0 ? (int)$request->id_transaksi : 0;
        $where = array('transaksi.status' => 4, 'id_driver'=>null);
		$count = DB::table('transaksi')->where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => $id_driver
        );
        if ((int)$count > 0) {
			$data = array(
				'id_driver'				=> $id_driver,
				'status'				=> 5,
				'tgl_diambil_driver'	=> $tgl,
			);
            DB::table('transaksi')->where('id_transaksi', $id_transaksi)->update($data);
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        }
        return response($result);
    }
	
	function history_transaksi(Request $request)
    {
        $tgl = Carbon::now();
        $data = array();
        $result = array();
        $id_driver = (int)$request->id_driver > 0 ? (int)$request->id_driver : 0;
		// Helper::auto_reject_payment($id_member);
        $status = (int)$request->status > 0 ? (int)$request->status :0;
		$sort_column = !empty($request->sort_column) ? $request->sort_column : 'id_transaksi';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'DESC';
        $column_int = array("id_transaksi");		
        if (in_array($sort_column, $column_int)) $sort_column = "ABS($sort_column)";
        $sort_column = $sort_column . " " . $sort_order;
        $where = array('transaksi.id_driver' => $id_driver);
        if ($status > 0) $where += array('transaksi.status' => (int)$status);
        $count = 0;
        $count = DB::table('transaksi')->where($where)->count();
		$_data = DB::table('transaksi')->select(
            'transaksi.*',
            'members.nama as nama_member',
            'members.email',
            'members.phone as phone_member',  
			'driver.nama as nama_driver',
            'driver.email as email_driver',
            'driver.phone as phone_driver',
        )
            ->where($where)->leftJoin('members', 'members.id_member', '=', 'transaksi.id_member')
			->leftJoin('driver', 'driver.id_driver', '=', 'transaksi.id_driver')
            ->orderByRaw($sort_column)->get();
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
		if ($count > 0) {
			
            foreach ($_data as $d) {
                $status = $d->status;
                $payment = $d->payment;
                
                unset($d->url_payment);               
                unset($d->process_by);               
                unset($d->completed_by);                 
                unset($d->status_payment_by);                 
							
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
}
