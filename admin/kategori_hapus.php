<?php
session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: kategori.php"); exit; }

// Cek apakah kategori masih dipakai oleh berita
$cek = mysqli_query($koneksi, "SELECT id_artikel FROM artikel WHERE kategori_id=$id LIMIT 1");
if (mysqli_num_rows($cek) > 0) {
    header("Location: kategori.php?msg=gagal"); exit;
}

// Cek apakah kategori benar-benar ada sebelum dihapus
$kat = mysqli_query($koneksi, "SELECT id_kategori FROM kategori WHERE id_kategori=$id LIMIT 1");
if (mysqli_num_rows($kat) === 0) {
    header("Location: kategori.php"); exit;
}

mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori=$id");
header("Location: kategori.php?msg=hapus"); exit;