<?php

  switch ($_GET['status']) {
    case 'sukses':
      $judul="Terimakasih";
      $deskripsi="Pembayaran berhasil, saldo akan masuk ke akun olshop anda.<br>Pastikan anda merestart aplikasi untuk melihat perubahan saldo.";
      $checkmark="✓";
      break;
      case 'notfound':
      $judul="Tidak Ditemukan";
      $deskripsi="Data tidak ditemukan, coba lakukan request topup kembali";
      $checkmark="✖";
      break;
      case 'error':
      $judul="Gagal";
      $deskripsi="Gagal menambahkan data saldo.<br>Saldo pada transaksi ini kemungkinan sudah ditambah ke akun anda";
      $checkmark="✖";
      break;

    default:
      $judul="Belum Terdeteksi";
      $deskripsi="Kami belum menemukan jumlah topup anda yang masuk, coba beberapa saat kembali";
      $checkmark="!";
      break;
  }

 ?>
<html>
  <head>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
    <title>Cek Pembayaran</title>
  </head>
    <style>
      body {
        text-align: center;
        padding: 40px 0;
        background: #EBF0F5;
      }
        h1 {
          color: #88B04B;
          font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
          font-weight: 900;
          font-size: 40px;
          margin-bottom: 10px;
        }
        p {
          color: #404F5E;
          font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
          font-size:20px;
          margin: 0;
        }
      i {
        color: #9ABC66;
        font-size: 100px;
        line-height: 200px;
        margin-left:-15px;
      }
      .card {
        background: white;
        padding: 60px;
        border-radius: 4px;
        box-shadow: 0 2px 3px #C8D0D8;
        display: inline-block;
        margin: 0 auto;
      }
    </style>
    <body>
      <div class="card">
      <div style="border-radius:200px; height:200px; width:200px; background: #F8FAF5; margin:0 auto;">
        <i class="checkmark"><?=$checkmark?></i>
      </div>
        <h1><?=$judul?></p>
        <p><?=$deskripsi?></p>

      </div>
    </body>
</html>
