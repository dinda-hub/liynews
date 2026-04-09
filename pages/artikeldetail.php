<?php 
include '../config/koneksi.php';
include '../includes/header.php';
include '../includes/navbar.php';

$id = $_GET['id'];
$query = "SELECT a.*, k.nama_kategori, u.nama_lengkap 
          FROM artikel a
          JOIN kategori k ON a.id_kategori = k.id_kategori
          JOIN user u ON a.id_user = u.id_user
          WHERE a.id_artikel = $id";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);
?>

<div class="container">
  <h2><?php echo $data['judul']; ?></h2>
  <p class="text-muted">Kategori: <?php echo $data['nama_kategori']; ?> | Penulis: <?php echo $data['nama_lengkap']; ?></p>
  <img src="../assets/images/<?php echo $data['thumbnail']; ?>" class="img-fluid mb-3" alt="">
  <p><?php echo nl2br($data['isi']); ?></p>

  <hr>
  <h5>Komentar:</h5>
  <?php
  $kom = mysqli_query($koneksi, "SELECT k.*, u.nama_lengkap 
                                FROM komentar k 
                                JOIN user u ON k.id_user = u.id_user 
                                WHERE k.id_artikel = $id AND k.status='tampil'");
  while($row = mysqli_fetch_assoc($kom)) {
      echo "<div class='border p-2 mb-2'><b>{$row['nama_lengkap']}</b><br>{$row['isi_komentar']}</div>";
  }
  ?>
</div>

<?php include '../includes/footer.php'; ?>