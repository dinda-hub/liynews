<?php
require_once '_base.php';
liy_head('Iklan', 'Pasang iklan di LiyNews dan jangkau jutaan pembaca setia Indonesia setiap bulannya.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);

$pakets = [
  ['nama'=>'Starter','icon'=>'bi-rocket','harga'=>'Rp 500.000','periode'=>'/minggu','warna'=>'#4a7fc1',
   'fitur'=>['Banner 300×250 px (sidebar)','Tayang 7 hari penuh','100K+ impresi estimasi','Laporan performa dasar']],
  ['nama'=>'Profesional','icon'=>'bi-star-fill','harga'=>'Rp 1.500.000','periode'=>'/minggu','warna'=>'#1a56db','popular'=>true,
   'fitur'=>['Banner 728×90 px (leaderboard)','Tayang 7 hari penuh','350K+ impresi estimasi','Laporan performa lengkap','Dukungan desain banner']],
  ['nama'=>'Enterprise','icon'=>'bi-building','harga'=>'Nego','periode'=>'','warna'=>'#0d3265',
   'fitur'=>['Semua format iklan premium','Jangka waktu fleksibel','Jaminan tayang prioritas','Manajer akun dedicated','Laporan real-time & analytics']],
];
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-megaphone-fill"></i> Pasang Iklan</div>
    <h1>Jangkau Jutaan Pembaca<br>Bersama LiyNews</h1>
    <p>Hadirkan brand Anda di hadapan jutaan pembaca aktif LiyNews setiap bulannya. Solusi iklan digital yang efektif dan terukur.</p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Iklan</span>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:38px">
      <?php $stats=[['500K+','Pembaca/bulan'],['2.1M+','Pageviews/bulan'],['65%','Pengguna mobile'],['18 mnt','Rata-rata waktu baca']];
      foreach($stats as $s): ?>
      <div style="background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:18px;text-align:center;box-shadow:var(--sh2)">
        <div style="font-family:var(--fd);font-size:1.8rem;font-weight:900;color:var(--blue);line-height:1;margin-bottom:4px"><?= $s[0] ?></div>
        <div style="font-size:.72rem;color:var(--muted)"><?= $s[1] ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Paket -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
      <div style="font-family:var(--fc);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);display:flex;align-items:center;gap:5px;white-space:nowrap"><span style="width:2px;height:10px;background:var(--blue);border-radius:2px;display:block"></span> Paket Iklan</div>
      <div style="flex:1;height:1px;background:var(--bdr)"></div>
    </div>

    <div class="ads-pkg">
      <?php foreach ($pakets as $p): ?>
      <div class="ads-card" style="<?= !empty($p['popular']) ? 'border-color:var(--blue);box-shadow:0 0 0 2px var(--blue-soft)' : '' ?>">
        <?php if (!empty($p['popular'])): ?>
        <div style="background:var(--blue);text-align:center;padding:4px;font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#fff">⭐ Paling Populer</div>
        <?php endif; ?>
        <div class="ads-card-top" style="background:linear-gradient(135deg,<?= $p['warna'] ?> 0%,<?= $p['warna'] ?>cc 100%)">
          <div class="ads-card-ico"><i class="bi <?= $p['icon'] ?>"></i></div>
          <div class="ads-card-name"><?= $p['nama'] ?></div>
          <div class="ads-card-price"><strong><?= $p['harga'] ?></strong><?= $p['periode'] ?></div>
        </div>
        <div class="ads-card-body">
          <?php foreach ($p['fitur'] as $f): ?>
          <div class="ads-feature"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($f) ?></div>
          <?php endforeach; ?>
          <a href="kontak.php?paket=<?= urlencode($p['nama']) ?>" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:14px;height:36px;background:var(--blue);color:#fff;border-radius:6px;font-size:.8rem;font-weight:600;text-decoration:none;transition:.15s" onmouseover="this.style.background='var(--blue-d)'" onmouseout="this.style.background='var(--blue)'">
            <i class="bi bi-chat-dots"></i> Hubungi Kami
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="fp-divider"></div>

    <!-- Format Iklan -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
      <div style="font-family:var(--fc);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);display:flex;align-items:center;gap:5px;white-space:nowrap"><span style="width:2px;height:10px;background:var(--blue);border-radius:2px;display:block"></span> Format Iklan Tersedia</div>
      <div style="flex:1;height:1px;background:var(--bdr)"></div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:36px">
      <?php $formats=[
        ['bi-image','Banner Display','Ukuran standar: 728×90, 300×250, 320×50 (mobile)'],
        ['bi-file-earmark-text','Artikel Sponsor','Konten editorial berbayar dengan label "Advertorial"'],
        ['bi-play-circle','Video Pre-Roll','Iklan video 15–30 detik sebelum konten video berita'],
        ['bi-phone','Native Mobile','Iklan terintegrasi di feed berita versi mobile'],
        ['bi-envelope-at','Newsletter','Slot iklan di buletin email mingguan LiyNews'],
        ['bi-grid-3x3-gap','Homepage Takeover','Penguasaan seluruh unit iklan halaman utama'],
      ]; foreach($formats as $f): ?>
      <div style="background:var(--card);border:1px solid var(--bdr);border-radius:8px;padding:14px;display:flex;align-items:flex-start;gap:10px">
        <div style="width:32px;height:32px;background:var(--blue-soft);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="bi <?= $f[0] ?>" style="color:var(--blue);font-size:.82rem"></i></div>
        <div><strong style="font-size:.82rem;color:var(--tx);display:block;margin-bottom:2px"><?= $f[1] ?></strong><span style="font-size:.72rem;color:var(--muted);line-height:1.5"><?= $f[2] ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="fp-prose fp-full">
      <h2>Hubungi Tim Iklan Kami</h2>
      <p>Untuk diskusi lebih lanjut mengenai paket iklan, penawaran khusus, atau pertanyaan lainnya, silakan hubungi tim iklan kami:</p>
      <ul>
        <li><strong>Email:</strong> iklan@liynews.id</li>
        <li><strong>WhatsApp:</strong> +62 812-3456-7890</li>
        <li><strong>Jam kerja:</strong> Senin–Jumat, 09.00–17.00 WIB</li>
      </ul>
      <p>Tim kami siap memberikan solusi terbaik yang sesuai dengan kebutuhan dan anggaran iklan Anda.</p>
    </div>

  </div>
</div>

<?php liy_footer($halfKats); ?>