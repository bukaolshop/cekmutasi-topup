<?php
require_once 'config.php';

//Cek apakah variable token ada dan hanya digit angka saja
if(isset($_GET['token']) and ctype_digit($_GET['token'])){

  // Lakukan koneksi ke mySQL
  $koneksi=mysqli_connect($server,$username,$password_sql,$nama_database);
  if(!$koneksi){
    exit('Database gagal terkoneksi');
  }

  //Buat variabel token_topup
  $token_topup=mysqli_real_escape_string($koneksi,$_GET['token']);
  $sql = "SELECT * FROM data_topup WHERE token_topup = '$token_topup'";
  if($cek_data_topup = mysqli_query($koneksi,$sql)){
    if(mysqli_num_rows($cek_data_topup)==1){
      $hasil_cek=mysqli_fetch_assoc($cek_data_topup);
      if($hasil_cek['status_bayar']=="paid"){
        // Token topup ini telah memiliki status paid, alihkan halaman ke pesan selesai
        header('location:pesan_cekmutasi.php?status=sukses&bukaolshop_finish_page=true');
        exit();
      }else{
        // Set variabel jumlah_topup
        $jumlah_topup=$hasil_cek['jumlah_topup'];
      }
    }else{
      // Token topup tidak ditemukan di database, Alihkan halaman ke pesan tidak ditemukan
      header('location:pesan_cekmutasi.php?status=notfound&bukaolshop_finish_page=true');
      exit();
    }


    //Cek apakah variabel $jumlah_topup sudah di set, jika sudah lanjutkan cek ke API cekmutasi.co.id
    if(!empty($jumlah_topup)){
      // Convert $jumlah_topup ke tipe desimal 2 digit sesuai syarat dari cekmutasi.co.id
      $jumlah_topup=number_format((float)$jumlah_topup, 2, '.', '');



      // Kode dibawah diambil dari website https://cekmutasi.co.id/developer
      // --------------------------------------------------------------------------------------------------
      // ------------------------------------ CEK MUTASI 1 ------------------------------------------------
      // --------------------------------------------------------------------------------------------------
      $data = array(
        "search"  => array(
          "date"            => array(
            "from"    => date("Y-m-d")." 00:00:00",
            "to"      => date("Y-m-d")." 23:59:59"
          ),
          "service_code"    => "bca",
          "account_number"  => "1234567890",
          "amount"          => $jumlah_topup
        )
      );

      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL             => "https://api.cekmutasi.co.id/v1/bank/search",
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => http_build_query($data),
        CURLOPT_HTTPHEADER      => ["Api-Key: $api_key_cekmutasi", "Accept: application/json"], // tanpa tanda kurung
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_HEADER          => false,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_TIMEOUT         => 15,
        CURLOPT_IPRESOLVE		=> CURL_IPRESOLVE_V4,
      ));
      $result = curl_exec($ch);
      curl_close($ch);

      $json_cekmutasi = json_decode($result);
      // Cek apakah data json ada yang error
      if(json_last_error() !== JSON_ERROR_NONE ) {
        exit("Invalid JSON");
      }

      if($json_cekmutasi->success){
        // Contoh hasil json bisa dilihat di https://cekmutasi.co.id/developer
        // Parameter response merupakan Array, lakukan loop untuk memeriksa tiap data
        foreach ($json_cekmutasi->response as $key_mutasi) {

          //Cek apakah credit = transaksi masuk
          if($key_mutasi->type=="credit"){
            // Cek apakah ada jumlah dana yang masuk yang sama dengan jumlah topup
            if($key_mutasi->amount==$jumlah_topup){

              // Jumlah transfer sama dengan kode unik
              // Lakukan konfirmasi topup saldo member menggunakan API bukaOlshop
              // Setting API key bukaOlshop
              $header=  array("Authorization: Bearer ".$api_key_bukaolshop );

              // Masukkan parameter token_topup
              $post_body=array(
                "token_topup"=>$token_topup,
              );

              //Kirim perintah curl
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL,"https://bukaolshop.net/api/v1/member/topup");
              curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
              curl_setopt($ch, CURLOPT_POST, TRUE);
              curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

              $hasil = curl_exec($ch);
              curl_close ($ch);

              //Cek apakah konfirmasi saldo berhasil
              $json_api_bukaolshop=json_decode($hasil);
              if($json_api_bukaolshop->code=="200"){
                //Konfirmasi saldo berhasil
                //Ubah status topup di lokal database menjadi "paid"
                mysqli_query($koneksi,"UPDATE `data_topup` SET `status_bayar` = 'paid',`tanggal_dibayar`=CURRENT_TIMESTAMP WHERE `token_topup` = '$token_topup';");

                // alihkan halaman ke status paid
                // tambahkan parameter bukaolshop_finish_page=true agar jika user tekan tombol kembali, halaman override langung tertutup.
                header('location:pesan_cekmutasi.php?status=sukses&bukaolshop_finish_page=true');
                exit();
              }else{
                // saldo gagal ditambah, print pesan error
                header('location:pesan_cekmutasi.php?status=error&bukaolshop_finish_page=true');
                exit();
              }

            }
          }

        }

      }
      // --------------------------------------------------------------------------------------------------
      // ------------------------------------ CEK MUTASI 1 ------------------------------------------------
      // --------------------------------------------------------------------------------------------------



    }


  }
  // Jika tidak ada pengalihan halaman, artinya tidak ada data yang terdeteksi, alihkan halaman ke halaman default
    header('location:pesan_cekmutasi.php');
}


?>
