<?php
// cek_koneksi.php — jalankan sekali untuk debug, hapus setelah selesai
header('Content-Type: text/plain; charset=utf-8');

$urls = [
    'CNN Indonesia'  => 'https://www.cnnindonesia.com/ekonomi/rss',
    'Kompas Ekonomi' => 'https://ekonomi.kompas.com/rss/xml/',
    'Tempo Bisnis'   => 'https://bisnis.tempo.co/rss/berita',
    'Tribun News'    => 'https://www.tribunnews.com/rss/kuliner',
    'Google (test)'  => 'https://www.google.com',
];

foreach ($urls as $nama => $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    $status = $err ? "GAGAL — $err" : "HTTP $code — " . strlen($data) . " bytes";
    echo "$nama: $status\n";
}