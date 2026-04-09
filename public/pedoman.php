<?php
require_once '_base.php';
liy_head('Pedoman Media Siber', 'Pedoman Pemberitaan Media Siber LiyNews sesuai ketentuan Dewan Pers Indonesia.');
liy_header($kats, $isLogin, $userNama, $userRole, $userInit, $dashLink);

$lastUpdated = '1 Januari 2025';
$sections = [
  '1. Definisi',
  '2. Verifikasi & Keberimbangan Berita',
  '3. Perlindungan Narasumber',
  '4. Informasi Iklan dan Konten Berbayar',
  '5. Privasi dan Perlindungan Anak',
  '6. Hak Jawab dan Koreksi',
  '7. Pencabutan Berita',
  '8. Larangan Konten',
  '9. Penggunaan Kecerdasan Buatan (AI)',
  '10. Pelanggaran dan Sanksi',
];
?>

<section class="fp-hero">
  <div class="W">
    <div class="eyebrow"><i class="bi bi-newspaper"></i> Legal</div>
    <h1>Pedoman Media Siber</h1>
    <p>Terakhir diperbarui: <strong style="color:rgba(255,255,255,.8)"><?= $lastUpdated ?></strong></p>
  </div>
</section>

<div class="fp-body">
  <div class="W">
    <div class="breadcrumb">
      <a href="index.php">Beranda</a>
      <i class="bi bi-chevron-right"></i>
      <span>Pedoman Media Siber</span>
    </div>

    <div class="fp-grid">
      <div class="fp-prose">

        <blockquote>
          <p>LiyNews berkomitmen penuh terhadap Pedoman Pemberitaan Media Siber yang diterbitkan oleh Dewan Pers Republik Indonesia. Pedoman ini merupakan panduan operasional jurnalisme LiyNews sebagai wujud tanggung jawab kami kepada publik.</p>
        </blockquote>

        <div class="fp-divider"></div>

        <h2 id="s1">1. Definisi</h2>
        <p>Dalam pedoman ini yang dimaksud dengan:</p>
        <ul>
          <li><strong>Media Siber</strong> adalah segala bentuk media yang menggunakan wahana internet dan melaksanakan kegiatan jurnalistik, serta memenuhi persyaratan Undang-Undang Pers.</li>
          <li><strong>Jurnalisme Daring</strong> adalah kegiatan jurnalistik yang dilakukan dan/atau disebarluaskan melalui internet.</li>
          <li><strong>Konten Pengguna (User Generated Content/UGC)</strong> adalah segala konten yang dibuat dan dipublikasikan oleh pengguna, bukan oleh redaksi media siber.</li>
          <li><strong>Ralat/Koreksi</strong> adalah perbaikan atas kesalahan fakta dalam berita yang telah dipublikasikan.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s2">2. Verifikasi &amp; Keberimbangan Berita</h2>
        <p>LiyNews menerapkan prinsip-prinsip dasar jurnalistik dalam setiap proses peliputan dan pemberitaan:</p>
        <ul>
          <li>Setiap berita wajib diverifikasi dari minimal dua sumber yang independen dan dapat dipertanggungjawabkan sebelum dipublikasikan.</li>
          <li>Dalam pemberitaan yang memuat tuduhan, tudingan, atau permasalahan hukum terhadap seseorang, pihak yang bersangkutan wajib diberikan kesempatan untuk memberikan pernyataan (right of reply).</li>
          <li>Pemberitaan bersifat berimbang (cover both sides) dan tidak berpihak kepada kepentingan tertentu.</li>
          <li>Berita yang belum terverifikasi sepenuhnya dapat dipublikasikan dengan keterangan "sedang berkembang" atau "masih dalam konfirmasi", namun wajib segera diperbarui setelah verifikasi selesai.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s3">3. Perlindungan Narasumber</h2>
        <ul>
          <li>Identitas narasumber yang meminta kerahasiaan wajib dilindungi oleh redaksi.</li>
          <li>Wartawan tidak diperkenankan membocorkan identitas narasumber anonim kepada siapapun, termasuk kepada rekan wartawan atau pimpinan redaksi, kecuali dalam kondisi yang mengancam keselamatan jiwa.</li>
          <li>Narasumber yang rentan (anak-anak, korban kekerasan, orang dengan gangguan jiwa) diperlakukan dengan sensitivitas dan perlindungan tambahan.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s4">4. Informasi Iklan dan Konten Berbayar</h2>
        <ul>
          <li>Seluruh konten berbayar (advertorial, konten sponsor, iklan native) wajib diberi label yang jelas dan mudah dibaca, seperti "Iklan", "Advertorial", "Konten Sponsor", atau "Sponsored Content".</li>
          <li>Konten iklan tidak boleh menyerupai tampilan berita reguler LiyNews sehingga menyesatkan pembaca.</li>
          <li>Departemen iklan dan departemen redaksi LiyNews beroperasi secara terpisah (firewall) untuk menjaga independensi editorial.</li>
          <li>LiyNews tidak menerima imbalan apapun untuk memuat berita dengan nada tertentu dalam konten editorial reguler.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s5">5. Privasi dan Perlindungan Anak</h2>
        <ul>
          <li>Identitas anak di bawah umur yang menjadi korban kejahatan tidak boleh dipublikasikan, termasuk nama, foto, dan informasi lain yang dapat mengidentifikasi korban.</li>
          <li>Identitas pelaku kejahatan yang masih berusia anak tidak dipublikasikan.</li>
          <li>Konten yang berkaitan dengan korban pelecehan seksual wajib dijaga kerahasiaannya sesuai ketentuan hukum yang berlaku.</li>
          <li>Foto atau video korban kecelakaan atau bencana yang bersifat eksplisit tidak dipublikasikan tanpa alasan jurnalistik yang kuat.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s6">6. Hak Jawab dan Koreksi</h2>
        <ul>
          <li>Setiap orang atau pihak yang merasa dirugikan oleh pemberitaan LiyNews berhak mengajukan hak jawab atau koreksi.</li>
          <li>Hak jawab dan koreksi disampaikan melalui email <a href="mailto:redaksi@liynews.id">redaksi@liynews.id</a> atau formulir kontak kami.</li>
          <li>Redaksi wajib merespons pengajuan hak jawab dalam 2×24 jam kerja.</li>
          <li>Koreksi dipublikasikan secara jelas dan proporsional, disertai penjelasan mengenai kesalahan yang dikoreksi.</li>
          <li>Hak jawab dipublikasikan di tempat yang setara dengan berita yang dipermasalahkan.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s7">7. Pencabutan Berita</h2>
        <p>LiyNews sangat selektif dalam mencabut berita yang telah dipublikasikan. Pencabutan hanya dilakukan apabila:</p>
        <ul>
          <li>Berita terbukti mengandung informasi yang sepenuhnya tidak benar dan berpotensi merugikan pihak tertentu.</li>
          <li>Terdapat perintah pengadilan yang sah untuk mencabut berita.</li>
          <li>Berita berisi data pribadi sensitif yang dipublikasikan tanpa persetujuan dan melanggar hukum privasi.</li>
        </ul>
        <p>Dalam hal pencabutan berita, LiyNews menerbitkan penjelasan kepada publik mengenai alasan pencabutan tersebut.</p>

        <div class="fp-divider"></div>

        <h2 id="s8">8. Larangan Konten</h2>
        <p>LiyNews tidak akan mempublikasikan konten yang:</p>
        <ul>
          <li>Mengandung ujaran kebencian (hate speech) berdasarkan SARA.</li>
          <li>Menghasut, memfitnah, atau melakukan pencemaran nama baik.</li>
          <li>Merupakan propaganda atau disinformasi yang dapat memecah belah masyarakat.</li>
          <li>Bertentangan dengan nilai-nilai kemanusiaan dan norma sosial yang berlaku di Indonesia.</li>
          <li>Mengandung pornografi atau konten tidak pantas lainnya.</li>
          <li>Melanggar ketentuan UU ITE dan peraturan perundang-undangan lainnya.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s9">9. Penggunaan Kecerdasan Buatan (AI)</h2>
        <p>Dalam era perkembangan teknologi AI, LiyNews menetapkan kebijakan penggunaan AI dalam proses jurnalistik:</p>
        <ul>
          <li>AI boleh digunakan sebagai alat bantu penulisan, pencarian data, dan riset, namun tidak sebagai pengganti penilaian editorial manusia.</li>
          <li>Konten yang sepenuhnya dihasilkan oleh AI wajib diberi keterangan yang jelas kepada pembaca.</li>
          <li>Verifikasi fakta tetap menjadi tanggung jawab wartawan dan editor manusia.</li>
          <li>Penggunaan AI untuk menghasilkan gambar atau video yang menyesatkan (deepfake) dilarang keras.</li>
        </ul>

        <div class="fp-divider"></div>

        <h2 id="s10">10. Pelanggaran dan Sanksi</h2>
        <p>Anggota redaksi yang terbukti melanggar pedoman ini akan dikenakan sanksi internal sesuai dengan tingkat pelanggaran, mulai dari teguran tertulis, skorsing, hingga pemutusan hubungan kerja.</p>
        <p>Pengaduan terkait pelanggaran Pedoman Pemberitaan Media Siber ini dapat disampaikan kepada:</p>
        <ul>
          <li><strong>Redaksi LiyNews:</strong> <a href="mailto:redaksi@liynews.id">redaksi@liynews.id</a></li>
          <li><strong>Dewan Pers Indonesia:</strong> <a href="https://dewanpers.or.id" target="_blank" rel="noopener">dewanpers.or.id</a></li>
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
          <div class="sb-card-hd"><i class="bi bi-info-circle-fill"></i> Tentang Dewan Pers</div>
          <p style="font-size:.78rem;color:var(--muted);line-height:1.65;margin-bottom:12px">LiyNews terdaftar sebagai media siber yang mengikuti pedoman Dewan Pers Republik Indonesia.</p>
          <a href="https://dewanpers.or.id" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:7px;padding:9px 11px;background:var(--alt);border-radius:6px;font-size:.78rem;color:var(--blue);font-weight:600;text-decoration:none"><i class="bi bi-box-arrow-up-right"></i> dewanpers.or.id</a>
        </div>

        <div class="sb-card" style="margin-top:16px">
          <div class="sb-card-hd"><i class="bi bi-link-45deg"></i> Dokumen Legal</div>
          <div class="quick-links">
            <a href="privasi.php"><i class="bi bi-shield-lock-fill"></i> Kebijakan Privasi</a>
            <a href="syarat.php"><i class="bi bi-file-earmark-check-fill"></i> Syarat &amp; Ketentuan</a>
            <a href="pedoman.php" style="background:var(--blue-soft);color:var(--blue)"><i class="bi bi-newspaper"></i> Pedoman Media Siber</a>
          </div>
        </div>

        <div class="sb-card" style="margin-top:16px">
          <div class="sb-card-hd"><i class="bi bi-envelope-fill"></i> Pengaduan</div>
          <p style="font-size:.78rem;color:var(--muted);line-height:1.65;margin-bottom:10px">Untuk pengaduan pelanggaran pedoman, hubungi redaksi kami:</p>
          <a href="mailto:redaksi@liynews.id" style="display:flex;align-items:center;gap:7px;padding:9px 11px;background:var(--alt);border-radius:6px;font-size:.78rem;color:var(--blue);font-weight:600;text-decoration:none"><i class="bi bi-envelope"></i>redaksi@liynews.id</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php liy_footer($halfKats); ?>