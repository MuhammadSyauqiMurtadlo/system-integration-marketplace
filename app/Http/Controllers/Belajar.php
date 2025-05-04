<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Belajar extends Controller
{
    public function coba()
    {
       return '{"kode:"01", "pesan":"Selamat Datang"}'; 
    }
//function post    
    public function cek_tgl(Request $x){
        $tgl = $x->input("tanggal");
        $hari = date("l", strtotime($tgl));
        $terjemah = [
            "Monday" => "Senin",
            "Tuesday" => "Selasa",
            "Wednesday" => "Rabu",
            "Thursday" => "Kamis",
            "Friday" => "Jumat",
            "Saturday" => "Sabtu",
            "Sunday" => "Minggu",
        ];
        $json = '{
        "tanggal":"'.$tgl.'",
        "hari":"'.$terjemah[$hari].'"
        }';
        return $json;        
    }
    public function enkripsi_dekripsi($jenis, $teks){
        $kunci = "123456789";
        if($jenis == "enkripsi"){
            $hasil = openssl_encrypt($teks, "AES-256-CBC", $kunci,0,"0123456789abcdef");
            $kategori = "Enkripsi";
        }else{
            $hasil = openssl_decrypt($teks, "AES-256-CBC", $kunci,0,"0123456789abcdef");
            $kategori = "Dekripsi";
        }
        $json = '{
        "jenis" => "'.$kategori.'",
        "teks" => "'.$teks.'",
        "hasil" => "'.$hasil.'",
        }';
        return $json;
    }
}
