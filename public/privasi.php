<?php
require_once '_base.php';
liy_head('Kebijakan Privasi', 'Kebijakan Privasi LiyNews — informasi tentang pengumpulan, penggunaan, dan perlindungan data pengguna.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);

$lastUpdated = '1 Januari 2025';
$sections = [
  '1. Informasi yang Kami Kumpulkan',
  '2. Cara Kami Menggunakan Informasi',
  '3. Berbagi Informasi dengan Pihak Ketiga',
  '4. Cookie dan Teknologi Pelacakan',
  '5. Keamanan Data',
  '6. Hak-Hak Pengguna',
  '7. Perubahan Kebijakan Privasi',
  '8. Hubungi Kami',
];
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-shield-lock-fill"></i> Legal</div>
    <h1>Kebijakan Privasi</h1>
    <p>Terakhir diperbarui: <strong style="color:rgba(255,255,255,.8)"><?= $lastUpdated ?></strong></p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Kebijakan Privasi</span>
    </div>

    <div class="fp-grid">
      <div class="fp-prose">

        <p>LiyNews ("kami", "milik kami") menghormati privasi Anda dan berkomitmen untuk melindungi informasi pribadi yang Anda berikan kepada kami. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, mengungkapkan, dan melindungi informasi Anda ketika Anda mengunjungi situs web kami.</p>

        <div class="fp-divider"></div>

        <h2 id="s1">1. Informasi yang Kami Kumpulkan</h2>
        <h3>a. Informasi yang Anda Berikan</h3>
        <p>Kami dapat mengumpulkan informasi yang Anda berikan secara langsung kepada kami, seperti:</p>
        <ul>
          <li>Nama dan alamat email saat mendaftar akun atau berlangganan newsletter</li>
          <li>Nama pengguna dan kata sandi untuk akun terdaftar</li>
          <li>Informasi profil seperti foto dan bio pengguna</li>
          <li>Pesan yang Anda kirimkan melalui formulir kontak kami</li>
          <li>Komentar atau ulasan yang Anda posting di platform kami</li>
        </ul>

        <h3>b. Informasi yang Dikumpulkan Otomatis</h3>
        <p>Ketika Anda mengakses platform kami, kami secara otomatis mengumpulkan informasi tertentu, termasuk:</p>
        <ul>
          <li>Alamat IP dan informasi perangkat (jenis browser, sistem operasi)</li>
          <li>Halaman yang Anda kunjungi dan durasi kunjungan</li>
          <li>URL perujuk (halaman yang mengarahkan Anda ke situs kami)</li>
          <li>Data penggunaan fitur-fitur platform</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s2">2. Cara Kami Menggunakan Informasi</h2>
        <p>Informasi yang kami kumpulkan digunakan untuk:</p>
        <ul>
          <li>Menyediakan, mengoperasikan, dan meningkatkan layanan kami</li>
          <li>Memproses pendaftaran akun dan autentikasi pengguna</li>
          <li>Mengirimkan newsletter dan konten yang Anda pilih berlangganan</li>
          <li>Merespons pertanyaan dan memberikan dukungan pelanggan</li>
          <li>Menganalisis penggunaan platform untuk meningkatkan pengalaman pengguna</li>
          <li>Mendeteksi dan mencegah aktivitas penipuan atau pelanggaran ketentuan</li>
          <li>Memenuhi kewajiban hukum yang berlaku</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s3">3. Berbagi Informasi dengan Pihak Ketiga</h2>
        <p>Kami <strong>tidak menjual</strong> informasi pribadi Anda kepada pihak ketiga. Kami dapat berbagi informasi hanya dalam situasi berikut:</p>
        <ul>
          <li><strong>Penyedia layanan:</strong> Mitra yang membantu operasional platform (hosting, analitik, layanan email) dengan kewajiban kerahasiaan yang ketat.</li>
          <li><strong>Kewajiban hukum:</strong> Apabila diwajibkan oleh peraturan perundang-undangan atau perintah pengadilan yang sah.</li>
          <li><strong>Perlindungan hak:</strong> Untuk melindungi hak, properti, atau keselamatan LiyNews, pengguna kami, atau publik.</li>
          <li><strong>Persetujuan Anda:</strong> Dalam situasi lain dengan persetujuan eksplisit dari Anda.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s4">4. Cookie dan Teknologi Pelacakan</h2>
        <p>Kami menggunakan cookie dan teknologi serupa untuk meningkatkan pengalaman pengguna. Cookie adalah file kecil yang disimpan di perangkat Anda. Jenis cookie yang kami gunakan:</p>
        <ul>
          <li><strong>Cookie esensial:</strong> Diperlukan untuk fungsi dasar platform (sesi login, preferensi tema).</li>
          <li><strong>Cookie analitik:</strong> Membantu kami memahami bagaimana pengguna berinteraksi dengan platform.</li>
          <li><strong>Cookie iklan:</strong> Digunakan untuk menampilkan iklan yang relevan bagi pengguna.</li>
        </ul>
        <p>Anda dapat mengontrol penggunaan cookie melalui pengaturan browser Anda. Namun, menonaktifkan cookie tertentu dapat memengaruhi fungsi platform.</p>

        <div class="fp-divider"></div>

        <h2 id="s5">5. Keamanan Data</h2>
        <p>Kami menerapkan langkah-langkah keamanan teknis dan organisasional yang sesuai untuk melindungi informasi Anda dari akses, pengubahan, pengungkapan, atau penghancuran yang tidak sah. Langkah-langkah ini mencakup enkripsi SSL/TLS, kontrol akses berbasis peran, dan pemantauan keamanan rutin.</p>
        <p>Namun, tidak ada sistem transmisi atau penyimpanan data yang 100% aman. Kami mendorong Anda untuk menggunakan kata sandi yang kuat dan tidak membagikannya kepada siapapun.</p>

        <div class="fp-divider"></div>

        <h2 id="s6">6. Hak-Hak Pengguna</h2>
        <p>Anda memiliki hak-hak berikut terkait data pribadi Anda:</p>
        <ul>
          <li><strong>Akses:</strong> Meminta salinan informasi pribadi yang kami miliki tentang Anda.</li>
          <li><strong>Koreksi:</strong> Meminta koreksi atas informasi yang tidak akurat atau tidak lengkap.</li>
          <li><strong>Penghapusan:</strong> Meminta penghapusan data pribadi Anda (dengan batasan tertentu).</li>
          <li><strong>Portabilitas:</strong> Meminta data Anda dalam format yang dapat dibaca mesin.</li>
          <li><strong>Berhenti berlangganan:</strong> Berhenti berlangganan newsletter kapan saja melalui tautan "unsubscribe" di email.</li>
        </ul>
        <p>Untuk menggunakan hak-hak tersebut, hubungi kami di <a href="mailto:privasi@liynews.id">privasi@liynews.id</a>.</p>

        <div class="fp-divider"></div>

        <h2 id="s7">7. Perubahan Kebijakan Privasi</h2>
        <p>Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Perubahan material akan kami beritahukan melalui pemberitahuan di platform atau email. Tanggal pembaruan terakhir selalu tercantum di bagian atas halaman ini.</p>
        <p>Penggunaan Anda yang berkelanjutan atas layanan kami setelah perubahan berlaku merupakan persetujuan Anda terhadap kebijakan yang diperbarui.</p>

        <div class="fp-divider"></div>

        <h2 id="s8">8. Hubungi Kami</h2>
        <p>Jika Anda memiliki pertanyaan atau kekhawatiran tentang Kebijakan Privasi ini, silakan hubungi kami:</p>
        <ul>
          <li><strong>Email:</strong> <a href="mailto:privasi@liynews.id">privasi@liynews.id</a></li>
          <li><strong>Formulir kontak:</strong> <a href="kontak.php">liynews.id/kontak</a></li>
          <li><strong>Alamat:</strong> Jl. Jurnalis Merdeka No. 10, Jakarta Pusat 10110</li>
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
            <a href="privasi.php" style="background:var(--blue-soft);color:var(--blue)"><i class="bi bi-shield-lock-fill"></i> Kebijakan Privasi</a>
            <a href="syarat.php"><i class="bi bi-file-earmark-check-fill"></i> Syarat &amp; Ketentuan</a>
            <a href="pedoman.php"><i class="bi bi-newspaper"></i> Pedoman Media Siber</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php liy_footer($halfKats); ?>