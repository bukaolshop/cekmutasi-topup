<?php
require_once 'config.php';

$incomingApiSignature = isset($_SERVER['HTTP_API_SIGNATURE']) ? $_SERVER['HTTP_API_SIGNATURE'] : '';

if( !hash_equals($api_signature_cekmutasi, $incomingApiSignature) ) {
  exit("Invalid Signature");
}

//Dapatkan data POST yang dikirim oleh cekmutasi.co.id
$post = file_get_contents("php://input");
// Jadikan data cekmutasi.co.id menjadi format json
$json_cekmutasi = json_decode($post);
// Cek apakah data json ada yang error
if(json_last_error() !== JSON_ERROR_NONE ) {
  exit("Invalid JSON");
}

// Lakukan koneksi ke mySQL
$koneksi=mysqli_connect($server,$username,$password_sql,$nama_database);
if(!$koneksi){
  exit('Database gagal terkoneksi');
}

if( $json_cekmutasi->action == "payment_report" ){
    
  foreach( $json_cekmutasi->content->data as $data )
  {
    # Waktu transaksi
    $waktu_transaksi =  date("Y-m-d H:i:s", strtotime($data->unix_timestamp));

    # Tipe transaksi : credit / debit
    $type = $data->type;

    # Jumlah (2 desimal) : 50000.00
    # Ubah langsung data desimal ke integer
    $amount = (int)$data->amount;

    # Berita transfer
    # Berita transfer ini harus berisi token_topup
    $berita_transfer = mysqli_real_escape_string($koneksi,$data->description);

    # Saldo rekening (2 desimal) : 1500000.00
    //Variabel ini tidak digunakan dalam script ini
    $balance = $data->balance;

    // cek apakah tipe merupakan "dana masuk"
    if( $type == "credit" ) {
       
      $sql = "SELECT * FROM data_topup WHERE jumlah_topup='$amount'";
      if($cek_data_topup = mysqli_query($koneksi,$sql)){
        if(mysqli_num_rows($cek_data_topup)==1){
          //Data ditemukan

          //Dapatkan token topup dari database
          $hasil_data_topup=mysqli_fetch_assoc($cek_data_topup);
          
          //cek apakah data memiliki status "paid", jika iya maka pembayaran sudah dilakukan, maka dari itu hentikan eksekusi program
          if($hasil_data_topup['status_bayar']=="paid"){
             exit("Transaksi ini sudah selesai");
          }
          
          $token_topup=mysqli_real_escape_string($koneksi,$hasil_data_topup['token_topup']);
 

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
             
             // print pesan sukses
             echo "saldo dengan token ".$token_topup." berhasil ditambah ke saldo member";
          }else{
             // saldo gagal ditambah, print pesan error
             print_r($json_api_bukaolshop);
          }

           
        }else if(mysqli_num_rows($cek_data_topup)>1){
            echo "jumlah topup di local database lebih dari 1";
        }
      }else{
          echo "cek data gagal";
      }
    }

  }
}


?>
