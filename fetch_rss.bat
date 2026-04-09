<?php
/**
 * fetch_rss.php
 * Auto-fetch berita dari RSS Feed portal berita Indonesia
 * + Auto-hapus berita yang lebih dari 2 hari
 *
 * Letakkan file ini di: /portal_berita/admin/fetch_rss.php
 *
 * Cara pakai:
 * 1. Jalankan manual: buka di browser (login sebagai admin) atau via CLI
 * 2. Otomatis via cron (setiap 30 menit):
 *    * /30 * * * * php /path/to/fetch_rss.php >> /tmp/rss.log 2>&1
 */

session_start();
define('BASE_PATH', dirname(__DIR__));
include BASE_PATH . '/config/koneksi.php';

// ── Keamanan: hanya admin yang bisa akses via browser
$isCLI = php_sapi_name() === 'cli';
if (!$isCLI) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        die('Akses ditolak. Hanya admin.');
    }
}

// ══════════════════════════════════════════
//  KONFIGURASI RSS FEED & MAPPING KATEGORI
// ══════════════════════════════════════════

$RSS_FEEDS = [
    // ── Detik.com
    ['url' => 'https://rss.detik.com/index.php/detikcom',               'kategori_nama' => 'Nasional'],
    ['url' => 'https://rss.detik.com/index.php/detikfinance',           'kategori_nama' => 'Bisnis'],
    ['url' => 'https://rss.detik.com/index.php/detiksport',             'kategori_nama' => 'Olahraga'],
    ['url' => 'https://rss.detik.com/index.php/detikhealth',            'kategori_nama' => 'Kesehatan'],
    ['url' => 'https://rss.detik.com/index.php/detikfood',              'kategori_nama' => 'Kuliner'],
    ['url' => 'https://rss.detik.com/index.php/detikoto',               'kategori_nama' => 'Otomotif'],
    ['url' => 'https://rss.detik.com/index.php/detikinet',              'kategori_nama' => 'Teknologi'],
    ['url' => 'https://rss.detik.com/index.php/detiktravel',            'kategori_nama' => 'Travel'],
    ['url' => 'https://rss.detik.com/index.php/detikentertainment',     'kategori_nama' => 'Hiburan'],
    ['url' => 'https://rss.detik.com/index.php/detikNews',              'kategori_nama' => 'Nasional'],
    ['url' => 'https://rss.detik.com/index.php/detikHot',               'kategori_nama' => 'Hiburan'],
    ['url' => 'https://rss.detik.com/index.php/wolipop',                'kategori_nama' => 'Gaya Hidup'],

    // ── CNN Indonesia
    ['url' => 'https://www.cnnindonesia.com/nasional/rss',              'kategori_nama' => 'Politik'],
    ['url' => 'https://www.cnnindonesia.com/ekonomi/rss',               'kategori_nama' => 'Bisnis'],
    ['url' => 'https://www.cnnindonesia.com/olahraga/rss',              'kategori_nama' => 'Olahraga'],
    ['url' => 'https://www.cnnindonesia.com/teknologi/rss',             'kategori_nama' => 'Teknologi'],
    ['url' => 'https://www.cnnindonesia.com/hiburan/rss',               'kategori_nama' => 'Hiburan'],
    ['url' => 'https://www.cnnindonesia.com/gaya-hidup/rss',            'kategori_nama' => 'Gaya Hidup'],
    ['url' => 'https://www.cnnindonesia.com/internasional/rss',         'kategori_nama' => 'Internasional'],

    // ── Kompas.com
    ['url' => 'https://rss.kompas.com/asset/html/keadaan.xml',         'kategori_nama' => 'Politik'],
    ['url' => 'https://indeks.kompas.com/rss/xml/18',                  'kategori_nama' => 'Olahraga'],
    ['url' => 'https://indeks.kompas.com/rss/xml/11',                  'kategori_nama' => 'Bisnis'],
    ['url' => 'https://indeks.kompas.com/rss/xml/8',                   'kategori_nama' => 'Teknologi'],
    ['url' => 'https://indeks.kompas.com/rss/xml/6',                   'kategori_nama' => 'Hiburan'],
    ['url' => 'https://indeks.kompas.com/rss/xml/9',                   'kategori_nama' => 'Internasional'],
    ['url' => 'https://indeks.kompas.com/rss/xml/7',                   'kategori_nama' => 'Nasional'],

    // ── Republika
    ['url' => 'https://www.republika.co.id/rss/berita',                'kategori_nama' => 'Nasional'],
    ['url' => 'https://www.republika.co.id/rss/olahraga',              'kategori_nama' => 'Olahraga'],
    ['url' => 'https://www.republika.co.id/rss/ekonomi',               'kategori_nama' => 'Bisnis'],
    ['url' => 'https://www.republika.co.id/rss/teknologi',             'kategori_nama' => 'Teknologi'],
    ['url' => 'https://www.republika.co.id/rss/internasional',         'kategori_nama' => 'Internasional'],
    ['url' => 'https://www.republika.co.id/rss/khazanah',              'kategori_nama' => 'Religi'],
    ['url' => 'https://www.republika.co.id/rss/gaya-hidup',            'kategori_nama' => 'Gaya Hidup'],

    // ── Antara News
    ['url' => 'https://www.antaranews.com/rss/terkini.xml',            'kategori_nama' => 'Nasional'],
    ['url' => 'https://www.antaranews.com/rss/ekonomi.xml',            'kategori_nama' => 'Bisnis'],
    ['url' => 'https://www.antaranews.com/rss/olahraga.xml',           'kategori_nama' => 'Olahraga'],
    ['url' => 'https://www.antaranews.com/rss/teknologi.xml',          'kategori_nama' => 'Teknologi'],
    ['url' => 'https://www.antaranews.com/rss/internasional.xml',      'kategori_nama' => 'Internasional'],
    ['url' => 'https://www.antaranews.com/rss/hiburan.xml',            'kategori_nama' => 'Hiburan'],

    // ── Tribun News
    ['url' => 'https://www.tribunnews.com/rss',                        'kategori_nama' => 'Nasional'],
    ['url' => 'https://www.tribunnews.com/rss/nasional',               'kategori_nama' => 'Nasional'],
    ['url' => 'https://www.tribunnews.com/rss/bisnis',                 'kategori_nama' => 'Bisnis'],
    ['url' => 'https://www.tribunnews.com/rss/sport',                  'kategori_nama' => 'Olahraga'],
    ['url' => 'https://www.tribunnews.com/rss/seleb',                  'kategori_nama' => 'Hiburan'],
    ['url' => 'https://www.tribunnews.com/rss/techno',                 'kategori_nama' => 'Teknologi'],
    ['url' => 'https://www.tribunnews.com/rss/travel',                 'kategori_nama' => 'Travel'],

    // ── Liputan6
    ['url' => 'https://www.liputan6.com/rss',                          'kategori_nama' => 'Nasional'],
    ['url' => 'https://www.liputan6.com/rss/bisnis',                   'kategori_nama' => 'Bisnis'],
    ['url' => 'https://www.liputan6.com/rss/bola',                     'kategori_nama' => 'Olahraga'],
    ['url' => 'https://www.liputan6.com/rss/tekno',                    'kategori_nama' => 'Teknologi'],
    ['url' => 'https://www.liputan6.com/rss/showbiz',                  'kategori_nama' => 'Hiburan'],
    ['url' => 'https://www.liputan6.com/rss/health',                   'kategori_nama' => 'Kesehatan'],
    ['url' => 'https://www.liputan6.com/rss/otomotif',                 'kategori_nama' => 'Otomotif'],

    // ── Okezone
    ['url' => 'https://sindikasi.okezone.com/index.php/rss/1/RSS2.0', 'kategori_nama' => 'Nasional'],
    ['url' => 'https://sindikasi.okezone.com/index.php/rss/2/RSS2.0', 'kategori_nama' => 'Internasional'],
    ['url' => 'https://sindikasi.okezone.com/index.php/rss/3/RSS2.0', 'kategori_nama' => 'Bisnis'],
    ['url' => 'https://sindikasi.okezone.com/index.php/rss/4/RSS2.0', 'kategori_nama' => 'Olahraga'],
    ['url' => 'https://sindikasi.okezone.com/index.php/rss/7/RSS2.0', 'kategori_nama' => 'Hiburan'],
    ['url' => 'https://sindikasi.okezone.com/index.php/rss/6/RSS2.0', 'kategori_nama' => 'Teknologi'],
    ['url' => 'https://sindikasi.okezone.com/index.php/rss/5/RSS2.0', 'kategori_nama' => 'Otomotif'],
];

// ══════════════════════════════════════════
//  KONFIGURASI UMUM
// ══════════════════════════════════════════
define('MAX_PER_FEED',      50);   // Maksimal artikel per feed
define('FETCH_TIMEOUT',     15);   // Timeout request (detik)
define('DELETE_OLDER_DAYS',  2);   // Hapus berita lebih dari N hari

// ══════════════════════════════════════════
//  HELPER FUNCTIONS
// ══════════════════════════════════════════

function log_msg($msg) {
    $ts = date('Y-m-d H:i:s');
    echo "[$ts] $msg\n";
    flush();
}

function fetch_url($url) {
    $ctx = stream_context_create(['http' => [
        'timeout'         => FETCH_TIMEOUT,
        'user_agent'      => 'Mozilla/5.0 (compatible; PortalBeritaBot/1.0)',
        'follow_location' => true,
        'max_redirects'   => 5,
    ]]);
    $data = @file_get_contents($url, false, $ctx);
    return $data ?: null;
}

function clean_html($str) {
    $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $str = strip_tags($str);
    $str = preg_replace('/\s+/', ' ', $str);
    return trim($str);
}

function extract_image($item) {
    // Coba dari media:content
    $media = $item->children('media', true);
    if (isset($media->content)) {
        $attrs = $media->content->attributes();
        if (!empty($attrs['url'])) return (string)$attrs['url'];
    }

    // Coba dari enclosure
    foreach ($item->enclosure as $enc) {
        $attrs = $enc->attributes();
        if (!empty($attrs['url']) && strpos((string)$attrs['type'], 'image') !== false) {
            return (string)$attrs['url'];
        }
    }

    // Coba dari description img tag
    $desc = (string)($item->description ?? '');
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $desc, $m)) {
        return $m[1];
    }

    // Coba dari content:encoded
    $content = $item->children('content', true);
    if (isset($content->encoded)) {
        $enc = (string)$content->encoded;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $enc, $m)) {
            return $m[1];
        }
    }

    return null;
}

function get_or_create_kategori($koneksi, $nama) {
    $nama_esc = mysqli_real_escape_string($koneksi, $nama);
    $r = mysqli_query($koneksi, "SELECT id_kategori FROM kategori WHERE nama_kategori='$nama_esc' LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) {
        return (int)mysqli_fetch_assoc($r)['id_kategori'];
    }
    mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES ('$nama_esc')");
    return (int)mysqli_insert_id($koneksi);
}

function artikel_exists($koneksi, $judul) {
    $j = mysqli_real_escape_string($koneksi, $judul);
    $r = mysqli_query($koneksi, "SELECT id_artikel FROM artikel WHERE judul='$j' LIMIT 1");
    return $r && mysqli_num_rows($r) > 0;
}

function slug_from_title($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '-', trim($slug));
    $slug = preg_replace('/-+/', '-', $slug);
    return substr($slug, 0, 100);
}

function has_column($koneksi, $table, $col) {
    $r = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $r && mysqli_num_rows($r) > 0;
}

// ══════════════════════════════════════════
//  STEP 1: HAPUS BERITA LEBIH DARI 2 HARI
// ══════════════════════════════════════════

function auto_delete_old($koneksi) {
    $days    = DELETE_OLDER_DAYS;
    $cutoff  = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // Deteksi nama kolom tanggal yang dipakai
    $tgl_col = 'tgl_posting';
    foreach (['tgl_posting', 'created_at', 'tanggal', 'publish_date'] as $col) {
        if (has_column($koneksi, 'artikel', $col)) {
            $tgl_col = $col;
            break;
        }
    }

    // Hanya hapus artikel dari RSS Bot, bukan tulisan manual admin/penulis
    $hasPenulis  = has_column($koneksi, 'artikel', 'penulis');
    $hasAuthor   = has_column($koneksi, 'artikel', 'author');
    $hasStatus   = has_column($koneksi, 'artikel', 'status');

    $where = "`$tgl_col` < '$cutoff'";

    // Filter hanya artikel RSS Bot
    if ($hasPenulis) {
        $where .= " AND penulis = 'RSS Bot'";
    } elseif ($hasAuthor) {
        $where .= " AND author = 'RSS Bot'";
    }

    // Ambil dulu berapa yang akan dihapus
    $count_res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM artikel WHERE $where");
    $total_del = $count_res ? (int)mysqli_fetch_assoc($count_res)['total'] : 0;

    if ($total_del === 0) {
        log_msg("  Tidak ada berita lama yang perlu dihapus.");
        return 0;
    }

    $del = mysqli_query($koneksi, "DELETE FROM artikel WHERE $where");
    if ($del) {
        $affected = mysqli_affected_rows($koneksi);
        log_msg("  ✓ Berhasil menghapus $affected berita (lebih dari $days hari lalu).");
        return $affected;
    } else {
        log_msg("  ✗ Gagal hapus: " . mysqli_error($koneksi));
        return 0;
    }
}

// ══════════════════════════════════════════
//  MAIN PROCESS
// ══════════════════════════════════════════

$hasSlug      = has_column($koneksi, 'artikel', 'slug');
$hasStatus    = has_column($koneksi, 'artikel', 'status');
$hasPenulis   = has_column($koneksi, 'artikel', 'penulis');
$hasAuthor    = has_column($koneksi, 'artikel', 'author');
$hasThumbnail = has_column($koneksi, 'artikel', 'thumbnail');
$hasGambar    = has_column($koneksi, 'artikel', 'gambar');

$totalInserted = 0;
$totalSkipped  = 0;
$totalError    = 0;
$totalDeleted  = 0;

if (!$isCLI) {
    header('Content-Type: text/plain; charset=utf-8');
    ob_start();
}

log_msg("╔══════════════════════════════════════╗");
log_msg("║       RSS FETCH — PORTAL BERITA      ║");
log_msg("╚══════════════════════════════════════╝");
log_msg("Total feed  : " . count($RSS_FEEDS));
log_msg("Max per feed: " . MAX_PER_FEED);
log_msg("Hapus > " . DELETE_OLDER_DAYS . " hari: YA (hanya artikel RSS Bot)");
log_msg("");

// ── 1. Hapus berita lama dulu sebelum fetch baru
log_msg("─── HAPUS BERITA LAMA ───────────────────");
$totalDeleted = auto_delete_old($koneksi);
log_msg("");

// ── 2. Fetch & insert berita baru
log_msg("─── FETCH RSS FEEDS ─────────────────────");

foreach ($RSS_FEEDS as $feed) {
    $feedUrl = $feed['url'];
    $katNama = $feed['kategori_nama'];

    log_msg("Fetching [$katNama]: $feedUrl");

    $xml_str = fetch_url($feedUrl);
    if (!$xml_str) {
        log_msg("  ✗ Gagal fetch.");
        $totalError++;
        continue;
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xml_str);
    libxml_clear_errors();

    if (!$xml) {
        log_msg("  ✗ Gagal parse XML.");
        $totalError++;
        continue;
    }

    // Support RSS 2.0 dan Atom
    $items = $xml->channel->item ?? $xml->entry ?? [];
    $count = 0;

    foreach ($items as $item) {
        if ($count >= MAX_PER_FEED) break;

        // Judul
        $judul = clean_html((string)($item->title ?? ''));
        if (empty($judul)) continue;

        // Skip duplikat
        if (artikel_exists($koneksi, $judul)) {
            $totalSkipped++;
            continue;
        }

        // Isi
        $isi = '';
        $content = $item->children('content', true);
        if (isset($content->encoded)) {
            $isi = (string)$content->encoded;
        } elseif (!empty($item->description)) {
            $isi = (string)$item->description;
        }
        if (empty($isi)) $isi = $judul;

        // Tanggal
        $tgl_raw = (string)($item->pubDate ?? $item->published ?? $item->updated ?? '');
        $tgl     = $tgl_raw ? date('Y-m-d H:i:s', strtotime($tgl_raw)) : date('Y-m-d H:i:s');

        // Gambar
        $thumbnail_url = extract_image($item);

        // Kategori
        $kategori_id = get_or_create_kategori($koneksi, $katNama);

        // Escape
        $judul_esc = mysqli_real_escape_string($koneksi, $judul);
        $isi_esc   = mysqli_real_escape_string($koneksi, $isi);
        $tgl_esc   = mysqli_real_escape_string($koneksi, $tgl);
        $thumb_esc = $thumbnail_url ? mysqli_real_escape_string($koneksi, $thumbnail_url) : '';
        $slug_esc  = mysqli_real_escape_string($koneksi, slug_from_title($judul));

        // Build INSERT dinamis
        $cols = "judul, isi, kategori_id, tgl_posting";
        $vals = "'$judul_esc', '$isi_esc', $kategori_id, '$tgl_esc'";

        if ($hasSlug)   { $cols .= ", slug";   $vals .= ", '$slug_esc'"; }
        if ($hasStatus) { $cols .= ", status";  $vals .= ", 'publish'"; }

        if ($hasThumbnail && $thumb_esc)      { $cols .= ", thumbnail"; $vals .= ", '$thumb_esc'"; }
        elseif ($hasGambar && $thumb_esc)     { $cols .= ", gambar";    $vals .= ", '$thumb_esc'"; }

        if ($hasPenulis)      { $cols .= ", penulis"; $vals .= ", 'RSS Bot'"; }
        elseif ($hasAuthor)   { $cols .= ", author";  $vals .= ", 'RSS Bot'"; }

        $sql = "INSERT INTO artikel ($cols) VALUES ($vals)";
        $ok  = mysqli_query($koneksi, $sql);

        if ($ok) {
            log_msg("  ✓ $judul");
            $totalInserted++;
            $count++;
        } else {
            log_msg("  ✗ DB Error: " . mysqli_error($koneksi));
            $totalError++;
        }
    }

    log_msg("  → $count artikel ditambahkan dari feed ini.");
}

// ── 3. Ringkasan
log_msg("");
log_msg("╔══════════════════════════════════════╗");
log_msg("║              RINGKASAN               ║");
log_msg("╠══════════════════════════════════════╣");
log_msg("║ ✓ Berhasil ditambah  : " . str_pad($totalInserted, 6) . "          ║");
log_msg("║ 🗑 Dihapus (>2 hari) : " . str_pad($totalDeleted,  6) . "          ║");
log_msg("║ - Dilewati (duplikat): " . str_pad($totalSkipped,  6) . "          ║");
log_msg("║ ✗ Error              : " . str_pad($totalError,    6) . "          ║");
log_msg("╚══════════════════════════════════════╝");

if (!$isCLI) {
    $output = ob_get_clean();
    echo '<pre style="font-family:monospace;font-size:13px;background:#111;color:#0f0;padding:20px;border-radius:8px;">';
    echo htmlspecialchars($output);
    echo '</pre>';
}