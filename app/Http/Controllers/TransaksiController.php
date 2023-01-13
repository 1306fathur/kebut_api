<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class TransaksiController extends Controller
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
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'id_transaksi';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'DESC';

        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        $status = $request->has('status') && (int)$request->status >= 0 ? (int)$request->status : 0;
        $column_int = array("id_transaksi");
        if (in_array($sort_column, $column_int)) $sort_column = "ABS($sort_column)";
        $sort_column = $sort_column . " " . $sort_order;
        $where = array();

        $where = $status > 0 && $status != 5 ? array('transaksi.status' => $status) : array();
        $count = 0;
        $_data = array();
        $data = null;

        if (!empty($keyword)) {
            $_data = DB::table('transaksi')->select(
                'transaksi.*',
                'members.nama as nama_member',
                'members.email',
                'members.phone as phone_member',
            )
                ->where($where)->leftJoin('members', 'members.id_member', '=', 'transaksi.id_member')
                ->whereRaw("(id_transaksi like '%" . $keyword . "%' or LOWER(id_transaksi) like '%" . $keyword . "%')");
            if ($status == 5) $_data->whereIn('status', [5, 6]);
            $_data = $_data->get();
            $count = count($_data);
        } else {
            $count = DB::table('transaksi')->where($where);
            $count = $count->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
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
                ->offset($offset)->limit($per_page)->orderByRaw($sort_column);
            if ($status == 5) $_data->whereIn('status', [5, 6]);
            $_data = $_data->get();
        }
        $result = array(
            'err_code' => '04',
            'err_msg' => 'data not found',
            'total_data' => $count,
            'data' => null
        );
        if ($count > 0) {

            $result = array(
                'err_code' => '00',
                'err_msg' => 'ok',
                'total_data' => $count,
                'data' => $_data
            );
        }
        return response($result);
    }

    public function trans_available(Request $request)
    {
        $lat = $request->has('latitude') && !empty($request->latitude) ? $request->latitude : '';
        $lon = $request->has('longitude') && !empty($request->longitude) ? $request->longitude : '';

        $result = array();
        if (empty($lat)) {
            $result = array(
                'err_code' => '06',
                'err_msg' => 'Param latitude required',
                'data' => null,
            );
            return response($result);
        }
        if (empty($lon)) {
            $result = array(
                'err_code' => '06',
                'err_msg' => 'Param longitude required',
                'data' => null,
            );
            return response($result);
        }
        $count = 0;
        $data = [];
        $sql = "SELECT transaksi_detail.nama_penerima, transaksi_detail.hp_penerima, transaksi_detail.tgl_pickup,
                transaksi_detail.tgl_antar, transaksi_detail.alamat_kirim as alamat_antar, transaksi_detail.alamat_pemesan as alamat_pickup,
                transaksi.*, (6371 * acos (cos(radians($lat)) * cos(radians(transaksi.latitude_pickup)) *
                cos(radians(transaksi.longitude_pickup) - radians($lon)) + sin(radians($lat)) *
                sin(radians(transaksi.latitude_pickup)))) AS distance, members.nama as nama_member, members.email, members.phone as phone_member
                FROM transaksi
                left join transaksi_detail on transaksi_detail.id_trans = transaksi.id_transaksi
                left join members on members.id_member = transaksi.id_member
                where transaksi.status = 4 and id_driver is null
                HAVING distance <= 10 order by distance ASC";
        $_data = DB::select(DB::raw($sql));
        $count = count($_data);
        $result = array(
            'err_code' => '04',
            'err_msg' => 'data not found',
            'total_data' => $count,
            'data' => null
        );
        if ((int)$count > 0) {
            foreach ($_data as $d) {
                $distance = round($d->distance, 2, PHP_ROUND_HALF_UP);
                unset($d->log_payment);
                unset($d->distance);
                $d->distance = $distance;
                $data[] = $d;
            }
            $result = array(
                'err_code' => '00',
                'err_msg' => 'ok',
                'total_data' => $count,
                'data' => $data
            );
        }
        return response($result);
    }


    function store(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $result = array();

        $free_bongkar_muat = $request->has('free_bongkar_muat') ? $request->free_bongkar_muat : 0;
        $tambahan_bongkar_muat = $request->has('tambahan_bongkar_muat') ? $request->tambahan_bongkar_muat : 0;
        if ($tambahan_bongkar_muat != 0) {
            $free_bongkar_muat += $tambahan_bongkar_muat;
        }
        $id_member = (int)$request->id_member > 0 ? Helper::last_login((int)$request->id_member) : 0;
        $id_ac = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;
        $lokasi_pickup = $request->has('lokasi_pickup') ? $request->lokasi_pickup : '';
        $latitude_pickup = $request->has('latitude_pickup') ? $request->latitude_pickup : '';
        $longitude_pickup = $request->has('longitude_pickup') ? $request->longitude_pickup : '';
        $id_kel_pickup = (int)$request->id_kel_pickup > 0 ? (int)$request->id_kel_pickup : 0;

        $payment = (int)$request->payment > 0 ? (int)$request->payment : 0;
        $payment_id = (int)$request->payment_id > 0 ? (int)$request->payment_id : 0;

        $ttl_biaya_bm = $request->has('ttl_biaya_bm') ? str_replace(',', '', $request->ttl_biaya_bm) : 0;
        $sub_ttl = 0;
        $id_voucher = (int)$request->id_voucher > 0 ? (int)$request->id_voucher : 0;
        $kode_voucher = $request->has('kode_voucher') ? $request->kode_voucher : '';
        $pot_voucher = $request->has('pot_voucher') ? str_replace(',', '', $request->pot_voucher) : 0;
        $ttl_biaya = $request->has('ttl_biaya') && (int)$request->ttl_biaya > 0 ? str_replace(',', '', $request->ttl_biaya) : 0;

        $where_kel = array('kelurahan.id_kelurahan' => $id_kel_pickup);
        $data_area = DB::table('kelurahan')->where($where_kel)->select('kelurahan.nama_kel', 'kecamatan.id_kec', 'kota.id_kota', 'provinsi.id_provinsi', 'nama_provinsi', 'nama_city', 'nama_kec')
            ->leftJoin('kecamatan', 'kecamatan.id_kec', '=', 'kelurahan.id_kec')
            ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
            ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')->first();

        $where_ac = array('id_ac' => $id_ac);
        $data_cargo = DB::table('armada_cargo')->select('nama_cargo')->where($where_ac)->first();

        $kode_unik_transfer = 0;
        $status = 1;
        $payment_name = '';
        $payment_name = (int)$payment == 1 ? 'Transfer' : $payment_name;
        $payment_name = (int)$payment == 2 ? 'Online Payment' : $payment_name;
        $payment_name = (int)$payment == 3 ? 'Kredit KEBUT' : $payment_name;
        if ($payment == 1) $kode_unik_transfer = rand(100, 499);
        $ttl_biaya_asuransi = 0;
        $dt_trans = array();
        $dt_update = array();
        $ttl_biaya = $ttl_biaya + (int)$kode_unik_transfer + $ttl_biaya_bm + $ttl_biaya_asuransi;
        DB::beginTransaction();
        $dt_trans = array(
            'id_member' => $id_member,
            'id_ac' => $id_ac,
            'type' => 1,
            'nama_cargo' => isset($data_cargo->nama_cargo) ? $data_cargo->nama_cargo : '',
            'lokasi_pickup' => $lokasi_pickup,
            'latitude_pickup' => $latitude_pickup,
            'longitude_pickup' => $longitude_pickup,
            'id_kel_pickup' => $id_kel_pickup,
            'id_provinsi_pickup' => isset($data_area->id_provinsi) ? $data_area->id_provinsi : '',
            'id_kota_pickup' => isset($data_area->id_kota) ? $data_area->id_kota : '',
            'id_kec_pickup' => isset($data_area->id_kec) ? $data_area->id_kec : '',
            'provinsi_pickup' => isset($data_area->nama_provinsi) ? $data_area->nama_provinsi : '',
            'kota_pickup' => isset($data_area->nama_city) ? $data_area->nama_city : '',
            'kecamatan_pickup' => isset($data_area->nama_kec) ? $data_area->nama_kec : '',
            'kelurahan_pickup' => isset($data_area->nama_kel) ? $data_area->nama_kel : '',
            'payment' => $payment,
            'payment_name' => $payment_name,
            'payment_id' => $payment_id,
            'ttl_biaya_asuransi' => 0,
            'ttl_biaya_bm' => $ttl_biaya_bm,
            'sub_ttl' => $ttl_biaya,
            // 'id_voucher'			=> $id_voucher,
            // 'kode_voucher'			=> $kode_voucher,
            // 'pot_voucher'			=> $pot_voucher,
            'kode_unik_transfer' => $kode_unik_transfer,
            'ttl_biaya' => $ttl_biaya,
            'status' => $status,
            'created_at' => $tgl,
            'free_bongkar_muat' => $free_bongkar_muat,
        );
        $id = 0;
        $id = DB::table('transaksi')->insertGetId($dt_trans, "id_transaksi");
        $id_pricelist = $request->has('id_pricelist') ? $request->id_pricelist : 0;
        $tgl_pickup = $request->has('tgl_pickup') ? $request->tgl_pickup : '';
        $jam_pickup = $request->has('jam_pickup') ? $request->jam_pickup : '';
        $tgl_antar = $request->has('tgl_antar') ? $request->tgl_antar : '';
        $jam_antar = $request->has('jam_antar') ? $request->jam_antar : '';
        $tambahan_bm = $request->has('tambahan_bm') ? $request->tambahan_bm : '';
        $biaya_tambahan_bm = $request->has('biaya_tambahan_bm') ? $request->biaya_tambahan_bm : '';
        $asuransi = $request->has('asuransi') ? $request->asuransi : '';
        $biaya_asuransi = $request->has('biaya_asuransi') ? $request->biaya_asuransi : '';
        $harga_barang = $request->has('harga_barang') ? $request->harga_barang : '';
        $srt_jln_kembali = $request->has('srt_jln_kembali') ? $request->srt_jln_kembali : 0;
        $id_maping = $request->has('id_maping') ? $request->id_maping : '';
        $hrg = $request->has('hrg') ? $request->hrg : '';
        $nama_pemesan = $request->has('nama_pemesan') ? $request->nama_pemesan : '';
        $hp_pemesan = $request->has('hp_pemesan') ? $request->hp_pemesan : '';
        $perusahaan_pemesan = $request->has('perusahaan_pemesan') ? $request->perusahaan_pemesan : '';
        $pic_gudang = $request->has('pic_gudang') ? $request->pic_gudang : '';
        $jabatan_pemesan = $request->has('jabatan_pemesan') ? $request->jabatan_pemesan : '';
        $hp_pic = $request->has('hp_pic') ? $request->hp_pic : '';
        $alamat_pemesan = $request->has('alamat_pemesan') ? $request->alamat_pemesan : '';
        $detail_lokasi_pemesan = $request->has('detail_lokasi_pemesan') ? $request->detail_lokasi_pemesan : '';
        $pesan_utk_driver = $request->has('pesan_utk_driver') ? $request->pesan_utk_driver : '';
        $nama_penerima = $request->has('nama_penerima') ? $request->nama_penerima : '';
        $jabatan_penerima = $request->has('jabatan_penerima') ? $request->jabatan_penerima : '';
        $perusahaan_penerima = $request->has('perusahaan_penerima') ? $request->perusahaan_penerima : '';
        $hp_penerima = $request->has('hp_penerima') ? $request->hp_penerima : '';
        $alamat_kirim = $request->has('alamat_kirim') ? $request->alamat_kirim : '';
        $detail_lokasi_kirim = $request->has('detail_lokasi_kirim') ? $request->detail_lokasi_kirim : '';
        $latitude_origin = $request->has('latitude_origin') ? $request->latitude_origin : '';
        $longitude_origin = $request->has('longitude_origin') ? $request->longitude_origin : '';
        $latitude_destination = $request->has('latitude_destination') ? $request->latitude_destination : '';
        $longitude_destination = $request->has('longitude_destination') ? $request->longitude_destination : '';
        $deskripsi_barang = $request->has('deskripsi_barang') ? $request->deskripsi_barang : '';
        $qty = $request->has('qty') ? $request->longitude_destination : '';
        $is_faktur = $request->has('is_faktur') ? $request->is_faktur : 0;
        $faktur_atas_nama = $request->has('faktur_atas_nama') ? $request->faktur_atas_nama : '';
        $faktur_nama = $request->has('faktur_nama') ? $request->faktur_nama : '';
        $faktur_perusahaan = $request->has('faktur_perusahaan') ? $request->faktur_perusahaan : '';
        $faktur_hp = $request->has('faktur_hp') ? $request->faktur_hp : '';
        $faktur_alamat = $request->has('faktur_alamat') ? $request->faktur_alamat : '';
        $faktur_kode_pos = $request->has('faktur_kode_pos') ? $request->faktur_kode_pos : '';
        $faktur_catatan = $request->has('faktur_catatan') ? $request->faktur_catatan : '';
        $img_srt_jln = $request->has('img_srt_jln') ? $request->file("img_srt_jln") : '';

        $dt_pesanan = array();
        $imageName[] = '';
        if ((int)$id > 0) {
            for ($i = 0; $i < count($id_pricelist); $i++) {
                if (!empty($img_srt_jln) && isset($img_srt_jln[$i])) {
                    $_tgl = date('YmdHi');
                    $randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
                    $nama_file = base64_encode($_tgl . "" . $randomletter);
                    $fileSize = $img_srt_jln[$i]->getSize();
                    $extension = $img_srt_jln[$i]->getClientOriginalExtension();
                    $imageName[$i] = $nama_file . '.' . $extension;
                    $tujuan_upload = 'uploads/transaksi';

                    $_extension = array('png', 'jpg', 'jpeg');
                    if ($fileSize > 2099200) { // satuan bytes
                        $result = array(
                            'err_code' => '07',
                            'err_msg' => 'file size over 2048',
                            'data' => $fileSize
                        );
                        return response($result);
                        return false;
                    }
                    if (!in_array($extension, $_extension)) {
                        $result = array(
                            'err_code' => '07',
                            'err_msg' => 'file extension not valid',
                            'data' => null
                        );
                        return response($result);
                        return false;
                    }
                    $img_srt_jln[$i]->move($tujuan_upload, $imageName[$i]);
                }
                $b_add_bm = !empty($biaya_tambahan_bm) && isset($biaya_tambahan_bm[$i]) ? str_replace(',', '', $biaya_tambahan_bm[$i]) : 0;
                $b_asuransi = !empty($biaya_asuransi) && isset($biaya_asuransi[$i]) ? str_replace(',', '', $biaya_asuransi[$i]) : 0;
                /* $harga_barang = !empty($biaya_asuransi) && isset($biaya_asuransi[$i]) ? str_replace(',', '', $harga_barang[$i]) : 0;*/


                $_hrg = !empty($hrg) && isset($hrg[$i]) ? str_replace(',', '', $hrg[$i]) : 0;
                $nominal_biaya_asuransi = $_hrg * ($b_asuransi / 100);
                $ttl_biaya_asuransi += $nominal_biaya_asuransi;
                $dt_pesanan[] = array(
                    'id_trans' => $id,
                    'id_pricelist' => $id_pricelist[$i],
                    'tgl_pickup' => !empty($tgl_pickup) && isset($tgl_pickup[$i]) ? date('Y-m-d', strtotime($tgl_pickup[$i])) : null,
                    'jam_pickup' => !empty($jam_pickup) && isset($jam_pickup[$i]) ? $jam_pickup[$i] : '',
                    'tgl_antar' => !empty($tgl_antar) && isset($tgl_antar[$i]) ? date('Y-m-d', strtotime($tgl_antar[$i])) : null,
                    'jam_antar' => !empty($jam_antar) && isset($jam_antar[$i]) ? $jam_antar[$i] : '',
                    'tambahan_bm' => !empty($tambahan_bm) && isset($tambahan_bm[$i]) ? $tambahan_bm[$i] : '',
                    'biaya_tambahan_bm' => $b_add_bm,
                    'asuransi' => !empty($asuransi) && isset($asuransi[$i]) ? $asuransi[$i] : '',
                    'biaya_asuransi' => $b_asuransi,
                    /*'harga_barang' => $harga_barang,*/
                    'nominal_biaya_asuransi' => $nominal_biaya_asuransi,
                    'srt_jln_kembali' => !empty($srt_jln_kembali) && isset($srt_jln_kembali[$i]) ? (int)$srt_jln_kembali[$i] : 0,
                    'id_maping' => !empty($id_maping) && isset($id_maping[$i]) ? $id_maping[$i] : '',
                    'hrg' => $_hrg,
                    'nama_pemesan' => !empty($nama_pemesan) && isset($nama_pemesan[$i]) ? $nama_pemesan[$i] : '',
                    'hp_pemesan' => !empty($hp_pemesan) && isset($hp_pemesan[$i]) ? $hp_pemesan[$i] : '',
                    'perusahaan_pemesan' => !empty($perusahaan_pemesan) && isset($perusahaan_pemesan[$i]) ? $perusahaan_pemesan[$i] : '',
                    'pic_gudang' => !empty($pic_gudang) && isset($pic_gudang[$i]) ? $pic_gudang[$i] : '',
                    'jabatan_pemesan' => !empty($jabatan_pemesan) && isset($jabatan_pemesan[$i]) ? $jabatan_pemesan[$i] : '',
                    'hp_pic' => !empty($hp_pic) && isset($hp_pic[$i]) ? $hp_pic[$i] : '',
                    'alamat_pemesan' => !empty($alamat_pemesan) && isset($alamat_pemesan[$i]) ? $alamat_pemesan[$i] : '',
                    'detail_lokasi_pemesan' => !empty($detail_lokasi_pemesan) && isset($detail_lokasi_pemesan[$i]) ? $detail_lokasi_pemesan[$i] : '',
                    'pesan_utk_driver' => !empty($pesan_utk_driver) && isset($pesan_utk_driver[$i]) ? $pesan_utk_driver[$i] : '',
                    'nama_penerima' => !empty($nama_penerima) && isset($nama_penerima[$i]) ? $nama_penerima[$i] : '',
                    'jabatan_penerima' => !empty($jabatan_penerima) && isset($jabatan_penerima[$i]) ? $jabatan_penerima[$i] : '',
                    'perusahaan_penerima' => !empty($perusahaan_penerima) && isset($perusahaan_penerima[$i]) ? $perusahaan_penerima[$i] : '',
                    'hp_penerima' => !empty($hp_penerima) && isset($hp_penerima[$i]) ? $hp_penerima[$i] : '',
                    'alamat_kirim' => !empty($alamat_kirim) && isset($alamat_kirim[$i]) ? $alamat_kirim[$i] : '',
                    'detail_lokasi_kirim' => !empty($detail_lokasi_kirim) && isset($detail_lokasi_kirim[$i]) ? $detail_lokasi_kirim[$i] : '',
                    'latitude_origin' => !empty($latitude_origin) && isset($latitude_origin[$i]) ? $latitude_origin[$i] : '',
                    'longitude_origin' => !empty($longitude_origin) && isset($longitude_origin[$i]) ? $longitude_origin[$i] : '',
                    'latitude_destination' => !empty($latitude_destination) && isset($latitude_destination[$i]) ? $latitude_destination[$i] : '',
                    'longitude_destination' => !empty($longitude_destination) && isset($longitude_destination[$i]) ? $longitude_destination[$i] : '',
                    'deskripsi_barang' => !empty($deskripsi_barang) && isset($deskripsi_barang[$i]) ? $deskripsi_barang[$i] : '',
                    'qty' => !empty($qty) && isset($qty[$i]) ? $qty[$i] : 1,
                    'is_faktur' => !empty($is_faktur) && isset($is_faktur[$i]) ? (int)$is_faktur[$i] : 0,
                    'faktur_atas_nama' => !empty($faktur_atas_nama) && isset($faktur_atas_nama[$i]) ? $faktur_atas_nama[$i] : '',
                    'faktur_nama' => !empty($faktur_nama) && isset($faktur_nama[$i]) ? $faktur_nama[$i] : '',
                    'faktur_perusahaan' => !empty($faktur_perusahaan) && isset($faktur_perusahaan[$i]) ? $faktur_perusahaan[$i] : '',
                    'faktur_hp' => !empty($faktur_hp) && isset($faktur_hp[$i]) ? $faktur_hp[$i] : '',
                    'faktur_alamat' => !empty($faktur_alamat) && isset($faktur_alamat[$i]) ? $faktur_alamat[$i] : '',
                    'faktur_kode_pos' => !empty($faktur_kode_pos) && isset($faktur_kode_pos[$i]) ? $faktur_kode_pos[$i] : '',
                    'faktur_catatan' => !empty($faktur_catatan) && isset($faktur_catatan[$i]) ? $faktur_catatan[$i] : '',
                    'img_srt_jln' => !empty($imageName) && isset($imageName[$i]) ? env('PUBLIC_URL') . '/uploads/transaksi/' . $imageName[$i] : '',
                );
            }
            $ttl_biaya = $ttl_biaya + $ttl_biaya_asuransi;
            $dt_update = array('ttl_biaya_asuransi' => $ttl_biaya_asuransi, 'ttl_biaya' => $ttl_biaya);
            $dt_trans += array('list_pesanan' => $dt_pesanan);
            if (!empty($dt_pesanan)) DB::table('transaksi_detail')->insert($dt_pesanan);
            if ($payment == 2) {
                $randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
                $base64 = base64_encode($randomletter . "" . $id);
                $url_payment = env('PUBLIC_URL') . '/payment_online/' . $base64;
                $merchant_code = env('MERCHANT_CODE');
                $merchant_key = env('MERCHANT_KEY');
                $price = $ttl_biaya + (int)$kode_unik_transfer;
                $words = $merchant_key . '' . $merchant_code . '' . $id . '' . $price . '00IDR';
                $signature = Helper::iPay88_signature($words);
                $dt_update += array('url_payment' => $url_payment, 'signature' => $signature);
                $dt_trans += array('url_payment' => $url_payment);
            }
            DB::table('transaksi')->where('id_transaksi', $id)->update($dt_update);
            DB::commit();
            $result = array(
                'err_code' => '00',
                'err_msg' => 'ok',
                'data' => $dt_trans
            );
        } else {
            DB::rollback();
        }

        return response($result);
    }

    function transaksi_detail(Request $request)
    {
        $result = array();
        $_data = array();
        $id_transaksi = (int)$request->id_transaksi > 0 ? (int)$request->id_transaksi : 0;
        $where = array('transaksi.id_transaksi' => $id_transaksi);
        $_data = DB::table('transaksi')->select(
            'transaksi.*',
            'members.nama as nama_member',
            'members.email',
            'members.phone as phone_member',
            'driver.nama as nama_driver',
            'driver.email as email_driver',
            'driver.phone as phone_driver',
            'driver.latitude',
            'driver.longitude',
        )
            ->where($where)
            ->leftJoin('members', 'members.id_member', '=', 'transaksi.id_member')
            ->leftJoin('driver', 'driver.id_driver', '=', 'transaksi.id_driver')->first();
            if ($_data->status == 10) {
                $start_bm = $_data->start_bm;
                $free_bongkar_muat = $_data->free_bongkar_muat;
                //start_bm + free_bongkar_muat in hour
                $end_bm = date('Y-m-d H:i:s', strtotime($start_bm . ' + ' . $free_bongkar_muat . ' hours'));
                $now = date('Y-m-d H:i:s');
                //diff end_bm - now
                $diff = strtotime($end_bm) - strtotime($now);
                $hours = floor($diff / (60 * 60));
                $minutes = floor(($diff - $hours * 60 * 60) / 60);
                $seconds = $diff - $hours * 60 * 60 - $minutes * 60;
                //merge hours, minutes, seconds to datetime
                $timer = date('H:i:s', mktime($hours, $minutes, $seconds));
                $_data->timer = $timer;
            }
        $cnt_details = DB::table('transaksi_detail')->where(array('id_trans' => $id_transaksi))->count();
        $list_pesanan = null;
        if ($cnt_details > 0) {
            $details = DB::table('transaksi_detail')->where(array('id_trans' => $id_transaksi))->get();
            foreach ($details as $d) {
                $list_pesanan[] = $d;
            }
        }
        unset($_data->session_id);
        unset($_data->delivery_by);
        unset($_data->log_payment);
        $_data->list_pesanan = $list_pesanan;

        if ($_data->latitude != null && $_data->longitude != null) {
            $_data->location = 'https://www.google.com/maps/place/' . $_data->latitude . ',' . $_data->longitude;
            unset($_data->latitude);
            unset($_data->longitude);
        }
        $result = array(
            'err_code' => '00',
            'err_msg' => 'ok',
            'data' => $_data
        );
        return response($result);
    }

    function upl_bt(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $_tgl = date('YmdHi');
        $id_transaksi = (int)$request->id_transaksi > 0 ? (int)$request->id_transaksi : 0;
        $where = array('transaksi.id_transaksi' => $id_transaksi);

        $path_img = $request->file("img_bukti_transfer");
        $data = array();
        $data = array(
            'upload_date_bt' => $tgl,
            'status' => 3,
        );
        if (!empty($path_img)) {
            $randomletter = substr(str_shuffle("kebutKEBUT"), 0, 5);
            $nama_file = base64_encode($_tgl . "" . $randomletter . "" . $id_transaksi);
            $fileSize = $path_img->getSize();
            $extension = $path_img->getClientOriginalExtension();
            $imageName = $nama_file . '.' . $extension;
            $tujuan_upload = 'uploads/bukti_transfer';

            $_extension = array('png', 'jpg', 'jpeg');
            if ($fileSize > 2099200) { // satuan bytes
                $result = array(
                    'err_code' => '07',
                    'err_msg' => 'file size over 2048',
                    'data' => $fileSize
                );
                return response($result);
                return false;
            }
            if (!in_array($extension, $_extension)) {
                $result = array(
                    'err_code' => '07',
                    'err_msg' => 'file extension not valid',
                    'data' => null
                );
                return response($result);
                return false;
            }
            $path_img->move($tujuan_upload, $imageName);
            $data += array("img_bukti_transfer" => env('PUBLIC_URL') . '/uploads/transaksi/' . $imageName);
        }
        DB::table('transaksi')->where($where)->update($data);
        $data += array('id_transaksi' => $id_transaksi);
        $result = array(
            'err_code' => '00',
            'err_msg' => 'ok',
            'data' => $data
        );
        return response($result);
    }

    function appr_rej_bt(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_transaksi = (int)$request->id_transaksi > 0 ? (int)$request->id_transaksi : 0;
        $status = (int)$request->status > 0 ? (int)$request->status : 2;
        $where = array('transaksi.id_transaksi' => $id_transaksi);
        $data = array();
        $data = array(
            'appr_rej_bt_date' => $tgl,
            'status' => $status,
            'appr_rej_bt_by' => (int)$request->id_operator,
        );
        DB::table('transaksi')->where($where)->update($data);
        $data += array('id_transaksi' => $id_transaksi);
        $result = array(
            'err_code' => '00',
            'err_msg' => 'ok',
            'data' => $data
        );
        return response($result);
    }
}
