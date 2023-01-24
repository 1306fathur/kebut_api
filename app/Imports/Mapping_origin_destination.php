<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class Mapping_origin_destination implements ToCollection
{
    /**
     * @param Collection $collection
     */

     public function __construct($id_ac)
     {
         $this->id_ac= $id_ac;
     }
    public function collection(Collection $collection)
    {
        //insert data to table mapping_origin_destination

        foreach ($collection as $key => $value) {
            if ($key > 0) {
                $get_id_origin = DB::table('kelurahan')->where('nama_kel', $value[0])->first();
                $id_kelurahan_origin = $get_id_origin->id_kelurahan;
                $get_id_destination = DB::table('kelurahan')->where('nama_kel', $value[1])->first();
                $id_kelurahan_destination = $get_id_destination->id_kelurahan;

                $check_exist_mapping = DB::table('pricelist_cargo')
                    ->join('mapping_origin_destination', 'pricelist_cargo.id_mapping', '=', 'mapping_origin_destination.id_mapping')
                    ->where('id_kel_origin', $id_kelurahan_origin)
                    ->where('id_kel_destination', $id_kelurahan_destination)
                    ->where('id_ac', $this->id_ac)
                    ->first();
                if ($check_exist_mapping == null) {
                    $data = [
                        'id_kel_origin' => $id_kelurahan_origin,
                        'id_kel_destination' => $id_kelurahan_destination,
                        'status' => '1',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $mapping_post = DB::table('mapping_origin_destination')->insert($data);

                    //insert id_mapping to pricelist_cargo
                    $get_id_mapping = DB::table('mapping_origin_destination')->where('id_kel_origin', $id_kelurahan_origin)->where('id_kel_destination', $id_kelurahan_destination)->first();
                    $id_mapping = $get_id_mapping->id_mapping;
                    //add id_mapping to pricelist_cargo
                    $data_price_list = [
                        'id_ac' => $this->id_ac,
                        'id_mapping' => $id_mapping,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'status' => '1',
                        'hrg' => $value[2],
                    ];

                    $pricelist_cargo_post = DB::table('pricelist_cargo')->insert($data_price_list);
                } else {
                    $data = [
                        'id_kel_origin' => $id_kelurahan_origin,
                        'id_kel_destination' => $id_kelurahan_destination,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $mapping_post = DB::table('mapping_origin_destination')->where('id_mapping', $check_exist_mapping->id_mapping)->update($data);

                    //insert id_mapping to pricelist_cargo
                    $get_id_mapping = DB::table('mapping_origin_destination')->where('id_kel_origin', $id_kelurahan_origin)->where('id_kel_destination', $id_kelurahan_destination)->first();
                    $id_mapping = $get_id_mapping->id_mapping;
                    //update hrg pricelist_cargo
                    $data_price_list = [
                        'hrg' => $value[2],
                    ];

                    $pricelist_cargo_post = DB::table('pricelist_cargo')->where('id_mapping', $id_mapping)->update($data_price_list);
                }
            }
        }
    }
}
