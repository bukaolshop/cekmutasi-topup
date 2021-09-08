<?php
require_once 'config.php';

function FormatRupiah($angka){
  if($angka=="" or !is_numeric($angka)){
    return "Rp0,00";
  }else{
    $hasil_rupiah = "Rp" . number_format($angka,0,',','.');
    return $hasil_rupiah;
  }
  }

//Periksa secret key
if(isset($_POST['secret_callback'])){
    if(!hash_equals($_POST['secret_callback'],$my_secret_key)){
      // secret_callback tidak cocok, hentikan eksekusi program
      exit("secret key salah");
    }
}else{
  // secret_callback tidak ada, hentikan eksekusi program
  exit("secret key salah");
}

//check apakah data valid
 if(empty($_POST['id_user']) or
   empty($_POST['token_topup']) or
   empty($_POST['jumlah_topup']) or
   empty($_POST['kode_unik']) or
   empty($_POST['total_topup']) or
   !ctype_digit($_POST['jumlah_topup']) or
   !ctype_digit($_POST['kode_unik']) or
   !ctype_digit($_POST['total_topup']) or
   $_POST['status']!="ok"){
   // data ada yang kosong atau tidak valid
   exit("data invalid");
 }

// Lakukan koneksi ke mySQL
$koneksi=mysqli_connect($server,$username,$password_sql,$nama_database);
if(!$koneksi){
  exit('Database gagal terkoneksi');
}

//Buat variabel
  $token_topup=mysqli_real_escape_string($koneksi,$_POST['token_topup']);
  $total_topup=mysqli_real_escape_string($koneksi,$_POST['total_topup']);


$sql = "SELECT * FROM data_topup WHERE jumlah_topup = '$total_topup'";
if($cek_data_topup = mysqli_query($koneksi,$sql)){
    
// cek apakah total data lebih dari 1, jika iya, hentikan eksekusi program karena jumlah topup + kode unik hanya boleh ada 1 data saja.    
  if(mysqli_num_rows($cek_data_topup)>1){
    exit("Pembayaran gagal di muat, coba lakukan request topup kembali");
  }else if(mysqli_num_rows($cek_data_topup)==1){
    $hasil_cek=mysqli_fetch_assoc($cek_data_topup);
    if($hasil_cek['status_bayar']=="paid"){
      header('location:pesan_cekmutasi.php?status=sukses&bukaolshop_finish_page=true');
      exit();
    }else if($hasil_cek['token_topup']!=$token_topup){
      // token topup yg dikirim oleh server bukaolshop tidak cocok dengan lokal database, demi keamanan data, minta member untuk request ulang agar mendapatkan token yang baru
      exit("Pembayaran gagal di muat, coba lakukan request topup kembali");
    }
  }else{
    // Insert data ke lokal database, jika insert gagal dilakukan, hentikan eksekusi program
    if(!mysqli_query($koneksi,"INSERT INTO `data_topup` (`token_topup`, `jumlah_topup`, `status_bayar`, `tanggal_token`, `tanggal_dibayar`) VALUES ('$token_topup', '$total_topup', 'unpaid', CURRENT_TIMESTAMP, NULL);")){
      exit("Pembayaran gagal di muat, coba lakukan request topup kembali");
    }
  }
}

//Memasukkan url file "cekmutasi_cekpembayaran.php" dibawah.
//Anda bisa memasukkan link full url, atau jika file berada didalam satu folder, anda cukup memasukkan nama file nya saja.
$url_cek_mutasi="cekmutasi_cekpembayaran.php?token=".htmlentities($token_topup);

 ?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="utf-8">
  <title>TopUp Saldo</title>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" charset="utf-8"></script>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
</head>
<body>
  <div class="m-3 d-flex justify-content-center">
    <div class="card">
      <div>
        <div class="d-flex pt-4 pl-3">
          <div><img src="https://testing.bukaolshop.net/cekmutasi/logo.png" width="80" height="80" /></div>
          <div class="mt-3 pl-2"><span class="name">Nama aplikasi</span>

          </div>
        </div>

        <table class="table table-clear mt-4">
          <tbody>
            <tr>
              <td class="left">
                <strong>Jumlah TopUp</strong>
              </td>
              <td class="right"><?=FormatRupiah($_POST['jumlah_topup'])?></td>
            </tr>
            <tr>
              <td class="left">
                <strong>Kode unik</strong>
              </td>
              <td class="right"><?=FormatRupiah($_POST['kode_unik'])?></td>
            </tr>

            <tr class="table-success">
              <td class="left">
                <strong>Total Transfer</strong>
              </td>
              <td class="right">
                <strong><?=FormatRupiah($_POST['total_topup'])?></strong>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- REKENING PERTAMA -->
        <div class="py-2 px-3">
          <div class="second pl-2 d-flex py-2">
            <div style="width:25%;margin:10px"><img src="https://image.cermati.com/v1428073854/brands/avqoa9rfng8bklutfhm6.jpg" class="img-thumbnail"/></div>
            <div class="border-left pl-2">
              <span class="head">BANK BCA</span>
              <div><span class="amount" id="rek_1">82452356235</span></div>
              <span><button type="button" class="btn btn-info btn-sm " onclick="copyToClipboard('#rek_1')">Copy rekening BCA</button></span>
            </div>
          </div>
        </div>


        <!-- REKENING KEDUA -->
        <div class="py-2 px-3">
          <div class="second pl-2 d-flex py-2">
            <div style="width:25%;margin:10px"><img src="https://image.cermati.com/v1428073854/brands/avqoa9rfng8bklutfhm6.jpg" class="img-thumbnail"/></div>
            <div class="border-left pl-2">
              <span class="head">BANK BCA</span>
              <div><span class="amount" id="rek_2">123456789</span></div>
              <span><button type="button" class="btn btn-info btn-sm " onclick="copyToClipboard('#rek_2')">Copy rekening BCA</button></span>
            </div>
          </div>
        </div>



        <!-- REKENING KETIGA -->
        <div class="py-2 px-3">
          <div class="second pl-2 d-flex py-2">
            <div style="width:25%;margin:10px"><img src="https://image.cermati.com/v1428073854/brands/avqoa9rfng8bklutfhm6.jpg" class="img-thumbnail"/></div>
            <div class="border-left pl-2">
              <span class="head">BANK BCA</span>
              <div><span class="amount" id="rek_3">987654321</span></div>
              <span><button type="button" class="btn btn-info btn-sm " onclick="copyToClipboard('#rek_3')">Copy rekening BCA</button></span>
            </div>
          </div>
        </div>

       


        <div class="px-3 pt-4 pb-3">
          <p>Sudah melakukan pembayaran? tekan tombol dibawah untuk mengecek status pembayaran anda.</p>
          <a href="<?=$url_cek_mutasi?>"><button type="button" class="btn btn-primary ">Cek Pembayaran</button></a>
        </div>
      </div>
    </div>
  </div>
  <style media="screen">
  body {
    background-color: #ffffff
  }

  .container {

    background-color: #fff;
    padding-top: 100px;
    padding-bottom: 100px
  }

  .card {
    background-color: #fff;
    width: 90%;
    border-radius: 15px;
    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19)
  }

  .name {
    font-size: 15px;
    color: #403f3f;
    font-weight: bold
  }

  .cross {
    font-size: 11px;
    color: #b0aeb7
  }

  .pin {
    font-size: 14px;
    color: #b0aeb7
  }

  .first {
    border-radius: 8px;
    border: 1.5px solid #78b9ff;
    color: #000;
    background-color: #eaf4ff
  }

  .second {
    border-radius: 8px;
    border: 1px solid #acacb0;
    color: #000;
    background-color: #fff
  }

  .dot {}

    .head {
      color: #137ff3;
      font-size: 12px
    }

    .dollar {
      font-size: 18px;
      color: #097bf7
    }

    .amount {
      color: #007bff;
      font-weight: bold;
      font-size: 18px
    }

    .form-control {
      font-size: 18px;
      font-weight: bold;
      width: 60px;
      height: 28px
    }

    .back {
      color: #aba4a4;
      font-size: 15px;
      line-height: 73px;
      font-weight: 400
    }

    .button {
      width: 150px;
      height: 60px;
      border-radius: 8px;
      font-size: 17px
    }
    </style>
  </body>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js" charset="utf-8"></script>

  <script type="text/javascript">
  function copyToClipboard(element) {
    var aux = document.createElement("input");
    aux.setAttribute("value", $(element).text());
    document.body.appendChild(aux);
    aux.select();
    document.execCommand("copy");
    document.body.removeChild(aux);

    alert("Nomor rekening telah dicopy : "+$(element).text());
  }
</script>
</html>

