<?php
require_once '_base.php';
liy_head('Syarat & Ketentuan', 'Syarat dan Ketentuan Penggunaan LiyNews — pedoman penggunaan platform berita kami.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);

$lastUpdated = '1 Januari 2025';
$sections = [
  '1. Penerimaan Syarat',
  '2. Penggunaan Platform',
  '3. Akun Pengguna',
  '4. Konten dan Hak Kekayaan Intelektual',
  '5. Konten yang Dilarang',
  '6. Tautan ke Situs Pihak Ketiga',
  '7. Penafian dan Batasan Tanggung Jawab',
  '8. Perubahan Layanan',
  '9. Hukum yang Berlaku',
  '10. Hubungi Kami',
];
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-file-earmark-check-fill"></i> Legal</div>
    <h1>Syarat &amp; Ketentuan</h1>
    <p>Terakhir diperbarui: <strong style="color:rgba(255,255,255,.8)"><?= $lastUpdated ?></strong></p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Syarat &amp; Ketentuan</span>
    </div>

    <div class="fp-grid">
      <div class="fp-prose">

        <blockquote><p>Dengan mengakses atau menggunakan platform LiyNews, Anda menyatakan telah membaca, memahami, dan menyetujui Syarat dan Ketentuan ini. Jika Anda tidak menyetujui syarat ini, harap hentikan penggunaan platform kami.</p></blockquote>

        <div class="fp-divider"></div>

        <h2 id="s1">1. Penerimaan Syarat</h2>
        <p>Syarat dan Ketentuan ini ("Syarat") merupakan perjanjian yang mengikat antara Anda ("Pengguna") dan LiyNews ("Perusahaan"). Syarat ini berlaku untuk seluruh penggunaan layanan, fitur, konten, dan produk yang tersedia melalui platform LiyNews, baik melalui website, aplikasi mobile, maupun saluran distribusi lainnya.</p>
        <p>Pengguna yang berusia di bawah 13 tahun tidak diperbolehkan menggunakan platform kami. Pengguna berusia 13–17 tahun wajib mendapatkan persetujuan orang tua atau wali sebelum mendaftar akun.</p>

        <div class="fp-divider"></div>

        <h2 id="s2">2. Penggunaan Platform</h2>
        <p>Anda diperbolehkan menggunakan platform LiyNews untuk tujuan yang sah dan sesuai dengan Syarat ini. Anda setuju untuk:</p>
        <ul>
          <li>Menggunakan platform hanya untuk keperluan pribadi, non-komersial kecuali dengan izin tertulis dari kami.</li>
          <li>Tidak melakukan tindakan yang dapat mengganggu atau merusak infrastruktur platform.</li>
          <li>Tidak mencoba mengakses area platform yang tidak diotorisasi untuk Anda.</li>
          <li>Tidak menggunakan bot, scraper, atau alat otomatis tanpa izin tertulis dari kami.</li>
          <li>Mematuhi seluruh peraturan perundang-undangan yang berlaku di Republik Indonesia.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s3">3. Akun Pengguna</h2>
        <p>Untuk mengakses fitur tertentu, Anda perlu membuat akun. Anda bertanggung jawab atas:</p>
        <ul>
          <li>Kerahasiaan kata sandi dan keamanan akun Anda.</li>
          <li>Seluruh aktivitas yang terjadi di bawah akun Anda.</li>
          <li>Memberikan informasi yang akurat dan terkini saat mendaftar.</li>
          <li>Segera memberitahu kami jika mendeteksi penggunaan akun yang tidak sah.</li>
        </ul>
        <p>Kami berhak menangguhkan atau menghapus akun yang melanggar Syarat ini tanpa pemberitahuan sebelumnya.</p>

        <div class="fp-divider"></div>

        <h2 id="s4">4. Konten dan Hak Kekayaan Intelektual</h2>
        <p>Seluruh konten yang dipublikasikan di LiyNews — termasuk artikel, foto, video, grafis, logo, dan elemen desain — adalah milik LiyNews atau mitra konten kami dan dilindungi oleh hak cipta, merek dagang, serta hak kekayaan intelektual lainnya.</p>
        <p>Anda diizinkan untuk:</p>
        <ul>
          <li>Membaca dan mengakses konten untuk penggunaan pribadi.</li>
          <li>Berbagi tautan artikel LiyNews di media sosial dengan atribusi yang jelas.</li>
          <li>Mengutip sebagian kecil konten untuk tujuan ulasan atau komentar dengan menyebutkan sumber.</li>
        </ul>
        <p>Anda <strong>tidak diizinkan</strong> untuk menyalin, mendistribusikan, memodifikasi, atau mempublikasikan ulang konten LiyNews secara keseluruhan tanpa izin tertulis dari kami.</p>

        <div class="fp-divider"></div>

        <h2 id="s5">5. Konten yang Dilarang</h2>
        <p>Pengguna yang berkontribusi konten (komentar, kiriman pengguna) dilarang memposting konten yang:</p>
        <ul>
          <li>Mengandung ujaran kebencian, diskriminasi, atau pelecehan berdasarkan suku, agama, ras, atau golongan.</li>
          <li>Bersifat fitnah, pencemaran nama baik, atau melanggar privasi orang lain.</li>
          <li>Mengandung informasi palsu (hoaks), disinformasi, atau misinformasi.</li>
          <li>Melanggar hak kekayaan intelektual pihak ketiga.</li>
          <li>Mengandung materi pornografi, eksploitasi anak, atau konten tidak pantas.</li>
          <li>Berisi spam, iklan tidak sah, atau konten promosi yang menyesatkan.</li>
        </ul>
        <p>Kami berhak menghapus konten yang melanggar ketentuan ini dan melaporkan ke pihak berwenang jika diperlukan.</p>

        <div class="fp-divider"></div>

        <h2 id="s6">6. Tautan ke Situs Pihak Ketiga</h2>
        <p>Platform kami mungkin mengandung tautan ke situs web pihak ketiga. Tautan tersebut disediakan untuk kenyamanan Anda dan tidak berarti kami mendukung atau bertanggung jawab atas konten, kebijakan privasi, atau praktik situs pihak ketiga tersebut.</p>

        <div class="fp-divider"></div>

        <h2 id="s7">7. Penafian dan Batasan Tanggung Jawab</h2>
        <p>Platform LiyNews disediakan "sebagaimana adanya" tanpa jaminan tersurat maupun tersirat. Kami tidak menjamin bahwa layanan akan selalu tersedia tanpa gangguan atau bebas dari kesalahan.</p>
        <p>Sejauh diizinkan hukum yang berlaku, LiyNews tidak bertanggung jawab atas kerugian langsung, tidak langsung, insidental, atau konsekuensial yang timbul dari penggunaan atau ketidakmampuan menggunakan platform kami.</p>

        <div class="fp-divider"></div>

        <h2 id="s8">8. Perubahan Layanan</h2>
        <p>Kami berhak mengubah, menangguhkan, atau menghentikan layanan atau fitur tertentu kapan saja dengan atau tanpa pemberitahuan. Kami tidak bertanggung jawab atas kerugian yang mungkin timbul akibat perubahan tersebut.</p>

        <div class="fp-divider"></div>

        <h2 id="s9">9. Hukum yang Berlaku</h2>
        <p>Syarat dan Ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum Republik Indonesia. Setiap sengketa yang timbul sehubungan dengan Syarat ini akan diselesaikan melalui musyawarah, dan apabila tidak tercapai kesepakatan, akan diselesaikan melalui Pengadilan Negeri Jakarta Pusat.</p>

        <div class="fp-divider"></div>

        <h2 id="s10">10. Hubungi Kami</h2>
        <p>Pertanyaan mengenai Syarat dan Ketentuan ini dapat diajukan ke:</p>
        <ul>
          <li><strong>Email:</strong> <a href="mailto:legal@liynews.id">legal@liynews.id</a></li>
          <li><strong>Formulir kontak:</strong> <a href="kontak.php">liynews.id/kontak</a></li>
        </ul>

      </div>

      <!-- TOC Sidebar -->
      <div>
        <div class="legal-toc">
          <div class="legal-toc-hd"><i class="bi bi-list-ul"></i> Daftar Isi</div>
          <ol>
            <?php foreach ($sections as $i => $s): ?>
            <li><a href="#s<?= $i+1 ?>"><?= htmlspecialchars($s) ?></a></li>
            <?php endforeach; ?>
          </ol>
        </div>

        <div class="sb-card" style="margin-top:16px">
          <div class="sb-card-hd"><i class="bi bi-link-45deg"></i> Dokumen Legal</div>
          <div class="quick-links">
            <a href="privasi.php"><i class="bi bi-shield-lock-fill"></i> Kebijakan Privasi</a>
            <a href="syarat.php" style="background:var(--blue-soft);color:var(--blue)"><i class="bi bi-file-earmark-check-fill"></i> Syarat &amp; Ketentuan</a>
            <a href="pedoman.php"><i class="bi bi-newspaper"></i> Pedoman Media Siber</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php liy_footer($halfKats); ?>