<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingAreaController extends Controller
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
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama_kel';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        $where = array('kelurahan.deleted_at' => null);
        $wheree = array('mapping_origin_destination.deleted_at' => null);
        if ((int)$request->id_kelurahan  > 0) $wheree += array('mapping_origin_destination.id_kel_origin' => $request->id_kelurahan );
        $count = 0;
        $_data = array();
        $data = null;
        if (!empty($keyword)) {
            $_data = DB::table('kelurahan')->where($where)->select('kelurahan.*', 'nama_provinsi', 'nama_city','nama_kec')
				->leftJoin('kecamatan', 'kecamatan.id_kec', '=', 'kelurahan.id_kec')
                ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                ->whereRaw("LOWER(nama_kel) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('kelurahan')->where($where)->count();
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('kelurahan')->where($where)->select('kelurahan.*','kelurahan.nama_kel','nama_provinsi', 'nama_city','nama_kec')
                ->leftJoin('kecamatan', 'kecamatan.id_kec', '=', 'kelurahan.id_kec')
                ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                ->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }	
		$provinsi = '';
		$city = '';
		$kecamatan = '';
		$kelurahan = '';
		if($count == 0){
			$data_kec = DB::table('kecamatan')->where(array('id_kec' => $request->id_kec))->first();
			$data_city = DB::table('kota')->where(array('id_kota' => $data_kec->id_city))->first();
			$data_prov = DB::table('provinsi')->where(array('id_provinsi' => $data_city->id_prov))->first();
			$data_kel = DB::table('kelurahan')->where($where)->first();
			$provinsi = $data_prov->nama_provinsi;
			$city = $data_city->nama_city;
			$kecamatan = $data_kec->nama_kec;
			$kelurahan = $data_kec->nama_kel;
		}
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'provinsi'      => $provinsi,
            'city'          => $city,
            'kecamatan'     => $kecamatan,
            'kelurahan'     => $kelurahan,
            'data'          => null
        );
		$dt_mapping = array();
        if ($count > 0) {
			$provinsi = $_data[0]->nama_provinsi;
            $city = $_data[0]->nama_city;
            $kecamatan = $_data[0]->nama_kec;
            $kelurahan = $_data[0]->nama_kel;
			if ((int)$request->id_kelurahan  > 0){
				$count_mapping = DB::table('mapping_origin_destination')->where($wheree)->count();
				if($count_mapping > 0){
					$data_mapping = DB::table('mapping_origin_destination')->where($wheree)
						->select('id_mapping', 'id_kel_origin', 'id_kel_destination','status')->get();
					foreach ($data_mapping as $dm) {
						$dt_mapping[$dm->id_kel_destination] = $dm;
					}
				}
			}
            foreach ($_data as $d) {
				$d->id_mapping = isset($dt_mapping[$d->id_kelurahan]) ? $dt_mapping[$dm->id_kel_destination]->id_mapping : '';
				$d->id_kel_origin = isset($request->id_kelurahan) && (int)$request->id_kelurahan > 0 ? $request->id_kelurahan : '';
				$d->id_kel_destination = $d->id_kelurahan;
				$d->status = isset($dt_mapping[$d->id_kelurahan]) ? $dt_mapping[$dm->id_kel_destination]->status : 0;
                unset($d->created_by);
                unset($d->updated_by);
                unset($d->deleted_by);
                unset($d->created_at);
                unset($d->updated_at);
                unset($d->deleted_at);
                unset($d->id_kelurahan);
				
                $data[] = $d;
            }
            $result = array(
                'err_code'      => '00',
                'err_msg'       => 'ok',
                'total_data'    => $count,
				'provinsi'      => $provinsi,
				'city'          => $city,
				'kecamatan'     => $kecamatan,
				'kelurahan'     => $kelurahan,
                'data'          => $data
            );
        }
        return response($result);
    }

    function store(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $data = array();
        $id = (int)$request->id_mapping  > 0 ? (int)$request->id_mapping  : 0;
		$id_kel_origin = $request->has('id_kel_origin') ? $request->id_kel_origin : '';
		$id_kel_destination = $request->has('id_kel_destination') ? $request->id_kel_destination : '';
		$status = $request->has('status') && (int)$request->status > 0 ? (int)$request->status : 0;
        
        if ($id > 0) {
            $data = array("updated_at" => $tgl, "updated_by" => $request->id_operator,'status'=>$status);
            DB::table('mapping_origin_destination')->where('id_mapping', $id)->update($data);
        } else {
            $data = array(
                "id_kel_origin"    		=> (int)$request->id_kel_origin,
                "id_kel_destination"    => (int)$request->id_kel_destination,
				"status"				=> 1,
                "created_at" 			=> $tgl,
                "created_by" 			=> $request->id_operator
            );
            $id = DB::table('mapping_origin_destination')->insertGetId($data, "id_mapping");
        }
        $result = array();
        if ($id > 0) {
            $data += array('id_mapping' => $id);
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

    function mapping_price(Request $request){
		$per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama_kel';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
		$id_cargo = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;	

		$_idKel = array();
		$cnt = 0;
		if (!empty($keyword)) {
            $data_keyword = DB::table('kelurahan')->where(array('deleted_at' => null))->select('id_kelurahan')				
                ->whereRaw("LOWER(nama_kel) like '%" . $keyword . "%'")->get();
			$cnt = count($data_keyword);
			if((int)$cnt > 0){
				foreach ($data_keyword as $dk) {
					$_idKel[] = (int)$dk->id_kelurahan;
					
				}
			}
            
        }
		
		
		$where = array('deleted_at' => null,'status'=>1);
		$count = DB::table('mapping_origin_destination')->where($where);
		if(!empty($keyword)) $count = $count->whereIn('id_kel_origin',$_idKel);
		$count = $count->count();
        $per_page = $per_page > 0 ? $per_page : $count;
        $offset = ($page_number - 1) * $per_page;
		$sort_columnn = "ABS(id_mapping) ASC";
        $id_kel = array();
        $dt_area = array();
        $res = array();
        $dt_price = array();
        $data_price = array();
		$nama_cargo = '';
		if((int)$count > 0){
			$data_cargo = DB::table('armada_cargo')->where(array('deleted_at' => null, 'id_ac' => $id_cargo))->first();
			$nama_cargo = isset($data_cargo->nama_cargo) ? $data_cargo->nama_cargo : '';
			$_data = DB::table('mapping_origin_destination')->where($where)->select('id_mapping','id_kel_origin','id_kel_destination');
			if(!empty($keyword)) $_data = $_data->whereIn('id_kel_origin',$_idKel);			
			$_data = $_data->offset($offset)->limit($per_page)->orderByRaw($sort_columnn)->get();
			
			foreach ($_data as $dm) {
                $id_kel[$dm->id_kel_origin] = (int)$dm->id_kel_origin;
                $id_kel[$dm->id_kel_destination] = (int)$dm->id_kel_destination;
				//$whereIn = implode(',',$id_kel);
            }
			$data_area = DB::table('kelurahan')->where(array('kelurahan.deleted_at' => null))->select('kelurahan.*','kelurahan.nama_kel','nama_provinsi', 'nama_city','nama_kec')
                ->leftJoin('kecamatan', 'kecamatan.id_kec', '=', 'kelurahan.id_kec')
                ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                ->whereIn('kelurahan.id_kelurahan', $id_kel)->get();
			$data_price = DB::table('pricelist_cargo')->where(array('id_ac' => $id_cargo))->select('id_pricelist','id_ac','id_mapping','hrg','status')->get();
			foreach($data_price as $dp){
				$dt_price[$dp->id_mapping] = $dp;
			}
			foreach($data_area as $da){
				$dt_area[$da->id_kelurahan] = $da;
			}
			foreach ($_data as $dm) {
				$dm->nama_provinsi_origin = isset($dt_area[$dm->id_kel_origin]) ? $dt_area[$dm->id_kel_origin]->nama_provinsi : '';
				$dm->nama_city_origin = isset($dt_area[$dm->id_kel_origin]) ? $dt_area[$dm->id_kel_origin]->nama_city : '';
				$dm->nama_kec_origin = isset($dt_area[$dm->id_kel_origin]) ? $dt_area[$dm->id_kel_origin]->nama_kec : '';
				$dm->nama_kel_origin = isset($dt_area[$dm->id_kel_origin]) ? $dt_area[$dm->id_kel_origin]->nama_kel : '';
				$dm->kode_pos_origin = isset($dt_area[$dm->id_kel_origin]) ? $dt_area[$dm->id_kel_origin]->kode_pos : '';
				
				$dm->nama_provinsi_destination = isset($dt_area[$dm->id_kel_destination]) ? $dt_area[$dm->id_kel_destination]->nama_provinsi : '';
				$dm->nama_city_destination = isset($dt_area[$dm->id_kel_destination]) ? $dt_area[$dm->id_kel_destination]->nama_city : '';
				$dm->nama_kec_destination = isset($dt_area[$dm->id_kel_destination]) ? $dt_area[$dm->id_kel_destination]->nama_kec : '';
				$dm->nama_kel_destination = isset($dt_area[$dm->id_kel_destination]) ? $dt_area[$dm->id_kel_destination]->nama_kel : '';
				$dm->kode_pos_destination = isset($dt_area[$dm->id_kel_destination]) ? $dt_area[$dm->id_kel_destination]->kode_pos : '';
				$dm->id_pricelist = isset($dt_price[$dm->id_mapping]) ? $dt_price[$dm->id_mapping]->id_pricelist : '';
				$dm->id_ac = $id_cargo > 0 ? $id_cargo : '';				
				$dm->hrg = isset($dt_price[$dm->id_mapping]) ? $dt_price[$dm->id_mapping]->hrg : '';
				$dm->status = isset($dt_price[$dm->id_mapping]) ? (int)$dt_price[$dm->id_mapping]->status : 0;
                $res[] = $dm;
                
            }
		}
		$result = array(
                'err_code'  	=> '00',
                'err_msg'   	=> 'ok',
				'total_data'	=> $count,
				'nama_cargo'	=> $nama_cargo,
                'data'      	=> $res
            );
		return response($result);
	}
	
	function store_price(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $data = array();
        $id = (int)$request->id_pricelist > 0 ? (int)$request->id_pricelist : 0;
        $id_ac = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;
        $id_mapping = (int)$request->id_mapping > 0 ? (int)$request->id_mapping : 0;
		$hrg = $request->has('hrg') ? str_replace(',', '', $request->hrg) : '';		
        $status = $request->has('status') && (int)$request->status > 0 ? (int)$request->status : 0;
		
        if ($id > 0) {
            $data = array("updated_at" => $tgl, "updated_by" => $request->id_operator,'hrg'=>$hrg,'status'=>$status);
            DB::table('pricelist_cargo')->where('id_pricelist', $id)->update($data);
        } else {
            $data = array(
                "id_ac"    		=> (int)$request->id_ac,
                "id_mapping"    => (int)$request->id_mapping,
				"hrg"			=> $hrg,
				"status"		=> $status,
                "created_at" 			=> $tgl,
                "created_by" 			=> $request->id_operator
            );
            $id = DB::table('pricelist_cargo')->insertGetId($data, "id_pricelist");
        }
        $result = array();
        if ($id > 0) {
            $data += array('id_pricelist' => $id);
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
	
	function get_id_kelurahan(Request $request)
    {
		$nama_provinsi = $request->has('nama_provinsi') ? strtolower($request->nama_provinsi) : '';		
		$nama_city = $request->has('nama_city') ? strtolower($request->nama_city) : '';		
		$nama_kec = $request->has('nama_kec') ? strtolower($request->nama_kec) : '';		
		$nama_kel = $request->has('nama_kel') ? strtolower($request->nama_kel) : '';		
		$where = array('kelurahan.deleted_at' => null, 'kecamatan.deleted_at' => null, 'kota.deleted_at' => null, 'provinsi.deleted_at' => null);
		$data = DB::table('kelurahan')->where($where)->select('kelurahan.*','provinsi.id_provinsi','kota.id_kota','kelurahan.nama_kel','nama_provinsi', 'nama_city','nama_kec')
                ->leftJoin('kecamatan', 'kecamatan.id_kec', '=', 'kelurahan.id_kec')
                ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
                ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov');
		if(!empty($nama_provinsi)) $data = $data->whereRaw("LOWER(provinsi.nama_provinsi) = '$nama_provinsi'");
		if(!empty($nama_city)) $data = $data->whereRaw("LOWER(kota.nama_city) = '$nama_city'");
		if(!empty($nama_kec)) $data = $data->whereRaw("LOWER(kecamatan.nama_kec) = '$nama_kec'");
		if(!empty($nama_kel)) $data = $data->whereRaw("LOWER(kelurahan.nama_kel) = '$nama_kel'");
        $data = $data->get();
		$result = array();
		$result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'data'          => null
        );
		if(count($data) > 0){
			 foreach ($data as $d) {
                unset($d->created_by);
                unset($d->updated_by);
                unset($d->deleted_by);
                unset($d->created_at);
                unset($d->updated_at);
                unset($d->deleted_at);
                $_data[] = $d;
            }
			$result = array(
				'err_code'      => '00',
				'err_msg'       => 'ok',
				'data'          => $_data
			);
		}
		return response($result);
	}
	
	function get_ongkirs(Request $request)
    {
		$id_kel_origin = (int)$request->id_kel_origin > 0 ? (int)$request->id_kel_origin : 0;
		$id_kel_destination = (int)$request->id_kel_destination > 0 ? (int)$request->id_kel_destination : 0;
        $id_ac = (int)$request->id_ac > 0 ? (int)$request->id_ac : 0;
		$where = array('deleted_at' => null,'status'=>1, 'id_kel_origin'=>$id_kel_origin,'id_kel_destination'=>$id_kel_destination);
		$count = DB::table('mapping_origin_destination')->where($where)->count();
		$result = array();
		$result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'data'          => null
        );
		if((int)$count > 0){
			$_data = DB::table('mapping_origin_destination')->where($where)->select('id_mapping','id_kel_origin','id_kel_destination')->first();
			$id_mapping = (int)$_data->id_mapping;
			// $data_area = DB::table('kelurahan')->where(array('kelurahan.deleted_at' => null))->select('kelurahan.*','kelurahan.nama_kel','nama_provinsi', 'nama_city','nama_kec')
                // ->leftJoin('kecamatan', 'kecamatan.id_kec', '=', 'kelurahan.id_kec')
                // ->leftJoin('kota', 'kota.id_kota', '=', 'kecamatan.id_city')
                // ->leftJoin('provinsi', 'provinsi.id_provinsi', '=', 'kota.id_prov')
                // ->whereIn('kelurahan.id_kelurahan', $id_kel)->get();
			$wheree = array();
			$wheree = array('pricelist_cargo.id_mapping'=>$id_mapping,'pricelist_cargo.status'=>1);
			if($id_ac > 0){
				$wheree += array('pricelist_cargo.id_ac'=>$id_ac);				
			}
			$data_price = DB::table('pricelist_cargo')->where($wheree)->select('id_pricelist','pricelist_cargo.id_ac','id_mapping','nama_cargo','hrg','status')->leftJoin('armada_cargo', 'armada_cargo.id_ac', '=', 'pricelist_cargo.id_ac')->get();
			if(count($data_price) > 0){
				$result = array(
					'err_code'      => '00',
					'err_msg'       => 'ok',
					'data'          => $data_price
				);
			}
			
		}
		return response($result);
	}
	
}
