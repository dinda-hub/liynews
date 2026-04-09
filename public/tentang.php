<?php
require_once '_base.php';
liy_head('Tentang Kami', 'Mengenal LiyNews — portal berita terpercaya yang hadir untuk seluruh masyarakat Indonesia.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-info-circle-fill"></i> Tentang Kami</div>
    <h1>Berita Terpercaya<br>untuk Indonesia</h1>
    <p>LiyNews hadir sebagai portal berita digital yang berkomitmen menyajikan informasi akurat, berimbang, dan bertanggung jawab untuk seluruh masyarakat Indonesia.</p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Tentang Kami</span>
    </div>
    <div class="fp-grid">

      <!-- Konten Utama -->
      <div class="fp-prose">
        <h2>Siapa Kami?</h2>
        <p>LiyNews adalah portal berita daring Indonesia yang didirikan dengan semangat jurnalisme berkualitas dan independen. Kami percaya bahwa masyarakat berhak mendapatkan informasi yang akurat, adil, dan tidak memihak siapapun kecuali kebenaran.</p>
        <p>Dengan tim jurnalis berpengalaman yang tersebar di berbagai penjuru Indonesia, LiyNews hadir 24 jam sehari, 7 hari seminggu untuk memastikan Anda tidak melewatkan berita penting yang terjadi di tanah air maupun mancanegara.</p>

        <div class="fp-divider"></div>

        <h2>Visi Kami</h2>
        <p>Menjadi portal berita digital terpercaya dan terdepan di Indonesia yang memberikan kontribusi nyata bagi kemajuan bangsa melalui jurnalisme yang bertanggung jawab, inovatif, dan berpihak pada kepentingan publik.</p>

        <h2>Misi Kami</h2>
        <ul>
          <li>Menyajikan berita yang akurat, berimbang, dan telah melalui proses verifikasi ketat.</li>
          <li>Mengedukasi masyarakat dengan konten informatif dan berkualitas tinggi.</li>
          <li>Membangun ruang diskusi publik yang sehat dan konstruktif.</li>
          <li>Menjaga independensi editorial dari pengaruh kepentingan politik maupun bisnis.</li>
          <li>Memanfaatkan teknologi terkini untuk pengalaman membaca yang optimal.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2>Komitmen Editorial</h2>
        <p>Seluruh konten yang diterbitkan LiyNews melewati proses editorial yang ketat. Setiap laporan dikonfirmasi dari minimal dua sumber independen sebelum dipublikasikan. Kami berkomitmen untuk segera melakukan koreksi apabila terdapat kesalahan dalam pemberitaan kami.</p>
        <p>LiyNews terdaftar dan patuh pada Pedoman Pemberitaan Media Siber yang ditetapkan Dewan Pers Indonesia, serta menjunjung tinggi Kode Etik Jurnalistik dalam setiap proses peliputan dan penulisan berita.</p>

        <div class="fp-divider"></div>

        <h2>Sejarah Singkat</h2>
        <p>LiyNews berdiri pada tahun 2020 di tengah kebutuhan masyarakat akan informasi digital yang cepat, akurat, dan mudah diakses. Berawal dari tim kecil yang berdedikasi, kini LiyNews telah berkembang menjadi salah satu portal berita yang dipercaya jutaan pembaca di seluruh Indonesia.</p>

        <div class="fp-divider"></div>

        <h2>Hubungi Kami</h2>
        <p>Untuk pertanyaan umum, pengiriman rilis pers, atau informasi lainnya, silakan hubungi kami melalui halaman <a href="kontak.php">Kontak</a>. Tim kami siap membantu Anda dengan sepenuh hati.</p>
      </div>

      <!-- Sidebar -->
      <div>
        <div class="sb-card">
          <div class="sb-card-hd"><i class="bi bi-bar-chart-fill"></i> LiyNews dalam Angka</div>
          <div class="stat-grid">
            <div class="stat-item"><div class="stat-num">500K+</div><div class="stat-lbl">Pembaca/bulan</div></div>
            <div class="stat-item"><div class="stat-num">50+</div><div class="stat-lbl">Jurnalis</div></div>
            <div class="stat-item"><div class="stat-num">10K+</div><div class="stat-lbl">Artikel</div></div>
            <div class="stat-item"><div class="stat-num">2020</div><div class="stat-lbl">Berdiri sejak</div></div>
          </div>
        </div>

        <div class="sb-card">
          <div class="sb-card-hd"><i class="bi bi-shield-check"></i> Nilai-Nilai Kami</div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-patch-check"></i></div><div class="val-t"><strong>Akurasi</strong><span>Fakta selalu diverifikasi sebelum diterbitkan</span></div></div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-balance-scale"></i></div><div class="val-t"><strong>Independensi</strong><span>Bebas dari pengaruh politik dan korporat</span></div></div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-eye"></i></div><div class="val-t"><strong>Transparansi</strong><span>Terbuka dalam proses dan sumber berita</span></div></div>
          <div class="val-item"><div class="val-ico"><i class="bi bi-people"></i></div><div class="val-t"><strong>Pelayanan Publik</strong><span>Mengutamakan kepentingan masyarakat luas</span></div></div>
        </div>

        <div class="sb-card">
          <div class="sb-card-hd"><i class="bi bi-link-45deg"></i> Tautan Terkait</div>
          <div class="quick-links">
            <a href="redaksi.php"><i class="bi bi-people-fill"></i> Tim Redaksi</a>
            <a href="karier.php"><i class="bi bi-briefcase-fill"></i> Karier</a>
            <a href="kontak.php"><i class="bi bi-envelope-fill"></i> Kontak</a>
            <a href="iklan.php"><i class="bi bi-megaphone-fill"></i> Pasang Iklan</a>
            <a href="pedoman.php"><i class="bi bi-file-earmark-text-fill"></i> Pedoman Media Siber</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php liy_footer($halfKats); ?>