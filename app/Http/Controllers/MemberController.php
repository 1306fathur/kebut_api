<?php

namespace App\Http\Controllers;

use App\Models\Members;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MemberController extends Controller
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
            $data = DB::table('members')->where($where)->whereRaw("LOWER(nama) like '%" . $keyword . "%'")->get()->toArray();
            $count = count($data);
        } else {
            $count = Members::where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $data = Members::where($where)->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }
        $result = array();
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ((int)$count > 0) {
            foreach ($data as $d) {
                $path_img = null;
                $path_img  = !empty($d->photo) ? env('PUBLIC_URL') . '/uploads/members/' . $d->photo : null;
                unset($d->photo);
                // unset($d->status);
                $d->photo = $path_img;
                $_data[] = $d;
            }

            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'total_data'    => $count,
                'data'      => $_data
            );
        }
        return response($result);
    }

    function detail(Request $request)
    {
        $id_member = (int)$request->id_member;
        $id_token = (int)$request->id_token_fcm > 0 ? (int)$request->id_token_fcm : 0;
        $where = ['deleted_at' => null, 'id_member' => $id_member];
        $count = Members::where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => $id_member
        );
        if ((int)$count > 0) {
            Helper::last_login($id_member);
            $data = Members::where($where)->first();
            $photo = !empty($data->photo) ? env('PUBLIC_URL') . '/uploads/members/' . $data->photo : '';
            // if($id_token > 0){				
            // $fcm_token = DB::table('fcm_token')->where(array('id_token_fcm' => $id_token))->first();
            // }

            // $data->id_token_fcm = $id_token;
            // $data->token_fcm = isset($fcm_token->token_fcm) ? $fcm_token->token_fcm : '';
            $data->photo = $photo;
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
        $data = new Members();
        $data->email = isset($request->email) ? $request->email : '';
        $data->phone = isset($request->phone) ? preg_replace($ptn, $rpltxt, $request->phone) : '';
        $data->nama = $request->nama;
        $data->perusahaan = isset($request->perusahaan) ? $request->perusahaan : '';
        $verify_code = rand(100000, 999999);
        $data->status = 0;
        $data->pass = Crypt::encryptString(strtolower($request->pass));
        $data->created_at = $tgl;
        $data->updated_at = $tgl;
        $data->verify_phone = $verify_code;
        $result = array();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'not found',
            'data'      => null
        );

        if (empty($data->email)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'email is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $result = array(
                'err_code'    => '06',
                'err_msg'    => 'email invalid format',
                'data'      => null
            );
            return response($result);
            return false;
        }

        if (empty($data->phone)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'phone is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $count = 0;

        $where = ['deleted_at' => null, 'email' => $data->email];
        $count = Members::where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'email already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $where = ['deleted_at' => null, 'phone' => $data->phone];
        $count = Members::where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'phone already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }

        $save = $data->save();
        // if ($save) {
        // $setting = DB::table('setting')->get()->toArray();
        // $out = array();
        // if (!empty($setting)) {
        // foreach ($setting as $val) {
        // $out[$val->setting_key] = $val->setting_val;
        // }
        // }
        // $id_member = Crypt::encryptString($data->id_member);
        // $verify_link = '';
        // $verify_link = env('APP_URL') . '/api_mekar/verify_email/'.$id_member;
        // $content_member = $out['content_reg'];
        // $content = str_replace('[#name#]', $data->nama, $content_member);
        // $content = str_replace('[#verify_link#]', $verify_link, $content);
        // $data->content = $content;
        // Mail::send([], ['users' => $data], function ($message) use ($data) {
        // $message->to($data->email, $data->nama)->subject('Register')->setBody($data->content, 'text/html');
        // });
        // }
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $data
        );
        return response($result);
    }

    function verify_phone(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member;
        $kode = (int)$request->kode;
        if ($id_member <= 0) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'id user required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if ($kode <= 0) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'kode required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $data = DB::table('members')->where(array('id_member' => $id_member))->first();
        if ($kode == (int)$data->verify_phone) {
            $dt_upd = array(
                'verify_phone'    => 1,
                'status'        => 1,
                'updated_at'    => $tgl,
                'updated_by'    => $id_member,
            );
            DB::table('members')->where('id_member', $id_member)->update($dt_upd);
            unset($data->verify_phone);
            unset($data->updated_at);
            unset($data->updated_by);
            $data->verify_phone = 1;
            $data->updated_at = $tgl;
            $data->updated_by = $id_member;
            $result = array(
                'err_code'      => '00',
                'err_msg'       => 'ok',
                'data'          => $data
            );
        } else {
            $result = array(
                'err_code'  => '02',
                'err_msg'   => 'kode not match',
                'data'      => $data->verify_phone
            );
        }

        return response($result);
    }


    function resend_code_phone(Request $request)
    {
        $verify_code = rand(100000, 999999);
        $tgl = date('Y-m-d H:i:s');
        $ptn = "/^0/";
        $rpltxt = "62";
        $phone = isset($request->phone) ? preg_replace($ptn, $rpltxt, $request->phone) : '';
        $count = DB::table('members')->where(array('phone' => $phone))->count();
        $result = array();
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'data'          => null
        );
        if ((int)$count > 0) {
            $data = DB::table('members')->where(array('phone' => $phone))->first();
            //update
            DB::table('members')->where('id_member', $data->id_member)->update(array('verify_phone' => $verify_code));

            if ((int)$data->verify_phone == 1) {
                $result = array(
                    'err_code'  => '03',
                    'err_msg'   => 'phone sudah terverifikasi sebelumnya',
                    'data'      => $data
                );
                return response($result);
                return false;
            }
            if (empty($data->verify_phone)) {
                $verify_code = rand(100000, 999999);
                $dt_upd = array(
                    'verify_phone'    => $verify_code,
                    'updated_at'    => $tgl,
                    'updated_by'    => $id_user,
                );
                DB::table('members')->where('id_member', $id_user)->update($dt_upd);
                unset($data->verify_phone);
                $data->verify_phone = $verify_code;
            }

            $result = array(
                'err_code'      => '00',
                'err_msg'       => 'ok',
                'data'          => $data
            );
        }
        return response($result);
    }

    function edit(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member;
        Helper::last_login($id_member);
        $result = array();
        if ($id_member > 0) {
            $data = Members::where('id_member', $id_member)->first();
            $data->nama = $request->nama;
            $data->perusahaan = $request->perusahaan;
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
                'data'      => null
            );
        }
        return response($result);
    }

    function login_member(Request $request)
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

        $count = Members::where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => null
        );
        if ($count > 0) {
            $data = Members::where($where)->first();
            $password = Crypt::decryptString($data->pass);
            if ($pass == $password) {
                $photo = !empty($data->photo) ? env('PUBLIC_URL') . '/uploads/members/' . $data->photo : '';
                unset($data->pass);
                unset($data->photo);
                Helper::last_login($data->id_member);
                $data->photo = $photo;
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
            if ((int)$data->status != 1) {
                $result = array();
                $result = array(
                    'err_code'  => '05',
                    'err_msg'   => 'Status inactive',
                    'data'      => null
                );
            }
            if ((int)$data->verify_phone != 1) {
                $result = array();
                $result = array(
                    'err_code'  => '05',
                    'err_msg'   => 'Phone belum diverifikasi',
                    'data'      => null
                );
            }
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

    function upl_photo(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member;
        $photo = $request->file("photo");
        $result = array();
        if ($id_member <= 0) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'id_member required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (empty($photo)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'photo required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $_tgl = date('YmdHi');
        $data = Members::where('id_member', $id_member)->first();
        $nama = str_replace(' ', '', $data->name);
        if (strlen($nama) > 32) $nama = substr($nama, 0, 32);
        $nama = strtolower($nama);
        $nama_file = $_tgl . '' . $nama;
        $nama_file = Crypt::encryptString($nama_file);
        $fileSize = $photo->getSize();
        $extension = $photo->getClientOriginalExtension();
        $imageName = $nama_file . '.' . $extension;
        $tujuan_upload = 'uploads/members';
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
        $photo->move($tujuan_upload, $imageName);
        $data->photo = $imageName;
        $data->updated_at = $tgl;
        $data->updated_by = $id_member;
        $data->save();
        $result = array(
            'err_code'      => '00',
            'err_msg'       => 'ok',
            'data'          => $data,
            'fileSize'      => $fileSize,
            'extension'     => $extension,
            'imageName'     => env('PUBLIC_URL') . '/uploads/members/' . $imageName,
        );
        Helper::last_login($id_member);
        return response($result);
    }



    function forgot_pass(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $email = $request->email;
        if (!empty($email)) {
            $data = Members::whereRaw("LOWER(email) = '" . strtolower($email) . "'")->first();
            // if ((int)$data->verify_email <= 0) {
            // $result = array(
            // 'err_code'  => '07',
            // 'err_msg'   => 'email belum terverifikasi',
            // 'data'      => null
            // );
            // return response($result);
            // return false;
            // }
            $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            $new_pass = implode($pass);
            $data->pass = Crypt::encryptString(strtolower($new_pass));
            $data->updated_at = $tgl;
            $data->save();
            $setting = DB::table('setting')->get()->toArray();
            $out = array();
            if (!empty($setting)) {
                foreach ($setting as $val) {
                    $out[$val->setting_key] = $val->setting_val;
                }
            }
            $content_member = $out['content_forgotPass'];
            $content = str_replace('[#name#]', $data->nama, $content_member);
            $content = str_replace('[#email#]', $data->email, $content);
            $content = str_replace('[#new_pass#]', $new_pass, $content);
            $data->content = $content;
            Mail::send([], ['users' => $data], function ($message) use ($data) {
                $message->to($data->email, $data->nama)->subject('Forgot Password')->setBody($data->content, 'text/html');
            });
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        } else {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'email required',
                'data'      => null
            );
        }
        return response($result);
    }

    function update_sttts(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member > 0 ? (int)$request->id_member : 0;
        $status = (int)$request->status > 0 ? (int)$request->status : 2;
        $data = Members::where('id_member', $id_member)->first();
        $data->verify_phone = 1;
        $data->status = $status;
        $data->status_date = $tgl;
        $data->status_by = $request->id_operator;
        $data->save();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $data
        );
        return response($result);
    }

    // function verify_email($id)
    // {
    // $tgl = date('Y-m-d H:i:s');
    // $id_member = Crypt::decryptString($id);

    // $id_member = (int)$id_member;
    // $data = Members::where('id_member', $id_member)->first();
    // Log::info($data);
    // if ((int)$data->status == 1) {
    // $result = array(
    // 'err_code'  => '03',
    // 'err_msg'   => 'email sudah terverifikasi sebelumnya',
    // 'data'      => null
    // );
    // return response($result);
    // return false;
    // }
    // $data->status = 1;
    // $data->updated_at = $tgl;
    // $data->updated_by = $id_member;
    // $data->save();
    // $result = array();
    // $result = array(
    // 'err_code'      => '00',
    // 'err_msg'       => 'ok',
    // 'data'          => $data
    // );
    // return response($result);
    // }

    function history_transaksi(Request $request)
    {
        $tgl = Carbon::now();
        $data = array();
        $result = array();
        $id_member = (int)$request->id_member > 0 ? Helper::last_login((int)$request->id_member) : 0;
        // Helper::auto_reject_payment($id_member);
        $status = (int)$request->status > 0 ? (int)$request->status : 0;
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'id_transaksi';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'DESC';
        $column_int = array("id_transaksi");
        if (in_array($sort_column, $column_int)) $sort_column = "ABS($sort_column)";
        $sort_column = $sort_column . " " . $sort_order;
        $where = array('transaksi.id_member' => $id_member);
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

    function set_token_fcm(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member > 0 ? Helper::last_login((int)$request->id_member) : 0;
        $id = (int)$request->id_token_fcm  > 0 ? (int)$request->id_token_fcm  : 0;
        $token_fcm = $request->token_fcm;
        if ($id_member <= 0) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'id_member required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $data = array(
            'id_member'    => $id_member,
            'token_fcm'    => $token_fcm
        );
        if ($id > 0) {
            $data += array("updated_at" => $tgl);
            DB::table('fcm_token')->where('id_token_fcm', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl);
            $id = DB::table('fcm_token')->insertGetId($data, "id_token_fcm");
        }
        $result = array();
        if ($id > 0) {
            $data += array('id_token_fcm ' => $id);

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


    function test_mail()
    {

        Mail::raw('mail text', function ($message) {
            $message->to('hanssn88@gmail.com', 'CNI')->subject('Test Mail CNI');
        });
    }

    function test_wa(Request $request)
    {
        $id = (int)$request->id_transaksi  > 0 ? (int)$request->id_transaksi  : 0;
        $status = (int)$request->status > 0 ? (int)$request->status : 0;
        $result = Helper::send_wa($id, $status);
        return response($result);
    }


    //
}
