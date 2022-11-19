<?php

use Illuminate\Support\Facades\Route;


// header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

$router->get('/payment_online/{id_transaksi}', function ($id_transaksi) {
    $tgl = date('Y-m-d H:i:s');
    $base64 = base64_decode($id_transaksi);

    $vowels = array("k", "e", "b", "u", "t", "K", "E", "B", "U", "T");
    $_id_transaksi = str_replace($vowels, "", $base64);
    DB::connection()->enableQueryLog();
    $where = array('transaksi.status' => 1, 'id_transaksi' => $_id_transaksi);
    $data = DB::table('transaksi')->select('transaksi.*', 'members.nama as nama_member',
        'members.email',
        'members.phone as phone_member')->where($where)->leftJoin('members', 'members.id_member', '=', 'transaksi.id_member')->first();

    $id_transaksi = $data->id_transaksi;
    $PaymentId = (int)$data->payment_id > 0 ? (int)$data->payment_id : 1;
    $tgl_test = date("Y-m-d", strtotime($data->created_at));
    $ttl_price = $data->ttl_biaya;
    $merchant_code = env('MERCHANT_CODE');
    $merchant_key = env('MERCHANT_KEY');
    $currency = 'IDR';
    $prodDesc = '';
    $service_name = $data->nama_cargo;
    $prodDesc = "Booking " . $service_name . " " . $tgl_test . " #" . $id_transaksi;

    $name = $data->nama_member;
    $email = $data->email;
    $contact = $data->phone_member;
    $signature = $data->signature;
    $remark = 'Payment ' . $id_transaksi;
    $lang = 'UTF-8';
    $res = array();
    $res = array(
        'merchantCode' => $merchant_code,
        'paymentId' => $PaymentId,
        'id_transaksi' => $id_transaksi,
        'refno' => $id_transaksi,
        'amount' => $ttl_price . '00',
        'currency' => $currency,
        'prodDesc' => $prodDesc,
        'userName' => $name,
        'userEmail' => $email,
        'userContact' => $contact,
        'remark' => $remark,
        'lang' => $lang,
        'signature' => $signature,
        'url_payment' => env('URL_IPAY88'),
        'ResponseURL' => env('PUBLIC_URL') . '/ipay_redirect',
        'BackendURL' => env('PUBLIC_URL') . '/ipay_notif',
    );
    return view('greeting', $res);
});

$router->post('/admin', 'AdminController@index');
$router->post('/admin_detail', 'AdminController@detail');
$router->post('/del_admin', 'AdminController@del');
$router->post('/login_admin', 'AdminController@login_cms');
$router->post('/simpan_admin', 'AdminController@del');

$router->post('/redeem', 'RedeemController@index');
$router->post('/simpan_product_redeem', 'RedeemController@store');
$router->post('/delete_product_redeem', 'RedeemController@proses_delete');
$router->post('/detail_product_redeem', 'RedeemController@detail');
$router->post('/redeem_point', 'RedeemController@redeem_point');
$router->post('/redeem_detail', 'RedeemController@redeem_detail');
$router->post('/list_redeem', 'RedeemController@list_redeem');
$router->post('/upd_stts_redeem', 'RedeemController@upd_stts');

$router->post('/banner', 'BannerController@index');
$router->post('/simpan_banner', 'BannerController@store');
$router->post('/del_banner', 'BannerController@proses_delete');

$router->post('/drivers', 'DriverController@index');
$router->post('/drivers/activation', 'DriverController@activation');
$router->post('/register_driver', 'DriverController@reg');
$router->post('/detail_driver', 'DriverController@detail');
$router->post('/login_driver', 'DriverController@login');
$router->post('/change_pass_driver', 'DriverController@change_pass');
$router->post('/forgot_pass_driver', 'DriverController@forgot_pass');
$router->post('/status_work', 'DriverController@status_work');
$router->post('/ambil_job', 'DriverController@ambil_job');
$router->post('/barang_diambil', 'DriverController@barang_diambil');
$router->post('/barang_diserahkan', 'DriverController@barang_diserahkan');
$router->post('/history_trans_driver', 'DriverController@history_transaksi');

$router->post('/master_data', 'MasterController@index');
$router->post('/upd_setting', 'MasterController@upd_setting');

$router->post('/faq_cust', 'FaqController@cust');
$router->post('/faq_driver', 'FaqController@driver');
$router->post('/simpan_faq', 'FaqController@store');
$router->post('/del_faq', 'FaqController@proses_delete');
$router->post('/faq_detail', 'FaqController@detail');

$router->post('/hak_akses', 'LevelController@index');
$router->post('/simpan_akses', 'LevelController@store');
$router->post('/del_akses', 'LevelController@proses_delete');
$router->post('/detail_akses', 'LevelController@detail');

$router->post('/outlets', 'OutletController@index');
$router->post('/simpan_outlet', 'OutletController@store');
$router->post('/del_outlet', 'OutletController@proses_delete');
$router->post('/detail_outlet', 'OutletController@detail');

$router->post('/ac', 'CargoController@index');
$router->post('/simpan_ac', 'CargoController@store');
$router->post('/del_ac', 'CargoController@proses_delete');
$router->post('/detail_ac', 'CargoController@detail');

$router->post('/bm', 'BmController@index');
$router->post('/simpan_bm', 'BmController@store');
$router->post('/del_bm', 'BmController@proses_delete');

$router->post('/asuransi', 'AsuransiController@index');
$router->post('/simpan_asuransi', 'AsuransiController@store');
$router->post('/del_asuransi', 'AsuransiController@proses_delete');

$router->post('/biaya_inap', 'BiController@index');
$router->post('/simpan_bi', 'BiController@store');
$router->post('/del_bi', 'BiController@proses_delete');

$router->post('/provinsi', 'ProvinsiController@index');
$router->post('/simpan_provinsi', 'ProvinsiController@store');
$router->post('/del_provinsi', 'ProvinsiController@proses_delete');

$router->post('/city', 'CityController@index');
$router->post('/simpan_city', 'CityController@store');
$router->post('/del_city', 'CityController@proses_delete');

$router->post('/kecamatan', 'KecController@index');
$router->post('/simpan_kec', 'KecController@store');
$router->post('/del_kec', 'KecController@proses_delete');

$router->post('/kelurahan', 'KelController@index');
$router->post('/simpan_kel', 'KelController@store');
$router->post('/del_kel', 'KelController@proses_delete');

$router->post('/members', 'MemberController@index');
$router->post('/set_stts_member', 'MemberController@update_sttts');
$router->post('/history_transaksi', 'MemberController@history_transaksi');
$router->post('/verify_phone_cust', 'MemberController@verify_phone');
$router->post('/resend_code_phone_cust', 'MemberController@resend_code_phone');
$router->post('/profile_member', 'MemberController@detail');
$router->post('/login', 'MemberController@login_member');
$router->post('/edit', 'MemberController@edit');
$router->post('/upload_photo', 'MemberController@upl_photo');
$router->post('/chg_pass', 'MemberController@change_pass');
$router->post('/forgot_pass', 'MemberController@forgot_pass');
$router->post('/register_member', 'MemberController@reg');

$router->post('/mapping_price', 'MappingAreaController@mapping_price');
$router->post('/mapping_area', 'MappingAreaController@index');
$router->post('/set_mapping', 'MappingAreaController@store');
$router->post('/set_price', 'MappingAreaController@store_price');
$router->post('/get_id_kelurahan', 'MappingAreaController@get_id_kelurahan');
$router->post('/get_ongkirs', 'MappingAreaController@get_ongkirs');

$router->post('/transaksi', 'TransaksiController@index');
$router->post('/transaksi_detail', 'TransaksiController@transaksi_detail');
$router->post('/appr_rej_bt', 'TransaksiController@appr_rej_bt');
$router->post('/upl_bukti_transfer', 'TransaksiController@upl_bt');
$router->post('/trans_available', 'TransaksiController@trans_available');
$router->post('/submit_carter', 'TransaksiController@store');

$router->post('/ipay_redirect', 'IpayController@redirect');
$router->post('/ipay_notif', 'IpayController@notify');
