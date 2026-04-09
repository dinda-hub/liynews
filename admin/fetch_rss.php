<?php
/**
 * fetch_rss.php — RSS Fetcher CNN Indonesia (Versi Perbaikan)
 * ─────────────────────────────────────────────────────────────
 * Perubahan utama:
 *  1. Fetch hanya artikel BARU berdasarkan pubDate dari feed RSS
 *     → Setiap feed menyimpan "last_fetched_at" ke tabel rss_meta
 *     → Item yang pubDate-nya lebih lama dari artikel terbaru di DB di-skip
 *  2. Duplicate check diprioritaskan ke url_sumber (lebih andal dari judul)
 *  3. Klasifikasi kategori dipisah ke fungsi tersendiri yang lebih bersih
 *  4. Error handling lebih informatif
 *  5. Penghapusan artikel lama tetap dipertahankan dengan logika views
 */

set_time_limit(0);
ini_set('memory_limit', '256M');
ignore_user_abort(true);
session_start();

define('BASE_PATH', dirname(__DIR__));
include BASE_PATH . '/config/koneksi.php';

$isCLI = php_sapi_name() === 'cli';
if (!$isCLI) {
    header('Content-Type: text/plain; charset=utf-8');
    while (ob_get_level()) ob_end_flush();
    ob_implicit_flush(true);
}

// ══════════════════════════════════════════════════════════════
//  KONFIGURASI
// ══════════════════════════════════════════════════════════════
define('MAX_UMUR_HARI',    2);    // Hapus artikel lebih tua dari N hari
define('MIN_VIEWS_AMAN', 200);   // Artikel dengan views >= ini tidak dihapus walau tua
define('MAX_PER_FEED',    10);   // Maksimal artikel baru per feed per run
define('FETCH_TIMEOUT',   20);   // Timeout cURL dalam detik

// ══════════════════════════════════════════════════════════════
//  RSS FEEDS — CNN Indonesia
// ══════════════════════════════════════════════════════════════
$RSS_FEEDS = [
    ['url' => 'https://www.cnnindonesia.com/rss',               'kategori_nama' => 'Berita Utama'],
    ['url' => 'https://www.cnnindonesia.com/nasional/rss',      'kategori_nama' => 'Politik'],
    ['url' => 'https://www.cnnindonesia.com/internasional/rss', 'kategori_nama' => 'Internasional'],
    ['url' => 'https://www.cnnindonesia.com/ekonomi/rss',       'kategori_nama' => 'Bisnis'],
    ['url' => 'https://www.cnnindonesia.com/olahraga/rss',      'kategori_nama' => 'Olahraga'],
    ['url' => 'https://www.cnnindonesia.com/teknologi/rss',     'kategori_nama' => 'Teknologi'],
    ['url' => 'https://www.cnnindonesia.com/otomotif/rss',      'kategori_nama' => 'Otomotif'],
    ['url' => 'https://www.cnnindonesia.com/hiburan/rss',       'kategori_nama' => 'Hiburan'],
    ['url' => 'https://www.cnnindonesia.com/gaya-hidup/rss',    'kategori_nama' => 'Gaya Hidup'],
    ['url' => 'https://www.cnnindonesia.com/edukasi/rss',       'kategori_nama' => 'Pendidikan'],
];

// ══════════════════════════════════════════════════════════════
//  KEYWORD CLASSIFIER — Gaya Hidup → Sub-kategori
//  Urutan penting: lebih spesifik di atas
// ══════════════════════════════════════════════════════════════
$GAYA_HIDUP_CLASSIFIER = [
    'Religi' => [
        'islam', 'muslim', 'quran', 'alquran', 'hadits', 'sunnah',
        'sholat', 'solat', 'puasa', 'ramadan', 'ramadhan', 'lebaran',
        'idul fitri', 'idul adha', 'zakat', 'infak', 'sedekah',
        'haji', 'umrah', 'masjid', 'mushola', 'ustaz', 'ustadz',
        'kyai', 'ulama', 'dakwah', 'khutbah', 'doa', 'ibadah',
        'iman', 'taqwa', 'takwa', 'halal', 'haram', 'muhammadiyah',
        'nahdlatul ulama', 'pesantren', 'madrasah', 'syariah', 'fiqih',
        'tafsir', 'isra miraj', 'maulid nabi', 'agama', 'religi',
        'spiritual', 'rohani', 'pahala', 'surga', 'akhirat',
        'kristen', 'katolik', 'gereja', 'alkitab', 'pendeta', 'pastor',
        'natal', 'paskah', 'hindu', 'budha', 'buddha', 'pura',
        'vihara', 'waisak', 'nyepi', 'galungan', 'konghucu', 'imlek',
    ],
    'Kesehatan' => [
        'sehat', 'kesehatan', 'sakit', 'dokter', 'penyakit', 'rumah sakit',
        'obat', 'vitamin', 'nutrisi', 'diet', 'kalori', 'gizi', 'medis',
        'kanker', 'diabetes', 'jantung', 'covid', 'virus', 'vaksin',
        'mental health', 'kesehatan mental', 'psikologi', 'tidur',
        'stres', 'meditasi', 'yoga', 'kebugaran', 'imun', 'terapi',
        'kolesterol', 'hipertensi', 'obesitas', 'kulit', 'rambut sehat',
        'suplemen', 'herbal', 'apotek', 'klinik', 'puskesmas',
    ],
    'Kuliner' => [
        'kuliner', 'makanan', 'minuman', 'resep', 'masak', 'restoran',
        'cafe', 'kafe', 'menu', 'sajian', 'olahan', 'hidangan',
        'kopi', 'teh', 'jajanan', 'street food', 'gastronomi', 'chef',
        'bumbu', 'rempah', 'dapur', 'food', 'snack', 'dessert', 'kue',
        'nasi', 'mie', 'sate', 'rendang', 'soto', 'bakso', 'gado-gado',
        'pizza', 'burger', 'steak', 'sushi', 'dim sum', 'makan malam',
        'sarapan', 'makan siang', 'buka puasa', 'sahur', 'camilan',
        'minuman segar', 'jus', 'smoothie', 'boba', 'es krim',
    ],
    'Travel' => [
        'wisata', 'travel', 'perjalanan', 'destinasi', 'liburan', 'vakasi',
        'hotel', 'resort', 'villa', 'penginapan', 'pantai', 'gunung',
        'pulau', 'danau', 'air terjun', 'tiket', 'pesawat', 'penerbangan',
        'bandara', 'kapal', 'kereta', 'backpacker', 'tips perjalanan',
        'passport', 'visa', 'itinerary', 'bali', 'lombok', 'jogja',
        'jakarta', 'singapore', 'eropa', 'jepang', 'korea', 'bangkok',
        'museum', 'taman', 'objek wisata', 'tempat wisata', 'tour',
        'tur', 'trip', 'jalan-jalan', 'piknik', 'camping', 'glamping',
        'snorkeling', 'diving', 'hiking', 'trekking', 'budget travel',
    ],
];

// ══════════════════════════════════════════════════════════════
//  KEYWORD CLASSIFIER — Semua Feed → Sub-kategori Baru
// ══════════════════════════════════════════════════════════════
$BERITA_UTAMA_CLASSIFIER = [
    'Cuaca' => [
        'cuaca', 'prakiraan cuaca', 'bmkg', 'hujan', 'banjir', 'banjir bandang',
        'longsor', 'angin', 'angin kencang', 'angin puting beliung', 'topan',
        'badai', 'gempa', 'gempa bumi', 'tsunami', 'gunung berapi', 'erupsi',
        'kekeringan', 'kemarau', 'musim hujan', 'musim kemarau', 'iklim',
        'perubahan iklim', 'el nino', 'la nina', 'suhu', 'kelembaban',
        'kabut', 'asap', 'polusi udara', 'kualitas udara', 'aqi',
        'gelombang panas', 'bencana alam', 'bencana hidrometeorologi',
        'siaga bencana', 'waspada cuaca', 'peringatan dini',
    ],
    'Hukum' => [
        'hukum', 'pengadilan', 'sidang', 'vonis', 'hakim', 'jaksa',
        'terdakwa', 'tersangka', 'polisi', 'kepolisian', 'polda', 'polres',
        'kpk', 'korupsi', 'suap', 'gratifikasi', 'pencucian uang',
        'kejaksaan', 'kejati', 'kejari', 'mahkamah agung', 'mahkamah konstitusi',
        'pengadilan negeri', 'pengadilan tinggi', 'kasasi', 'banding',
        'penjara', 'hukuman', 'pidana', 'perdata', 'gugatan', 'tuntutan',
        'dakwaan', 'pembuktian', 'advokat', 'pengacara', 'kuasa hukum',
        'kriminal', 'kejahatan', 'pencurian', 'pembunuhan', 'narkoba',
        'penangkapan', 'penahanan', 'penyelidikan', 'penyidikan',
        'uu', 'undang-undang', 'peraturan', 'regulasi', 'legislasi',
        'penipuan', 'penggelapan', 'pemerasan', 'pelecehan', 'kekerasan',
    ],
    'Nasional' => [
        'indonesia', 'pemerintah', 'presiden', 'menteri', 'kementerian',
        'dpr', 'dprd', 'mpr', 'dpd', 'dewan perwakilan', 'parlemen',
        'kabinet', 'kota', 'provinsi', 'gubernur', 'wali kota', 'bupati',
        'pemda', 'pemkot', 'pemkab', 'pemprov', 'apbd', 'apbn',
        'infrastruktur', 'pembangunan nasional', 'ibu kota nusantara', 'ikn',
        'pilkada', 'pilpres', 'pemilu', 'kpu', 'bawaslu',
        'jakarta', 'surabaya', 'bandung', 'medan', 'makassar', 'semarang',
        'bencana nasional', 'darurat nasional', 'kebijakan pemerintah',
        'demo', 'demonstrasi', 'unjuk rasa', 'buruh', 'tki', 'tkw',
        'subsidi', 'bansos', 'bantuan sosial', 'program pemerintah',
    ],
    'Ekonomi' => [
        'ekonomi', 'ekonomi makro', 'pertumbuhan ekonomi', 'gdp', 'pdb',
        'inflasi', 'deflasi', 'suku bunga', 'bi rate', 'bank indonesia',
        'rupiah', 'kurs', 'valuta asing', 'dolar', 'euro',
        'ekspor', 'impor', 'neraca perdagangan', 'neraca pembayaran',
        'investasi', 'penanaman modal', 'bkpm', 'fdi',
        'pasar modal', 'bursa', 'ihsg', 'saham', 'obligasi', 'reksa dana',
        'perbankan', 'bank', 'kredit', 'pinjaman', 'cicilan',
        'umkm', 'usaha kecil', 'wirausaha', 'startup', 'unicorn',
        'pajak', 'bea cukai', 'pendapatan negara', 'utang negara',
        'sri mulyani', 'menkeu', 'kemenkeu', 'ojk', 'lps',
        'harga', 'komoditas', 'minyak', 'gas', 'batu bara', 'nikel',
        'pangan', 'beras', 'cabai', 'minyak goreng', 'bbm',
        'lapangan kerja', 'pengangguran', 'phk', 'pesangon',
    ],
    'Sains' => [
        'sains', 'ilmu pengetahuan', 'penelitian', 'riset', 'ilmuwan',
        'teknologi', 'inovasi', 'penemuan', 'terobosan', 'eksperimen',
        'fisika', 'kimia', 'biologi', 'astronomi', 'antariksa', 'luar angkasa',
        'nasa', 'brin', 'lapan', 'badan antariksa', 'roket', 'satelit',
        'planet', 'bintang', 'galaksi', 'gerhana', 'meteor', 'komet',
        'dna', 'gen', 'genetika', 'evolusi', 'fosil', 'dinosaurus',
        'kecerdasan buatan', 'ai', 'artificial intelligence', 'machine learning',
        'robot', 'otomasi', 'digitalisasi', 'komputasi', 'kuantum',
        'energi terbarukan', 'panel surya', 'angin', 'nuklir', 'baterai',
        'lingkungan hidup', 'ekologi', 'spesies', 'satwa', 'hutan',
        'laut', 'kelautan', 'alam', 'konservasi', 'biodiversitas',
        'matematika', 'geologi', 'seismologi', 'vulkanologi',
    ],
];

// Feed yang TIDAK di-reclassify oleh $BERITA_UTAMA_CLASSIFIER
$NO_RECLASSIFY = ['Gaya Hidup', 'Olahraga', 'Otomotif', 'Hiburan', 'Teknologi', 'Internasional', 'Pendidikan'];

// ══════════════════════════════════════════════════════════════
//  FUNGSI LOG
// ══════════════════════════════════════════════════════════════
function log_msg($msg) {
    $ts = date('Y-m-d H:i:s');
    echo "[$ts] $msg\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// ══════════════════════════════════════════════════════════════
//  FUNGSI DB — Schema helpers
// ══════════════════════════════════════════════════════════════
function has_column($koneksi, $table, $col) {
    $r = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $r && mysqli_num_rows($r) > 0;
}

function has_table($koneksi, $table) {
    $r = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
    return $r && mysqli_num_rows($r) > 0;
}

function ensure_column($koneksi, $table, $col, $def) {
    if (!has_column($koneksi, $table, $col)) {
        $ok = mysqli_query($koneksi, "ALTER TABLE `$table` ADD COLUMN $col $def");
        return $ok ? "dibuat" : "GAGAL: " . mysqli_error($koneksi);
    }
    return "sudah ada";
}

/**
 * Buat tabel rss_meta jika belum ada.
 * Tabel ini menyimpan:
 *  - feed_url      : URL feed RSS
 *  - last_pub_date : pubDate item terbaru yang sudah dimasukkan ke DB
 *  - last_run_at   : kapan terakhir feed ini diproses
 *
 * Gunanya: pada run berikutnya, item dengan pubDate <= last_pub_date di-skip.
 */
function ensure_rss_meta_table($koneksi) {
    if (has_table($koneksi, 'rss_meta')) return;
    mysqli_query($koneksi, "
        CREATE TABLE rss_meta (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            feed_url     VARCHAR(500) NOT NULL,
            last_pub_date DATETIME     NULL COMMENT 'pubDate item terbaru yg sudah diinsert',
            last_run_at  DATETIME     NULL COMMENT 'Kapan feed terakhir diproses',
            PRIMARY KEY (id),
            UNIQUE KEY uq_feed_url (feed_url(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * Ambil last_pub_date dari rss_meta untuk satu feed URL.
 * Return: string datetime MySQL atau null jika belum pernah diproses.
 */
function get_last_pub_date($koneksi, $feed_url) {
    $u = mysqli_real_escape_string($koneksi, $feed_url);
    $r = mysqli_query($koneksi, "SELECT last_pub_date FROM rss_meta WHERE feed_url='$u' LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) return null;
    $row = mysqli_fetch_assoc($r);
    return $row['last_pub_date']; // bisa null jika belum pernah ada item
}

/**
 * Update last_pub_date di rss_meta.
 * Hanya update jika $newDate lebih baru dari yang tersimpan.
 */
function update_last_pub_date($koneksi, $feed_url, $newDateStr) {
    if (empty($newDateStr)) return;
    $u = mysqli_real_escape_string($koneksi, $feed_url);
    $d = mysqli_real_escape_string($koneksi, $newDateStr);
    mysqli_query($koneksi, "
        INSERT INTO rss_meta (feed_url, last_pub_date, last_run_at)
        VALUES ('$u', '$d', NOW())
        ON DUPLICATE KEY UPDATE
            last_pub_date = IF(last_pub_date IS NULL OR '$d' > last_pub_date, '$d', last_pub_date),
            last_run_at   = NOW()
    ");
}

/**
 * Update last_run_at saja (dipanggil di akhir setiap feed meskipun tak ada item baru).
 */
function touch_feed_run($koneksi, $feed_url) {
    $u = mysqli_real_escape_string($koneksi, $feed_url);
    mysqli_query($koneksi, "
        INSERT INTO rss_meta (feed_url, last_run_at)
        VALUES ('$u', NOW())
        ON DUPLICATE KEY UPDATE last_run_at = NOW()
    ");
}

// ══════════════════════════════════════════════════════════════
//  FUNGSI FETCH
// ══════════════════════════════════════════════════════════════
function fetch_direct($url, $timeout = FETCH_TIMEOUT) {
    if (!function_exists('curl_init')) return null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => 'gzip, deflate',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: id-ID,id;q=0.9,en;q=0.8',
        ],
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err || $code >= 400) return null;
    return $data ?: null;
}

function fetch_via_rss2json($rss_url) {
    $api = 'https://api.rss2json.com/v1/api.json?rss_url=' . urlencode($rss_url) . '&count=' . MAX_PER_FEED;
    $raw = fetch_direct($api, 15);
    if (!$raw) return null;
    $json = json_decode($raw, true);
    if (!$json || ($json['status'] ?? '') !== 'ok') return null;
    return $json;
}

function fetch_via_allorigins($rss_url) {
    $api = 'https://api.allorigins.win/get?url=' . urlencode($rss_url);
    $raw = fetch_direct($api, 15);
    if (!$raw) return null;
    $json = json_decode($raw, true);
    return $json['contents'] ?? null;
}

/**
 * Smart fetch: coba langsung → rss2json → allorigins
 * Return: ['mode' => string, 'data' => mixed] atau null
 */
function smart_fetch($url) {
    $raw = fetch_direct($url);
    if ($raw) return ['mode' => 'direct', 'data' => $raw];
    usleep(600000);
    $json = fetch_via_rss2json($url);
    if ($json) return ['mode' => 'rss2json', 'data' => $json];
    usleep(300000);
    $xml = fetch_via_allorigins($url);
    if ($xml) return ['mode' => 'allorigins', 'data' => $xml];
    return null;
}

// ══════════════════════════════════════════════════════════════
//  FUNGSI PARSE TANGGAL
//  Mengkonversi berbagai format pubDate RSS → timestamp Unix
//  dan string MySQL DATETIME.
// ══════════════════════════════════════════════════════════════

/**
 * Parse tanggal dari RSS feed ke Unix timestamp.
 * Mendukung: RFC 2822 (RSS), ISO 8601 (Atom), dan format lain.
 * Return: int (unix timestamp) atau 0 jika gagal parse.
 */
function parse_pub_date($dateStr) {
    if (empty($dateStr)) return 0;
    $dateStr = trim($dateStr);

    // strtotime menangani sebagian besar format RSS & Atom
    $ts = strtotime($dateStr);
    if ($ts !== false && $ts > 0) return $ts;

    // Fallback: coba ganti zona waktu WIB/WITA/WIT ke +0700/+0800/+0900
    $dateStr = str_replace(['WIB', 'WITA', 'WIT'], ['+0700', '+0800', '+0900'], $dateStr);
    $ts = strtotime($dateStr);
    return ($ts !== false && $ts > 0) ? $ts : 0;
}

/**
 * Konversi Unix timestamp ke format MySQL DATETIME.
 * Return: string 'Y-m-d H:i:s' atau null jika ts = 0.
 */
function ts_to_mysql($ts) {
    if ($ts <= 0) return null;
    return date('Y-m-d H:i:s', $ts);
}

/**
 * Cek apakah item RSS ini lebih baru dari last_pub_date feed.
 *
 * @param int         $itemTs     Unix timestamp item
 * @param string|null $lastPubStr MySQL datetime string dari DB, atau null
 * @return bool true = artikel BARU, perlu diproses; false = sudah lama, skip
 */
function is_newer_than_last($itemTs, $lastPubStr) {
    // Jika belum pernah fetch sebelumnya → semua dianggap baru
    if ($lastPubStr === null) return true;
    // Jika item tidak punya tanggal → tetap proses (tidak bisa dibandingkan)
    if ($itemTs <= 0) return true;

    $lastTs = strtotime($lastPubStr);
    if ($lastTs === false || $lastTs <= 0) return true;

    // Item baru jika pubDate-nya LEBIH BESAR dari last_pub_date
    return $itemTs > $lastTs;
}

// ══════════════════════════════════════════════════════════════
//  FUNGSI CONTENT
// ══════════════════════════════════════════════════════════════
function clean_html($str) {
    $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $str = strip_tags($str);
    return trim(preg_replace('/\s+/', ' ', $str));
}

function extract_image_xml($item) {
    $media = $item->children('media', true);
    if (isset($media->content)) {
        $a = $media->content->attributes();
        if (!empty($a['url'])) return (string)$a['url'];
    }
    if (isset($media->thumbnail)) {
        $a = $media->thumbnail->attributes();
        if (!empty($a['url'])) return (string)$a['url'];
    }
    foreach ($item->enclosure as $enc) {
        $a = $enc->attributes();
        if (!empty($a['url']) && strpos((string)$a['type'], 'image') !== false)
            return (string)$a['url'];
    }
    $desc = (string)($item->description ?? '');
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $desc, $m)) return $m[1];
    $c = $item->children('content', true);
    if (isset($c->encoded) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string)$c->encoded, $m))
        return $m[1];
    return null;
}

function scrape_full_content($url) {
    if (empty($url) || !function_exists('curl_init')) return null;
    $html = fetch_direct($url, 20);
    if (!$html) $html = fetch_via_allorigins($url);
    if (!$html) return null;
    $html = mb_convert_encoding($html, 'UTF-8', 'auto');
    $dom  = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $removeSelectors = [
        '//*[contains(@class,"pilihan-redaksi")]', '//*[contains(@class,"related")]',
        '//*[contains(@class,"baca-juga")]',        '//*[contains(@class,"artikel-terkait")]',
        '//*[contains(@class,"ads")]',              '//*[contains(@class,"iklan")]',
        '//*[contains(@class,"share")]',            '//*[contains(@class,"social")]',
        '//*[contains(@class,"newsletter")]',       '//*[contains(@class,"sidebar")]',
        '//*[contains(@class,"cnn-logo")]',         '//*[contains(@id,"related")]',
        '//aside', '//nav', '//header', '//footer',
    ];
    foreach ($removeSelectors as $sel) {
        $nodes = @$xpath->query($sel);
        if ($nodes) foreach ($nodes as $node)
            if ($node->parentNode) $node->parentNode->removeChild($node);
    }
    $sels = [
        '//div[contains(@class,"detail__body-text")]',
        '//div[contains(@class,"detail__body")]',
        '//div[contains(@class,"detail-text")]',
        '//div[contains(@class,"content-detail")]',
        '//div[contains(@class,"itp_bodycontent")]',
        '//div[contains(@class,"read__content")]',
        '//div[contains(@class,"article__content")]',
        '//div[@itemprop="articleBody"]',
        '//article',
        '//div[contains(@class,"article-body")]',
        '//div[contains(@class,"entry-content")]',
    ];
    $content = null;
    $bestLen = 0;
    foreach ($sels as $sel) {
        $nodes = @$xpath->query($sel);
        if (!$nodes || $nodes->length === 0) continue;
        $raw = $dom->saveHTML($nodes->item(0));
        $tl  = strlen(strip_tags($raw));
        if ($tl > $bestLen && $tl > 200) { $bestLen = $tl; $content = $raw; }
        if ($bestLen > 1500) break;
    }
    if (!$content) {
        $pn = @$xpath->query('//body//p[string-length(normalize-space(.)) > 40]');
        if ($pn && $pn->length >= 3) {
            $content = '';
            foreach ($pn as $p) $content .= $dom->saveHTML($p);
        }
    }
    if (!$content || strlen(strip_tags($content)) < 150) return null;
    $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
    $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is',   '', $content);
    $content = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $content);
    $nc = 'ads?|iklan|related|recom|promo|share|social|newsletter|cookie|baca-?juga|pilihan|redaksi|sidebar';
    $content = preg_replace('/<(div|section|aside|ul|ol)[^>]*class="[^"]*\b(' . $nc . ')\b[^"]*"[^>]*>.*?<\/\1>/is', '', $content);
    $content = preg_replace('/\s+(class|id|style|data-[a-z0-9_-]+)="[^"]*"/i', '', $content);
    $parsed  = parse_url($url);
    $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    $content = preg_replace_callback(
        '/<img([^>]+)src=["\'](?!https?:\/\/)([^"\']+)["\']/i',
        function ($m) use ($baseUrl) {
            $src = (strpos($m[2], '/') === 0) ? $baseUrl . $m[2] : $baseUrl . '/' . ltrim($m[2], '/');
            return '<img' . $m[1] . 'src="' . $src . '"';
        },
        $content
    );
    return trim($content);
}

// ══════════════════════════════════════════════════════════════
//  FUNGSI DB — Kategori & Artikel
// ══════════════════════════════════════════════════════════════
function get_or_create_kategori($koneksi, $nama) {
    $e = mysqli_real_escape_string($koneksi, $nama);
    $r = mysqli_query($koneksi, "SELECT id_kategori FROM kategori WHERE nama_kategori='$e' LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) return (int)mysqli_fetch_assoc($r)['id_kategori'];
    mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES ('$e')");
    return (int)mysqli_insert_id($koneksi);
}

/**
 * Cek apakah artikel sudah ada di DB.
 * Prioritas: cek url_sumber dulu (paling akurat), fallback ke judul.
 * Return: array row ['id_artikel', 'kategori_id', 'kategori_manual'] atau null.
 */
function get_artikel_existing($koneksi, $judul, $url = '') {
    // Prioritas 1: cek by URL sumber (paling akurat)
    if (!empty($url)) {
        $u = mysqli_real_escape_string($koneksi, $url);
        $r = mysqli_query($koneksi,
            "SELECT id_artikel, kategori_id, COALESCE(kategori_manual,0) AS kategori_manual
             FROM artikel WHERE url_sumber='$u' LIMIT 1"
        );
        if ($r && mysqli_num_rows($r) > 0) return mysqli_fetch_assoc($r);
    }
    // Prioritas 2: fallback ke judul (kurang akurat, tapi coverage lebih luas)
    if (!empty($judul)) {
        $j = mysqli_real_escape_string($koneksi, $judul);
        $r = mysqli_query($koneksi,
            "SELECT id_artikel, kategori_id, COALESCE(kategori_manual,0) AS kategori_manual
             FROM artikel WHERE judul='$j' LIMIT 1"
        );
        if ($r && mysqli_num_rows($r) > 0) return mysqli_fetch_assoc($r);
    }
    return null;
}

function slug_from_title($t) {
    $s = strtolower($t);
    $s = preg_replace('/[^a-z0-9\s-]/', '', preg_replace('/\s+/', '-', trim($s)));
    return substr(preg_replace('/-+/', '-', $s), 0, 100);
}

/**
 * Insert artikel baru ke DB.
 * $f = array flag kolom yang tersedia (from has_column check).
 */
function do_insert($koneksi, $judul, $isi, $link, $thumb, $kat_id, $f) {
    $je  = mysqli_real_escape_string($koneksi, $judul);
    $ie  = mysqli_real_escape_string($koneksi, $isi);
    $le  = mysqli_real_escape_string($koneksi, $link);
    $the = $thumb ? mysqli_real_escape_string($koneksi, $thumb) : '';
    $se  = mysqli_real_escape_string($koneksi, slug_from_title($judul));

    $cols = "judul,isi,kategori_id,tgl_posting";
    $vals = "'$je','$ie',$kat_id,NOW()";

    if ($f['hasSlug'])                    { $cols .= ",slug";       $vals .= ",'$se'"; }
    if ($f['hasStatus'])                  { $cols .= ",status";     $vals .= ",'publish'"; }
    if ($f['hasThumbnail'] && $the)       { $cols .= ",thumbnail";  $vals .= ",'$the'"; }
    elseif ($f['hasGambar'] && $the)      { $cols .= ",gambar";     $vals .= ",'$the'"; }
    if ($f['hasPenulis'])                 { $cols .= ",penulis";    $vals .= ",'RSS Bot'"; }
    elseif ($f['hasAuthor'])              { $cols .= ",author";     $vals .= ",'RSS Bot'"; }
    if ($f['hasUrlSumber'] && !empty($le)){ $cols .= ",url_sumber"; $vals .= ",'$le'"; }
    if ($f['hasSumber'])                  { $cols .= ",sumber";     $vals .= ",'cnnindonesia.com'"; }

    return mysqli_query($koneksi, "INSERT INTO artikel ($cols) VALUES ($vals)");
}

// ══════════════════════════════════════════════════════════════
//  FUNGSI KLASIFIKASI
// ══════════════════════════════════════════════════════════════

/**
 * Hitung skor keyword untuk satu set classifier.
 * Judul dihitung 3x lebih berat dari isi.
 */
function score_keywords($judul, $isi, $keywords) {
    $tJudul = strtolower($judul);
    $tIsi   = strtolower(strip_tags($isi));
    $score  = 0;
    foreach ($keywords as $kw) {
        if (strpos($tJudul, $kw) !== false) $score += 3;
        if (strpos($tIsi,   $kw) !== false) $score += 1;
    }
    return $score;
}

/**
 * Klasifikasi artikel dari feed Gaya Hidup ke sub-kategori.
 * Fallback: 'Gaya Hidup' jika tidak ada keyword cocok.
 */
function classify_gaya_hidup($judul, $isi, $classifier) {
    $scores = [];
    foreach ($classifier as $katNama => $keywords)
        $scores[$katNama] = score_keywords($judul, $isi, $keywords);
    arsort($scores);
    $top = array_key_first($scores);
    return ($scores[$top] > 0) ? $top : 'Gaya Hidup';
}

/**
 * Klasifikasi ulang artikel dari feed lain ke kategori baru.
 * Threshold minimal 2 agar tidak over-classify.
 * Return: string nama kategori baru, atau null jika tidak cocok.
 */
function classify_berita_utama($judul, $isi, $classifier) {
    $scores = [];
    foreach ($classifier as $katNama => $keywords)
        $scores[$katNama] = score_keywords($judul, $isi, $keywords);
    arsort($scores);
    $top = array_key_first($scores);
    return ($scores[$top] >= 2) ? $top : null;
}

// ══════════════════════════════════════════════════════════════
//  SETUP DATABASE
// ══════════════════════════════════════════════════════════════
// Kolom tabel artikel
$s1 = ensure_column($koneksi, 'artikel', 'url_sumber',     'TEXT NULL AFTER isi');
$s2 = ensure_column($koneksi, 'artikel', 'isi_penuh',       'LONGTEXT NULL AFTER url_sumber');
$s3 = ensure_column($koneksi, 'artikel', 'sumber',          "VARCHAR(100) NULL AFTER url_sumber");
$s4 = ensure_column($koneksi, 'artikel', 'views',           "INT UNSIGNED NOT NULL DEFAULT 0 AFTER sumber");
$s5 = ensure_column($koneksi, 'artikel', 'kategori_manual', "TINYINT(1) NOT NULL DEFAULT 0 AFTER views");
log_msg("Kolom => url_sumber: $s1 | isi_penuh: $s2 | sumber: $s3 | views: $s4 | kategori_manual: $s5");

// Tabel rss_meta untuk tracking pubDate
ensure_rss_meta_table($koneksi);
log_msg("Tabel rss_meta: siap.");

// Cache flag kolom
$f = [
    'hasSlug'           => has_column($koneksi, 'artikel', 'slug'),
    'hasStatus'         => has_column($koneksi, 'artikel', 'status'),
    'hasPenulis'        => has_column($koneksi, 'artikel', 'penulis'),
    'hasAuthor'         => has_column($koneksi, 'artikel', 'author'),
    'hasThumbnail'      => has_column($koneksi, 'artikel', 'thumbnail'),
    'hasGambar'         => has_column($koneksi, 'artikel', 'gambar'),
    'hasIsiPenuh'       => has_column($koneksi, 'artikel', 'isi_penuh'),
    'hasUrlSumber'      => has_column($koneksi, 'artikel', 'url_sumber'),
    'hasSumber'         => has_column($koneksi, 'artikel', 'sumber'),
    'hasViews'          => has_column($koneksi, 'artikel', 'views'),
    'hasKategoriManual' => has_column($koneksi, 'artikel', 'kategori_manual'),
];

// Pre-create semua kategori yang dibutuhkan
$allKategori = ['Religi', 'Kesehatan', 'Kuliner', 'Travel', 'Cuaca', 'Hukum', 'Nasional', 'Ekonomi', 'Sains', 'Gaya Hidup'];
foreach ($allKategori as $kn) get_or_create_kategori($koneksi, $kn);

// ══════════════════════════════════════════════════════════════
//  STEP 1 — AUTO HAPUS ARTIKEL LAMA + MINIM VIEWS
//  Hapus hanya jika: umur > MAX_UMUR_HARI DAN views < MIN_VIEWS_AMAN
// ══════════════════════════════════════════════════════════════
log_msg("");
log_msg("=== AUTO CLEANUP ===");
log_msg("Hapus jika: umur > " . MAX_UMUR_HARI . " hari DAN views < " . MIN_VIEWS_AMAN);

$viewsCond = $f['hasViews'] ? "AND (views < " . MIN_VIEWS_AMAN . " OR views IS NULL)" : "";
$umurCond  = "tgl_posting < DATE_SUB(NOW(), INTERVAL " . MAX_UMUR_HARI . " DAY)";

if ($f['hasSumber']) {
    $deleteQ = mysqli_query($koneksi,
        "DELETE FROM artikel WHERE sumber='cnnindonesia.com' AND $umurCond $viewsCond"
    );
} elseif ($f['hasPenulis']) {
    $deleteQ = mysqli_query($koneksi,
        "DELETE FROM artikel WHERE penulis='RSS Bot' AND $umurCond $viewsCond"
    );
} elseif ($f['hasAuthor']) {
    $deleteQ = mysqli_query($koneksi,
        "DELETE FROM artikel WHERE author='RSS Bot' AND $umurCond $viewsCond"
    );
} else {
    $deleteQ = mysqli_query($koneksi,
        "DELETE FROM artikel WHERE $umurCond $viewsCond"
    );
    log_msg("  ! Kolom sumber/penulis/author tidak ditemukan — hapus berdasarkan tgl_posting saja.");
}

$terhapus = mysqli_affected_rows($koneksi);
log_msg("  -> $terhapus artikel dihapus (tua + minim views).");

// Log artikel viral yang dipertahankan
if ($f['hasViews'] && $f['hasSumber']) {
    $rViralQ = mysqli_query($koneksi,
        "SELECT COUNT(*) as jml FROM artikel
         WHERE sumber='cnnindonesia.com' AND $umurCond AND views >= " . MIN_VIEWS_AMAN
    );
    if ($rViralQ) {
        $rViral = mysqli_fetch_assoc($rViralQ);
        if ((int)$rViral['jml'] > 0)
            log_msg("  ~ " . $rViral['jml'] . " artikel lama dipertahankan (views >= " . MIN_VIEWS_AMAN . ").");
    }
}

// ══════════════════════════════════════════════════════════════
//  STEP 2 — FETCH RSS & INSERT HANYA ARTIKEL BARU
// ══════════════════════════════════════════════════════════════
$totalInserted = 0;
$totalSkipped  = 0;   // sudah ada di DB (duplikat)
$totalOld      = 0;   // di-skip karena pubDate lama
$totalError    = 0;

log_msg("");
log_msg("=== RSS FETCH CNN INDONESIA ===");
log_msg("Total feed : " . count($RSS_FEEDS));
log_msg("Max baru   : " . MAX_PER_FEED . " artikel per feed");
log_msg("Mode       : hanya insert artikel dengan pubDate LEBIH BARU dari run sebelumnya");
log_msg("");

foreach ($RSS_FEEDS as $feed) {
    $feedUrl = $feed['url'];
    $katNama = $feed['kategori_nama'];

    // Ambil last_pub_date untuk feed ini
    $lastPubDate  = get_last_pub_date($koneksi, $feedUrl);
    $lastPubLabel = $lastPubDate ?? '(belum pernah)';
    log_msg("Fetching [$katNama]: $feedUrl");
    log_msg("  ~ last pub date: $lastPubLabel");

    $result = smart_fetch($feedUrl);
    if (!$result) {
        log_msg("  x Gagal semua metode — skip");
        touch_feed_run($koneksi, $feedUrl);
        $totalError++;
        log_msg("");
        continue;
    }
    log_msg("  ~ via: " . $result['mode']);

    $kat_id_default  = get_or_create_kategori($koneksi, $katNama);
    $allowReclassify = !in_array($katNama, $NO_RECLASSIFY);
    $count           = 0;
    $newestPubTs     = 0; // unix timestamp item terbaru yang berhasil diinsert di run ini

    // ── Closure: tentukan kategori final ──
    $resolve_kategori = function ($judul, $isi) use (
        $katNama, $kat_id_default, $koneksi,
        $GAYA_HIDUP_CLASSIFIER, $BERITA_UTAMA_CLASSIFIER,
        $allowReclassify
    ) {
        if ($katNama === 'Gaya Hidup') {
            $finalNama = classify_gaya_hidup($judul, $isi, $GAYA_HIDUP_CLASSIFIER);
            return [$finalNama, get_or_create_kategori($koneksi, $finalNama)];
        }
        if ($allowReclassify) {
            $reclassified = classify_berita_utama($judul, $isi, $BERITA_UTAMA_CLASSIFIER);
            if ($reclassified !== null)
                return [$reclassified, get_or_create_kategori($koneksi, $reclassified)];
        }
        return [$katNama, $kat_id_default];
    };

    // ── Proses berdasarkan mode fetch ──
    if ($result['mode'] === 'rss2json') {
        // rss2json: items sudah dalam bentuk array associatif
        $items = $result['data']['items'] ?? [];

        foreach ($items as $item) {
            if ($count >= MAX_PER_FEED) break;

            $judul = clean_html($item['title'] ?? '');
            if (empty($judul)) continue;

            // ── Cek pubDate: skip jika artikel sudah lama ──
            $pubDateStr = $item['pubDate'] ?? ($item['published'] ?? '');
            $itemTs     = parse_pub_date($pubDateStr);
            if (!is_newer_than_last($itemTs, $lastPubDate)) {
                $totalOld++;
                continue; // artikel lebih lama dari run sebelumnya → skip
            }

            $isi   = $item['description'] ?? ($item['content'] ?? $judul);
            $link  = trim($item['link'] ?? '');
            $thumb = $item['thumbnail'] ?? ($item['enclosure']['link'] ?? null);
            if (empty($thumb) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $isi, $m))
                $thumb = $m[1];

            // ── Cek duplikat di DB ──
            $existing = get_artikel_existing($koneksi, $judul, $link);
            if ($existing !== null) {
                if ((int)$existing['kategori_manual'] === 1)
                    log_msg("  ~ [terlindungi] $judul");
                $totalSkipped++;
                // Tetap update newestPubTs agar last_pub_date tidak mundur
                if ($itemTs > $newestPubTs) $newestPubTs = $itemTs;
                continue;
            }

            [$finalKatNama, $kat_id_final] = $resolve_kategori($judul, $isi);

            $ok = do_insert($koneksi, $judul, $isi, $link, $thumb, $kat_id_final, $f);
            if (!$ok) {
                log_msg("  x DB error: " . mysqli_error($koneksi));
                $totalError++;
                continue;
            }

            $newId   = (int)mysqli_insert_id($koneksi);
            $scraped = false;
            if ($f['hasIsiPenuh'] && !empty($link)) {
                $fc = scrape_full_content($link);
                if ($fc) {
                    mysqli_query($koneksi,
                        "UPDATE artikel SET isi_penuh='" . mysqli_real_escape_string($koneksi, $fc) . "'
                         WHERE id_artikel=$newId"
                    );
                    $scraped = true;
                }
            }

            if ($itemTs > $newestPubTs) $newestPubTs = $itemTs;
            log_msg("  + " . ($scraped ? "[penuh] " : "[ringkasan] ") . "[$finalKatNama] $judul");
            $totalInserted++;
            $count++;
        }

    } else {
        // direct / allorigins: parse XML
        $xml_str = preg_replace('/^\xEF\xBB\xBF/', '', $result['data']);
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_str);
        libxml_clear_errors();

        if (!$xml) {
            log_msg("  x Gagal parse XML");
            touch_feed_run($koneksi, $feedUrl);
            $totalError++;
            log_msg("");
            continue;
        }

        $xmlItems = $xml->channel->item ?? $xml->entry ?? [];

        foreach ($xmlItems as $item) {
            if ($count >= MAX_PER_FEED) break;

            $judul = clean_html((string)($item->title ?? ''));
            if (empty($judul)) continue;

            // ── Cek pubDate: skip jika artikel sudah lama ──
            $pubDateStr = (string)($item->pubDate ?? $item->published ?? $item->updated ?? '');
            $itemTs     = parse_pub_date($pubDateStr);
            if (!is_newer_than_last($itemTs, $lastPubDate)) {
                $totalOld++;
                continue;
            }

            // Ambil isi
            $isi = '';
            $cn  = $item->children('content', true);
            if (isset($cn->encoded))            $isi = (string)$cn->encoded;
            elseif (!empty($item->description)) $isi = (string)$item->description;
            elseif (!empty($item->summary))     $isi = (string)$item->summary;
            if (empty($isi)) $isi = $judul;

            // Ambil link
            $link = trim((string)($item->link ?? ''));
            if (empty($link) && isset($item->link)) {
                $la = $item->link->attributes();
                if (!empty($la['href'])) $link = trim((string)$la['href']);
            }
            if (!empty($link) && !filter_var($link, FILTER_VALIDATE_URL)) $link = '';

            $thumb = extract_image_xml($item);

            // ── Cek duplikat di DB ──
            $existing = get_artikel_existing($koneksi, $judul, $link);
            if ($existing !== null) {
                if ((int)$existing['kategori_manual'] === 1)
                    log_msg("  ~ [terlindungi] $judul");
                $totalSkipped++;
                if ($itemTs > $newestPubTs) $newestPubTs = $itemTs;
                continue;
            }

            [$finalKatNama, $kat_id_final] = $resolve_kategori($judul, $isi);

            $ok = do_insert($koneksi, $judul, $isi, $link, $thumb, $kat_id_final, $f);
            if (!$ok) {
                log_msg("  x DB error: " . mysqli_error($koneksi));
                $totalError++;
                continue;
            }

            $newId   = (int)mysqli_insert_id($koneksi);
            $scraped = false;
            if ($f['hasIsiPenuh'] && !empty($link)) {
                $fc = scrape_full_content($link);
                if ($fc) {
                    mysqli_query($koneksi,
                        "UPDATE artikel SET isi_penuh='" . mysqli_real_escape_string($koneksi, $fc) . "'
                         WHERE id_artikel=$newId"
                    );
                    $scraped = true;
                }
            }

            if ($itemTs > $newestPubTs) $newestPubTs = $itemTs;
            log_msg("  + " . ($scraped ? "[penuh] " : "[ringkasan] ") . "[$finalKatNama] $judul");
            $totalInserted++;
            $count++;
        }
    }

    // ── Simpan last_pub_date terbaru dari run ini ──
    if ($newestPubTs > 0) {
        update_last_pub_date($koneksi, $feedUrl, ts_to_mysql($newestPubTs));
        log_msg("  ~ last_pub_date diperbarui: " . ts_to_mysql($newestPubTs));
    } else {
        touch_feed_run($koneksi, $feedUrl);
    }

    log_msg("  -> $count artikel baru [$katNama]");
    log_msg("");
    usleep(500000); // jeda 0.5 detik antar feed
}

// ══════════════════════════════════════════════════════════════
//  REKAP AKHIR
// ══════════════════════════════════════════════════════════════
log_msg("====================================");
log_msg("=== SELESAI ===");
log_msg("Sumber      : CNN Indonesia");
log_msg("Dihapus     : $terhapus artikel (umur > " . MAX_UMUR_HARI . " hari & views < " . MIN_VIEWS_AMAN . ")");
log_msg("+ Berhasil  : $totalInserted artikel baru diinsert");
log_msg("- Lama/skip : $totalOld artikel di-skip (pubDate lama)");
log_msg("- Duplikat  : $totalSkipped artikel sudah ada di DB");
log_msg("x Error     : $totalError feed/insert gagal");
log_msg("");
log_msg("--- Rekap per kategori ---");
$rekap = mysqli_query($koneksi, "
    SELECT k.nama_kategori, COUNT(a.id_artikel) as jumlah
    FROM kategori k
    LEFT JOIN artikel a ON a.kategori_id = k.id_kategori
    GROUP BY k.id_kategori
    ORDER BY k.nama_kategori ASC
");
while ($r = mysqli_fetch_assoc($rekap))
    log_msg("  " . str_pad($r['nama_kategori'], 16) . " : " . $r['jumlah'] . " artikel");
log_msg("====================================");