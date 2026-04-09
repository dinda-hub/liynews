<?php
include '../config/koneksi.php';
session_start();
$id_user = $_SESSION['id_user'] ?? 1; // default pembaca anonim
$id_artikel = $_POST['id_artikel'];
$isi = mysqli_real_escape_string($koneksi, $_POST['isi_komentar']);

mysqli_query($koneksi, "INSERT INTO komentar (id_artikel, id_user, isi_komentar) VALUES ('$id_artikel', '$id_user', '$isi')");
header("Location: artikel.php?id=$id_artikel");
exit();
?>