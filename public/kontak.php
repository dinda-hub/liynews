<?php
require_once '_base.php';

// Handle form submission
$status = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $subjek  = trim($_POST['subjek']  ?? '');
    $pesan   = trim($_POST['pesan']   ?? '');

    if (!$nama)   $errors[] = 'Nama wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!$subjek) $errors[] = 'Subjek wajib diisi.';
    if (strlen($pesan) < 20) $errors[] = 'Pesan minimal 20 karakter.';

    if (empty($errors)) {
        // Simpan ke tabel kontak jika ada, atau kirim email
        // Contoh: simpan ke DB
        $namaEs   = mysqli_real_escape_string($koneksi, $nama);
        $emailEs  = mysqli_real_escape_string($koneksi, $email);
        $telEs    = mysqli_real_escape_string($koneksi, $telepon);
        $subjekEs = mysqli_real_escape_string($koneksi, $subjek);
        $pesanEs  = mysqli_real_escape_string($koneksi, $pesan);

        // Cek apakah tabel kontak ada
        $tblChk = mysqli_query($koneksi, "SHOW TABLES LIKE 'kontak'");
        if (mysqli_num_rows($tblChk) > 0) {
            mysqli_query($koneksi,
                "INSERT INTO kontak (nama, email, telepon, subjek, pesan, created_at)
                 VALUES ('$namaEs','$emailEs','$telEs','$subjekEs','$pesanEs',NOW())"
            );
        }
        // Opsional: kirim email notifikasi
        // mail('redaksi@liynews.id', "Pesan Kontak: $subjek", $pesan, "From: $email");
        $status = 'success';
    }
}

// Pre-fill dari query string (dari tombol Lamar/Paket Iklan)
$lamar = htmlspecialchars($_GET['lamar'] ?? '');
$paket = htmlspecialchars($_GET['paket'] ?? '');
$subjekDefault = $lamar ? "Lamaran: $lamar" : ($paket ? "Iklan Paket $paket" : '');

liy_head('Kontak', 'Hubungi LiyNews — kami siap membantu pertanyaan, masukan, dan kebutuhan Anda.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-envelope-fill"></i> Kontak</div>
    <h1>Ada yang Bisa<br>Kami Bantu?</h1>
    <p>Tim LiyNews siap membantu Anda. Kirimkan pesan, pertanyaan, atau masukan melalui formulir di bawah ini.</p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Kontak</span>
    </div>

    <div class="kontak-grid">

      <!-- Form -->
      <div>
        <?php if ($status === 'success'): ?>
        <div class="alert alert-success" style="margin-bottom:24px">
          <i class="bi bi-check-circle-fill"></i>
          <div><strong>Pesan terkirim!</strong> Terima kasih telah menghubungi kami. Tim kami akan membalas dalam 1–2 hari kerja.</div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom:24px">
          <i class="bi bi-exclamation-circle-fill"></i>
          <div><strong>Terdapat kesalahan:</strong><br><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
        </div>
        <?php endif; ?>

        <div style="background:var(--card);border:1px solid var(--bdr);border-radius:12px;padding:28px;box-shadow:var(--sh2)">
          <h2 style="font-family:var(--fd);font-size:1.25rem;font-weight:800;color:var(--tx);margin-bottom:20px">Kirim Pesan</h2>

          <form method="POST" action="kontak.php">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
              <div class="form-group">
                <label class="form-label">Nama Lengkap <span style="color:#e53e3e">*</span></label>
                <input type="text" name="nama" class="form-control" placeholder="Nama Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Email <span style="color:#e53e3e">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="email@domain.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
              <div class="form-group">
                <label class="form-label">No. Telepon</label>
                <input type="tel" name="telepon" class="form-control" placeholder="+62 8xx-xxxx-xxxx" value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Subjek <span style="color:#e53e3e">*</span></label>
                <input type="text" name="subjek" class="form-control" placeholder="Topik pesan Anda" value="<?= htmlspecialchars($_POST['subjek'] ?? $subjekDefault) ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Pesan <span style="color:#e53e3e">*</span></label>
              <textarea name="pesan" class="form-control" placeholder="Tulis pesan Anda di sini (minimal 20 karakter)..." required><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="form-btn">
              <i class="bi bi-send-fill"></i> Kirim Pesan
            </button>
          </form>
        </div>
      </div>

      <!-- Info Kontak -->
      <div>
        <div class="sb-card" style="margin-bottom:16px">
          <div class="sb-card-hd"><i class="bi bi-geo-alt-fill"></i> Informasi Kontak</div>
          <div class="contact-info-item">
            <div class="ci-ico"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="ci-body"><strong>Alamat Redaksi</strong><span>Jl. Jurnalis Merdeka No. 10,<br>Jakarta Pusat, DKI Jakarta 10110</span></div>
          </div>
          <div class="contact-info-item">
            <div class="ci-ico"><i class="bi bi-envelope-fill"></i></div>
            <div class="ci-body"><strong>Email Umum</strong><span>halo@liynews.id</span></div>
          </div>
          <div class="contact-info-item">
            <div class="ci-ico"><i class="bi bi-people-fill"></i></div>
            <div class="ci-body"><strong>Email Redaksi</strong><span>redaksi@liynews.id</span></div>
          </div>
          <div class="contact-info-item">
            <div class="ci-ico"><i class="bi bi-megaphone-fill"></i></div>
            <div class="ci-body"><strong>Email Iklan</strong><span>iklan@liynews.id</span></div>
          </div>
          <div class="contact-info-item">
            <div class="ci-ico"><i class="bi bi-telephone-fill"></i></div>
            <div class="ci-body"><strong>Telepon</strong><span>+62 21 1234 5678</span></div>
          </div>
          <div class="contact-info-item" style="margin-bottom:0">
            <div class="ci-ico"><i class="bi bi-clock-fill"></i></div>
            <div class="ci-body"><strong>Jam Operasional</strong><span>Senin – Jumat<br>09.00 – 17.00 WIB</span></div>
          </div>
        </div>

        <div class="sb-card">
          <div class="sb-card-hd"><i class="bi bi-share-fill"></i> Media Sosial</div>
          <?php $sosmed=[
            ['bi-facebook','Facebook','fb.com/liynews','https://facebook.com'],
            ['bi-twitter-x','Twitter/X','@liynews','https://twitter.com'],
            ['bi-instagram','Instagram','@liynews.id','https://instagram.com'],
            ['bi-youtube','YouTube','LiyNews Official','https://youtube.com'],
          ]; foreach($sosmed as $s): ?>
          <a href="<?= $s[3] ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:6px;background:var(--alt);margin-bottom:7px;font-size:.8rem;color:var(--tx2);text-decoration:none;transition:.14s" onmouseover="this.style.background='var(--blue-soft)';this.style.color='var(--blue)'" onmouseout="this.style.background='var(--alt)';this.style.color='var(--tx2)'">
            <i class="bi <?= $s[0] ?>" style="color:var(--blue);font-size:.9rem;width:14px;text-align:center"></i>
            <div><strong style="display:block;font-size:.78rem"><?= $s[1] ?></strong><span style="font-size:.7rem;color:var(--muted)"><?= $s[2] ?></span></div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php liy_footer($halfKats); ?>