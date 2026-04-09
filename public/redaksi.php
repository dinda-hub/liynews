<?php
require_once '_base.php';
liy_head('Redaksi', 'Tim redaksi LiyNews — jurnalis berpengalaman yang berkomitmen menyajikan berita terpercaya.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);

$redaksi = [
  ['nama'=>'Ahmad Fauzi',       'jabatan'=>'Pemimpin Redaksi',     'inisial'=>'AF', 'desc'=>'Lebih dari 15 tahun pengalaman di dunia jurnalisme nasional. Berpengalaman di berbagai media cetak dan digital.'],
  ['nama'=>'Siti Rahayu',       'jabatan'=>'Wakil Pemimpin Redaksi','inisial'=>'SR', 'desc'=>'Jurnalis senior dengan keahlian di bidang liputan politik dan pemerintahan selama 12 tahun.'],
  ['nama'=>'Budi Santoso',      'jabatan'=>'Redaktur Pelaksana',   'inisial'=>'BS', 'desc'=>'Mengelola alur editorial dan memastikan standar kualitas di setiap edisi LiyNews.'],
  ['nama'=>'Dewi Lestari',      'jabatan'=>'Redaktur Ekonomi',     'inisial'=>'DL', 'desc'=>'Spesialis liputan ekonomi, bisnis, dan pasar modal dengan latar belakang sarjana ekonomi.'],
  ['nama'=>'Eko Prasetyo',      'jabatan'=>'Redaktur Teknologi',   'inisial'=>'EP', 'desc'=>'Mengikuti perkembangan teknologi dan startup Indonesia sejak lebih dari 8 tahun lalu.'],
  ['nama'=>'Fitri Handayani',   'jabatan'=>'Redaktur Gaya Hidup',  'inisial'=>'FH', 'desc'=>'Meliput tren gaya hidup, kuliner, wisata, dan kesehatan untuk segmen pembaca modern.'],
  ['nama'=>'Galih Nugroho',     'jabatan'=>'Redaktur Olahraga',    'inisial'=>'GN', 'desc'=>'Penggemar olahraga sekaligus jurnalis berpengalaman dalam liputan sepakbola dan bulu tangkis.'],
  ['nama'=>'Hana Putri',        'jabatan'=>'Fotografer Senior',    'inisial'=>'HP', 'desc'=>'Mendokumentasikan momen bersejarah dengan kamera selama lebih dari 10 tahun karier jurnalistik.'],
  ['nama'=>'Irfan Maulana',     'jabatan'=>'Editor Digital',       'inisial'=>'IM', 'desc'=>'Bertanggung jawab atas strategi konten digital, SEO, dan pengalaman pengguna di platform LiyNews.'],
  ['nama'=>'Junita Sari',       'jabatan'=>'Jurnalis Investigasi', 'inisial'=>'JS', 'desc'=>'Mengkhususkan diri dalam liputan investigatif dan mendalam isu-isu sosial dan hukum.'],
];
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-people-fill"></i> Tim Redaksi</div>
    <h1>Orang-Orang di Balik<br>LiyNews</h1>
    <p>Tim jurnalis dan editor berpengalaman kami bekerja keras setiap hari untuk menghadirkan berita terpercaya bagi jutaan pembaca Indonesia.</p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Redaksi</span>
    </div>

    <div style="margin-bottom:14px">
      <div class="sec" style="display:flex;align-items:center;gap:10px;margin:0 0 20px">
        <div style="font-family:var(--fc);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);display:flex;align-items:center;gap:5px;white-space:nowrap"><span style="width:2px;height:10px;background:var(--blue);border-radius:2px;display:block"></span> Jajaran Redaksi</div>
        <div style="flex:1;height:1px;background:var(--bdr)"></div>
      </div>
      <div class="redaksi-grid">
        <?php foreach ($redaksi as $r): ?>
        <div class="redaksi-card">
          <div class="redaksi-avatar"><?= $r['inisial'] ?></div>
          <div class="redaksi-name"><?= htmlspecialchars($r['nama']) ?></div>
          <div class="redaksi-role"><?= htmlspecialchars($r['jabatan']) ?></div>
          <div class="redaksi-desc"><?= htmlspecialchars($r['desc']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="fp-divider"></div>

    <div class="fp-prose fp-full">
      <h2>Standar Editorial</h2>
      <p>Seluruh anggota redaksi LiyNews terikat pada Kode Etik Jurnalistik yang ditetapkan Dewan Pers Indonesia. Kami berkomitmen untuk selalu memverifikasi fakta, berimbang dalam pemberitaan, dan memberikan hak jawab kepada semua pihak yang diberitakan.</p>

      <h2>Kebijakan Koreksi</h2>
      <p>Apabila terdapat kesalahan dalam pemberitaan kami, LiyNews berkomitmen untuk segera melakukan koreksi yang jelas dan transparan. Koreksi ditampilkan secara terbuka di artikel yang bersangkutan dengan penjelasan yang memadai.</p>

      <h2>Pengaduan Berita</h2>
      <p>Jika Anda menemukan ketidakakuratan dalam pemberitaan LiyNews atau ingin mengajukan hak jawab, silakan hubungi kami melalui halaman <a href="kontak.php">Kontak</a> atau kirim email ke <strong>redaksi@liynews.id</strong>. Setiap aduan akan ditanggapi dalam 2×24 jam kerja.</p>
    </div>

  </div>
</div>

<?php liy_footer($halfKats); ?>