<?php
require_once '_base.php';
liy_head('Karier', 'Bergabunglah bersama LiyNews — kami mencari talenta terbaik untuk tim jurnalisme digital kami.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);

$jobs = [
  [
    'title'   => 'Jurnalis Liputan Ekonomi',
    'type'    => 'Full-time',
    'lokasi'  => 'Jakarta',
    'exp'     => 'Min. 2 tahun',
    'desc'    => 'Meliput berita ekonomi, bisnis, pasar modal, dan investasi. Mampu menulis dengan cepat dan akurat serta memiliki jaringan narasumber di sektor ekonomi.',
    'req'     => ['Sarjana Ekonomi/Jurnalistik/Komunikasi', 'Pengalaman min. 2 tahun di media ekonomi', 'Memahami pasar modal dan analisis bisnis', 'Mampu memenuhi tenggat waktu ketat'],
  ],
  [
    'title'   => 'Video Journalist / Content Creator',
    'type'    => 'Full-time',
    'lokasi'  => 'Jakarta',
    'exp'     => 'Min. 1 tahun',
    'desc'    => 'Membuat konten video berita untuk platform digital dan media sosial. Mahir mengoperasikan kamera, drone, dan software editing video.',
    'req'     => ['Pengalaman di bidang videografi/jurnalisme video', 'Mahir menggunakan Adobe Premiere/Final Cut Pro', 'Kreatif dan berorientasi pada konten digital', 'Bersedia bekerja dinamis dan mobile'],
  ],
  [
    'title'   => 'Redaktur/Editor Berita',
    'type'    => 'Full-time',
    'lokasi'  => 'Jakarta',
    'exp'     => 'Min. 5 tahun',
    'desc'    => 'Mengedit dan memvalidasi tulisan jurnalis sebelum dipublikasikan. Bertanggung jawab atas kualitas konten dan akurasi informasi di desk yang dipimpin.',
    'req'     => ['Sarjana Jurnalistik/Komunikasi/Sastra Indonesia', 'Pengalaman min. 5 tahun di media nasional', 'Menguasai Kode Etik Jurnalistik', 'Kemampuan editing dan verifikasi fakta yang kuat'],
  ],
  [
    'title'   => 'Social Media Strategist',
    'type'    => 'Full-time',
    'lokasi'  => 'Remote / Jakarta',
    'exp'     => 'Min. 2 tahun',
    'desc'    => 'Mengelola dan mengembangkan strategi media sosial LiyNews di seluruh platform. Menganalisis data engagement dan mengoptimalkan distribusi konten.',
    'req'     => ['Berpengalaman mengelola media sosial brand/media besar', 'Menguasai tools analitik (Meta Business Suite, dll)', 'Kreatif dalam pembuatan konten visual dan copywriting', 'Memahami tren konten terkini'],
  ],
  [
    'title'   => 'Web Developer (PHP/Laravel)',
    'type'    => 'Full-time',
    'lokasi'  => 'Jakarta / Remote',
    'exp'     => 'Min. 3 tahun',
    'desc'    => 'Mengembangkan dan memelihara platform berita LiyNews. Memastikan performa, keamanan, dan skalabilitas sistem berjalan optimal.',
    'req'     => ['Menguasai PHP (Laravel/CodeIgniter) dan MySQL', 'Berpengalaman dengan sistem CMS dan manajemen konten', 'Memahami keamanan web dan optimasi performa', 'Familiar dengan Git dan deployment'],
  ],
];
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-briefcase-fill"></i> Karier</div>
    <h1>Bergabunglah dengan<br>Tim LiyNews</h1>
    <p>Kami mencari talenta terbaik yang bersemangat dan berkomitmen untuk memajukan jurnalisme digital Indonesia.</p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Karier</span>
    </div>

    <div class="fp-grid">
      <div>
        <!-- Mengapa LiyNews -->
        <div class="fp-prose" style="margin-bottom:32px">
          <h2>Mengapa Bergabung dengan LiyNews?</h2>
          <p>LiyNews menawarkan lingkungan kerja yang dinamis, kolaboratif, dan mendukung pertumbuhan karier Anda. Kami percaya bahwa talenta terbaik layak mendapatkan yang terbaik.</p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:36px">
          <?php $perks=[
            ['bi-graph-up-arrow','Karier Berkembang','Jalur karier jelas dengan evaluasi rutin dan kesempatan promosi'],
            ['bi-award','Kompensasi Kompetitif','Gaji dan tunjangan kompetitif sesuai standar industri media nasional'],
            ['bi-laptop','Fleksibel & Remote','Beberapa posisi mendukung kerja dari mana saja secara remote'],
            ['bi-mortarboard','Pelatihan & Beasiswa','Program pelatihan, workshop, dan beasiswa pengembangan kompetensi'],
          ]; foreach($perks as $p): ?>
          <div style="background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:16px;text-align:center">
            <div style="width:40px;height:40px;background:var(--blue-soft);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px"><i class="bi <?= $p[0] ?>" style="color:var(--blue);font-size:.95rem"></i></div>
            <div style="font-family:var(--fd);font-size:.9rem;font-weight:800;color:var(--tx);margin-bottom:4px"><?= $p[1] ?></div>
            <div style="font-size:.72rem;color:var(--muted);line-height:1.55"><?= $p[2] ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Lowongan -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
          <div style="font-family:var(--fc);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);display:flex;align-items:center;gap:5px;white-space:nowrap"><span style="width:2px;height:10px;background:var(--blue);border-radius:2px;display:block"></span> Lowongan Tersedia</div>
          <div style="flex:1;height:1px;background:var(--bdr)"></div>
          <span style="font-size:.72rem;color:var(--muted)"><?= count($jobs) ?> posisi terbuka</span>
        </div>

        <?php foreach ($jobs as $job): ?>
        <div class="job-card">
          <div class="job-top">
            <div class="job-title"><?= htmlspecialchars($job['title']) ?></div>
            <div class="job-badge"><i class="bi bi-circle-fill" style="font-size:.4rem"></i><?= $job['type'] ?></div>
          </div>
          <div class="job-meta">
            <span><i class="bi bi-geo-alt-fill"></i><?= $job['lokasi'] ?></span>
            <span><i class="bi bi-briefcase"></i><?= $job['exp'] ?></span>
          </div>
          <div class="job-desc"><?= htmlspecialchars($job['desc']) ?></div>
          <ul style="padding-left:18px;margin-bottom:14px">
            <?php foreach ($job['req'] as $r): ?>
            <li style="font-size:.78rem;color:var(--muted);margin-bottom:4px;line-height:1.65"><?= htmlspecialchars($r) ?></li>
            <?php endforeach; ?>
          </ul>
          <a href="kontak.php?lamar=<?= urlencode($job['title']) ?>" class="job-btn">
            <i class="bi bi-send-fill"></i> Lamar Sekarang
          </a>
        </div>
        <?php endforeach; ?>

      </div>

      <!-- Sidebar -->
      <div>
        <div class="sb-card">
          <div class="sb-card-hd"><i class="bi bi-send-fill"></i> Cara Melamar</div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-1-circle"></i></div><div class="val-t"><strong>Pilih Posisi</strong><span>Pilih posisi yang sesuai dengan keahlian Anda</span></div></div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-2-circle"></i></div><div class="val-t"><strong>Kirim Lamaran</strong><span>Kirim CV dan portofolio ke karier@liynews.id</span></div></div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-3-circle"></i></div><div class="val-t"><strong>Tes & Wawancara</strong><span>Ikuti tahapan seleksi dan wawancara</span></div></div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-4-circle"></i></div><div class="val-t"><strong>Bergabung!</strong><span>Selamat datang di keluarga LiyNews</span></div></div>
        </div>

        <div class="sb-card">
          <div class="sb-card-hd"><i class="bi bi-envelope-fill"></i> Kirim Lamaran</div>
          <p style="font-size:.8rem;color:var(--muted);margin-bottom:10px;line-height:1.65">Tidak menemukan posisi yang sesuai? Kirim CV terbaikmu ke:</p>
          <a href="mailto:karier@liynews.id" style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--alt);border-radius:6px;font-size:.82rem;color:var(--blue);font-weight:600;text-decoration:none"><i class="bi bi-envelope"></i>karier@liynews.id</a>
        </div>

        <div class="sb-card">
          <div class="sb-card-hd"><i class="bi bi-link-45deg"></i> Halaman Lain</div>
          <div class="quick-links">
            <a href="tentang.php"><i class="bi bi-info-circle-fill"></i> Tentang LiyNews</a>
            <a href="redaksi.php"><i class="bi bi-people-fill"></i> Tim Redaksi</a>
            <a href="kontak.php"><i class="bi bi-envelope-fill"></i> Kontak Kami</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php liy_footer($halfKats); ?>