<?php
/**
 * update_isi_penuh.php
 * Update isi penuh untuk artikel lama yang belum punya konten lengkap
 * Letakkan di: /portal_berita/admin/update_isi_penuh.php
 *
 * Jalankan via CMD:
 *   php update_isi_penuh.php
 * Atau buka di browser (harus login sebagai admin)
 */

set_time_limit(0);
ini_set('memory_limit', '256M');
ignore_user_abort(true);

session_start();
define('BASE_PATH', dirname(__DIR__));
include BASE_PATH . '/config/koneksi.php';

$isCLI = php_sapi_name() === 'cli';
if (!$isCLI) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        die('Akses ditolak. Hanya admin.');
    }
    header('Content-Type: text/plain; charset=utf-8');
    while (ob_get_level()) ob_end_flush();
    ob_implicit_flush(true);
}

define('BATCH_SIZE', 5);   // Proses 5 artikel per detik (jangan terlalu cepat)
define('SLEEP_MS',   500); // Jeda 0.5 detik antar artikel (hindari rate limit)

function log_msg($msg) {
    $ts = date('Y-m-d H:i:s');
    echo "[$ts] $msg\n";
    if (ob_get_level()) ob_flush();
    flush();
}

function scrape_full_content($url) {
    if (empty($url)) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => 'gzip, deflate',
        CURLOPT_HTTPHEADER     => ['Accept-Language: id-ID,id;q=0.9,en;q=0.8'],
    ]);
    $html     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$html || $httpCode >= 400) return null;

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $selectors = [
        '//div[contains(@class,"detail__body-text")]',
        '//div[contains(@class,"detail__body")]',
        '//div[contains(@class,"detail-text")]',
        '//section[contains(@class,"detail_text")]',
        '//div[contains(@class,"read__content")]',
        '//div[contains(@class,"article__content")]',
        '//div[contains(@class,"detail-konten")]',
        '//div[contains(@class,"story-content")]',
        '//div[contains(@class,"txt-article")]',
        '//div[@itemprop="articleBody"]',
        '//article',
        '//div[contains(@class,"article-body")]',
        '//div[contains(@class,"entry-content")]',
        '//div[contains(@class,"post-content")]',
        '//div[contains(@class,"content-detail")]',
    ];

    $content = null;
    foreach ($selectors as $sel) {
        $nodes = $xpath->query($sel);
        if ($nodes && $nodes->length > 0) {
            $node    = $nodes->item(0);
            $content = $dom->saveHTML($node);
            if (strlen(strip_tags($content)) > 200) break;
            $content = null;
        }
    }
    if (!$content) return null;

    $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
    $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is',   '', $content);
    $content = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $content);
    $content = preg_replace('/<div[^>]*class="[^"]*(?:ads|iklan|related|promo|share|social|newsletter)[^"]*"[^>]*>.*?<\/div>/is', '', $content);

    $parsed  = parse_url($url);
    $baseUrl = $parsed['scheme'] . '://' . $parsed['host'];
    $content = preg_replace_callback('/<img([^>]+)src=["\'](?!http)([^"\']+)["\']/', function($m) use ($baseUrl) {
        return '<img' . $m[1] . 'src="' . $baseUrl . '/' . ltrim($m[2], '/') . '"';
    }, $content);

    return strlen(strip_tags($content)) > 200 ? trim($content) : null;
}

// ── Pastikan kolom ada
$colCek = mysqli_query($koneksi, "SHOW COLUMNS FROM artikel LIKE 'isi_penuh'");
if (!$colCek || mysqli_num_rows($colCek) === 0) {
    mysqli_query($koneksi, "ALTER TABLE artikel ADD COLUMN isi_penuh LONGTEXT NULL");
    log_msg("✓ Kolom isi_penuh dibuat otomatis");
}

$colLink = mysqli_query($koneksi, "SHOW COLUMNS FROM artikel LIKE 'url_sumber'");
if (!$colLink || mysqli_num_rows($colLink) === 0) {
    log_msg("✗ Kolom 'url_sumber' tidak ada di tabel artikel — tidak bisa scrape");
    exit;
}

// Ambil parameter dari URL/CLI
// ?limit=100 untuk batasi jumlah artikel yang diproses
// ?offset=0 untuk mulai dari posisi tertentu
$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : (isset($argv[1]) ? (int)$argv[1] : 100);
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : (isset($argv[2]) ? (int)$argv[2] : 0);

// Ambil artikel yang belum punya isi_penuh tapi punya link
$totalQ = mysqli_query($koneksi,
    "SELECT COUNT(*) as total FROM artikel
     WHERE (isi_penuh IS NULL OR isi_penuh = '')
     AND url_sumber IS NOT NULL AND url_sumber != ''"
);
$totalRow   = mysqli_fetch_assoc($totalQ);
$totalBelum = (int)$totalRow['total'];

log_msg("=== UPDATE ISI PENUH DIMULAI ===");
log_msg("Artikel belum punya isi penuh: $totalBelum");
log_msg("Akan diproses: $limit artikel (mulai offset $offset)");
log_msg("Jeda antar artikel: " . SLEEP_MS . "ms");
log_msg("");

$q = mysqli_query($koneksi,
    "SELECT id_artikel, judul, link FROM artikel
     WHERE (isi_penuh IS NULL OR isi_penuh = '')
     AND url_sumber IS NOT NULL AND url_sumber != ''
     ORDER BY tgl_posting DESC
     LIMIT $limit OFFSET $offset"
);

$berhasil = 0;
$gagal    = 0;
$i        = 0;

while ($row = mysqli_fetch_assoc($q)) {
    $i++;
    $id    = $row['id_artikel'];
    $judul = substr($row['judul'], 0, 60);
    $link  = $row['link'];

    log_msg("[$i] Scraping id=$id: $judul");

    $scraped = scrape_full_content($link);
    if ($scraped) {
        $esc = mysqli_real_escape_string($koneksi, $scraped);
        mysqli_query($koneksi, "UPDATE artikel SET isi_penuh='$esc' WHERE id_artikel=$id");
        log_msg("     ✓ Berhasil (" . strlen(strip_tags($scraped)) . " karakter)");
        $berhasil++;
    } else {
        log_msg("     ✗ Gagal scrape");
        $gagal++;
    }

    // Jeda antar request agar tidak kena rate limit
    usleep(SLEEP_MS * 1000);
}

log_msg("");
log_msg("════════════════════════════");
log_msg("=== SELESAI ===");
log_msg("✓ Berhasil: $berhasil artikel");
log_msg("✗ Gagal   : $gagal artikel");
log_msg("Sisa belum diproses: " . ($totalBelum - $offset - $limit) . " artikel");
log_msg("");
if ($totalBelum > $offset + $limit) {
    $nextOffset = $offset + $limit;
    log_msg("Lanjut batch berikutnya:");
    log_msg("  Browser : ?limit=$limit&offset=$nextOffset");
    log_msg("  CMD     : php update_isi_penuh.php $limit $nextOffset");
}
log_msg("════════════════════════════");