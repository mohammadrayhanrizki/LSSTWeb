<?php
// Sesuaikan dengan pengaturan lokalmu (XAMPP biasanya root dan password kosong)
$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "db_lsst_kas";

// Melakukan koneksi ke MySQL
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek apakah koneksi berhasil
if (!$conn) {
    die("Koneksi database terputus: " . mysqli_connect_error());
}

// Hapus tanda miring ganda di bawah ini jika kamu ingin mengetes koneksi
// echo "Koneksi ke database db_lsst_kas berhasil!";
?>  