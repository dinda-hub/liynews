<?php
include '../config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

$q = mysqli_query($koneksi,
    "SELECT a.*, k.nama_kategori, u.username AS nama_penulis
     FROM artikel a
     LEFT JOIN kategori k ON a.kategori_id = k.id_kategori
     LEFT JOIN user u ON a.penulis_id = u.id_user
     WHERE a.id_artikel = $id LIMIT 1");
if (!$q || mysqli_num_rows($q) == 0) { header("Location: index.php"); exit; }
$artikel = mysqli_fetch_assoc($q);

// ── Scraper
function fetch_url($url, $timeout = 20) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,   CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_HTTPHEADER => ['Accept: text/html,*/*;q=0.8','Accept-Language: id-ID,id;q=0.9,en;q=0.8'],
    ]);
    $html = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ($html && $code < 400) ? $html : null;
}

function scrape_artikel($url) {
    if (empty($url)) return null;
    $html = fetch_url($url);
    if (!$html) return null;
    $html = mb_convert_encoding($html, 'UTF-8', 'auto');
    $dom  = new DOMDocument('1.0','UTF-8');
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $removeSelectors = [
        '//*[contains(@class,"pilihan-redaksi")]','//*[contains(@class,"related")]',
        '//*[contains(@class,"baca-juga")]','//*[contains(@class,"artikel-terkait")]',
        '//*[contains(@class,"ads")]','//*[contains(@class,"iklan")]',
        '//*[contains(@class,"share")]','//*[contains(@class,"social")]',
        '//*[contains(@class,"newsletter")]','//*[contains(@class,"recommendation")]',
        '//*[contains(@class,"mostpopular")]','//*[contains(@class,"most-popular")]',
        '//*[contains(@class,"sidebar")]','//*[contains(@class,"tag-label")]',
        '//*[contains(@class,"content-label")]','//*[contains(@class,"cnn-logo")]',
        '//*[contains(@class,"box-pilihan")]','//*[contains(@class,"box-related")]',
        '//*[contains(@class,"detikAds")]','//*[contains(@class,"detail__body-tag")]',
        '//*[contains(@class,"detail__author")]','//*[contains(@id,"pilihan-redaksi")]',
        '//*[contains(@id,"related")]','//aside','//nav','//header','//footer',
    ];
    foreach ($removeSelectors as $sel) {
        $nodes = @$xpath->query($sel);
        if ($nodes) foreach ($nodes as $node) if ($node->parentNode) $node->parentNode->removeChild($node);
    }
    $selectors = [
        '//div[contains(@class,"detail__body-text")]','//div[contains(@class,"detail__body")]',
        '//div[contains(@class,"detail-text")]','//section[contains(@class,"detail_text")]',
        '//div[contains(@class,"itp_bodycontent")]','//div[contains(@class,"read__content")]',
        '//div[contains(@class,"side-article")]','//div[contains(@class,"txt-article")]',
        '//div[contains(@class,"article__content")]','//div[contains(@class,"content-detail")]',
        '//div[contains(@class,"article-content-body")]','//div[@itemprop="articleBody"]',
        '//div[contains(@class,"story-content")]','//div[contains(@class,"detail-konten")]',
        '//article','//div[contains(@class,"article-body")]',
        '//div[contains(@class,"entry-content")]','//div[contains(@class,"post-content")]',
    ];
    $content = null; $bestLen = 0;
    foreach ($selectors as $sel) {
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
    $noiseClasses = 'ads?|iklan|related|recom|promo|share|social|newsletter|cookie|baca-?juga|pilihan|redaksi|sidebar|tag|label|most.?popular|cnn-?logo|detik-?ads|box-?pilihan|box-?related|artikel-?terkait';
    $content = preg_replace('/<(div|section|aside|ul|ol)[^>]*class="[^"]*\b('.$noiseClasses.')\b[^"]*"[^>]*>.*?<\/\1>/is', '', $content);
    $content = preg_replace('/\s+(class|id|style|data-[a-z0-9_-]+)="[^"]*"/i', '', $content);
    $content = preg_replace("/\s+(class|id|style|data-[a-z0-9_-]+)='[^']*'/i", '', $content);
    $parsed = parse_url($url);
    $base   = $parsed['scheme'].'://'.$parsed['host'];
    $content = preg_replace_callback(
        '/<img([^>]+)src=["\'](?!https?:\/\/)([^"\']+)["\']/i',
        function($m) use ($base) {
            $src = (strpos($m[2],'/') === 0) ? $base.$m[2] : $base.'/'.ltrim($m[2],'/');
            return '<img'.$m[1].'src="'.$src.'"';
        }, $content
    );
    return trim($content);
}

$hasIsiPenuh = (bool)(mysqli_num_rows(mysqli_query($koneksi,"SHOW COLUMNS FROM artikel LIKE 'isi_penuh'")));
$hasLink     = (bool)(mysqli_num_rows(mysqli_query($koneksi,"SHOW COLUMNS FROM artikel LIKE 'url_sumber'")));
if (!$hasIsiPenuh) {
    mysqli_query($koneksi,"ALTER TABLE artikel ADD COLUMN isi_penuh LONGTEXT NULL AFTER isi");
    $hasIsiPenuh = true;
}

$isiTampil    = $artikel['isi'];
$sumberUrl    = $hasLink ? ($artikel['url_sumber'] ?? '') : '';
$scrapeStatus = 'db';

if ($sumberUrl) {
    if ($hasIsiPenuh && !empty($artikel['isi_penuh'])) {
        $isiTampil    = $artikel['isi_penuh'];
        $scrapeStatus = 'cache';
    } else {
        $scraped = scrape_artikel($sumberUrl);
        if ($scraped && strlen(strip_tags($scraped)) > 150) {
            $isiTampil    = $scraped;
            $scrapeStatus = 'scraped';
            if ($hasIsiPenuh) {
                $esc = mysqli_real_escape_string($koneksi, $scraped);
                mysqli_query($koneksi,"UPDATE artikel SET isi_penuh='$esc' WHERE id_artikel=$id");
            }
        } else { $scrapeStatus = 'failed'; }
    }
}

if ($sumberUrl && isset($_GET['refresh']) && $_GET['refresh'] == 1) {
    $scraped = scrape_artikel($sumberUrl);
    if ($scraped && strlen(strip_tags($scraped)) > 150) {
        $isiTampil    = $scraped;
        $scrapeStatus = 'scraped';
        if ($hasIsiPenuh) {
            $esc = mysqli_real_escape_string($koneksi, $scraped);
            mysqli_query($koneksi,"UPDATE artikel SET isi_penuh='$esc' WHERE id_artikel=$id");
        }
    }
}

$f = !empty($artikel['thumbnail']) ? $artikel['thumbnail'] : ($artikel['gambar'] ?? '');
if (!empty($f)) {
    if (filter_var($f, FILTER_VALIDATE_URL))       $gambar = $f;
    elseif (file_exists("../uploads/".$f))          $gambar = "../uploads/".$f;
    else $gambar = null;
} else { $gambar = null; }

$relQ   = mysqli_query($koneksi,"SELECT a.*,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori WHERE a.kategori_id={$artikel['kategori_id']} AND a.id_artikel!=$id ORDER BY a.tgl_posting DESC LIMIT 4");
$katQ   = mysqli_query($koneksi,"SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kategoriList = []; while ($k = mysqli_fetch_assoc($katQ)) $kategoriList[] = $k;

function tgl($t){
    $b=[1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
        7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    $p=explode('-',date('Y-m-d',strtotime($t)));return $p[2].' '.$b[(int)$p[1]].' '.$p[0];
}
function ago($t){$d=time()-strtotime($t);if($d<3600)return floor($d/60).' menit lalu';if($d<86400)return floor($d/3600).' jam lalu';return floor($d/86400).' hari lalu';}

function renderIsi($isi) {
    $isi = trim($isi);
    if (empty($isi)) return '<p><em>Isi artikel tidak tersedia.</em></p>';
    if (strip_tags($isi) === $isi) {
        $kalimat = preg_split('/(?<=[.!?])\s+/', $isi, -1, PREG_SPLIT_NO_EMPTY);
        if (count($kalimat) <= 1) return '<p>'.nl2br(htmlspecialchars($isi)).'</p>';
        $out = '';
        foreach (array_chunk($kalimat, 3) as $c) {
            $p = trim(implode(' ', $c));
            if ($p) $out .= '<p>'.htmlspecialchars($p).'</p>';
        }
        return $out;
    }
    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><h2><h3><h4><blockquote><a><img><figure><figcaption><span>';
    $isi = strip_tags($isi, $allowed);
    $isi = preg_replace('/\s+(on\w+|style|class|id|data-[a-z0-9_-]+)="[^"]*"/i', '', $isi);
    $isi = preg_replace("/\s+(on\w+|style|class|id|data-[a-z0-9_-]+)='[^']*'/i", '', $isi);
    $isi = preg_replace('/<br\s*\/?>\s*<br\s*\/?>+/i', '</p><p>', $isi);
    $isi = preg_replace('/<br\s*\/?>/i', ' ', $isi);
    if (strpos($isi, '<p') === false) $isi = '<p>'.$isi.'</p>';
    $isi = preg_replace('/<p>\s*<\/p>/i', '', $isi);
    return $isi;
}

$pageUrl   = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
$pageTitle = urlencode($artikel['judul']);

// ── SESSION & USER
session_start();
$isLogin  = isset($_SESSION['user_login']) && $_SESSION['user_login'] === true;
$userNama = $isLogin ? ($_SESSION['user_nama'] ?? $_SESSION['user_username'] ?? 'User') : '';
$userRole = $isLogin ? ($_SESSION['user_role'] ?? '') : '';
$userInit = $isLogin ? strtoupper(substr($_SESSION['user_username'] ?? 'U', 0, 1)) : '';
$userId   = $isLogin ? (int)($_SESSION['user_id'] ?? 0) : 0;
$dashLink = '#';
if ($userRole === 'admin')       $dashLink = '../admin/dashboardadmin.php';
elseif ($userRole === 'penulis') $dashLink = '../admin/dashboardpenulis.php';

// ── PASTIKAN TABEL KOMENTAR ADA
mysqli_query($koneksi, "
    CREATE TABLE IF NOT EXISTS komentar (
        id_komentar  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        artikel_id   INT NOT NULL,
        user_id      INT NOT NULL,
        isi          TEXT NOT NULL,
        status       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        tgl_komentar DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_artikel (artikel_id),
        INDEX idx_status  (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── PROSES KIRIM KOMENTAR
$komentarMsg   = '';
$komentarError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_komentar'])) {
    if (!$isLogin) {
        $komentarError = "Kamu harus login untuk berkomentar.";
    } else {
        $isiKomentar = trim($_POST['isi_komentar'] ?? '');
        if (empty($isiKomentar)) {
            $komentarError = "Komentar tidak boleh kosong.";
        } elseif (mb_strlen($isiKomentar) > 1000) {
            $komentarError = "Komentar maksimal 1000 karakter.";
        } else {
            $isiEsc = mysqli_real_escape_string($koneksi, $isiKomentar);
            mysqli_query($koneksi,
                "INSERT INTO komentar (artikel_id, user_id, isi, status)
                 VALUES ($id, $userId, '$isiEsc', 'pending')"
            );
            $komentarMsg = "Komentar berhasil dikirim dan menunggu persetujuan admin.";
        }
    }
}

// ── AMBIL KOMENTAR APPROVED
$komentarQ = mysqli_query($koneksi,
    "SELECT km.*, u.username
     FROM komentar km
     JOIN user u ON km.user_id = u.id_user
     WHERE km.artikel_id = $id AND km.status = 'approved'
     ORDER BY km.tgl_komentar DESC"
);
$jumlahKomentar = mysqli_num_rows($komentarQ);
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($artikel['judul']) ?> — LiyNews</title>
<meta property="og:title" content="<?= htmlspecialchars($artikel['judul']) ?>">
<?php if ($gambar): ?><meta property="og:image" content="<?= htmlspecialchars($gambar) ?>"><?php endif; ?>
<meta property="og:type" content="article">
<script>
(function(){
  var s = localStorage.getItem('pb_theme');
  if (!s) s = matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', s);
})();
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --blue:#1a56db; --blue-d:#1044b8; --blue-soft:rgba(26,86,219,.1);
  --red:#e53e3e;
  --navy:#0d1f3c; --navy2:#132040;
  --bg:#f7f8fa; --bg-card:#fff; --bg-input:#eef1f7;
  --border:#dde3ef; --border-lt:#eaecf5;
  --text:#0f1923; --text2:#374151; --text-muted:#6b7a99; --text-faint:#b0bdd0;
  --sh1:0 1px 4px rgba(15,25,60,.06);
  --sh2:0 4px 20px rgba(15,25,60,.1);
  --sh3:0 12px 40px rgba(15,25,60,.14);
  --fd:'Playfair Display',Georgia,serif;
  --fs:'Inter',system-ui,sans-serif;
  --r:6px; --rm:10px;
}
[data-theme="dark"] {
  --blue:#4d8ef7; --blue-d:#3a7be8; --blue-soft:rgba(77,142,247,.12);
  --navy:#060e1c; --navy2:#0a1628;
  --bg:#0c1628; --bg-card:#111f38; --bg-input:#162040;
  --border:#1d3058; --border-lt:#172848;
  --text:#e8eef8; --text2:#b8c8de; --text-muted:#6a86aa; --text-faint:#253a58;
  --sh1:0 1px 4px rgba(0,0,0,.3);
  --sh2:0 4px 20px rgba(0,0,0,.4);
  --sh3:0 12px 40px rgba(0,0,0,.5);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--fs);background:var(--bg);color:var(--text);font-size:15px;line-height:1.6;transition:background .2s,color .2s;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
img{display:block;max-width:100%;object-fit:cover}

#prog{position:fixed;top:0;left:0;height:3px;width:0;background:var(--blue);z-index:9999;border-radius:0 2px 2px 0;transition:width .06s linear}

/* ── TICKER ── */
.ticker{background:var(--blue);overflow:hidden;padding:0}
[data-theme="dark"] .ticker{background:#0c2260}
.ticker-inner{display:flex;align-items:stretch;height:36px}
.ticker-label{background:rgba(0,0,0,.2);color:#fff;font-size:.62rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;padding:0 20px 0 16px;display:flex;align-items:center;white-space:nowrap;flex-shrink:0;clip-path:polygon(0 0,calc(100% - 10px) 0,100% 50%,calc(100% - 10px) 100%,0 100%);margin-right:4px}
.ticker-scroll{overflow:hidden;flex:1;display:flex;align-items:center}
.ticker-track{display:flex;width:max-content;animation:tick 50s linear infinite}
.ticker-track:hover{animation-play-state:paused}
.ticker-item{color:rgba(255,255,255,.92);font-size:.78rem;font-weight:500;white-space:nowrap;padding:0 40px 0 0;display:flex;align-items:center;gap:8px;transition:color .15s}
.ticker-item:hover{color:#fff}
.ticker-item::before{content:'◆';font-size:.4rem;opacity:.5;flex-shrink:0}
@keyframes tick{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}

/* ── HEADER ── */
.site-header{background:var(--navy)}
.hdr-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0}
.logo{font-family:var(--fd);font-size:2rem;font-weight:900;color:#fff;letter-spacing:-.03em;line-height:1;flex-shrink:0}
.logo em{color:var(--blue);font-style:normal}
.logo-sub{font-size:.55rem;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.18em;margin-top:2px}
.hdr-right{display:flex;align-items:center;gap:8px;flex-shrink:0}
.btn-icon{background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.14);color:rgba(255,255,255,.6);width:36px;height:36px;border-radius:99px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:.15s;flex-shrink:0}
.btn-icon:hover{border-color:var(--blue);color:#fff;background:rgba(255,255,255,.15)}
.btn-a{font-family:var(--fs);font-size:.76rem;font-weight:600;padding:7px 18px;border-radius:99px;cursor:pointer;transition:.15s;white-space:nowrap;border:none;display:inline-flex;align-items:center;gap:5px}
.btn-a-ghost{background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.2);color:#fff}
.btn-a-ghost:hover{background:rgba(255,255,255,.18)}
.btn-a-solid{background:var(--blue);color:#fff}
.btn-a-solid:hover{background:var(--blue-d)}
.u-menu{position:relative}
.u-chip{display:flex;align-items:center;gap:8px;padding:5px 14px 5px 5px;border:1.5px solid rgba(255,255,255,.18);border-radius:99px;background:rgba(255,255,255,.08);cursor:pointer;transition:.15s;user-select:none}
.u-chip:hover{border-color:var(--blue);background:rgba(255,255,255,.14)}
.u-av{width:28px;height:28px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.72rem;font-weight:800;flex-shrink:0}
.u-nm{font-size:.79rem;font-weight:600;color:#fff;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.u-chip i.arr{font-size:.62rem;color:rgba(255,255,255,.4);transition:transform .2s}
.u-chip.open i.arr{transform:rotate(180deg)}
.u-drop{display:none;position:absolute;right:0;top:calc(100% + 8px);background:var(--bg-card);border:1.5px solid var(--border);border-radius:var(--rm);min-width:200px;box-shadow:var(--sh3);overflow:hidden;z-index:500}
.u-drop.open{display:block}
.dd-hd{padding:14px 16px 10px;border-bottom:1px solid var(--border)}
.dd-hd-name{font-size:.88rem;font-weight:700;color:var(--text)}
.dd-hd-role{font-size:.67rem;color:var(--text-muted);margin-top:2px}
.dd-it{display:flex;align-items:center;gap:9px;padding:9px 16px;font-size:.82rem;color:var(--text);transition:.12s;text-decoration:none}
.dd-it i{font-size:.88rem;color:var(--text-muted);width:16px;text-align:center}
.dd-it:hover{background:var(--bg);color:var(--blue)}
.dd-it:hover i{color:var(--blue)}
.dd-sep{height:1px;background:var(--border)}
.dd-it.out{color:#e53e3e}
.dd-it.out i{color:#e53e3e}
.dd-it.out:hover{background:#fff5f5}
[data-theme="dark"] .dd-it.out:hover{background:#1a0a0a}

/* ── CAT NAV ── */
.cat-nav{background:var(--navy2);border-bottom:3px solid var(--blue);position:sticky;top:0;z-index:400;box-shadow:0 3px 14px rgba(0,0,0,.3)}
.cat-nav-wrap{display:flex;align-items:center;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.cat-nav-wrap::-webkit-scrollbar{display:none}
.cat-lnk{font-family:var(--fs);font-size:.71rem;font-weight:600;text-transform:uppercase;letter-spacing:.09em;color:rgba(255,255,255,.45);padding:13px 16px;border-bottom:3px solid transparent;margin-bottom:-3px;transition:color .15s;white-space:nowrap;display:inline-block;flex-shrink:0}
.cat-lnk:hover{color:rgba(255,255,255,.85)}
.cat-lnk.on{color:#fff;border-bottom-color:var(--blue)}

.badge-cat{display:inline-block;font-family:var(--fs);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;background:var(--blue);color:#fff;padding:3px 10px;border-radius:3px;line-height:1.5}

/* ── PAGE LAYOUT ── */
.page{padding:32px 0 80px}
.pgrid{display:grid;grid-template-columns:1fr 296px;gap:40px;align-items:start}
@media(max-width:960px){.pgrid{grid-template-columns:1fr}}

/* ── ARTICLE ── */
.art{min-width:0}
.bc{display:flex;align-items:center;gap:6px;font-size:.73rem;color:var(--text-faint);margin-bottom:20px;flex-wrap:wrap}
.bc a{color:var(--blue);font-weight:500}
.bc a:hover{text-decoration:underline}
.bc .sep{opacity:.4}
.art-title{font-family:var(--fd);font-size:clamp(1.7rem,3.5vw,2.6rem);font-weight:900;line-height:1.12;color:var(--text);margin:10px 0 18px;letter-spacing:-.025em}
.art-meta{display:flex;align-items:center;gap:18px;font-size:.77rem;color:var(--text-muted);padding-bottom:18px;border-bottom:2px solid var(--border);margin-bottom:24px;flex-wrap:wrap}
.mi{display:flex;align-items:center;gap:5px}
.mi i{font-size:.82rem;color:var(--blue);opacity:.8}
.mi-author{font-weight:600;color:var(--text2)}

.hero{position:relative;border-radius:var(--rm);overflow:hidden;margin-bottom:24px;box-shadow:var(--sh2);background:var(--bg-input)}
.hero img{width:100%;max-height:480px;object-fit:cover;display:block}
.hero::after{content:'';position:absolute;inset:0;background:linear-gradient(to bottom,transparent 55%,rgba(13,31,60,.25));pointer-events:none}
.hero-ph{width:100%;height:280px;background:var(--bg-input);border-radius:var(--rm);margin-bottom:24px;display:flex;align-items:center;justify-content:center;color:var(--text-faint);font-size:2rem;border:1px solid var(--border)}

.srcbar{display:flex;align-items:center;gap:10px;background:var(--bg-card);border:1.5px solid var(--border);border-radius:var(--r);padding:10px 14px;margin-bottom:24px;font-size:.78rem;color:var(--text-muted);flex-wrap:wrap;box-shadow:var(--sh1)}
.srcbar i.lnk-ico{color:var(--blue);font-size:.95rem;flex-shrink:0}
.srcbar a.slink{color:var(--blue);font-weight:600}
.srcbar a.slink:hover{text-decoration:underline}
.rbtn{color:var(--text-faint);font-size:.75rem;transition:.15s;display:inline-flex;align-items:center;gap:3px}
.rbtn:hover{color:var(--blue)}
.sbadge{margin-left:auto;display:inline-flex;align-items:center;gap:4px;font-size:.63rem;font-weight:700;padding:3px 9px;border-radius:99px;border:1.5px solid;white-space:nowrap}
.sbadge.scraped{color:#15803d;border-color:#15803d;background:rgba(21,128,61,.08)}
.sbadge.cache{color:var(--blue);border-color:var(--blue);background:var(--blue-soft)}
.sbadge.failed{color:var(--red);border-color:var(--red);background:rgba(229,62,62,.08)}
.sbadge.db{color:var(--text-muted);border-color:var(--border)}

.abody{font-size:1.04rem;line-height:1.9;color:var(--text2)}
.abody p{margin-bottom:1.35rem}
.abody p:last-child{margin-bottom:0}
.abody h2,.abody h3,.abody h4{font-family:var(--fd);color:var(--text);font-weight:800;line-height:1.22;margin:2rem 0 .75rem}
.abody h2{font-size:1.4rem}.abody h3{font-size:1.18rem}
.abody strong,.abody b{color:var(--text);font-weight:700}
.abody a{color:var(--blue);text-decoration:underline;text-underline-offset:3px;text-decoration-thickness:1px}
.abody a:hover{opacity:.8}
.abody img{width:100%;border-radius:var(--r);margin:1.8rem 0;box-shadow:var(--sh2)}
.abody figure{margin:1.8rem 0}
.abody figcaption{font-size:.76rem;color:var(--text-muted);text-align:center;margin-top:6px;font-style:italic}
.abody blockquote{border-left:3px solid var(--blue);padding:6px 0 6px 18px;margin:1.6rem 0;color:var(--text-muted);font-style:italic;font-family:var(--fd);font-size:1.06rem}
.abody ul,.abody ol{padding-left:1.5rem;margin-bottom:1.35rem}
.abody li{margin-bottom:.4rem}

.srcfoot{margin:12px 0 4px;text-align:right}
.srcfoot a{font-size:.74rem;color:var(--text-muted);display:inline-flex;align-items:center;gap:4px;transition:.15s}
.srcfoot a:hover{color:var(--blue)}
.fcta{margin:24px 0;padding:24px;background:var(--bg-card);border:1.5px solid var(--border);border-radius:var(--rm);text-align:center;box-shadow:var(--sh1)}
.fcta p{font-size:.87rem;color:var(--text-muted);margin-bottom:14px}
.fcta a{display:inline-flex;align-items:center;gap:6px;background:var(--blue);color:#fff;padding:10px 22px;border-radius:var(--r);font-size:.84rem;font-weight:600;transition:.15s}
.fcta a:hover{background:var(--blue-d)}

.sharebar{display:flex;align-items:center;gap:10px;padding:18px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);margin:28px 0;flex-wrap:wrap}
.slabel{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted)}
.sbtn{width:36px;height:36px;border-radius:50%;border:1.5px solid var(--border);color:var(--text-muted);background:var(--bg-card);display:flex;align-items:center;justify-content:center;font-size:.9rem;cursor:pointer;transition:.15s;text-decoration:none}
.sbtn:hover{background:var(--blue);border-color:var(--blue);color:#fff}

.sec-hd{display:flex;align-items:center;gap:10px;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--border)}
.sec-hd-label{font-family:var(--fs);font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--blue);display:flex;align-items:center;gap:7px}
.sec-hd-label::before{content:'';width:3px;height:13px;background:var(--blue);border-radius:2px;display:block}
.sec-hd-line{flex:1;height:1px;background:var(--border)}

.relcard{display:flex;gap:13px;padding:13px 0;border-bottom:1px solid var(--border-lt);transition:.15s;text-decoration:none}
.relcard:last-child{border-bottom:none;padding-bottom:0}
.relcard:hover .reltitle{color:var(--blue)}
.relcard:hover .relthumb{transform:scale(1.04)}
.relthumb-wrap{width:86px;height:60px;border-radius:var(--r);overflow:hidden;flex-shrink:0;background:var(--bg-input);border:1px solid var(--border)}
.relthumb{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.relph{width:86px;height:60px;border-radius:var(--r);background:var(--bg-input);border:1px solid var(--border);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--text-faint);font-size:.85rem}
.relcat{font-size:.58rem;font-weight:700;color:var(--blue);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:3px}
.reltitle{font-family:var(--fd);font-size:.84rem;font-weight:700;color:var(--text);line-height:1.32;transition:color .15s;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.reltime{font-size:.65rem;color:var(--text-muted);margin-top:4px;display:flex;align-items:center;gap:4px}

/* ── SIDEBAR ── */
.sb-sticky{
  position:sticky;
  top:56px;
  max-height:calc(100vh - 70px);
  overflow-y:auto;
  scrollbar-width:thin;
  scrollbar-color:var(--border) transparent;
  display:flex;
  flex-direction:column;
  gap:16px;
  padding-right:2px;
}
.sb-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--rm);overflow:hidden;box-shadow:var(--sh1)}
.sb-head{padding:12px 14px;border-bottom:1px solid var(--border)}
.sb-head-label{font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.11em;color:var(--blue);display:flex;align-items:center;gap:6px}
.sb-head-label::before{content:'';width:2px;height:12px;background:var(--blue);border-radius:2px}
.sb-item{display:flex;align-items:center;gap:11px;padding:10px 12px;border-bottom:1px solid var(--border-lt);text-decoration:none;transition:background .12s}
.sb-item:last-child{border-bottom:none}
.sb-item:hover{background:var(--bg-input)}
.sb-item:hover .sb-ttl{color:var(--blue)}
.sb-num{font-family:var(--fd);font-size:1.4rem;font-weight:900;color:var(--border);min-width:28px;line-height:1;flex-shrink:0;transition:color .15s;text-align:center}
.sb-item:hover .sb-num{color:var(--blue)}
.sb-info{flex:1;min-width:0}
.sb-cat{font-size:.57rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--blue);margin-bottom:2px;display:block}
.sb-ttl{font-family:var(--fd);font-size:.8rem;font-weight:700;color:var(--text);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;transition:color .15s}
.sb-meta{font-size:.62rem;color:var(--text-muted);margin-top:3px;display:flex;gap:8px;flex-wrap:wrap}

/* Tag pill */
.sb-tag{
  display:inline-flex;align-items:center;
  font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;
  padding:4px 11px;border-radius:99px;
  background:var(--bg-input);border:1.5px solid var(--border);
  color:var(--text-muted);transition:background .15s,color .15s,border-color .15s;
  text-decoration:none;
}
.sb-tag:hover{background:var(--blue);color:#fff;border-color:var(--blue)}

/* About links */
.sb-about-link{
  font-size:.72rem;font-weight:600;color:var(--blue);
  display:inline-flex;align-items:center;gap:4px;
  text-decoration:none;transition:opacity .15s;
}
.sb-about-link:hover{opacity:.7}

/* Social buttons */
.sb-social-btn{
  width:30px;height:30px;border-radius:50%;
  border:1.5px solid var(--border);color:var(--text-muted);
  background:var(--bg-input);display:flex;align-items:center;
  justify-content:center;font-size:.8rem;text-decoration:none;
  transition:background .15s,color .15s,border-color .15s;flex-shrink:0;
}
.sb-social-btn:hover{background:var(--blue);color:#fff;border-color:var(--blue)}

/* ══════════════════════════════════════
   KOMENTAR
══════════════════════════════════════ */
.komentar-section{margin-top:44px}

.km-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:24px;padding-bottom:14px;border-bottom:2px solid var(--border);flex-wrap:wrap}
.km-title{font-family:var(--fs);font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--blue);display:flex;align-items:center;gap:7px}
.km-title::before{content:'';width:3px;height:13px;background:var(--blue);border-radius:2px;display:block}
.km-count{font-size:.72rem;color:var(--text-muted);background:var(--bg-input);border:1px solid var(--border);border-radius:99px;padding:2px 11px;font-weight:600}

.km-alert{padding:11px 16px;border-radius:var(--r);font-size:.82rem;margin-bottom:18px;display:flex;align-items:flex-start;gap:9px;line-height:1.5}
.km-alert i{margin-top:1px;flex-shrink:0}
.km-alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
.km-alert-err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
.km-alert-info{background:var(--blue-soft);border:1px solid rgba(26,86,219,.2);color:var(--blue)}
[data-theme="dark"] .km-alert-ok{background:#052e16;border-color:#14532d;color:#4ade80}
[data-theme="dark"] .km-alert-err{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.3);color:#f87171}
[data-theme="dark"] .km-alert-info{background:rgba(77,142,247,.1);border-color:rgba(77,142,247,.25);color:#7eb3f8}

.km-form-wrap{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--rm);padding:22px;margin-bottom:28px;box-shadow:var(--sh1)}
.km-form-top{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.km-av{width:38px;height:38px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem;font-weight:800;flex-shrink:0}
.km-user-info{flex:1;min-width:0}
.km-user-name{font-size:.85rem;font-weight:700;color:var(--text)}
.km-user-role{font-size:.67rem;color:var(--text-muted)}
.km-textarea{
  width:100%;background:var(--bg-input);border:1.5px solid var(--border);
  color:var(--text);border-radius:var(--r);padding:12px 14px;
  font-size:.88rem;font-family:var(--fs);outline:none;transition:.15s;
  resize:vertical;min-height:100px;line-height:1.6;
}
.km-textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft)}
.km-textarea::placeholder{color:var(--text-faint)}
.km-form-footer{display:flex;align-items:center;justify-content:space-between;margin-top:10px;gap:10px;flex-wrap:wrap}
.km-charcount{font-size:.7rem;color:var(--text-faint)}
.km-charcount.warn{color:#f59e0b}
.km-charcount.danger{color:#ef4444}
.km-submit{background:var(--blue);color:#fff;border:none;border-radius:var(--r);padding:9px 20px;font-size:.83rem;font-weight:600;font-family:var(--fs);cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:.15s}
.km-submit:hover{background:var(--blue-d)}
.km-submit:disabled{opacity:.55;cursor:not-allowed}

.km-login-cta{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--rm);padding:22px;margin-bottom:28px;box-shadow:var(--sh1);display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.km-login-icon{width:44px;height:44px;border-radius:50%;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:1.1rem;flex-shrink:0}
.km-login-text{flex:1;min-width:160px}
.km-login-text p{font-size:.85rem;color:var(--text);font-weight:600;margin-bottom:2px}
.km-login-text small{font-size:.75rem;color:var(--text-muted)}
.km-login-btn{display:inline-flex;align-items:center;gap:6px;background:var(--blue);color:#fff;padding:9px 20px;border-radius:var(--r);font-size:.83rem;font-weight:600;transition:.15s;white-space:nowrap;flex-shrink:0}
.km-login-btn:hover{background:var(--blue-d);color:#fff}

.km-list{display:flex;flex-direction:column;gap:0}
.km-item{padding:18px 0;border-bottom:1px solid var(--border-lt);display:flex;gap:13px;animation:fadeSlide .3s ease}
@keyframes fadeSlide{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.km-item:last-child{border-bottom:none;padding-bottom:0}
.km-item-av{width:36px;height:36px;border-radius:50%;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.15);display:flex;align-items:center;justify-content:center;color:var(--blue);font-size:.78rem;font-weight:800;flex-shrink:0;align-self:flex-start;margin-top:1px}
.km-item-body{flex:1;min-width:0}
.km-item-head{display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap}
.km-item-name{font-size:.83rem;font-weight:700;color:var(--text)}
.km-item-time{font-size:.68rem;color:var(--text-muted);display:flex;align-items:center;gap:3px}
.km-item-text{font-size:.88rem;color:var(--text2);line-height:1.72;word-break:break-word}

.km-empty{text-align:center;padding:40px 20px;color:var(--text-muted)}
.km-empty i{font-size:2rem;opacity:.3;display:block;margin-bottom:10px}
.km-empty p{font-size:.84rem}

/* ── FOOTER ── */
.site-footer{background:var(--navy);border-top:3px solid var(--blue);padding:40px 0 20px;margin-top:20px}
.ft-logo{font-family:var(--fd);font-size:1.6rem;font-weight:900;color:#e8eef8;margin-bottom:8px}
.ft-logo em{color:var(--blue);font-style:normal}
.ft-desc{font-size:.8rem;color:rgba(255,255,255,.45);max-width:360px;line-height:1.7}
.ft-nav{display:flex;flex-wrap:wrap;gap:6px 24px;margin-top:20px}
.ft-nav a{font-size:.76rem;color:rgba(255,255,255,.35);transition:color .15s}
.ft-nav a:hover{color:rgba(255,255,255,.75)}
.ft-hr{border-color:rgba(255,255,255,.08);margin:20px 0 16px}
.ft-bot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ft-copy{font-size:.7rem;color:rgba(255,255,255,.25)}
.ft-links{display:flex;gap:18px}
.ft-links a{font-size:.7rem;color:rgba(255,255,255,.25);transition:color .15s}
.ft-links a:hover{color:rgba(255,255,255,.6)}

@media(max-width:600px){
  .logo{font-size:1.6rem}
  .hdr-right{gap:5px}
  .btn-a span{display:none}
  .km-login-cta{flex-direction:column;align-items:flex-start;gap:14px}
  .km-login-btn{width:100%;justify-content:center}
  .km-form-footer{flex-direction:column;align-items:flex-start}
  .km-submit{width:100%;justify-content:center}
}
</style>
</head>
<body>

<div id="prog"></div>

<!-- TICKER -->
<div class="ticker">
  <div class="container">
    <div class="ticker-inner">
      <span class="ticker-label">Breaking</span>
      <div class="ticker-scroll">
        <div class="ticker-track">
          <?php
          $tq = mysqli_query($koneksi, "SELECT id_artikel, judul FROM artikel ORDER BY tgl_posting DESC LIMIT 8");
          $ti = []; while ($tr = mysqli_fetch_assoc($tq)) $ti[] = $tr;
          foreach (array_merge($ti, $ti) as $t): ?>
          <a href="detail.php?id=<?= $t['id_artikel'] ?>" class="ticker-item"><?= htmlspecialchars($t['judul']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- HEADER -->
<header class="site-header">
  <div class="container hdr-inner">
    <div>
      <a href="index.php" class="logo">Liy<em>News</em></a>
      <div class="logo-sub">Berita Terpercaya &amp; Terkini Indonesia</div>
    </div>
    <div class="hdr-right">
      <button class="btn-icon" id="themeBtn" title="Ganti tema"><i class="bi bi-moon-fill"></i></button>
      <?php if ($isLogin): ?>
      <div class="u-menu" id="uMenu">
        <div class="u-chip" id="uChip">
          <div class="u-av"><?= $userInit ?></div>
          <span class="u-nm"><?= htmlspecialchars($userNama) ?></span>
          <i class="bi bi-chevron-down arr"></i>
        </div>
        <div class="u-drop" id="uDrop">
          <div class="dd-hd">
            <div class="dd-hd-name"><?= htmlspecialchars($userNama) ?></div>
            <div class="dd-hd-role"><i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($userRole) ?></div>
          </div>
          <?php if ($userRole==='admin'||$userRole==='penulis'): ?>
          <a href="<?= $dashLink ?>" class="dd-it"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <?php endif; ?>
          <a href="../admin/profileadmin.php" class="dd-it"><i class="bi bi-person-circle"></i> Profil Saya</a>
          <div class="dd-sep"></div>
          <a href="logout.php" class="dd-it out"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
      </div>
      <?php else: ?>
      <a href="../login.php"    class="btn-a btn-a-ghost"><span>Masuk</span></a>
      <a href="../register.php" class="btn-a btn-a-solid"><span>Daftar</span></a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- CATEGORY NAV -->
<nav class="cat-nav">
  <div class="container">
    <div class="cat-nav-wrap">
      <a href="index.php" class="cat-lnk">Semua</a>
      <?php foreach ($kategoriList as $k): ?>
      <a href="index.php?kategori=<?= $k['id_kategori'] ?>"
         class="cat-lnk <?= $artikel['kategori_id']==$k['id_kategori']?'on':'' ?>">
        <?= htmlspecialchars($k['nama_kategori']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>

<!-- PAGE -->
<div class="page">
  <div class="container">
    <div class="pgrid">

      <!-- ═══════════════ ARTIKEL ═══════════════ -->
      <article class="art" id="artMain">

        <nav class="bc">
          <a href="index.php">Beranda</a>
          <span class="sep">›</span>
          <a href="index.php?kategori=<?= $artikel['kategori_id'] ?>"><?= htmlspecialchars($artikel['nama_kategori']) ?></a>
          <span class="sep">›</span>
          <span style="color:var(--text-muted)"><?= htmlspecialchars(mb_substr($artikel['judul'],0,50)) ?>…</span>
        </nav>

        <span class="badge-cat"><?= htmlspecialchars($artikel['nama_kategori']) ?></span>
        <h1 class="art-title"><?= htmlspecialchars($artikel['judul']) ?></h1>

        <div class="art-meta">
          <div class="mi">
            <i class="bi bi-person-circle"></i>
            <span class="mi-author"><?= htmlspecialchars(!empty($artikel['nama_penulis'])?$artikel['nama_penulis']:'Redaksi') ?></span>
          </div>
          <div class="mi">
            <i class="bi bi-calendar3"></i>
            <span><?= tgl($artikel['tgl_posting']) ?></span>
          </div>
          <div class="mi">
            <i class="bi bi-clock"></i>
            <span><?= ago($artikel['tgl_posting']) ?></span>
          </div>
          <div class="mi">
            <i class="bi bi-chat-dots"></i>
            <span><?= $jumlahKomentar ?> komentar</span>
          </div>
        </div>

        <?php if ($gambar): ?>
        <div class="hero">
          <img src="<?= htmlspecialchars($gambar) ?>"
               alt="<?= htmlspecialchars($artikel['judul']) ?>"
               onerror="this.parentElement.style.display='none'">
        </div>
        <?php else: ?>
        <div class="hero-ph"><i class="bi bi-image"></i></div>
        <?php endif; ?>

        <?php if ($sumberUrl): ?>
        <div class="srcbar">
          <i class="bi bi-link-45deg lnk-ico"></i>
          <div>
            Sumber: <a class="slink" href="<?= htmlspecialchars($sumberUrl) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(parse_url($sumberUrl, PHP_URL_HOST)) ?></a>
            <?php if ($scrapeStatus !== 'failed'): ?>
            <a href="detail.php?id=<?= $id ?>&refresh=1" class="rbtn" title="Muat ulang"><i class="bi bi-arrow-clockwise"></i> Refresh</a>
            <?php endif; ?>
          </div>
          <?php if ($scrapeStatus==='scraped'): ?>
            <span class="sbadge scraped"><i class="bi bi-check-circle-fill"></i> Artikel penuh</span>
          <?php elseif ($scrapeStatus==='cache'): ?>
            <span class="sbadge cache"><i class="bi bi-lightning-charge-fill"></i> Dari cache</span>
          <?php elseif ($scrapeStatus==='failed'): ?>
            <span class="sbadge failed"><i class="bi bi-exclamation-circle-fill"></i> Gagal scrape</span>
          <?php else: ?>
            <span class="sbadge db"><i class="bi bi-database-fill"></i> Ringkasan RSS</span>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="abody"><?= renderIsi($isiTampil) ?></div>

        <?php if ($sumberUrl && $scrapeStatus==='failed'): ?>
        <div class="fcta">
          <p>Isi artikel lengkap tidak dapat dimuat secara otomatis.</p>
          <a href="<?= htmlspecialchars($sumberUrl) ?>" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i> Baca di Sumber Asli
          </a>
        </div>
        <?php elseif ($sumberUrl): ?>
        <div class="srcfoot">
          <a href="<?= htmlspecialchars($sumberUrl) ?>" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i> Lihat artikel asli
          </a>
        </div>
        <?php endif; ?>

        <!-- SHARE BAR -->
        <div class="sharebar">
          <span class="slabel">Bagikan</span>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $pageUrl ?>" target="_blank" rel="noopener" class="sbtn"><i class="bi bi-facebook"></i></a>
          <a href="https://twitter.com/intent/tweet?url=<?= $pageUrl ?>&text=<?= $pageTitle ?>" target="_blank" rel="noopener" class="sbtn"><i class="bi bi-twitter-x"></i></a>
          <a href="https://wa.me/?text=<?= $pageTitle.'%20'.$pageUrl ?>" target="_blank" rel="noopener" class="sbtn"><i class="bi bi-whatsapp"></i></a>
          <button onclick="navigator.clipboard.writeText(window.location.href);this.innerHTML='<i class=\'bi bi-check-lg\'></i>';setTimeout(()=>this.innerHTML='<i class=\'bi bi-link-45deg\'></i>',1500)" class="sbtn"><i class="bi bi-link-45deg"></i></button>
        </div>

        <!-- BERITA TERKAIT -->
        <?php if (mysqli_num_rows($relQ) > 0): ?>
        <div class="sec-hd">
          <div class="sec-hd-label">Berita Terkait</div>
          <div class="sec-hd-line"></div>
        </div>
        <?php while ($r = mysqli_fetch_assoc($relQ)):
          $rf  = !empty($r['thumbnail'])?$r['thumbnail']:($r['gambar']??'');
          $ri  = (!empty($rf)&&filter_var($rf,FILTER_VALIDATE_URL))?$rf:((!empty($rf)&&file_exists("../uploads/$rf"))?"../uploads/$rf":null);
        ?>
        <a href="detail.php?id=<?= $r['id_artikel'] ?>" class="relcard">
          <?php if ($ri): ?>
          <div class="relthumb-wrap"><img class="relthumb" src="<?= htmlspecialchars($ri) ?>" alt=""></div>
          <?php else: ?>
          <div class="relph"><i class="bi bi-image"></i></div>
          <?php endif; ?>
          <div style="min-width:0">
            <span class="relcat"><?= htmlspecialchars($r['nama_kategori']) ?></span>
            <div class="reltitle"><?= htmlspecialchars($r['judul']) ?></div>
            <div class="reltime"><i class="bi bi-clock"></i><?= ago($r['tgl_posting']) ?></div>
          </div>
        </a>
        <?php endwhile; ?>
        <?php endif; ?>

        <!-- ═══════════════ KOMENTAR ═══════════════ -->
        <section class="komentar-section" id="komentar">

          <div class="km-header">
            <div class="km-title">Komentar</div>
            <span class="km-count"><?= $jumlahKomentar ?> komentar</span>
          </div>

          <?php if ($komentarMsg): ?>
          <div class="km-alert km-alert-ok">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= htmlspecialchars($komentarMsg) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($komentarError): ?>
          <div class="km-alert km-alert-err">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= htmlspecialchars($komentarError) ?></span>
          </div>
          <?php endif; ?>

          <?php if ($isLogin): ?>
          <div class="km-form-wrap">
            <div class="km-form-top">
              <div class="km-av"><?= $userInit ?></div>
              <div class="km-user-info">
                <div class="km-user-name"><?= htmlspecialchars($userNama) ?></div>
                <div class="km-user-role"><?= ucfirst(htmlspecialchars($userRole)) ?></div>
              </div>
            </div>
            <form method="POST" action="detail.php?id=<?= $id ?>#komentar" id="kmForm">
              <input type="hidden" name="aksi_komentar" value="1">
              <textarea
                class="km-textarea"
                name="isi_komentar"
                id="kmTextarea"
                placeholder="Tulis komentarmu di sini…"
                maxlength="1000"
                required
              ></textarea>
              <div class="km-form-footer">
                <span class="km-charcount" id="kmChar">0 / 1000</span>
                <button type="submit" class="km-submit" id="kmSubmit">
                  <i class="bi bi-send"></i> Kirim Komentar
                </button>
              </div>
            </form>
            <div class="km-alert km-alert-info" style="margin-top:14px;margin-bottom:0">
              <i class="bi bi-info-circle-fill"></i>
              <span>Komentarmu akan ditampilkan setelah disetujui admin.</span>
            </div>
          </div>
          <?php else: ?>
          <div class="km-login-cta">
            <div class="km-login-icon"><i class="bi bi-chat-heart"></i></div>
            <div class="km-login-text">
              <p>Ingin ikut berdiskusi?</p>
              <small>Login untuk menulis komentar di artikel ini.</small>
            </div>
            <a href="../login.php?redirect=<?= urlencode('public/detail.php?id='.$id.'#komentar') ?>" class="km-login-btn">
              <i class="bi bi-box-arrow-in-right"></i> Login Sekarang
            </a>
          </div>
          <?php endif; ?>

          <?php if ($jumlahKomentar > 0): ?>
          <div class="km-list">
            <?php while ($km = mysqli_fetch_assoc($komentarQ)):
              $kmInit = strtoupper(substr($km['username'], 0, 1));
            ?>
            <div class="km-item">
              <div class="km-item-av"><?= $kmInit ?></div>
              <div class="km-item-body">
                <div class="km-item-head">
                  <span class="km-item-name"><?= htmlspecialchars($km['username']) ?></span>
                  <span class="km-item-time">
                    <i class="bi bi-clock"></i>
                    <?= ago($km['tgl_komentar']) ?>
                  </span>
                </div>
                <div class="km-item-text"><?= nl2br(htmlspecialchars($km['isi'])) ?></div>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
          <?php else: ?>
          <div class="km-empty">
            <i class="bi bi-chat-dots"></i>
            <p>Belum ada komentar. Jadilah yang pertama berkomentar!</p>
          </div>
          <?php endif; ?>

        </section>

      </article>

      <!-- ═══════════════ SIDEBAR ═══════════════ -->
      <aside>
        <div class="sb-sticky">

          <!-- BERITA LAINNYA -->
          <?php
          $otherQ2 = mysqli_query($koneksi,
            "SELECT a.*, k.nama_kategori FROM artikel a
             JOIN kategori k ON a.kategori_id = k.id_kategori
             WHERE a.id_artikel != $id
             ORDER BY a.tgl_posting DESC LIMIT 8");
          if (mysqli_num_rows($otherQ2) > 0):
          ?>
          <div class="sb-card">
            <div class="sb-head"><div class="sb-head-label">Berita Lainnya</div></div>
            <?php $sn = 1; while ($o = mysqli_fetch_assoc($otherQ2)):
              $of = !empty($o['thumbnail']) ? $o['thumbnail'] : ($o['gambar'] ?? '');
              $oi = (!empty($of) && filter_var($of, FILTER_VALIDATE_URL)) ? $of
                  : ((!empty($of) && file_exists("../uploads/$of")) ? "../uploads/$of" : null);
            ?>
            <a href="detail.php?id=<?= $o['id_artikel'] ?>" class="sb-item">
              <?php if ($oi): ?>
              <div style="width:58px;height:42px;border-radius:5px;overflow:hidden;flex-shrink:0;background:var(--bg-input);border:1px solid var(--border)">
                <img src="<?= htmlspecialchars($oi) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
              </div>
              <?php else: ?>
              <div class="sb-num"><?= str_pad($sn, 2, '0', STR_PAD_LEFT) ?></div>
              <?php endif; ?>
              <div class="sb-info">
                <span class="sb-cat"><?= htmlspecialchars($o['nama_kategori']) ?></span>
                <div class="sb-ttl"><?= htmlspecialchars($o['judul']) ?></div>
                <div class="sb-meta"><i class="bi bi-clock"></i><?= ago($o['tgl_posting']) ?></div>
              </div>
            </a>
            <?php $sn++; endwhile; ?>
          </div>
          <?php endif; ?>

          <!-- TERPOPULER -->
          <?php
          $popQ = mysqli_query($koneksi,
            "SELECT a.*, k.nama_kategori, COUNT(km.id_komentar) AS jml_komentar
             FROM artikel a
             JOIN kategori k ON a.kategori_id = k.id_kategori
             LEFT JOIN komentar km ON km.artikel_id = a.id_artikel AND km.status = 'approved'
             WHERE a.id_artikel != $id
             GROUP BY a.id_artikel
             ORDER BY jml_komentar DESC, a.tgl_posting DESC
             LIMIT 5");
          if ($popQ && mysqli_num_rows($popQ) > 0):
          ?>
          <div class="sb-card">
            <div class="sb-head">
              <div class="sb-head-label">
                <i class="bi bi-fire" style="color:#e05c2a;font-size:.82rem"></i>
                Terpopuler
              </div>
            </div>
            <?php while ($pop = mysqli_fetch_assoc($popQ)):
              $pf = !empty($pop['thumbnail']) ? $pop['thumbnail'] : ($pop['gambar'] ?? '');
              $pi = (!empty($pf) && filter_var($pf, FILTER_VALIDATE_URL)) ? $pf
                  : ((!empty($pf) && file_exists("../uploads/$pf")) ? "../uploads/$pf" : null);
            ?>
            <a href="detail.php?id=<?= $pop['id_artikel'] ?>" class="sb-item">
              <?php if ($pi): ?>
              <div style="width:58px;height:42px;border-radius:5px;overflow:hidden;flex-shrink:0;background:var(--bg-input);border:1px solid var(--border)">
                <img src="<?= htmlspecialchars($pi) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
              </div>
              <?php else: ?>
              <div style="width:58px;height:42px;border-radius:5px;flex-shrink:0;background:var(--bg-input);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-faint);font-size:.78rem">
                <i class="bi bi-image"></i>
              </div>
              <?php endif; ?>
              <div class="sb-info">
                <span class="sb-cat"><?= htmlspecialchars($pop['nama_kategori']) ?></span>
                <div class="sb-ttl"><?= htmlspecialchars($pop['judul']) ?></div>
                <div class="sb-meta">
                  <span><i class="bi bi-chat-dots"></i> <?= $pop['jml_komentar'] ?></span>
                  <span><i class="bi bi-clock"></i> <?= ago($pop['tgl_posting']) ?></span>
                </div>
              </div>
            </a>
            <?php endwhile; ?>
          </div>
          <?php endif; ?>

          <!-- TOPIK -->
          <?php
          $tagQ = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
          if ($tagQ && mysqli_num_rows($tagQ) > 0):
          ?>
          <div class="sb-card">
            <div class="sb-head"><div class="sb-head-label">Topik</div></div>
            <div style="padding:12px 12px 10px;display:flex;flex-wrap:wrap;gap:6px">
              <?php while ($tag = mysqli_fetch_assoc($tagQ)): ?>
              <a href="index.php?kategori=<?= $tag['id_kategori'] ?>" class="sb-tag">
                <?= htmlspecialchars($tag['nama_kategori']) ?>
              </a>
              <?php endwhile; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- TENTANG LIYNEWS -->
          <div class="sb-card">
            <div class="sb-head"><div class="sb-head-label">Tentang LiyNews</div></div>
            <div style="padding:14px">
              <div style="font-family:var(--fd);font-size:1.1rem;font-weight:900;color:var(--text);margin-bottom:6px">
                Liy<span style="color:var(--blue)">News</span>
              </div>
              <p style="font-size:.78rem;color:var(--text-muted);line-height:1.7;margin-bottom:12px">
                Portal berita terpercaya yang menyajikan informasi akurat, terkini, dan berimbang untuk masyarakat Indonesia.
              </p>
              <div style="display:flex;align-items:center;gap:14px;padding-bottom:12px;border-bottom:1px solid var(--border-lt)">
                <a href="tentang.php" class="sb-about-link"><i class="bi bi-info-circle"></i> Tentang</a>
                <a href="kontak.php"  class="sb-about-link"><i class="bi bi-envelope"></i> Kontak</a>
                <a href="redaksi.php" class="sb-about-link"><i class="bi bi-people"></i> Redaksi</a>
              </div>
              <div style="display:flex;gap:8px;margin-top:12px">
                <?php foreach ([
                  ['bi-facebook','Facebook'],
                  ['bi-twitter-x','Twitter'],
                  ['bi-instagram','Instagram'],
                  ['bi-youtube','YouTube'],
                ] as [$icon, $title]): ?>
                <a href="#" title="<?= $title ?>" class="sb-social-btn">
                  <i class="bi <?= $icon ?>"></i>
                </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div>
      </aside>

    </div>
  </div>
</div>

<footer class="site-footer">
  <div class="container">
    <div class="ft-logo">Liy<em>News</em></div>
    <p class="ft-desc">Menyajikan berita terpercaya, akurat, dan terkini untuk seluruh masyarakat Indonesia.</p>
    <nav class="ft-nav">
      <a href="tentang.php">Tentang Kami</a>
      <a href="redaksi.php">Redaksi</a>
      <a href="pedoman.php">Pedoman Media Siber</a>
      <a href="privasi.php">Kebijakan Privasi</a>
      <a href="iklan.php">Iklan</a>
      <a href="kontak.php">Kontak</a>
    </nav>
    <hr class="ft-hr">
    <div class="ft-bot">
      <div class="ft-copy">&copy; <?= date('Y') ?> LiyNews. Semua hak dilindungi.</div>
      <div class="ft-links">
        <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" title="Twitter/X"><i class="bi bi-twitter-x"></i></a>
        <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" title="YouTube"><i class="bi bi-youtube"></i></a>
      </div>
    </div>
  </div>
</footer>

<script>
// Theme toggle
const html = document.documentElement;
const thBtn = document.getElementById('themeBtn');
function applyTheme(t) {
  html.setAttribute('data-theme', t);
  thBtn.innerHTML = t === 'dark' ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-fill"></i>';
}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click', () => {
  const n = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  localStorage.setItem('pb_theme', n);
  applyTheme(n);
});

// User dropdown
const uc = document.getElementById('uChip'), ud = document.getElementById('uDrop');
if (uc && ud) {
  uc.addEventListener('click', e => {
    e.stopPropagation();
    const o = ud.classList.toggle('open');
    uc.classList.toggle('open', o);
  });
  document.addEventListener('click', e => {
    if (!document.getElementById('uMenu')?.contains(e.target)) {
      ud.classList.remove('open');
      uc.classList.remove('open');
    }
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { ud.classList.remove('open'); uc.classList.remove('open'); }
  });
}

// Reading progress bar
const prog = document.getElementById('prog'), art = document.getElementById('artMain');
function upd() {
  const r = art.getBoundingClientRect(), tot = art.offsetHeight - innerHeight, sc = Math.max(0, -r.top);
  prog.style.width = (tot > 0 ? Math.min(100, sc / tot * 100) : 0) + '%';
}
window.addEventListener('scroll', upd, { passive: true });
upd();

// Komentar char counter
const kmTA = document.getElementById('kmTextarea');
const kmChar = document.getElementById('kmChar');
const kmSubmit = document.getElementById('kmSubmit');
if (kmTA) {
  kmTA.addEventListener('input', function() {
    const len = this.value.length;
    kmChar.textContent = len + ' / 1000';
    kmChar.className = 'km-charcount' + (len > 900 ? ' danger' : len > 750 ? ' warn' : '');
    kmSubmit.disabled = len === 0 || len > 1000;
  });
  kmTA.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      document.getElementById('kmForm').submit();
    }
  });
}
</script>
</body>
</html>