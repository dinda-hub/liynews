<?php
session_start();
include 'config/koneksi.php';

$username = $_POST['username'];
$password = $_POST['password'];

$cek = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password'");
$data = mysqli_fetch_assoc($cek);

if ($data) {
  $_SESSION['login'] = true;
  $_SESSION['nama'] = $data['nama_lengkap'];
  header("Location: admin/index.php");
} else {
  echo "<script>alert('Login gagal!'); window.location='login.php';</script>";
}
?>