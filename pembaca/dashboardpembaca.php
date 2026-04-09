<?php
session_start();
include '../config/koneksi.php';

// Proteksi: hanya untuk pembaca yang sudah login
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'pembaca') {
    header("Location: ../login.php");
    exit;
}

$isLogin  = true;
$userId   = $_SESSION['user_id'] ?? 0;
$userNama = $_SESSION['user_nama'] ?? $_SESSION['user_username'] ?? 'Pembaca';
$userRole = 'pembaca';
$userInit = strtoupper(substr($_SESSION['user_username'] ?? 'P', 0, 1));

if (!function_exists('tgl')) {
    function tgl($t) {
        $b = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
              7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $p = explode('-', date('Y-m-d', strtotime($t)));
        return $p[2] . ' ' . $b[(int)$p[1]] . ' ' . $p[0];
    }
}
if (!function_exists('ago')) {
    function ago($t) {
        $d = time() - strtotime($t);
        if ($d < 3600)  return floor($d / 60) . ' menit lalu';
        if ($d < 86400) return floor($d / 3600) . ' jam lalu';
        return floor($d / 86400) . ' hari lalu';
    }
}
if (!function_exists('img')) {
    function img($row) {
        $f = !empty($row['thumbnail']) ? $row['thumbnail'] : (!empty($row['gambar']) ? $row['gambar'] : '');
        if ($f) {
            if (filter_var($f, FILTER_VALIDATE_URL)) return $f;
            if (file_exists("../uploads/$f")) return "../uploads/$f";
        }
        return null;
    }
}

/* ── BOOKMARK & HISTORY ACTIONS ── */
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_bm (user_id, artikel_id)
)");
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS reading_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    artikel_id INT NOT NULL,
    read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rh (user_id, read_at)
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_bookmark') {
    header('Content-Type: application/json');
    $aid = (int)($_POST['artikel_id'] ?? 0);
    if ($aid && $userId) {
        $chk = mysqli_query($koneksi, "SELECT id FROM bookmarks WHERE user_id=$userId AND artikel_id=$aid");
        if (mysqli_num_rows($chk) > 0) {
            mysqli_query($koneksi, "DELETE FROM bookmarks WHERE user_id=$userId AND artikel_id=$aid");
            echo json_encode(['status'=>'removed']);
        } else {
            mysqli_query($koneksi, "INSERT IGNORE INTO bookmarks (user_id,artikel_id) VALUES ($userId,$aid)");
            echo json_encode(['status'=>'added']);
        }
    } else {
        echo json_encode(['status'=>'error']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_history') {
    header('Content-Type: application/json');
    $aid = (int)($_POST['artikel_id'] ?? 0);
    if ($aid && $userId) {
        mysqli_query($koneksi, "DELETE FROM reading_history WHERE user_id=$userId AND artikel_id=$aid");
        echo json_encode(['status'=>'ok']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_history') {
    header('Content-Type: application/json');
    if ($userId) {
        mysqli_query($koneksi, "DELETE FROM reading_history WHERE user_id=$userId");
        echo json_encode(['status'=>'ok']);
    }
    exit;
}

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['beranda','bookmark','history']) ? $_GET['tab'] : 'beranda';

$filter   = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$search   = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchEs = $search ? mysqli_real_escape_string($koneksi, $search) : '';

$perPage = 12;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$colCheck = mysqli_query($koneksi, "SHOW COLUMNS FROM artikel LIKE 'status'");
$w  = mysqli_num_rows($colCheck) > 0 ? "WHERE a.status='publish'" : "WHERE 1=1";
$wk = $filter   ? " AND a.kategori_id='$filter'" : "";
$ws = $searchEs ? " AND (a.judul LIKE '%$searchEs%' OR a.isi LIKE '%$searchEs%')" : "";

$bmIds = [];
if ($userId) {
    $bmQ = mysqli_query($koneksi, "SELECT artikel_id FROM bookmarks WHERE user_id=$userId");
    while ($bm = mysqli_fetch_assoc($bmQ)) $bmIds[] = $bm['artikel_id'];
}

/* ── DATA BERANDA ── */
$countRes   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM artikel a $w $wk $ws");
$totalCount = (int)mysqli_fetch_assoc($countRes)['total'];
if ($page === 1) { $fetchLimit = 13; $fetchOffset = 0; }
else { $fetchLimit = $perPage; $fetchOffset = 13 + ($page - 2) * $perPage; }
$totalPages = 1 + ceil(max(0, $totalCount - 13) / $perPage);

$rows = mysqli_query($koneksi,
    "SELECT a.*, k.nama_kategori FROM artikel a
     JOIN kategori k ON a.kategori_id = k.id_kategori
     $w $wk $ws ORDER BY a.tgl_posting DESC
     LIMIT $fetchLimit OFFSET $fetchOffset");
$berita = [];
while ($r = mysqli_fetch_assoc($rows)) $berita[] = $r;

/* ── DATA BOOKMARK ── */
$bmPage = isset($_GET['bm_page']) ? max(1,(int)$_GET['bm_page']) : 1;
$bmPer  = 12;
$bmOff  = ($bmPage-1)*$bmPer;
$bmTotal = 0; $bmPages = 1; $bmList = [];
if ($userId) {
    $bmTotal = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM bookmarks b JOIN artikel a ON b.artikel_id=a.id_artikel JOIN kategori k ON a.kategori_id=k.id_kategori $w AND b.user_id=$userId"))['t'];
    $bmPages = max(1, ceil($bmTotal/$bmPer));
    $bmQ2 = mysqli_query($koneksi, "SELECT a.*, k.nama_kategori, b.created_at as bm_date FROM bookmarks b JOIN artikel a ON b.artikel_id=a.id_artikel JOIN kategori k ON a.kategori_id=k.id_kategori $w AND b.user_id=$userId ORDER BY b.created_at DESC LIMIT $bmPer OFFSET $bmOff");
    while ($r = mysqli_fetch_assoc($bmQ2)) $bmList[] = $r;
}

/* ── DATA HISTORY ── */
$histPage = isset($_GET['h_page']) ? max(1,(int)$_GET['h_page']) : 1;
$histPer  = 15;
$histOff  = ($histPage-1)*$histPer;
$histTotal = 0; $histPages = 1; $histList = [];
if ($userId) {
    $histTotal = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(DISTINCT rh.artikel_id) as t FROM reading_history rh JOIN artikel a ON rh.artikel_id=a.id_artikel $w AND rh.user_id=$userId"))['t'];
    $histPages = max(1, ceil($histTotal/$histPer));
    $histQ = mysqli_query($koneksi, "SELECT a.*, k.nama_kategori, MAX(rh.read_at) as last_read FROM reading_history rh JOIN artikel a ON rh.artikel_id=a.id_artikel JOIN kategori k ON a.kategori_id=k.id_kategori $w AND rh.user_id=$userId GROUP BY rh.artikel_id ORDER BY last_read DESC LIMIT $histPer OFFSET $histOff");
    while ($r = mysqli_fetch_assoc($histQ)) $histList[] = $r;
}

/* ── KATEGORI & SIDEBAR ── */
$katRows = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kats = [];
while ($k = mysqli_fetch_assoc($katRows)) $kats[] = $k;

$gayaHidupId = null; $subKatGayaHidup = []; $activeKatNama = '';
foreach ($kats as $k) {
    if (strcasecmp($k['nama_kategori'], 'Gaya Hidup') === 0) $gayaHidupId = $k['id_kategori'];
    if ($k['id_kategori'] == $filter) $activeKatNama = $k['nama_kategori'];
}
$subNamaGH = ['kesehatan','kuliner','travel','food','health','wisata','religi'];
foreach ($kats as $k) {
    if (in_array(strtolower($k['nama_kategori']), $subNamaGH)) $subKatGayaHidup[] = $k;
}
$gayaHidupNamaList = array_map(fn($k) => strtolower($k['nama_kategori']), $subKatGayaHidup);
$gayaHidupNamaList[] = 'gaya hidup';
$isGayaHidupPage = in_array(strtolower($activeKatNama), $gayaHidupNamaList);

if ($isGayaHidupPage) {
    $ghPerPage = 12; $ghOffset = ($page - 1) * $ghPerPage;
    $ghKatIds = [];
    if ($filter) { $ghKatIds[] = $filter; }
    else if ($gayaHidupId) { $ghKatIds[] = $gayaHidupId; foreach ($subKatGayaHidup as $sk) $ghKatIds[] = $sk['id_kategori']; }
    $wkGH = !empty($ghKatIds) ? " AND a.kategori_id IN (" . implode(',', array_map('intval', $ghKatIds)) . ")" : "";
    $wsGH = $searchEs ? " AND (a.judul LIKE '%$searchEs%' OR a.isi LIKE '%$searchEs%')" : "";
    $ghTotal = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM artikel a $w $wkGH $wsGH"))['t'];
    $ghPages = max(1, ceil($ghTotal / $ghPerPage));
    $ghRows  = mysqli_query($koneksi, "SELECT a.*, k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id = k.id_kategori $w $wkGH $wsGH ORDER BY a.tgl_posting DESC LIMIT $ghPerPage OFFSET $ghOffset");
    $berita = [];
    while ($r = mysqli_fetch_assoc($ghRows)) $berita[] = $r;
    $totalCount = $ghTotal; $totalPages = $ghPages;
}

$sideRows = mysqli_query($koneksi,
    "SELECT a.id_artikel, a.judul, a.tgl_posting, a.thumbnail, k.nama_kategori
     FROM artikel a JOIN kategori k ON a.kategori_id = k.id_kategori
     $w ORDER BY a.tgl_posting DESC LIMIT 6");

$ghIcons = ['Gaya Hidup'=>'bi-stars','Kesehatan'=>'bi-heart-pulse','Kuliner'=>'bi-cup-hot','Travel'=>'bi-airplane','Religi'=>'bi-moon-stars'];
$ghDescs  = ['Gaya Hidup'=>'Kesehatan, kuliner, travel & tren terkini','Kesehatan'=>'Info kesehatan, medis & tips hidup sehat','Kuliner'=>'Resep, restoran & dunia kuliner Indonesia','Travel'=>'Destinasi wisata & tips perjalanan terbaik','Religi'=>'Kajian Islam, doa, ibadah & info keagamaan'];

if (!function_exists('pageUrl')) {
    function pageUrl($p, $filter, $search) {
        $params = ['page' => $p, 'tab' => 'beranda'];
        if ($filter) $params['kategori'] = $filter;
        if ($search) $params['q'] = $search;
        return 'dashboardpembaca.php?' . http_build_query($params);
    }
}
if (!function_exists('pgUrl')) {
    function pgUrl($p, $bp) { return 'dashboardpembaca.php?' . http_build_query(array_merge($bp, ['page' => $p])); }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<title>LiyNews — Dashboard Pembaca</title>
<script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Source+Serif+4:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════
   VARIABLES
═══════════════════════════════════════════════════ */
:root {
  --blue: #1a56db;
  --blue-d: #1044b8;
  --blue-soft: rgba(26,86,219,.1);
  --blue-xs: rgba(26,86,219,.06);
  --bg: #f4f6fb;
  --bg-card: #ffffff;
  --bg-alt: #edf0f8;
  --border: #d8dde8;
  --border-lt: #eaecf5;
  --text: #0d1520;
  --text2: #2d3748;
  --text-muted: #536380;
  --text-faint: #8fa3bf;
  --navy: #0b1929;
  --navy2: #0f2237;
  --navy-border: rgba(255,255,255,.09);
  --sh1: 0 1px 4px rgba(13,21,32,.07);
  --sh2: 0 4px 18px rgba(13,21,32,.10);
  --sh3: 0 10px 36px rgba(13,21,32,.14);
  --fd: 'Playfair Display', Georgia, serif;
  --fs: 'DM Sans', system-ui, sans-serif;
  --fb: 'Source Serif 4', Georgia, serif;
  --r: 8px;
  --rm: 12px;
  --hdr-h: 96px;
  --cat-h: 0px;
  --trend-h: 36px;

  --bm-color: #e05c2a;
  --bm-soft: rgba(224,92,42,.1);
  --hist-color: #6941c6;
  --hist-soft: rgba(105,65,198,.1);
}
[data-theme="dark"] {
  --blue: #4d8ef7;
  --blue-d: #3a7be8;
  --blue-soft: rgba(77,142,247,.13);
  --blue-xs: rgba(77,142,247,.06);
  --bg: #090f1c;
  --bg-card: #0f1e35;
  --bg-alt: #0c1628;
  --border: #1c3154;
  --border-lt: #152642;
  --text: #e4edf8;
  --text2: #b0c4de;
  --text-muted: #6888aa;
  --text-faint: #2e4a6a;
  --navy: #050d1a;
  --navy2: #080f1e;
  --navy-border: rgba(255,255,255,.07);
  --sh1: 0 1px 4px rgba(0,0,0,.35);
  --sh2: 0 4px 18px rgba(0,0,0,.45);
  --sh3: 0 10px 36px rgba(0,0,0,.55);
  --bm-color: #f07845;
  --bm-soft: rgba(240,120,69,.12);
  --hist-color: #9b78f4;
  --hist-soft: rgba(155,120,244,.12);
}

/* ═══════════════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════════════ */
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; overflow-x: hidden; -webkit-text-size-adjust: 100%; }
body {
  font-family: var(--fs);
  background: var(--bg);
  color: var(--text2);
  font-size: 15px;
  line-height: 1.65;
  overflow-x: hidden;
  -webkit-tap-highlight-color: transparent;
  transition: background .25s, color .25s;
}
a { color: inherit; text-decoration: none; }
img { display: block; max-width: 100%; object-fit: cover; }
button { font-family: var(--fs); cursor: pointer; }

.container {
  width: 100%;
  max-width: 1300px;
  margin: 0 auto;
  padding-left: clamp(14px, 3.5vw, 32px);
  padding-right: clamp(14px, 3.5vw, 32px);
}

/* ═══════════════════════════════════════════════════
   TICKER
═══════════════════════════════════════════════════ */
.ticker { background: var(--blue); overflow: hidden; width: 100%; }
[data-theme="dark"] .ticker { background: #0c2060; }
.ticker-inner { display: flex; align-items: stretch; height: 34px; }
.ticker-label {
  background: rgba(0,0,0,.22); color: #fff; font-size: .62rem; font-weight: 700;
  letter-spacing: .14em; text-transform: uppercase; padding: 0 20px 0 16px;
  display: flex; align-items: center; white-space: nowrap; flex-shrink: 0;
  clip-path: polygon(0 0,calc(100% - 10px) 0,100% 50%,calc(100% - 10px) 100%,0 100%);
  margin-right: 6px;
}
.ticker-scroll { overflow: hidden; flex: 1; display: flex; align-items: center; min-width: 0; }
.ticker-track { display: flex; width: max-content; animation: tick 55s linear infinite; }
.ticker-track:hover { animation-play-state: paused; }
.ticker-item {
  color: rgba(255,255,255,.92); font-size: .76rem; font-weight: 500;
  white-space: nowrap; padding: 0 38px 0 0; display: flex; align-items: center;
  gap: 9px; transition: color .15s;
}
.ticker-item:hover { color: #fff; }
.ticker-item::before { content: '◆'; font-size: .36rem; opacity: .5; flex-shrink: 0; }
@keyframes tick { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

/* ═══════════════════════════════════════════════════
   HEADER
═══════════════════════════════════════════════════ */
.site-header {
  background: var(--navy); width: 100%; position: sticky; top: 0; z-index: 600;
  border-bottom: 3px solid var(--blue); box-shadow: 0 2px 16px rgba(0,0,0,.3);
}
.hdr-main {
  display: flex; align-items: center; justify-content: space-between;
  gap: 14px; height: 62px; padding: 0;
}
.hdr-left { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
.logo { font-family: var(--fd); font-size: 2rem; font-weight: 900; color: #fff; letter-spacing: -.03em; line-height: 1; white-space: nowrap; }
.logo em { color: var(--blue); font-style: normal; }
.logo-sub { font-family: var(--fs); font-size: .52rem; color: rgba(255,255,255,.3); text-transform: uppercase; letter-spacing: .2em; margin-top: 2px; }
.hdr-center { flex: 1; max-width: 380px; min-width: 0; }
.hdr-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

.srch-desktop { position: relative; width: 100%; }
.srch-desktop input {
  width: 100%; font-family: var(--fs); font-size: .84rem; font-weight: 400;
  background: rgba(255,255,255,.11); border: 1.5px solid rgba(255,255,255,.16);
  color: #fff; border-radius: 99px; padding: 8px 42px 8px 18px; outline: none; transition: .25s;
}
.srch-desktop input:focus { border-color: var(--blue); background: rgba(255,255,255,.17); box-shadow: 0 0 0 3px var(--blue-soft); }
.srch-desktop input::placeholder { color: rgba(255,255,255,.38); }
.srch-desktop-btn {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: rgba(255,255,255,.5); font-size: .88rem; padding: 0; transition: .15s; display: flex; align-items: center; justify-content: center;
}
.srch-desktop-btn:hover { color: #fff; }

.live-dd {
  display: none; position: absolute; top: calc(100% + 10px); left: 0; right: 0;
  background: var(--bg-card); border: 1.5px solid var(--border); border-radius: var(--rm);
  box-shadow: var(--sh3); z-index: 700; overflow: hidden; animation: fadeDown .15s ease; min-width: 320px;
}
.live-dd.open { display: block; }
@keyframes fadeDown { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
.lsd-item { display: flex; align-items: center; gap: 11px; padding: 11px 15px; border-bottom: 1px solid var(--border-lt); transition: .12s; }
.lsd-item:last-of-type { border-bottom: none; }
.lsd-item:hover { background: var(--bg-alt); }
.lsd-item:hover .lsd-title { color: var(--blue); }
.lsd-thumb { width: 54px; height: 40px; border-radius: 5px; object-fit: cover; flex-shrink: 0; }
.lsd-ph { width: 54px; height: 40px; border-radius: 5px; background: var(--bg-alt); border: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: var(--text-faint); font-size: .8rem; }
.lsd-info { flex: 1; min-width: 0; }
.lsd-cat { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--blue); margin-bottom: 2px; }
.lsd-title { font-family: var(--fs); font-size: .84rem; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .12s; }
.lsd-title mark { background: transparent; color: var(--blue); font-weight: 700; padding: 0; }
.lsd-meta { font-size: .68rem; color: var(--text-muted); margin-top: 2px; }
.lsd-more { padding: 10px 15px; text-align: center; background: var(--bg-alt); border-top: 1px solid var(--border); font-size: .76rem; color: var(--text-muted); cursor: pointer; transition: .12s; }
.lsd-more:hover { color: var(--blue); }
.lsd-more strong { color: var(--blue); }
.lsd-empty { padding: 28px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 9px; }
.lsd-empty-icon-wrap { width: 48px; height: 48px; border-radius: 50%; background: var(--blue-xs); display: flex; align-items: center; justify-content: center; }
.lsd-empty-icon-wrap i { font-size: 1.15rem; color: var(--blue); opacity: .7; }
.lsd-empty-title { font-family: var(--fd); font-size: .92rem; font-weight: 700; color: var(--text); }
.lsd-empty-sub { font-size: .76rem; color: var(--text-muted); line-height: 1.55; }
.lsd-empty-sub strong { color: var(--text); font-weight: 600; }
.lsd-empty-hint { display: inline-flex; align-items: center; gap: 5px; font-size: .68rem; color: var(--text-faint); background: var(--bg-alt); border: 1px solid var(--border); border-radius: 99px; padding: 4px 12px; }

.btn-icon {
  background: rgba(255,255,255,.09); border: 1.5px solid rgba(255,255,255,.16); color: rgba(255,255,255,.65);
  width: 38px; height: 38px; border-radius: 99px; display: flex; align-items: center; justify-content: center;
  font-size: .9rem; transition: .15s; flex-shrink: 0; padding: 0; min-width: 38px;
}
.btn-icon:hover { border-color: var(--blue); color: #fff; background: rgba(255,255,255,.16); }

.srch-toggle {
  display: none; background: rgba(255,255,255,.09); border: 1.5px solid rgba(255,255,255,.16);
  color: rgba(255,255,255,.72); width: 38px; height: 38px; border-radius: 99px;
  align-items: center; justify-content: center; font-size: .9rem; flex-shrink: 0; padding: 0; transition: .15s; min-width: 38px;
}
.srch-toggle:hover { border-color: var(--blue); color: #fff; }

.mobile-search-bar { display: none; background: var(--navy2); padding: 10px clamp(14px,3.5vw,32px); border-top: 1px solid rgba(255,255,255,.07); }
.mobile-search-bar.open { display: block; }
.mobile-search-bar form { display: flex; gap: 8px; }
.mobile-search-bar input { flex: 1; font-family: var(--fs); font-size: .86rem; background: rgba(255,255,255,.11); border: 1.5px solid rgba(255,255,255,.16); color: #fff; border-radius: 99px; padding: 9px 18px; outline: none; transition: .2s; min-width: 0; }
.mobile-search-bar input:focus { border-color: var(--blue); background: rgba(255,255,255,.17); }
.mobile-search-bar input::placeholder { color: rgba(255,255,255,.38); }
.mobile-search-bar button { background: var(--blue); border: none; color: #fff; border-radius: 99px; padding: 9px 20px; font-family: var(--fs); font-size: .82rem; font-weight: 600; white-space: nowrap; flex-shrink: 0; }

.u-menu { position: relative; }
.u-chip { display: flex; align-items: center; gap: 7px; padding: 5px 12px 5px 5px; border: 1.5px solid rgba(255,255,255,.2); border-radius: 99px; background: rgba(255,255,255,.09); cursor: pointer; transition: .15s; user-select: none; }
.u-chip:hover { border-color: var(--blue); background: rgba(255,255,255,.15); }
.u-av { width: 28px; height: 28px; border-radius: 50%; background: var(--blue); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .72rem; font-weight: 800; flex-shrink: 0; }
.u-nm { font-size: .8rem; font-weight: 600; color: #fff; max-width: 84px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.u-chip i.arr { font-size: .6rem; color: rgba(255,255,255,.42); transition: transform .2s; }
.u-chip.open i.arr { transform: rotate(180deg); }
.u-drop { display: none; position: absolute; right: 0; top: calc(100% + 9px); background: var(--bg-card); border: 1.5px solid var(--border); border-radius: var(--rm); min-width: 200px; box-shadow: var(--sh3); overflow: hidden; animation: fadeDown .15s ease; z-index: 500; }
.u-drop.open { display: block; }
.dd-hd { padding: 14px 16px 11px; border-bottom: 1px solid var(--border); }
.dd-hd-name { font-size: .88rem; font-weight: 700; color: var(--text); }
.dd-hd-role { font-size: .68rem; color: var(--text-muted); margin-top: 3px; }
.dd-it { display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: .83rem; color: var(--text2); transition: .12s; text-decoration: none; }
.dd-it i { font-size: .88rem; color: var(--text-muted); width: 16px; text-align: center; }
.dd-it:hover { background: var(--bg-alt); color: var(--blue); }
.dd-it:hover i { color: var(--blue); }
.dd-sep { height: 1px; background: var(--border); }
.dd-it.out { color: #d63939; }
.dd-it.out i { color: #d63939; }
.dd-it.out:hover { background: #fff4f4; }
[data-theme="dark"] .dd-it.out:hover { background: #1e0a0a; }

/* ═══════════════════════════════════════════════════
   CATEGORY NAV
═══════════════════════════════════════════════════ */
.cat-nav { background: transparent; border-top: 1px solid var(--navy-border); width: 100%; }
.cat-nav-outer { position: relative; display: flex; align-items: stretch; }
.cat-nav-wrap { display: flex; align-items: center; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; flex: 1; -ms-overflow-style: none; }
.cat-nav-wrap::-webkit-scrollbar { display: none; }
.cat-lnk { font-family: var(--fs); font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .09em; color: rgba(255,255,255,.5); padding: 9px 13px; border-bottom: 3px solid transparent; transition: color .15s; white-space: nowrap; display: inline-flex; align-items: center; line-height: 1; flex-shrink: 0; }
.cat-lnk:hover { color: rgba(255,255,255,.88); }
.cat-lnk.on { color: #fff; border-bottom-color: var(--blue); }
.cat-nav-arrow { width: 34px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: none; border: none; color: rgba(255,255,255,.55); font-size: .84rem; transition: color .15s, opacity .2s; padding: 0; position: relative; z-index: 1; }
.cat-nav-arrow:hover { color: #fff; }
.cat-nav-arrow.left { background: linear-gradient(to right, var(--navy) 55%, transparent); }
.cat-nav-arrow.right { background: linear-gradient(to left, var(--navy) 55%, transparent); }
.cat-nav-arrow.hidden { opacity: 0; pointer-events: none; }

/* ═══════════════════════════════════════════════════
   READER TABS
═══════════════════════════════════════════════════ */
.reader-tabs-bar {
  background: var(--bg-card);
  border-bottom: 2px solid var(--border);
  position: sticky;
  top: var(--hdr-h);
  z-index: 400;
}
.reader-tabs-inner {
  display: flex; align-items: stretch; gap: 0; overflow-x: auto; scrollbar-width: none;
}
.reader-tabs-inner::-webkit-scrollbar { display: none; }
.rtab {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 0 20px; height: 46px;
  font-family: var(--fs); font-size: .8rem; font-weight: 600;
  color: var(--text-muted); border-bottom: 2px solid transparent;
  margin-bottom: -2px; transition: .15s; white-space: nowrap;
  text-decoration: none; flex-shrink: 0; background: none; border-top: none; border-left: none; border-right: none;
}
.rtab i { font-size: .9rem; }
.rtab:hover { color: var(--text); }
.rtab.active { color: var(--blue); border-bottom-color: var(--blue); }
.rtab.active.bm-tab { color: var(--bm-color); border-bottom-color: var(--bm-color); }
.rtab.active.hist-tab { color: var(--hist-color); border-bottom-color: var(--hist-color); }
.rtab-count {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 18px; height: 18px; padding: 0 5px;
  border-radius: 99px; font-size: .62rem; font-weight: 700; line-height: 1;
}
.rtab .rtab-count { background: var(--bg-alt); color: var(--text-muted); }
.rtab.active .rtab-count { background: var(--blue-soft); color: var(--blue); }
.rtab.active.bm-tab .rtab-count { background: var(--bm-soft); color: var(--bm-color); }
.rtab.active.hist-tab .rtab-count { background: var(--hist-soft); color: var(--hist-color); }
.rtab-sep { width: 1px; background: var(--border); margin: 10px 0; flex-shrink: 0; }
.rtab-divider { flex: 1; }

/* ═══════════════════════════════════════════════════
   TRENDING BAR
═══════════════════════════════════════════════════ */
.trending-bar { background: var(--bg-card); border-bottom: 1px solid var(--border); width: 100%; height: var(--trend-h); }
.trending-inner { display: flex; align-items: center; height: 100%; gap: 0; overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch; -ms-overflow-style: none; }
.trending-inner::-webkit-scrollbar { display: none; }
.trending-label { font-family: var(--fs); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .13em; color: var(--text-muted); white-space: nowrap; padding-right: 14px; border-right: 1px solid var(--border); margin-right: 14px; flex-shrink: 0; display: flex; align-items: center; gap: 6px; }
.trending-label i { color: var(--blue); font-size: .72rem; }
.trending-item { font-family: var(--fs); font-size: .76rem; font-weight: 500; color: var(--text2); white-space: nowrap; padding: 0 14px; border-right: 1px solid var(--border-lt); transition: color .15s; flex-shrink: 0; line-height: var(--trend-h); display: inline-block; }
.trending-item:first-of-type { padding-left: 0; }
.trending-item:last-of-type { border-right: none; }
.trending-item:hover { color: var(--blue); }

/* ═══════════════════════════════════════════════════
   LAYOUT
═══════════════════════════════════════════════════ */
.page { padding: 22px 0 60px; }
.badge { display: inline-block; font-family: var(--fs); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; background: var(--blue); color: #fff; padding: 3px 9px; border-radius: 4px; line-height: 1.6; flex-shrink: 0; }
.badge.ghost { background: transparent; color: var(--blue); border: 1.5px solid var(--blue); }
.sec-hd { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid var(--border); }
.sec-hd-label { font-family: var(--fs); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .13em; color: var(--blue); display: flex; align-items: center; gap: 7px; }
.sec-hd-label::before { content: ''; width: 3px; height: 13px; background: var(--blue); border-radius: 2px; display: block; }
.sec-hd-line { flex: 1; height: 1px; background: var(--border); }

.layout { display: grid; grid-template-columns: 1fr 308px; gap: 28px; align-items: start; min-width: 0; }
.layout > * { min-width: 0; }

.layout-gh { display: grid; grid-template-columns: 180px 1fr 290px; gap: 22px; align-items: start; min-width: 0; }
.layout-gh > * { min-width: 0; }

/* GH sidebar */
.gh-subnav { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; position: sticky; top: calc(var(--hdr-h) + 48px + var(--trend-h) + 14px); }
.gh-subnav-head { padding: 12px 16px; border-bottom: 2px solid var(--blue); font-family: var(--fs); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--blue); }
.gh-subnav-link { display: flex; align-items: center; gap: 9px; padding: 11px 16px; font-family: var(--fs); font-size: .84rem; font-weight: 500; color: var(--text2); border-bottom: 1px solid var(--border-lt); transition: .15s; text-decoration: none; border-left: 3px solid transparent; }
.gh-subnav-link:last-child { border-bottom: none; }
.gh-subnav-link:hover { background: var(--bg-alt); color: var(--blue); border-left-color: var(--blue); }
.gh-subnav-link.active { color: var(--blue); background: var(--blue-xs); border-left-color: var(--blue); font-weight: 700; }
.gh-subnav-link i { font-size: .88rem; width: 16px; text-align: center; opacity: .65; }
.gh-subnav-link.active i, .gh-subnav-link:hover i { opacity: 1; }
.gh-subnav-mobile { display: none; overflow-x: auto; scrollbar-width: none; gap: 7px; padding: 12px 0; -webkit-overflow-scrolling: touch; -ms-overflow-style: none; }
.gh-subnav-mobile::-webkit-scrollbar { display: none; }
.gh-subnav-pill { display: inline-flex; align-items: center; gap: 5px; padding: 8px 14px; border-radius: 99px; border: 1.5px solid var(--border); font-family: var(--fs); font-size: .74rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; transition: .15s; text-decoration: none; flex-shrink: 0; }
.gh-subnav-pill:hover, .gh-subnav-pill.active { background: var(--blue); border-color: var(--blue); color: #fff; }
.gh-banner { background: linear-gradient(118deg, var(--navy) 0%, #0d3265 100%); border-radius: var(--r); padding: 16px 18px; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; }
.gh-banner-icon { width: 40px; height: 40px; background: rgba(255,255,255,.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.12rem; color: #fff; flex-shrink: 0; }
.gh-banner-text h3 { font-family: var(--fd); font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 3px; }
.gh-banner-text p { font-family: var(--fs); font-size: .72rem; color: rgba(255,255,255,.55); }
.gh-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; }
.gh-card { display: flex; flex-direction: column; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; transition: box-shadow .22s, transform .22s; text-decoration: none; }
.gh-card:hover { box-shadow: var(--sh2); transform: translateY(-3px); }
.gh-card-img-wrap { overflow: hidden; aspect-ratio: 16/10; }
.gh-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s; }
.gh-card:hover .gh-card-img { transform: scale(1.07); }
.gh-card-img-ph { width: 100%; aspect-ratio: 16/10; background: var(--bg-alt); display: flex; align-items: center; justify-content: center; color: var(--text-faint); font-size: 1.5rem; }
.gh-card-body { padding: 11px 13px 13px; flex: 1; display: flex; flex-direction: column; }
.gh-card-cat { font-family: var(--fs); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--blue); margin-bottom: 5px; }
.gh-card-title { font-family: var(--fd); font-size: .92rem; font-weight: 700; color: var(--text); line-height: 1.38; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 8px; transition: color .15s; }
.gh-card:hover .gh-card-title { color: var(--blue); }
.gh-card-meta { font-family: var(--fs); font-size: .66rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }

/* Hero */
.hero-box { margin-bottom: 24px; }
.hero-grid { display: grid; grid-template-columns: 3fr 2fr; grid-template-rows: 1fr 1fr; gap: 3px; border-radius: var(--rm); overflow: hidden; background: var(--border); min-height: clamp(260px, 40vw, 420px); }
.hg-main { grid-column: 1; grid-row: 1/3; position: relative; display: block; overflow: hidden; }
.hg-main img { width: 100%; height: 100%; object-fit: cover; transition: transform .55s; }
.hg-main:hover img { transform: scale(1.04); }
.hg-main::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(4,10,28,.92) 0%, rgba(4,10,28,.22) 50%, transparent 80%); }
.hg-main-body { position: absolute; bottom: 0; left: 0; right: 0; padding: 22px; z-index: 2; }
.hg-main-body h2 { font-family: var(--fd); font-size: clamp(1rem, 2.2vw, 1.75rem); font-weight: 800; color: #fff; line-height: 1.22; margin: 8px 0 6px; text-shadow: 0 1px 6px rgba(0,0,0,.4); }
.hg-main-meta { font-family: var(--fs); font-size: .69rem; color: rgba(255,255,255,.6); display: flex; align-items: center; gap: 7px; }
.hg-side { position: relative; display: block; overflow: hidden; background: var(--bg-card); }
.hg-side img { width: 100%; height: 100%; object-fit: cover; transition: transform .45s; }
.hg-side:hover img { transform: scale(1.06); }
.hg-side::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(4,10,28,.88) 0%, transparent 55%); }
.hg-side-body { position: absolute; bottom: 0; left: 0; right: 0; padding: 12px 14px; z-index: 2; }
.hg-side-body h4 { font-family: var(--fd); font-size: .9rem; font-weight: 700; color: #fff; line-height: 1.28; margin-top: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-shadow: 0 1px 4px rgba(0,0,0,.4); }
.hg-side-meta { font-family: var(--fs); font-size: .64rem; color: rgba(255,255,255,.52); margin-top: 4px; }
.hero-single { position: relative; display: block; border-radius: var(--rm); overflow: hidden; aspect-ratio: 16/7; margin-bottom: 24px; }
.hero-single img { width: 100%; height: 100%; object-fit: cover; }
.hero-single::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(4,10,28,.9) 0%, transparent 58%); }
.hero-single-body { position: absolute; bottom: 0; left: 0; right: 0; padding: 22px; z-index: 2; }
.hero-single-body h2 { font-family: var(--fd); font-size: clamp(1rem, 3.2vw, 1.85rem); font-weight: 800; color: #fff; line-height: 1.22; margin: 8px 0; text-shadow: 0 1px 6px rgba(0,0,0,.4); }
.hero-text { display: block; background: linear-gradient(132deg, var(--navy) 0%, #1a3a6b 100%); border-radius: var(--rm); padding: 32px 28px; margin-bottom: 24px; }
.hero-text h2 { font-family: var(--fd); font-size: clamp(1.15rem, 4.5vw, 2.3rem); font-weight: 900; color: #fff; line-height: 1.2; margin: 8px 0 10px; }
.hero-text h2:hover { color: var(--blue); }
.hero-text .meta { font-family: var(--fs); font-size: .78rem; color: rgba(255,255,255,.48); display: flex; align-items: center; gap: 7px; }

/* Card grid */
.card-row { display: grid; gap: 16px; margin-bottom: 24px; }
.card-row-3 { grid-template-columns: repeat(3,1fr); }
.card-row-2 { grid-template-columns: repeat(2,1fr); }
.card { display: flex; flex-direction: column; background: var(--bg-card); border-radius: var(--r); overflow: hidden; border: 1px solid var(--border); transition: box-shadow .22s, transform .22s; }
.card:hover { box-shadow: var(--sh2); transform: translateY(-3px); }
.card-img-wrap { overflow: hidden; line-height: 0; }
.card-img { width: 100%; height: 152px; object-fit: cover; transition: transform .4s; }
.card:hover .card-img { transform: scale(1.07); }
.card-body { padding: 13px 14px 15px; display: flex; flex-direction: column; flex: 1; }
.card-body h5 { font-family: var(--fd); font-size: .92rem; font-weight: 700; color: var(--text); line-height: 1.38; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin: 6px 0 10px; transition: color .15s; }
.card:hover h5 { color: var(--blue); }
.card-meta { font-family: var(--fs); font-size: .68rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

/* News list */
.nlist { display: flex; flex-direction: column; }
.nli { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--border-lt); text-decoration: none; transition: .15s; position: relative; }
.nli:first-child { padding-top: 0; }
.nli:last-child { border-bottom: none; }
.nli:hover .nli-ttl { color: var(--blue); }
.nli-img-wrap { width: 112px; height: 75px; border-radius: var(--r); overflow: hidden; flex-shrink: 0; }
.nli-img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s; }
.nli:hover .nli-img { transform: scale(1.07); }
.nli-ph { width: 112px; height: 75px; border-radius: var(--r); background: var(--bg-alt); border: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: var(--text-faint); font-size: 1rem; }
.nli-body { flex: 1; min-width: 0; }
.nli-cat { font-family: var(--fs); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--blue); display: block; margin-bottom: 4px; }
.nli-ttl { font-family: var(--fd); font-size: .93rem; font-weight: 700; color: var(--text); line-height: 1.36; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 5px; transition: color .15s; }
.nli-meta { font-family: var(--fs); font-size: .69rem; color: var(--text-muted); }
.nli-actions { display: flex; align-items: center; gap: 4px; margin-left: auto; flex-shrink: 0; align-self: center; }

.divider { display: flex; align-items: center; gap: 10px; margin: 22px 0 18px; }
.div-line { flex: 1; height: 1px; background: var(--border); }
.div-dot { width: 5px; height: 5px; background: var(--blue); border-radius: 50%; }

/* Search results */
.sr-banner { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--r); padding: 12px 16px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.sr-banner-txt { font-family: var(--fs); font-size: .85rem; color: var(--text2); }
.sr-banner-txt strong { color: var(--blue); }
.sr-clear { font-family: var(--fs); font-size: .74rem; color: var(--text-muted); border: 1px solid var(--border); border-radius: 99px; padding: 5px 14px; background: none; cursor: pointer; transition: .15s; display: flex; align-items: center; gap: 5px; white-space: nowrap; text-decoration: none; }
.sr-clear:hover { border-color: var(--blue); color: var(--blue); }
.sr-item { display: flex; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--border-lt); text-decoration: none; transition: .15s; }
.sr-item:first-child { padding-top: 0; }
.sr-item:last-child { border-bottom: none; }
.sr-item:hover .sr-ttl { color: var(--blue); }
.sr-img-wrap { width: 112px; height: 75px; border-radius: var(--r); overflow: hidden; flex-shrink: 0; }
.sr-img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
.sr-item:hover .sr-img { transform: scale(1.07); }
.sr-ph { width: 112px; height: 75px; border-radius: var(--r); background: var(--bg-alt); border: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: var(--text-faint); font-size: 1rem; }
.sr-info { flex: 1; min-width: 0; }
.sr-cat { font-family: var(--fs); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--blue); display: block; margin-bottom: 4px; }
.sr-ttl { font-family: var(--fd); font-size: .93rem; font-weight: 700; color: var(--text); line-height: 1.36; margin-bottom: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: color .15s; }
.sr-ttl mark { background: var(--blue-soft); color: var(--blue); border-radius: 3px; padding: 0 2px; }
.sr-meta { font-family: var(--fs); font-size: .7rem; color: var(--text-muted); }
.sr-empty { text-align: center; padding: 54px 0; color: var(--text-muted); }
.sr-empty i { font-size: 2.4rem; display: block; margin-bottom: 12px; opacity: .22; }

/* ═══════════════════════════════════════════════════
   BOOKMARK BUTTON — BASE
   Digunakan di news list, sidebar, history
═══════════════════════════════════════════════════ */
.bm-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border-radius: 50%;
  border: 1.5px solid var(--border); background: var(--bg-card);
  color: var(--text-muted); font-size: .82rem; transition: .18s;
  cursor: pointer; flex-shrink: 0; padding: 0;
}
.bm-btn:hover { border-color: var(--bm-color); color: var(--bm-color); background: var(--bm-soft); }
.bm-btn.saved { border-color: var(--bm-color); color: var(--bm-color); background: var(--bm-soft); }
.bm-btn.saved i::before { content: "\f163"; }
.bm-btn[data-saving] { opacity: .5; pointer-events: none; }

/* ═══════════════════════════════════════════════════
   BOOKMARK BUTTON — OVERLAY di atas gambar card
   (card Berita Pilihan, gh-card, bm-card)
═══════════════════════════════════════════════════ */
.card-bm-overlay {
  position: absolute;
  top: 9px; right: 9px; z-index: 3;
  width: 28px !important;
  height: 28px !important;
  font-size: .74rem !important;
  background: rgba(0,0,0,.42) !important;
  border-color: rgba(255,255,255,.25) !important;
  color: rgba(255,255,255,.85) !important;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}
.card-bm-overlay:hover {
  background: rgba(224,92,42,.88) !important;
  border-color: var(--bm-color) !important;
  color: #fff !important;
}
.card-bm-overlay.saved {
  background: rgba(224,92,42,.88) !important;
  border-color: var(--bm-color) !important;
  color: #fff !important;
}

/* ═══════════════════════════════════════════════════
   BOOKMARK BUTTON — di dalam hero (di atas foto gelap)
   Khusus .hg-main-body dan .hg-side-body
═══════════════════════════════════════════════════ */
.bm-btn-hero {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px !important; height: 26px !important;
  border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,.3) !important;
  background: rgba(0,0,0,.38) !important;
  color: rgba(255,255,255,.82) !important;
  font-size: .7rem !important;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  cursor: pointer; flex-shrink: 0; padding: 0;
  transition: .18s;
}
.bm-btn-hero:hover {
  background: rgba(224,92,42,.85) !important;
  border-color: var(--bm-color) !important;
  color: #fff !important;
}
.bm-btn-hero.saved {
  background: rgba(224,92,42,.85) !important;
  border-color: var(--bm-color) !important;
  color: #fff !important;
}
.bm-btn-hero.saved i::before { content: "\f163"; }
.bm-btn-hero[data-saving] { opacity: .5; pointer-events: none; }

/* Bookmark page */
.feature-hero {
  border-radius: var(--r); padding: 22px 24px; margin-bottom: 22px;
  display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
}
.feature-hero.bm { background: linear-gradient(120deg, #7c2900 0%, #b84020 45%, #e05c2a 100%); }
.feature-hero.hist { background: linear-gradient(120deg, #2e1065 0%, #4c1d95 45%, #6941c6 100%); }
.feature-hero-left { display: flex; align-items: center; gap: 15px; }
.feature-hero-icon { width: 50px; height: 50px; border-radius: 12px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; flex-shrink: 0; }
.feature-hero-text h2 { font-family: var(--fd); font-size: 1.35rem; font-weight: 800; color: #fff; line-height: 1.2; }
.feature-hero-text p { font-family: var(--fs); font-size: .78rem; color: rgba(255,255,255,.6); margin-top: 4px; }
.feature-hero-badge { background: rgba(255,255,255,.2); color: #fff; font-family: var(--fs); font-size: .72rem; font-weight: 700; padding: 6px 14px; border-radius: 99px; }

.bm-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
.bm-card {
  display: flex; flex-direction: column; background: var(--bg-card); border-radius: var(--r);
  overflow: hidden; border: 1px solid var(--border); transition: box-shadow .22s, transform .22s; position: relative;
}
.bm-card:hover { box-shadow: var(--sh2); transform: translateY(-3px); }
.bm-card-img-wrap { overflow: hidden; aspect-ratio: 16/9; line-height: 0; position: relative; }
.bm-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.bm-card:hover .bm-card-img { transform: scale(1.06); }
.bm-card-ph { width: 100%; aspect-ratio: 16/9; background: var(--bg-alt); display: flex; align-items: center; justify-content: center; color: var(--text-faint); font-size: 1.5rem; }
.bm-card-remove {
  position: absolute; top: 8px; right: 8px; z-index: 4;
  width: 28px; height: 28px; border-radius: 50%; border: none;
  background: rgba(0,0,0,.55); color: rgba(255,255,255,.85);
  display: flex; align-items: center; justify-content: center; font-size: .75rem;
  cursor: pointer; transition: .15s; backdrop-filter: blur(4px);
}
.bm-card-remove:hover { background: rgba(220,38,38,.85); color: #fff; }
.bm-card-body { padding: 12px 13px 14px; display: flex; flex-direction: column; flex: 1; }
.bm-card-cat { font-family: var(--fs); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--bm-color); margin-bottom: 5px; }
.bm-card-title { font-family: var(--fd); font-size: .91rem; font-weight: 700; color: var(--text); line-height: 1.36; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 9px; transition: color .15s; text-decoration: none; }
.bm-card:hover .bm-card-title { color: var(--bm-color); }
.bm-card-meta { font-family: var(--fs); font-size: .66rem; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; gap: 6px; }
.bm-saved-date { display: inline-flex; align-items: center; gap: 4px; font-size: .63rem; color: var(--text-faint); }

/* History page */
.hist-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.hist-header-left { display: flex; align-items: center; gap: 8px; }
.hist-clear-btn {
  display: inline-flex; align-items: center; gap: 6px;
  font-family: var(--fs); font-size: .76rem; font-weight: 600; color: #d63939;
  border: 1.5px solid #f4d0d0; border-radius: 99px; padding: 6px 14px;
  background: none; cursor: pointer; transition: .15s;
}
.hist-clear-btn:hover { background: #fff0f0; border-color: #d63939; }
[data-theme="dark"] .hist-clear-btn:hover { background: #1e0a0a; }

.hist-group { margin-bottom: 22px; }
.hist-group-label { font-family: var(--fs); font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--text-faint); padding: 0 0 9px; border-bottom: 1px solid var(--border-lt); margin-bottom: 0; }
.hist-item { display: flex; gap: 13px; padding: 13px 0; border-bottom: 1px solid var(--border-lt); align-items: flex-start; }
.hist-item:last-child { border-bottom: none; }
.hist-item-img-wrap { width: 96px; height: 64px; border-radius: 7px; overflow: hidden; flex-shrink: 0; }
.hist-item-img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
.hist-item:hover .hist-item-img { transform: scale(1.06); }
.hist-item-ph { width: 96px; height: 64px; border-radius: 7px; background: var(--bg-alt); border: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: var(--text-faint); font-size: .9rem; }
.hist-item-body { flex: 1; min-width: 0; }
.hist-item-cat { font-family: var(--fs); font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--hist-color); display: block; margin-bottom: 3px; }
.hist-item-title { font-family: var(--fd); font-size: .88rem; font-weight: 700; color: var(--text); line-height: 1.34; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 4px; transition: color .15s; text-decoration: none; display: block; }
.hist-item:hover .hist-item-title { color: var(--hist-color); }
.hist-item-meta { font-family: var(--fs); font-size: .67rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.hist-item-actions { display: flex; align-items: center; gap: 5px; flex-shrink: 0; margin-left: auto; }
.hist-rm-btn { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid var(--border); background: none; color: var(--text-faint); font-size: .76rem; cursor: pointer; transition: .15s; }
.hist-rm-btn:hover { border-color: #d63939; color: #d63939; background: #fff0f0; }

/* Empty states */
.empty-state { text-align: center; padding: 60px 0; color: var(--text-muted); }
.empty-state i { font-size: 2.8rem; display: block; margin-bottom: 14px; opacity: .2; }
.empty-state p { font-family: var(--fd); font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 5px; }
.empty-feature {
  display: flex; flex-direction: column; align-items: center; text-align: center;
  padding: 60px 20px; border: 2px dashed var(--border); border-radius: var(--rm);
}
.empty-feature-icon { width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 16px; }
.empty-feature-icon.bm { background: var(--bm-soft); color: var(--bm-color); }
.empty-feature-icon.hist { background: var(--hist-soft); color: var(--hist-color); }
.empty-feature h3 { font-family: var(--fd); font-size: 1.1rem; font-weight: 800; color: var(--text); margin-bottom: 8px; }
.empty-feature p { font-family: var(--fs); font-size: .84rem; color: var(--text-muted); max-width: 340px; line-height: 1.6; }

/* Pagination */
.pagination-wrap { display: flex; align-items: center; justify-content: center; gap: 5px; padding: 28px 0 8px; flex-wrap: wrap; }
.pg-info { font-family: var(--fs); font-size: .77rem; color: var(--text-muted); margin-right: 5px; }
.pg-info strong { color: var(--text2); }
.pg-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 10px; border-radius: 7px; border: 1.5px solid var(--border); background: var(--bg-card); color: var(--text-muted); font-family: var(--fs); font-size: .8rem; font-weight: 600; transition: .15s; text-decoration: none; }
.pg-btn:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
.pg-btn.on { background: var(--blue); border-color: var(--blue); color: #fff; cursor: default; pointer-events: none; }
.pg-btn.disabled { opacity: .35; pointer-events: none; }
.pg-ellipsis { font-size: .86rem; color: var(--text-faint); padding: 0 2px; align-self: center; }
.pg-jump { display: flex; align-items: center; gap: 5px; margin-left: 5px; }
.pg-jump input { width: 50px; height: 38px; border-radius: 7px; border: 1.5px solid var(--border); background: var(--bg-card); color: var(--text); font-family: var(--fs); font-size: .8rem; text-align: center; outline: none; transition: .15s; }
.pg-jump input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px var(--blue-soft); }
.pg-jump-btn { height: 38px; padding: 0 13px; border-radius: 7px; border: 1.5px solid var(--border); background: var(--bg-card); color: var(--text-muted); font-family: var(--fs); font-size: .76rem; font-weight: 600; transition: .15s; }
.pg-jump-btn:hover { border-color: var(--blue); color: var(--blue); }

/* Sidebar */
.sidebar { position: sticky; top: calc(var(--hdr-h) + 48px + var(--trend-h) + 14px); }
.sb-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--r); overflow: hidden; margin-bottom: 16px; }
.sb-card:last-child { margin-bottom: 0; }
.sb-head { padding: 12px 15px; border-bottom: 1px solid var(--border); }
.sb-head-label { font-family: var(--fs); font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--blue); display: flex; align-items: center; gap: 6px; }
.sb-head-label::before { content: ''; width: 2px; height: 11px; background: var(--blue); border-radius: 2px; }
.sb-item { display: flex; gap: 10px; padding: 10px 13px; border-bottom: 1px solid var(--border-lt); text-decoration: none; transition: .12s; }
.sb-item:last-child { border-bottom: none; }
.sb-item:hover { background: var(--bg-alt); }
.sb-item:hover .sb-ttl { color: var(--blue); }
.sb-thumb-wrap { width: 62px; height: 46px; border-radius: 5px; overflow: hidden; flex-shrink: 0; }
.sb-thumb { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
.sb-item:hover .sb-thumb { transform: scale(1.09); }
.sb-ph { width: 62px; height: 46px; border-radius: 5px; background: var(--bg-alt); border: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: var(--text-faint); font-size: .75rem; }
.sb-info { flex: 1; min-width: 0; }
.sb-cat { font-family: var(--fs); font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--blue); margin-bottom: 3px; }
.sb-ttl { font-family: var(--fd); font-size: .8rem; font-weight: 700; color: var(--text); line-height: 1.33; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; transition: color .15s; }
.sb-meta { font-family: var(--fs); font-size: .64rem; color: var(--text-muted); margin-top: 3px; }
.pop-item { display: flex; gap: 11px; padding: 10px 13px; border-bottom: 1px solid var(--border-lt); text-decoration: none; transition: .12s; }
.pop-item:last-child { border-bottom: none; }
.pop-item:hover { background: var(--bg-alt); }
.pop-item:hover .pop-ttl, .pop-item:hover .pop-n { color: var(--blue); }
.pop-n { font-family: var(--fd); font-size: 1.5rem; font-weight: 900; color: var(--border); min-width: 28px; line-height: 1; transition: color .15s; flex-shrink: 0; }
.pop-info { flex: 1; min-width: 0; }
.pop-ttl { font-family: var(--fd); font-size: .8rem; font-weight: 700; color: var(--text); line-height: 1.33; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 3px; transition: color .15s; }
.pop-meta { font-family: var(--fs); font-size: .65rem; color: var(--text-muted); }
.pills { padding: 12px 13px; display: flex; flex-wrap: wrap; gap: 6px; }
.pill { font-family: var(--fs); font-size: .71rem; font-weight: 600; padding: 6px 13px; border-radius: 99px; border: 1.5px solid var(--border); color: var(--text-muted); transition: .15s; text-decoration: none; display: inline-block; }
.pill:hover, .pill.on { background: var(--blue); border-color: var(--blue); color: #fff; }
.sidebar-bottom { display: none; margin-top: 24px; }

/* User Stats Card */
.user-stats-card {
  background: linear-gradient(135deg, var(--navy) 0%, #0d2c54 100%);
  border: 1px solid var(--navy-border);
  border-radius: var(--r); padding: 16px; margin-bottom: 16px;
  color: #fff; overflow: hidden; position: relative;
}
.user-stats-card::before {
  content: ''; position: absolute; top: -20px; right: -20px;
  width: 90px; height: 90px; border-radius: 50%;
  background: rgba(26,86,219,.18); pointer-events: none;
}
.usc-greeting { font-family: var(--fs); font-size: .7rem; color: rgba(255,255,255,.5); margin-bottom: 3px; }
.usc-name { font-family: var(--fd); font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.usc-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.usc-stat { background: rgba(255,255,255,.09); border-radius: 8px; padding: 10px; text-align: center; }
.usc-stat-num { font-family: var(--fd); font-size: 1.3rem; font-weight: 900; color: #fff; line-height: 1; }
.usc-stat-label { font-family: var(--fs); font-size: .58rem; color: rgba(255,255,255,.45); text-transform: uppercase; letter-spacing: .1em; margin-top: 3px; }

/* TOAST */
.toast-stack { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
.toast {
  display: flex; align-items: center; gap: 10px;
  background: var(--bg-card); border: 1.5px solid var(--border); border-radius: 10px;
  padding: 11px 16px; box-shadow: var(--sh3); min-width: 220px; max-width: 340px;
  font-family: var(--fs); font-size: .82rem; color: var(--text2);
  animation: toastIn .2s ease; pointer-events: auto;
}
.toast.hide { animation: toastOut .25s ease forwards; }
.toast i { font-size: 1rem; flex-shrink: 0; }
.toast.success i { color: #16a34a; }
.toast.info i { color: var(--blue); }
.toast.warn i { color: var(--bm-color); }
@keyframes toastIn { from { opacity:0; transform:translateX(24px); } to { opacity:1; transform:translateX(0); } }
@keyframes toastOut { from { opacity:1; } to { opacity:0; transform:translateX(24px); } }

/* LOGOUT MODAL */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 9999; align-items: center; justify-content: center; }
.modal-overlay.show { display: flex; }
.logout-box { background: var(--bg-card); border-radius: 20px; padding: 32px 28px 24px; width: 90%; max-width: 380px; text-align: center; box-shadow: var(--sh3); animation: popIn .2s cubic-bezier(.34,1.56,.64,1); }
@keyframes popIn { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:scale(1); } }
.logout-icon-wrap { width: 60px; height: 60px; border-radius: 50%; background: #fee2e2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.logout-icon-wrap i { font-size: 1.6rem; color: #ef4444; }
.logout-box h5 { font-weight: 700; font-size: 1.05rem; margin-bottom: 8px; color: var(--text); }
.logout-box p { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0; line-height: 1.6; }
.logout-actions { display: flex; gap: 10px; margin-top: 22px; }
.btn-cancel-logout, .btn-confirm-logout { flex: 1; padding: 11px 10px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: 0.2s; text-align: center; }
.btn-cancel-logout { border: 1.5px solid var(--border); background: transparent; color: var(--text2); }
.btn-cancel-logout:hover { background: var(--bg-alt); }
.btn-confirm-logout { border: none; background: #ef4444; color: white; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
.btn-confirm-logout:hover { background: #dc2626; color: white; }

/* Footer */
.site-footer { background: var(--navy); border-top: 3px solid var(--blue); padding: 36px 0 26px; margin-top: 18px; }
.ft-logo { font-family: var(--fd); font-size: 1.5rem; font-weight: 900; color: #d0ddef; margin-bottom: 8px; }
.ft-logo em { color: var(--blue); font-style: normal; }
.ft-desc { font-family: var(--fs); font-size: .8rem; color: #3a5578; max-width: 360px; line-height: 1.75; }
.ft-nav { display: flex; flex-wrap: wrap; gap: 6px 20px; margin-top: 18px; }
.ft-nav a { font-family: var(--fs); font-size: .76rem; color: #2e4566; transition: color .15s; }
.ft-nav a:hover { color: #6a8aaa; }
.ft-hr { border-color: #0e2040; margin: 18px 0 14px; }
.ft-bot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.ft-copy { font-family: var(--fs); font-size: .7rem; color: #1c3050; }
.ft-links { display: flex; gap: 15px; }
.ft-links a { font-size: .7rem; color: #1c3050; transition: color .15s; }
.ft-links a:hover { color: #5a7a96; }

/* ═══════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════ */
@media (max-width:1280px) { .layout { grid-template-columns: 1fr 280px; gap: 22px; } .layout-gh { grid-template-columns: 162px 1fr 264px; gap: 18px; } .bm-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width:1080px) { .layout { grid-template-columns: 1fr 260px; gap: 20px; } }
@media (max-width:1024px) {
  .layout { grid-template-columns: 1fr; }
  .layout-gh { grid-template-columns: 155px 1fr; gap: 18px; }
  .layout-gh .sidebar, .layout .sidebar { display: none; }
  .sidebar-bottom { display: block; }
  .sidebar-bottom-inner { display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; }
  .sidebar-bottom-inner .sb-card { margin-bottom: 0; }
  .gh-grid { grid-template-columns: repeat(2,1fr); }
  .bm-grid { grid-template-columns: repeat(3,1fr); }
}
@media (max-width:900px) { .bm-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width:768px) {
  :root { --hdr-h: 84px; --trend-h: 32px; }
  .hdr-center { display: none; }
  .srch-toggle { display: flex; }
  .logo { font-size: 1.65rem; }
  .logo-sub { display: none; }
  .u-nm { display: none; }
  .u-chip { padding: 5px 8px 5px 5px; gap: 5px; }
  .layout-gh { grid-template-columns: 1fr; }
  .layout-gh .gh-subnav { display: none; }
  .gh-subnav-mobile { display: flex; }
  .gh-grid { grid-template-columns: repeat(2,1fr); }
  .sidebar-bottom-inner { grid-template-columns: 1fr; }
  .hero-grid { display: flex; flex-direction: column; min-height: auto; gap: 2px; }
  .hg-main { width: 100%; aspect-ratio: 16/9; min-height: 190px; grid-column: unset; grid-row: unset; }
  .hg-side { width: 100%; aspect-ratio: 16/7; min-height: 115px; }
  .card-row-3 { grid-template-columns: repeat(2,1fr); }
  .card-img { height: 134px; }
  .card-body h5 { font-size: .9rem; }
  .nli-img-wrap, .nli-ph, .sr-img-wrap, .sr-ph { width: 90px; height: 64px; }
  .nli-ttl, .sr-ttl { font-size: .88rem; }
  .nli-meta, .sr-meta { font-size: .67rem; }
  .pg-jump { display: none; }
  .pg-info { display: none; }
  .ft-bot { flex-direction: column; align-items: flex-start; gap: 8px; }
  .bm-grid { grid-template-columns: repeat(2,1fr); }
  .rtab { padding: 0 14px; font-size: .76rem; }
}
@media (max-width:580px) {
  :root { --hdr-h: 76px; --trend-h: 0px; }
  .trending-bar { display: none; }
  .logo { font-size: 1.48rem; }
  .btn-icon, .srch-toggle { width: 36px; height: 36px; font-size: .84rem; min-width: 36px; }
  .ticker-label { display: none; }
  .ticker-item { font-size: .72rem; padding: 0 26px 0 0; }
  .hg-main { min-height: 175px; }
  .hg-main-body { padding: 13px; }
  .hg-main-body h2 { font-size: clamp(.9rem, 4.5vw, 1.15rem); margin: 5px 0; }
  .hg-side { min-height: 105px; aspect-ratio: 16/7; }
  .hg-side-body { padding: 10px 12px; }
  .hg-side-body h4 { font-size: .84rem; }
  .card-row-3, .card-row-2 { grid-template-columns: 1fr; }
  .card-img { height: 185px; }
  .gh-grid { grid-template-columns: 1fr; }
  .nli-img-wrap, .nli-ph, .sr-img-wrap, .sr-ph { width: 82px; height: 58px; }
  .live-dd { position: fixed; left: 8px; right: 8px; top: var(--hdr-h); width: auto; min-width: unset; max-height: 55vh; overflow-y: auto; }
  .u-drop { right: 0; min-width: 184px; max-width: calc(100vw - 16px); }
  .bm-grid { grid-template-columns: 1fr; }
  .toast-stack { right: 10px; bottom: 14px; }
  .toast { min-width: unset; max-width: calc(100vw - 20px); }
}
@media (max-width:420px) {
  :root { --hdr-h: 70px; }
  .logo { font-size: 1.28rem; }
  .btn-icon, .srch-toggle { width: 33px; height: 33px; font-size: .8rem; min-width: 33px; }
  .hdr-main { gap: 8px; }
  .hdr-right { gap: 5px; }
}
@media (hover:none) and (pointer:coarse) {
  .cat-lnk { min-height: 40px; display: inline-flex; align-items: center; }
  .nli, .sr-item { padding: 15px 0; }
  .sb-item, .pop-item { padding: 12px 13px; min-height: 44px; }
  .pill { padding: 8px 15px; min-height: 38px; }
  .pg-btn { min-width: 42px; height: 42px; }
  .dd-it { padding: 12px 16px; min-height: 44px; }
  .gh-subnav-pill { padding: 9px 16px; min-height: 40px; }
  .lsd-item { padding: 12px 14px; min-height: 44px; }
  .btn-icon, .srch-toggle { width: 42px; height: 42px; min-width: 42px; }
  .card:hover, .gh-card:hover, .bm-card:hover { transform: none; }
  .rtab { min-height: 46px; }
}
</style>
</head>
<body>

<!-- TOAST STACK -->
<div class="toast-stack" id="toastStack"></div>

<!-- LOGOUT MODAL -->
<div class="modal-overlay" id="logoutOverlay" onclick="if(event.target===this)hideLogout()">
  <div class="logout-box">
    <div class="logout-icon-wrap"><i class="bi bi-box-arrow-right"></i></div>
    <h5>Yakin ingin keluar?</h5>
    <p>Sesi kamu akan diakhiri dan kamu perlu login kembali untuk mengakses LiyNews.</p>
    <div class="logout-actions">
      <button class="btn-cancel-logout" onclick="hideLogout()">Batal</button>
      <a href="../public/logout.php" class="btn-confirm-logout"><i class="bi bi-box-arrow-right"></i> Ya, keluar</a>
    </div>
  </div>
</div>

<!-- TICKER -->
<div class="ticker">
  <div class="container">
    <div class="ticker-inner">
      <span class="ticker-label">Breaking</span>
      <div class="ticker-scroll">
        <div class="ticker-track">
          <?php
          $tq = mysqli_query($koneksi, "SELECT a.id_artikel, a.judul FROM artikel a $w ORDER BY a.tgl_posting DESC LIMIT 8");
          $ti = [];
          while ($tr = mysqli_fetch_assoc($tq)) $ti[] = $tr;
          foreach (array_merge($ti, $ti) as $t): ?>
          <a href="../public/artikel.php?id=<?=$t['id_artikel']?>" class="ticker-item"><?=htmlspecialchars($t['judul'])?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- HEADER -->
<header class="site-header">
  <div class="container hdr-main">
    <div class="hdr-left">
      <div>
        <a href="dashboardpembaca.php" class="logo">Liy<em>News</em></a>
        <div class="logo-sub">Berita Terpercaya Indonesia</div>
      </div>
    </div>

    <div class="hdr-center">
      <div class="srch-desktop">
        <form method="GET" action="dashboardpembaca.php" id="searchForm">
          <input type="hidden" name="tab" value="beranda">
          <input type="text" name="q" id="searchInput" placeholder="Cari berita…" value="<?=htmlspecialchars($search)?>" autocomplete="off">
          <button type="submit" class="srch-desktop-btn"><i class="bi bi-search"></i></button>
        </form>
        <div class="live-dd <?=$search?'open':''?>" id="liveDD">
          <?php if ($search):
            $sdq = mysqli_query($koneksi, "SELECT a.id_artikel,a.judul,a.tgl_posting,a.thumbnail,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w AND (a.judul LIKE '%$searchEs%' OR a.isi LIKE '%$searchEs%') ORDER BY a.tgl_posting DESC LIMIT 6");
            $sdItems=[];
            while($sd=mysqli_fetch_assoc($sdq)) $sdItems[]=$sd;
            if(empty($sdItems)): ?>
            <div class="lsd-empty">
              <div class="lsd-empty-icon-wrap"><i class="bi bi-search"></i></div>
              <div class="lsd-empty-title">Tidak ditemukan</div>
              <div class="lsd-empty-sub">Tidak ada hasil untuk &ldquo;<strong><?=htmlspecialchars($search)?></strong>&rdquo;</div>
              <div class="lsd-empty-hint"><i class="bi bi-lightbulb"></i> Coba kata kunci lain</div>
            </div>
            <?php else: foreach($sdItems as $sd):
              $sdImg=null; $sdf=!empty($sd['thumbnail'])?$sd['thumbnail']:'';
              if($sdf){if(filter_var($sdf,FILTER_VALIDATE_URL))$sdImg=$sdf;elseif(file_exists("../uploads/$sdf"))$sdImg="../uploads/$sdf";}
              $hl=preg_replace('/('.preg_quote(htmlspecialchars($search),'/').')/i','<mark>$1</mark>',htmlspecialchars($sd['judul'])); ?>
            <a href="../public/artikel.php?id=<?=$sd['id_artikel']?>" class="lsd-item">
              <?php if($sdImg): ?><img class="lsd-thumb" src="<?=htmlspecialchars($sdImg)?>" alt="">
              <?php else: ?><div class="lsd-ph"><i class="bi bi-image"></i></div><?php endif; ?>
              <div class="lsd-info">
                <div class="lsd-cat"><?=htmlspecialchars($sd['nama_kategori'])?></div>
                <div class="lsd-title"><?=$hl?></div>
                <div class="lsd-meta"><i class="bi bi-clock"></i> <?=ago($sd['tgl_posting'])?></div>
              </div>
            </a>
            <?php endforeach; ?>
            <div class="lsd-more" onclick="document.getElementById('searchForm').submit()">Lihat semua hasil untuk "<strong><?=htmlspecialchars($search)?></strong>" →</div>
            <?php endif; endif; ?>
        </div>
      </div>
    </div>

    <div class="hdr-right">
      <button class="srch-toggle" id="srchToggle" aria-label="Cari" aria-expanded="false"><i class="bi bi-search"></i></button>
      <button class="btn-icon" id="themeBtn" aria-label="Ganti tema"><i class="bi bi-moon-fill"></i></button>
      <div class="u-menu" id="uMenu">
        <div class="u-chip" id="uChip" role="button" aria-haspopup="true" aria-expanded="false">
          <div class="u-av"><?=htmlspecialchars($userInit)?></div>
          <span class="u-nm"><?=htmlspecialchars($userNama)?></span>
          <i class="bi bi-chevron-down arr"></i>
        </div>
        <div class="u-drop" id="uDrop" role="menu">
          <div class="dd-hd">
            <div class="dd-hd-name"><?=htmlspecialchars($userNama)?></div>
            <div class="dd-hd-role"><i class="bi bi-person-fill"></i> Pembaca</div>
          </div>
          <a href="dashboardpembaca.php?tab=bookmark" class="dd-it"><i class="bi bi-bookmark-heart"></i> Bookmark Saya
            <?php if($bmTotal>0): ?><span class="rtab-count" style="margin-left:auto;background:var(--bm-soft);color:var(--bm-color)"><?=$bmTotal?></span><?php endif; ?>
          </a>
          <a href="dashboardpembaca.php?tab=history" class="dd-it"><i class="bi bi-clock-history"></i> Riwayat Baca
            <?php if($histTotal>0): ?><span class="rtab-count" style="margin-left:auto;background:var(--hist-soft);color:var(--hist-color)"><?=$histTotal?></span><?php endif; ?>
          </a>
          <a href="../profilepembaca.php" class="dd-it"><i class="bi bi-person-circle"></i> Profil Saya</a>
          <div class="dd-sep"></div>
          <a href="#" class="dd-it out" onclick="showLogout();return false;"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>

  <div class="mobile-search-bar" id="mobileSearchBar">
    <form method="GET" action="dashboardpembaca.php">
      <input type="hidden" name="tab" value="beranda">
      <input type="text" name="q" id="mobileSearchInput" placeholder="Cari berita…" value="<?=htmlspecialchars($search)?>" autocomplete="off" aria-label="Cari berita">
      <button type="submit">Cari</button>
    </form>
  </div>

  <nav class="cat-nav" aria-label="Kategori">
    <div class="container cat-nav-outer">
      <button class="cat-nav-arrow left hidden" id="catLeft" aria-label="Scroll kiri"><i class="bi bi-chevron-left"></i></button>
      <div class="cat-nav-wrap" id="catNavWrap" role="list">
        <a href="dashboardpembaca.php" class="cat-lnk <?=$filter===''&&$tab==='beranda'?'on':''?>" role="listitem">Semua</a>
        <?php
        $subNamaHidden = array_map('strtolower', array_column($subKatGayaHidup, 'nama_kategori'));
        foreach($kats as $k):
          if (in_array(strtolower($k['nama_kategori']), $subNamaHidden)) continue;
        ?>
        <a href="dashboardpembaca.php?kategori=<?=$k['id_kategori']?>"
           class="cat-lnk <?=($filter==$k['id_kategori'] || ($isGayaHidupPage && strcasecmp($k['nama_kategori'],'Gaya Hidup')===0))?'on':''?>"
           role="listitem"><?=htmlspecialchars($k['nama_kategori'])?></a>
        <?php endforeach; ?>
      </div>
      <button class="cat-nav-arrow right hidden" id="catRight" aria-label="Scroll kanan"><i class="bi bi-chevron-right"></i></button>
    </div>
  </nav>
</header>

<!-- READER TABS -->
<div class="reader-tabs-bar">
  <div class="container">
    <div class="reader-tabs-inner">
      <a href="dashboardpembaca.php" class="rtab <?=$tab==='beranda'?'active':''?>">
        <i class="bi bi-house"></i> Beranda
      </a>
      <div class="rtab-sep"></div>
      <a href="dashboardpembaca.php?tab=bookmark" class="rtab bm-tab <?=$tab==='bookmark'?'active':''?>">
        <i class="bi bi-bookmark-heart"></i> Bookmark
        <?php if($bmTotal>0): ?><span class="rtab-count"><?=$bmTotal?></span><?php endif; ?>
      </a>
      <a href="dashboardpembaca.php?tab=history" class="rtab hist-tab <?=$tab==='history'?'active':''?>">
        <i class="bi bi-clock-history"></i> Riwayat Baca
        <?php if($histTotal>0): ?><span class="rtab-count"><?=$histTotal?></span><?php endif; ?>
      </a>
    </div>
  </div>
</div>

<!-- TRENDING BAR -->
<?php
$trendRows = mysqli_query($koneksi, "SELECT a.id_artikel, a.judul FROM artikel a $w ORDER BY a.tgl_posting DESC LIMIT 7");
$trendItems = [];
while ($tr = mysqli_fetch_assoc($trendRows)) $trendItems[] = $tr;
?>
<?php if (!empty($trendItems) && $tab === 'beranda'): ?>
<div class="trending-bar">
  <div class="container">
    <div class="trending-inner">
      <span class="trending-label"><i class="bi bi-fire"></i> Trending</span>
      <?php foreach($trendItems as $ti): ?>
      <a href="../public/artikel.php?id=<?=$ti['id_artikel']?>" class="trending-item"><?=htmlspecialchars($ti['judul'])?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- PAGE -->
<div class="page">
  <div class="container">

    <?php if ($tab === 'beranda'): ?>
    <!-- ════════════════════════════════════════
         TAB: BERANDA
    ════════════════════════════════════════ -->
    <div class="<?=$isGayaHidupPage?'layout-gh':'layout'?>">

      <?php if ($isGayaHidupPage): ?>
      <aside class="gh-subnav" aria-label="Sub kategori">
        <div class="gh-subnav-head">Gaya Hidup</div>
        <?php $ghAllLink = $gayaHidupId ? "dashboardpembaca.php?kategori=$gayaHidupId" : "dashboardpembaca.php"; ?>
        <a href="<?=$ghAllLink?>" class="gh-subnav-link <?=$activeKatNama==='Gaya Hidup'?'active':''?>"><i class="bi bi-grid-3x3-gap"></i> Semua</a>
        <?php foreach($subKatGayaHidup as $sk):
          $icon = $ghIcons[$sk['nama_kategori']] ?? 'bi-tag'; ?>
        <a href="dashboardpembaca.php?kategori=<?=$sk['id_kategori']?>" class="gh-subnav-link <?=$filter==$sk['id_kategori']?'active':''?>">
          <i class="bi <?=$icon?>"></i> <?=htmlspecialchars($sk['nama_kategori'])?>
        </a>
        <?php endforeach; ?>
      </aside>
      <?php endif; ?>

      <!-- MAIN CONTENT -->
      <main>
        <?php if ($isGayaHidupPage): ?>
        <div class="gh-subnav-mobile" role="list">
          <?php $ghAllLink = $gayaHidupId ? "dashboardpembaca.php?kategori=$gayaHidupId" : "dashboardpembaca.php"; ?>
          <a href="<?=$ghAllLink?>" class="gh-subnav-pill <?=$activeKatNama==='Gaya Hidup'?'active':''?>" role="listitem"><i class="bi bi-grid-3x3-gap"></i> Semua</a>
          <?php foreach($subKatGayaHidup as $sk):
            $icon = $ghIcons[$sk['nama_kategori']] ?? 'bi-tag'; ?>
          <a href="dashboardpembaca.php?kategori=<?=$sk['id_kategori']?>" class="gh-subnav-pill <?=$filter==$sk['id_kategori']?'active':''?>" role="listitem">
            <i class="bi <?=$icon?>"></i> <?=htmlspecialchars($sk['nama_kategori'])?>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($search): ?>
        <!-- SEARCH RESULTS -->
        <div class="sr-banner">
          <div class="sr-banner-txt">
            <?php if($totalCount>0): ?>
              Ditemukan <strong><?=$totalCount?> berita</strong> untuk &ldquo;<strong><?=htmlspecialchars($search)?></strong>&rdquo;
            <?php else: ?>
              Tidak ada hasil untuk &ldquo;<strong><?=htmlspecialchars($search)?></strong>&rdquo;
            <?php endif; ?>
          </div>
          <a href="dashboardpembaca.php" class="sr-clear"><i class="bi bi-x-lg"></i> Hapus</a>
        </div>
        <?php if(empty($berita)): ?>
          <div class="sr-empty"><i class="bi bi-search"></i><p>Berita tidak ditemukan</p><small>Coba kata kunci lain.</small></div>
        <?php else: ?>
          <div><?php foreach($berita as $n): $ni=img($n);
            $jhl=htmlspecialchars($n['judul']);
            if($search) $jhl=preg_replace('/('.preg_quote(htmlspecialchars($search),'/').')/i','<mark>$1</mark>',$jhl); ?>
          <a href="../public/artikel.php?id=<?=$n['id_artikel']?>" class="sr-item">
            <?php if($ni): ?><div class="sr-img-wrap"><img class="sr-img" src="<?=htmlspecialchars($ni)?>" alt=""></div>
            <?php else: ?><div class="sr-ph"><i class="bi bi-image"></i></div><?php endif; ?>
            <div class="sr-info">
              <span class="sr-cat"><?=htmlspecialchars($n['nama_kategori'])?></span>
              <div class="sr-ttl"><?=$jhl?></div>
              <div class="sr-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?> · <?=tgl($n['tgl_posting'])?></div>
            </div>
          </a>
          <?php endforeach; ?></div>
        <?php endif; ?>

        <?php elseif ($isGayaHidupPage): ?>
        <!-- GAYA HIDUP -->
        <?php $bannerDesc=$ghDescs[$activeKatNama]??'Kesehatan, kuliner, travel & tren terkini';
              $bannerIcon=$ghIcons[$activeKatNama]??'bi-stars'; ?>
        <div class="gh-banner">
          <div class="gh-banner-icon"><i class="bi <?=$bannerIcon?>"></i></div>
          <div class="gh-banner-text"><h3><?=htmlspecialchars($activeKatNama)?></h3><p><?=$bannerDesc?></p></div>
        </div>
        <?php if(empty($berita)): ?>
          <div class="empty-state"><i class="bi bi-newspaper"></i><p>Belum ada berita.</p></div>
        <?php else: ?>
          <div class="sec-hd"><div class="sec-hd-label">Berita Terbaru</div><div class="sec-hd-line"></div></div>
          <div class="gh-grid">
            <?php foreach($berita as $n): $ni=img($n); $isBm=in_array($n['id_artikel'],$bmIds); ?>
            <a href="../public/artikel.php?id=<?=$n['id_artikel']?>" class="gh-card" style="position:relative">
              <?php if($ni): ?><div class="gh-card-img-wrap"><img class="gh-card-img" src="<?=htmlspecialchars($ni)?>" alt=""></div>
              <?php else: ?><div class="gh-card-img-ph"><i class="bi bi-image"></i></div><?php endif; ?>
              <button class="bm-btn card-bm-overlay <?=$isBm?'saved':''?>"
                onclick="event.preventDefault();toggleBookmark(this,<?=$n['id_artikel']?>)"
                data-id="<?=$n['id_artikel']?>" title="Bookmark">
                <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
              </button>
              <div class="gh-card-body">
                <div class="gh-card-cat"><?=htmlspecialchars($n['nama_kategori'])?></div>
                <div class="gh-card-title"><?=htmlspecialchars($n['judul'])?></div>
                <div class="gh-card-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?> · <?=tgl($n['tgl_posting'])?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- HALAMAN UTAMA / KATEGORI -->
        <?php if(empty($berita)): ?>
          <div class="empty-state"><i class="bi bi-newspaper"></i><p>Belum ada berita.</p></div>
        <?php else:
          if($page===1):
            $h=$berita[0]; $himg=img($h);
            $heroSides=[];
            for($i=1;$i<count($berita)&&count($heroSides)<2;$i++){if(img($berita[$i]))$heroSides[]=['index'=>$i,'data'=>$berita[$i]];}
            $useHeroGrid=$himg&&count($heroSides)>=1;
            if($useHeroGrid){
              $usedIdx=[0];foreach($heroSides as $hs)$usedIdx[]=$hs['index'];
              $remaining=[];for($i=0;$i<count($berita);$i++){if(!in_array($i,$usedIdx))$remaining[]=$berita[$i];}
            } else { $remaining=array_slice($berita,$himg?1:0); }
            $cards=array_slice($remaining,0,3);
            $listItems=array_slice($remaining,3); ?>

          <div class="hero-box">
            <?php if($useHeroGrid): ?>
            <div class="hero-grid" style="position:relative">
              <?php $isBm=in_array($h['id_artikel'],$bmIds); ?>
              <a href="../public/artikel.php?id=<?=$h['id_artikel']?>" class="hg-main">
                <img src="<?=htmlspecialchars($himg)?>" alt="<?=htmlspecialchars($h['judul'])?>">
                <div class="hg-main-body">
                  <span class="badge"><?=htmlspecialchars($h['nama_kategori'])?></span>
                  <h2><?=htmlspecialchars($h['judul'])?></h2>
                  <div class="hg-main-meta">
                    <i class="bi bi-clock"></i> <?=ago($h['tgl_posting'])?> · <?=tgl($h['tgl_posting'])?>
                    <!-- ✅ FIXED: tombol bookmark hero pakai .bm-btn-hero -->
                    <button class="bm-btn-hero <?=$isBm?'saved':''?>"
                      style="margin-left:auto"
                      onclick="event.preventDefault();toggleBookmark(this,<?=$h['id_artikel']?>)" title="Bookmark">
                      <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
                    </button>
                  </div>
                </div>
              </a>
              <?php foreach($heroSides as $hs): $s=$hs['data']; $si=img($s); $isBm=in_array($s['id_artikel'],$bmIds); ?>
              <a href="../public/artikel.php?id=<?=$s['id_artikel']?>" class="hg-side">
                <img src="<?=htmlspecialchars($si)?>" alt="">
                <div class="hg-side-body">
                  <span class="badge" style="font-size:.52rem;padding:2px 8px"><?=htmlspecialchars($s['nama_kategori'])?></span>
                  <h4><?=htmlspecialchars($s['judul'])?></h4>
                  <div class="hg-side-meta">
                    <?=ago($s['tgl_posting'])?>
                    <!-- ✅ FIXED: tombol bookmark hg-side pakai .bm-btn-hero -->
                    <button class="bm-btn-hero <?=$isBm?'saved':''?>"
                      style="margin-left:6px"
                      onclick="event.preventDefault();toggleBookmark(this,<?=$s['id_artikel']?>)" title="Bookmark">
                      <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
                    </button>
                  </div>
                </div>
              </a>
              <?php endforeach;
              if(count($heroSides)<2):
                $fallback=null;
                for($i=1;$i<count($berita);$i++){if(!in_array($i,$usedIdx)){$fallback=$berita[$i];$usedIdx[]=$i;$remaining=[];for($j=0;$j<count($berita);$j++){if(!in_array($j,$usedIdx))$remaining[]=$berita[$j];}$cards=array_slice($remaining,0,3);$listItems=array_slice($remaining,3);break;}}
                if($fallback): $isBm=in_array($fallback['id_artikel'],$bmIds); ?>
              <a href="../public/artikel.php?id=<?=$fallback['id_artikel']?>" class="hg-side" style="display:flex;flex-direction:column;justify-content:flex-end">
                <div style="padding:15px;display:flex;flex-direction:column;gap:5px">
                  <span class="badge ghost" style="font-size:.52rem"><?=htmlspecialchars($fallback['nama_kategori'])?></span>
                  <h4 style="font-family:var(--fd);font-size:.88rem;font-weight:700;color:var(--text);line-height:1.32"><?=htmlspecialchars($fallback['judul'])?></h4>
                  <div style="display:flex;align-items:center;gap:6px;font-family:var(--fs);font-size:.65rem;color:var(--text-muted)">
                    <?=ago($fallback['tgl_posting'])?>
                    <!-- ✅ FIXED: tombol bookmark fallback pakai .bm-btn-hero -->
                    <button class="bm-btn-hero <?=$isBm?'saved':''?>"
                      style="margin-left:auto"
                      onclick="event.preventDefault();toggleBookmark(this,<?=$fallback['id_artikel']?>)" title="Bookmark">
                      <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
                    </button>
                  </div>
                </div>
              </a>
              <?php endif; endif; ?>
            </div>
            <?php elseif($himg): ?>
            <?php $isBm=in_array($h['id_artikel'],$bmIds); ?>
            <a href="../public/artikel.php?id=<?=$h['id_artikel']?>" class="hero-single">
              <img src="<?=htmlspecialchars($himg)?>" alt="">
              <div class="hero-single-body">
                <span class="badge"><?=htmlspecialchars($h['nama_kategori'])?></span>
                <h2><?=htmlspecialchars($h['judul'])?></h2>
                <div style="font-family:var(--fs);font-size:.7rem;color:rgba(255,255,255,.52);display:flex;align-items:center;gap:7px">
                  <i class="bi bi-clock"></i><?=ago($h['tgl_posting'])?>
                  <!-- ✅ FIXED: tombol bookmark hero-single pakai .bm-btn-hero -->
                  <button class="bm-btn-hero <?=$isBm?'saved':''?>"
                    style="margin-left:auto"
                    onclick="event.preventDefault();toggleBookmark(this,<?=$h['id_artikel']?>)" title="Bookmark">
                    <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
                  </button>
                </div>
              </div>
            </a>
            <?php else: ?>
            <a href="../public/artikel.php?id=<?=$h['id_artikel']?>" class="hero-text">
              <span class="badge"><?=htmlspecialchars($h['nama_kategori'])?></span>
              <h2><?=htmlspecialchars($h['judul'])?></h2>
              <div class="meta"><i class="bi bi-clock"></i> <?=ago($h['tgl_posting'])?> · <?=tgl($h['tgl_posting'])?></div>
            </a>
            <?php endif; ?>
          </div>

          <?php if(!empty($cards)): ?>
          <div class="sec-hd"><div class="sec-hd-label">Berita Pilihan</div><div class="sec-hd-line"></div></div>
          <div class="card-row <?=count($cards)>=3?'card-row-3':'card-row-2'?>">
            <?php foreach($cards as $c): $ci=img($c); $isBm=in_array($c['id_artikel'],$bmIds); ?>
            <a href="../public/artikel.php?id=<?=$c['id_artikel']?>" class="card" style="position:relative">
              <?php if($ci): ?><div class="card-img-wrap"><img class="card-img" src="<?=htmlspecialchars($ci)?>" alt=""></div><?php endif; ?>
              <!-- ✅ FIXED: .card-bm-overlay sudah ada style gelap di CSS -->
              <button class="bm-btn card-bm-overlay <?=$isBm?'saved':''?>"
                onclick="event.preventDefault();toggleBookmark(this,<?=$c['id_artikel']?>)" title="Bookmark">
                <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
              </button>
              <div class="card-body">
                <span class="badge ghost" style="font-size:.56rem"><?=htmlspecialchars($c['nama_kategori'])?></span>
                <h5><?=htmlspecialchars($c['judul'])?></h5>
                <div class="card-meta"><i class="bi bi-clock"></i> <?=ago($c['tgl_posting'])?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if(!empty($listItems)): ?>
          <div class="divider"><div class="div-line"></div><div class="div-dot"></div><div class="div-line"></div></div>
          <div class="sec-hd"><div class="sec-hd-label">Berita Terkini</div><div class="sec-hd-line"></div></div>
          <div class="nlist">
            <?php foreach($listItems as $n): $ni=img($n); $isBm=in_array($n['id_artikel'],$bmIds); ?>
            <a href="../public/artikel.php?id=<?=$n['id_artikel']?>" class="nli">
              <?php if($ni): ?><div class="nli-img-wrap"><img class="nli-img" src="<?=htmlspecialchars($ni)?>" alt=""></div>
              <?php else: ?><div class="nli-ph"><i class="bi bi-image"></i></div><?php endif; ?>
              <div class="nli-body">
                <span class="nli-cat"><?=htmlspecialchars($n['nama_kategori'])?></span>
                <div class="nli-ttl"><?=htmlspecialchars($n['judul'])?></div>
                <div class="nli-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?> · <?=tgl($n['tgl_posting'])?></div>
              </div>
              <div class="nli-actions">
                <button class="bm-btn <?=$isBm?'saved':''?>"
                  onclick="event.preventDefault();toggleBookmark(this,<?=$n['id_artikel']?>)" title="Bookmark">
                  <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
                </button>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php else: ?>
          <!-- Page 2+ -->
          <div class="sec-hd" style="margin-top:4px">
            <div class="sec-hd-label">Berita Terkini · Hal. <?=$page?></div>
            <div class="sec-hd-line"></div>
          </div>
          <div class="nlist">
            <?php foreach($berita as $n): $ni=img($n); $isBm=in_array($n['id_artikel'],$bmIds); ?>
            <a href="../public/artikel.php?id=<?=$n['id_artikel']?>" class="nli">
              <?php if($ni): ?><div class="nli-img-wrap"><img class="nli-img" src="<?=htmlspecialchars($ni)?>" alt=""></div>
              <?php else: ?><div class="nli-ph"><i class="bi bi-image"></i></div><?php endif; ?>
              <div class="nli-body">
                <span class="nli-cat"><?=htmlspecialchars($n['nama_kategori'])?></span>
                <div class="nli-ttl"><?=htmlspecialchars($n['judul'])?></div>
                <div class="nli-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?> · <?=tgl($n['tgl_posting'])?></div>
              </div>
              <div class="nli-actions">
                <button class="bm-btn <?=$isBm?'saved':''?>"
                  onclick="event.preventDefault();toggleBookmark(this,<?=$n['id_artikel']?>)" title="Bookmark">
                  <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
                </button>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; endif; ?>
        <?php endif; ?>

        <!-- PAGINATION -->
        <?php if($totalPages>1):
          $baseParams=[];
          if($filter) $baseParams['kategori']=$filter;
          if($search) $baseParams['q']=$search;
          $range=2; $start=max(1,$page-$range); $end=min($totalPages,$page+$range); ?>
        <nav class="pagination-wrap" aria-label="Navigasi halaman">
          <span class="pg-info">Total <strong><?=number_format($totalCount)?></strong> berita</span>
          <?php if($page>1): ?><a href="<?=pgUrl($page-1,$baseParams)?>" class="pg-btn"><i class="bi bi-chevron-left"></i></a>
          <?php else: ?><span class="pg-btn disabled"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
          <?php if($start>1): ?><a href="<?=pgUrl(1,$baseParams)?>" class="pg-btn">1</a><?php if($start>2): ?><span class="pg-ellipsis">…</span><?php endif; endif; ?>
          <?php for($i=$start;$i<=$end;$i++): ?>
            <?php if($i===$page): ?><span class="pg-btn on" aria-current="page"><?=$i?></span>
            <?php else: ?><a href="<?=pgUrl($i,$baseParams)?>" class="pg-btn"><?=$i?></a><?php endif; ?>
          <?php endfor; ?>
          <?php if($end<$totalPages): ?><?php if($end<$totalPages-1): ?><span class="pg-ellipsis">…</span><?php endif; ?><a href="<?=pgUrl($totalPages,$baseParams)?>" class="pg-btn"><?=$totalPages?></a><?php endif; ?>
          <?php if($page<$totalPages): ?><a href="<?=pgUrl($page+1,$baseParams)?>" class="pg-btn"><i class="bi bi-chevron-right"></i></a>
          <?php else: ?><span class="pg-btn disabled"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
          <div class="pg-jump">
            <input type="number" id="pgJumpInput" min="1" max="<?=$totalPages?>" placeholder="<?=$page?>">
            <button class="pg-jump-btn" onclick="pgJump()">Pergi</button>
          </div>
        </nav>
        <?php endif; ?>
      </main>

      <!-- SIDEBAR KANAN -->
      <aside class="sidebar" aria-label="Sidebar">
        <div class="user-stats-card">
          <div class="usc-greeting">Selamat datang,</div>
          <div class="usc-name"><?=htmlspecialchars($userNama)?></div>
          <div class="usc-stats">
            <a href="dashboardpembaca.php?tab=bookmark" style="text-decoration:none">
              <div class="usc-stat">
                <div class="usc-stat-num" style="color:var(--bm-color)"><?=$bmTotal?></div>
                <div class="usc-stat-label"><i class="bi bi-bookmark-heart"></i> Bookmark</div>
              </div>
            </a>
            <a href="dashboardpembaca.php?tab=history" style="text-decoration:none">
              <div class="usc-stat">
                <div class="usc-stat-num" style="color:var(--hist-color)"><?=$histTotal?></div>
                <div class="usc-stat-label"><i class="bi bi-clock-history"></i> Riwayat</div>
              </div>
            </a>
          </div>
        </div>

        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label">Berita Terbaru</div></div>
          <?php mysqli_data_seek($sideRows,0);
          while($s=mysqli_fetch_assoc($sideRows)):
            $sImg=null; $sf=!empty($s['thumbnail'])?$s['thumbnail']:'';
            if($sf){if(filter_var($sf,FILTER_VALIDATE_URL))$sImg=$sf;elseif(file_exists("../uploads/$sf"))$sImg="../uploads/$sf";} ?>
          <a href="../public/artikel.php?id=<?=$s['id_artikel']?>" class="sb-item">
            <?php if($sImg): ?><div class="sb-thumb-wrap"><img class="sb-thumb" src="<?=htmlspecialchars($sImg)?>" alt=""></div>
            <?php else: ?><div class="sb-ph"><i class="bi bi-image"></i></div><?php endif; ?>
            <div class="sb-info">
              <div class="sb-cat"><?=htmlspecialchars($s['nama_kategori'])?></div>
              <div class="sb-ttl"><?=htmlspecialchars($s['judul'])?></div>
              <div class="sb-meta"><i class="bi bi-clock"></i> <?=ago($s['tgl_posting'])?></div>
            </div>
          </a>
          <?php endwhile; ?>
        </div>

        <?php $pq=mysqli_query($koneksi,"SELECT a.id_artikel,a.judul,a.tgl_posting,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w ORDER BY a.tgl_posting DESC LIMIT 5"); ?>
        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label">Terpopuler</div></div>
          <?php $pn=1; while($p=mysqli_fetch_assoc($pq)): ?>
          <a href="../public/artikel.php?id=<?=$p['id_artikel']?>" class="pop-item">
            <div class="pop-n"><?=str_pad($pn++,2,'0',STR_PAD_LEFT)?></div>
            <div class="pop-info">
              <div class="pop-ttl"><?=htmlspecialchars($p['judul'])?></div>
              <div class="pop-meta"><?=htmlspecialchars($p['nama_kategori'])?> · <?=ago($p['tgl_posting'])?></div>
            </div>
          </a>
          <?php endwhile; ?>
        </div>

        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label">Topik</div></div>
          <div class="pills">
            <a href="dashboardpembaca.php" class="pill <?=$filter===''?'on':''?>">Semua</a>
            <?php foreach($kats as $k): ?>
            <a href="dashboardpembaca.php?kategori=<?=$k['id_kategori']?>" class="pill <?=$filter==$k['id_kategori']?'on':''?>"><?=htmlspecialchars($k['nama_kategori'])?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </aside>
    </div>

    <!-- SIDEBAR BOTTOM (tablet/mobile) -->
    <div class="sidebar-bottom">
      <div class="sidebar-bottom-inner">
        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label">Berita Terbaru</div></div>
          <?php $sr2=mysqli_query($koneksi,"SELECT a.id_artikel,a.judul,a.tgl_posting,a.thumbnail,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w ORDER BY a.tgl_posting DESC LIMIT 5");
          while($s=mysqli_fetch_assoc($sr2)):
            $sImg=null;$sf=!empty($s['thumbnail'])?$s['thumbnail']:'';
            if($sf){if(filter_var($sf,FILTER_VALIDATE_URL))$sImg=$sf;elseif(file_exists("../uploads/$sf"))$sImg="../uploads/$sf";} ?>
          <a href="../public/artikel.php?id=<?=$s['id_artikel']?>" class="sb-item">
            <?php if($sImg): ?><div class="sb-thumb-wrap"><img class="sb-thumb" src="<?=htmlspecialchars($sImg)?>" alt=""></div>
            <?php else: ?><div class="sb-ph"><i class="bi bi-image"></i></div><?php endif; ?>
            <div class="sb-info">
              <div class="sb-cat"><?=htmlspecialchars($s['nama_kategori'])?></div>
              <div class="sb-ttl"><?=htmlspecialchars($s['judul'])?></div>
              <div class="sb-meta"><i class="bi bi-clock"></i> <?=ago($s['tgl_posting'])?></div>
            </div>
          </a>
          <?php endwhile; ?>
        </div>
        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label">Topik</div></div>
          <div class="pills">
            <a href="dashboardpembaca.php" class="pill <?=$filter===''?'on':''?>">Semua</a>
            <?php foreach($kats as $k): ?>
            <a href="dashboardpembaca.php?kategori=<?=$k['id_kategori']?>" class="pill <?=$filter==$k['id_kategori']?'on':''?>"><?=htmlspecialchars($k['nama_kategori'])?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <?php elseif ($tab === 'bookmark'): ?>
    <!-- ════════════════════════════════════════
         TAB: BOOKMARK
    ════════════════════════════════════════ -->
    <div class="layout">
      <main>
        <div class="feature-hero bm">
          <div class="feature-hero-left">
            <div class="feature-hero-icon"><i class="bi bi-bookmark-heart-fill"></i></div>
            <div class="feature-hero-text">
              <h2>Bookmark Saya</h2>
              <p>Artikel yang kamu simpan untuk dibaca nanti</p>
            </div>
          </div>
          <?php if($bmTotal>0): ?>
          <div class="feature-hero-badge"><?=$bmTotal?> artikel tersimpan</div>
          <?php endif; ?>
        </div>

        <?php if(empty($bmList)): ?>
        <div class="empty-feature">
          <div class="empty-feature-icon bm"><i class="bi bi-bookmark-heart"></i></div>
          <h3>Belum ada bookmark</h3>
          <p>Tekan ikon <i class="bi bi-bookmark"></i> di artikel mana saja untuk menyimpannya ke sini dan membacanya kapan pun.</p>
        </div>
        <?php else: ?>
        <div class="bm-grid" id="bmGrid">
          <?php foreach($bmList as $n): $ni=img($n); ?>
          <div class="bm-card" id="bmcard-<?=$n['id_artikel']?>">
            <div class="bm-card-img-wrap">
              <?php if($ni): ?><a href="../public/artikel.php?id=<?=$n['id_artikel']?>"><img class="bm-card-img" src="<?=htmlspecialchars($ni)?>" alt=""></a>
              <?php else: ?><a href="../public/artikel.php?id=<?=$n['id_artikel']?>" class="bm-card-ph"><i class="bi bi-image"></i></a><?php endif; ?>
              <button class="bm-card-remove" onclick="removeBookmark(this,<?=$n['id_artikel']?>)" title="Hapus bookmark">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
            <div class="bm-card-body">
              <div class="bm-card-cat"><?=htmlspecialchars($n['nama_kategori'])?></div>
              <a href="../public/artikel.php?id=<?=$n['id_artikel']?>" class="bm-card-title"><?=htmlspecialchars($n['judul'])?></a>
              <div class="bm-card-meta">
                <span><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?></span>
                <span class="bm-saved-date"><i class="bi bi-bookmark-check"></i> <?=ago($n['bm_date'])?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if($bmPages>1):
          $range=2; $start=max(1,$bmPage-$range); $end=min($bmPages,$bmPage+$range); ?>
        <nav class="pagination-wrap">
          <?php if($bmPage>1): ?><a href="dashboardpembaca.php?tab=bookmark&bm_page=<?=$bmPage-1?>" class="pg-btn"><i class="bi bi-chevron-left"></i></a><?php else: ?><span class="pg-btn disabled"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
          <?php if($start>1): ?><a href="dashboardpembaca.php?tab=bookmark&bm_page=1" class="pg-btn">1</a><?php if($start>2): ?><span class="pg-ellipsis">…</span><?php endif; endif; ?>
          <?php for($i=$start;$i<=$end;$i++): ?>
            <?php if($i===$bmPage): ?><span class="pg-btn on"><?=$i?></span>
            <?php else: ?><a href="dashboardpembaca.php?tab=bookmark&bm_page=<?=$i?>" class="pg-btn"><?=$i?></a><?php endif; ?>
          <?php endfor; ?>
          <?php if($end<$bmPages): ?><?php if($end<$bmPages-1): ?><span class="pg-ellipsis">…</span><?php endif; ?><a href="dashboardpembaca.php?tab=bookmark&bm_page=<?=$bmPages?>" class="pg-btn"><?=$bmPages?></a><?php endif; ?>
          <?php if($bmPage<$bmPages): ?><a href="dashboardpembaca.php?tab=bookmark&bm_page=<?=$bmPage+1?>" class="pg-btn"><i class="bi bi-chevron-right"></i></a><?php else: ?><span class="pg-btn disabled"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
      </main>

      <aside class="sidebar">
        <div class="user-stats-card">
          <div class="usc-greeting">Selamat datang,</div>
          <div class="usc-name"><?=htmlspecialchars($userNama)?></div>
          <div class="usc-stats">
            <div class="usc-stat">
              <div class="usc-stat-num" style="color:var(--bm-color)" id="bmCountStat"><?=$bmTotal?></div>
              <div class="usc-stat-label"><i class="bi bi-bookmark-heart"></i> Bookmark</div>
            </div>
            <a href="dashboardpembaca.php?tab=history" style="text-decoration:none">
              <div class="usc-stat">
                <div class="usc-stat-num" style="color:var(--hist-color)"><?=$histTotal?></div>
                <div class="usc-stat-label"><i class="bi bi-clock-history"></i> Riwayat</div>
              </div>
            </a>
          </div>
        </div>
        <div class="sb-card" style="overflow:hidden">
          <div class="sb-head"><div class="sb-head-label" style="--blue:var(--bm-color)">Tips Bookmark</div></div>
          <div style="padding:13px 14px;font-family:var(--fs);font-size:.8rem;color:var(--text-muted);line-height:1.65">
            <p style="margin-bottom:8px">Tekan <i class="bi bi-bookmark" style="color:var(--bm-color)"></i> di artikel mana saja untuk menyimpannya ke daftar ini.</p>
            <p>Bookmark tersimpan di akun kamu dan bisa diakses kapan pun.</p>
          </div>
        </div>
        <?php $pq3=mysqli_query($koneksi,"SELECT a.id_artikel,a.judul,a.tgl_posting,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w ORDER BY a.tgl_posting DESC LIMIT 5"); ?>
        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label">Artikel Terbaru</div></div>
          <?php $pn3=1; while($p3=mysqli_fetch_assoc($pq3)): ?>
          <a href="../public/artikel.php?id=<?=$p3['id_artikel']?>" class="pop-item">
            <div class="pop-n"><?=str_pad($pn3++,2,'0',STR_PAD_LEFT)?></div>
            <div class="pop-info">
              <div class="pop-ttl"><?=htmlspecialchars($p3['judul'])?></div>
              <div class="pop-meta"><?=htmlspecialchars($p3['nama_kategori'])?> · <?=ago($p3['tgl_posting'])?></div>
            </div>
          </a>
          <?php endwhile; ?>
        </div>
      </aside>
    </div>

    <?php elseif ($tab === 'history'): ?>
    <!-- ════════════════════════════════════════
         TAB: RIWAYAT BACA
    ════════════════════════════════════════ -->
    <div class="layout">
      <main>
        <div class="feature-hero hist">
          <div class="feature-hero-left">
            <div class="feature-hero-icon"><i class="bi bi-clock-history"></i></div>
            <div class="feature-hero-text">
              <h2>Riwayat Baca</h2>
              <p>Artikel yang pernah kamu baca sebelumnya</p>
            </div>
          </div>
          <?php if($histTotal>0): ?>
          <div class="feature-hero-badge"><?=$histTotal?> artikel dibaca</div>
          <?php endif; ?>
        </div>

        <?php if(empty($histList)): ?>
        <div class="empty-feature">
          <div class="empty-feature-icon hist"><i class="bi bi-clock-history"></i></div>
          <h3>Belum ada riwayat</h3>
          <p>Artikel yang kamu baca akan muncul di sini secara otomatis.</p>
        </div>
        <?php else: ?>
        <div class="hist-header">
          <div class="hist-header-left">
            <div class="sec-hd-label" style="margin:0"><i class="bi bi-clock-history" style="color:var(--hist-color)"></i> <?=$histTotal?> artikel dibaca</div>
          </div>
          <button class="hist-clear-btn" onclick="clearHistory()" id="clearHistBtn">
            <i class="bi bi-trash3"></i> Hapus semua
          </button>
        </div>

        <?php
        $grouped = [];
        foreach ($histList as $h) {
            $d = date('Y-m-d', strtotime($h['last_read']));
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            if ($d === $today) $label = 'Hari ini';
            elseif ($d === $yesterday) $label = 'Kemarin';
            else $label = tgl($h['last_read']);
            $grouped[$label][] = $h;
        }
        foreach ($grouped as $label => $items): ?>
        <div class="hist-group" id="histGroup-<?=md5($label)?>">
          <div class="hist-group-label"><?=$label?></div>
          <?php foreach($items as $h): $hi=img($h); $isBm=in_array($h['id_artikel'],$bmIds); ?>
          <div class="hist-item" id="histitem-<?=$h['id_artikel']?>">
            <?php if($hi): ?>
            <a href="../public/artikel.php?id=<?=$h['id_artikel']?>" class="hist-item-img-wrap">
              <img class="hist-item-img" src="<?=htmlspecialchars($hi)?>" alt="">
            </a>
            <?php else: ?><div class="hist-item-ph"><i class="bi bi-image"></i></div><?php endif; ?>
            <div class="hist-item-body">
              <span class="hist-item-cat"><?=htmlspecialchars($h['nama_kategori'])?></span>
              <a href="../public/artikel.php?id=<?=$h['id_artikel']?>" class="hist-item-title"><?=htmlspecialchars($h['judul'])?></a>
              <div class="hist-item-meta">
                <span><i class="bi bi-clock"></i> Dibaca <?=ago($h['last_read'])?></span>
                <span><i class="bi bi-calendar3"></i> <?=tgl($h['tgl_posting'])?></span>
              </div>
            </div>
            <div class="hist-item-actions">
              <button class="bm-btn <?=$isBm?'saved':''?>"
                onclick="toggleBookmark(this,<?=$h['id_artikel']?>)" title="Bookmark">
                <i class="bi bi-bookmark<?=$isBm?'-fill':''?>"></i>
              </button>
              <button class="hist-rm-btn" onclick="removeHistory(this,<?=$h['id_artikel']?>)" title="Hapus dari riwayat">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <?php if($histPages>1):
          $range=2; $start=max(1,$histPage-$range); $end=min($histPages,$histPage+$range); ?>
        <nav class="pagination-wrap">
          <?php if($histPage>1): ?><a href="dashboardpembaca.php?tab=history&h_page=<?=$histPage-1?>" class="pg-btn"><i class="bi bi-chevron-left"></i></a><?php else: ?><span class="pg-btn disabled"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
          <?php if($start>1): ?><a href="dashboardpembaca.php?tab=history&h_page=1" class="pg-btn">1</a><?php if($start>2): ?><span class="pg-ellipsis">…</span><?php endif; endif; ?>
          <?php for($i=$start;$i<=$end;$i++): ?>
            <?php if($i===$histPage): ?><span class="pg-btn on"><?=$i?></span>
            <?php else: ?><a href="dashboardpembaca.php?tab=history&h_page=<?=$i?>" class="pg-btn"><?=$i?></a><?php endif; ?>
          <?php endfor; ?>
          <?php if($end<$histPages): ?><?php if($end<$histPages-1): ?><span class="pg-ellipsis">…</span><?php endif; ?><a href="dashboardpembaca.php?tab=history&h_page=<?=$histPages?>" class="pg-btn"><?=$histPages?></a><?php endif; ?>
          <?php if($histPage<$histPages): ?><a href="dashboardpembaca.php?tab=history&h_page=<?=$histPage+1?>" class="pg-btn"><i class="bi bi-chevron-right"></i></a><?php else: ?><span class="pg-btn disabled"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
      </main>

      <aside class="sidebar">
        <div class="user-stats-card">
          <div class="usc-greeting">Selamat datang,</div>
          <div class="usc-name"><?=htmlspecialchars($userNama)?></div>
          <div class="usc-stats">
            <a href="dashboardpembaca.php?tab=bookmark" style="text-decoration:none">
              <div class="usc-stat">
                <div class="usc-stat-num" style="color:var(--bm-color)"><?=$bmTotal?></div>
                <div class="usc-stat-label"><i class="bi bi-bookmark-heart"></i> Bookmark</div>
              </div>
            </a>
            <div class="usc-stat">
              <div class="usc-stat-num" style="color:var(--hist-color)" id="histCountStat"><?=$histTotal?></div>
              <div class="usc-stat-label"><i class="bi bi-clock-history"></i> Riwayat</div>
            </div>
          </div>
        </div>
        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label" style="--blue:var(--hist-color)">Tentang Riwayat</div></div>
          <div style="padding:13px 14px;font-family:var(--fs);font-size:.8rem;color:var(--text-muted);line-height:1.65">
            <p style="margin-bottom:8px">Riwayat baca tersimpan otomatis saat kamu membuka artikel.</p>
            <p>Kamu bisa menghapus artikel tertentu atau semua riwayat sekaligus.</p>
          </div>
        </div>
        <?php $pq4=mysqli_query($koneksi,"SELECT a.id_artikel,a.judul,a.tgl_posting,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w ORDER BY a.tgl_posting DESC LIMIT 5"); ?>
        <div class="sb-card">
          <div class="sb-head"><div class="sb-head-label">Artikel Terbaru</div></div>
          <?php $pn4=1; while($p4=mysqli_fetch_assoc($pq4)): ?>
          <a href="../public/artikel.php?id=<?=$p4['id_artikel']?>" class="pop-item">
            <div class="pop-n"><?=str_pad($pn4++,2,'0',STR_PAD_LEFT)?></div>
            <div class="pop-info">
              <div class="pop-ttl"><?=htmlspecialchars($p4['judul'])?></div>
              <div class="pop-meta"><?=htmlspecialchars($p4['nama_kategori'])?> · <?=ago($p4['tgl_posting'])?></div>
            </div>
          </a>
          <?php endwhile; ?>
        </div>
      </aside>
    </div>

    <?php endif; ?>
  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="container">
    <div class="ft-logo">Liy<em>News</em></div>
    <p class="ft-desc">Menyajikan berita terpercaya, akurat, dan terkini untuk seluruh masyarakat Indonesia.</p>
    <nav class="ft-nav">
      <a href="#">Tentang Kami</a><a href="#">Redaksi</a><a href="#">Pedoman Media Siber</a>
      <a href="#">Kebijakan Privasi</a><a href="#">Iklan</a><a href="#">Kontak</a>
    </nav>
    <hr class="ft-hr">
    <div class="ft-bot">
      <div class="ft-copy">&copy; <?=date('Y')?> LiyNews. Semua hak dilindungi.</div>
      <div class="ft-links">
        <a href="#"><i class="bi bi-facebook"></i></a>
        <a href="#"><i class="bi bi-twitter-x"></i></a>
        <a href="#"><i class="bi bi-instagram"></i></a>
        <a href="#"><i class="bi bi-youtube"></i></a>
      </div>
    </div>
  </div>
</footer>

<script>
/* ── Theme ── */
const html=document.documentElement,thBtn=document.getElementById('themeBtn');
function applyTheme(t){
  html.setAttribute('data-theme',t);
  thBtn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';
  thBtn.setAttribute('aria-label',t==='dark'?'Mode terang':'Mode gelap');
}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click',()=>{
  const n=html.getAttribute('data-theme')==='dark'?'light':'dark';
  localStorage.setItem('pb_theme',n); applyTheme(n);
});

/* ── Logout modal ── */
const overlay=document.getElementById('logoutOverlay');
function showLogout(){ overlay.classList.add('show'); }
function hideLogout(){ overlay.classList.remove('show'); }
document.addEventListener('keydown',e=>{ if(e.key==='Escape') hideLogout(); });

/* ── Toast ── */
function showToast(msg, type='info', dur=2800){
  const stack=document.getElementById('toastStack');
  const icons={success:'bi-check-circle-fill',info:'bi-info-circle-fill',warn:'bi-bookmark-heart-fill'};
  const t=document.createElement('div');
  t.className=`toast ${type}`;
  t.innerHTML=`<i class="bi ${icons[type]||icons.info}"></i><span>${msg}</span>`;
  stack.appendChild(t);
  setTimeout(()=>{
    t.classList.add('hide');
    t.addEventListener('animationend',()=>t.remove());
  },dur);
}

/* ── Bookmark toggle ── */
function toggleBookmark(btn, aid){
  if(btn.dataset.saving) return;
  btn.dataset.saving='1';
  const fd=new FormData();
  fd.append('action','toggle_bookmark');
  fd.append('artikel_id',aid);
  fetch('dashboardpembaca.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      delete btn.dataset.saving;
      const icon=btn.querySelector('i');
      if(d.status==='added'){
        btn.classList.add('saved');
        if(icon){ icon.className='bi bi-bookmark-fill'; }
        showToast('Artikel disimpan ke bookmark','warn');
        updateBmBadge(1);
      } else if(d.status==='removed'){
        btn.classList.remove('saved');
        if(icon){ icon.className='bi bi-bookmark'; }
        showToast('Artikel dihapus dari bookmark','info');
        updateBmBadge(-1);
      }
    })
    .catch(()=>{ delete btn.dataset.saving; });
}

function updateBmBadge(delta){
  document.querySelectorAll('.rtab-count,.usc-stat-num').forEach(el=>{
    if(el.closest('.bm-tab')||el.id==='bmCountStat'){
      const v=parseInt(el.textContent||'0')+delta;
      el.textContent=Math.max(0,v);
    }
  });
}

/* ── Remove bookmark (bookmark page) ── */
function removeBookmark(btn, aid){
  const card=document.getElementById('bmcard-'+aid);
  const fd=new FormData();
  fd.append('action','toggle_bookmark');
  fd.append('artikel_id',aid);
  fetch('dashboardpembaca.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      if(d.status==='removed'&&card){
        card.style.transition='opacity .3s,transform .3s';
        card.style.opacity='0'; card.style.transform='scale(.95)';
        setTimeout(()=>{ card.remove(); updateBmBadge(-1); showToast('Dihapus dari bookmark','info'); },300);
      }
    });
}

/* ── Remove single history ── */
function removeHistory(btn, aid){
  const item=document.getElementById('histitem-'+aid);
  const fd=new FormData();
  fd.append('action','remove_history');
  fd.append('artikel_id',aid);
  fetch('dashboardpembaca.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      if(d.status==='ok'&&item){
        item.style.transition='opacity .25s,transform .25s';
        item.style.opacity='0'; item.style.transform='translateX(16px)';
        setTimeout(()=>{ item.remove(); updateHistBadge(-1); showToast('Dihapus dari riwayat','info'); },250);
      }
    });
}

/* ── Clear all history ── */
function clearHistory(){
  if(!confirm('Hapus semua riwayat baca?')) return;
  const fd=new FormData();
  fd.append('action','clear_history');
  fetch('dashboardpembaca.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      if(d.status==='ok'){
        document.querySelectorAll('.hist-group').forEach(g=>g.remove());
        document.querySelector('.hist-header')?.remove();
        showToast('Semua riwayat dihapus','info');
        const stat=document.getElementById('histCountStat');
        if(stat) stat.textContent='0';
        document.querySelectorAll('.rtab.hist-tab .rtab-count').forEach(el=>el.remove());
        const main=document.querySelector('main');
        if(main){
          const ef=document.createElement('div');
          ef.className='empty-feature';
          ef.innerHTML=`<div class="empty-feature-icon hist"><i class="bi bi-clock-history"></i></div><h3>Belum ada riwayat</h3><p>Artikel yang kamu baca akan muncul di sini secara otomatis.</p>`;
          main.insertBefore(ef, main.querySelector('.pagination-wrap')||null);
        }
      }
    });
}

function updateHistBadge(delta){
  const el=document.getElementById('histCountStat');
  if(el){ const v=parseInt(el.textContent||'0')+delta; el.textContent=Math.max(0,v); }
}

/* ── Mobile search toggle ── */
const srchToggle=document.getElementById('srchToggle'),
      mobileBar=document.getElementById('mobileSearchBar'),
      mobileInp=document.getElementById('mobileSearchInput');
if(srchToggle&&mobileBar){
  srchToggle.addEventListener('click',e=>{
    e.stopPropagation();
    const isOpen=mobileBar.classList.toggle('open');
    srchToggle.setAttribute('aria-expanded',isOpen);
    srchToggle.innerHTML=isOpen?'<i class="bi bi-x-lg"></i>':'<i class="bi bi-search"></i>';
    if(isOpen&&mobileInp) setTimeout(()=>mobileInp.focus(),80);
  });
  document.addEventListener('click',e=>{
    if(!mobileBar.contains(e.target)&&e.target!==srchToggle&&!srchToggle.contains(e.target)){
      mobileBar.classList.remove('open');
      srchToggle.setAttribute('aria-expanded','false');
      srchToggle.innerHTML='<i class="bi bi-search"></i>';
    }
  });
}

/* ── Desktop live search ── */
const si=document.getElementById('searchInput'),ldd=document.getElementById('liveDD');
if(si){
  if(ldd) ldd.addEventListener('mousedown',e=>e.preventDefault());
  <?php if($search): ?>requestAnimationFrame(()=>{si.focus();const l=si.value.length;si.setSelectionRange(l,l);});<?php endif; ?>
  let _t;
  si.addEventListener('input',function(){
    clearTimeout(_t);const q=this.value.trim();
    if(q.length<2){if(ldd)ldd.classList.remove('open');return;}
    _t=setTimeout(()=>{window.location.href='dashboardpembaca.php?tab=beranda&q='+encodeURIComponent(q);},700);
  });
  si.addEventListener('keydown',e=>{
    if(e.key==='Enter'){clearTimeout(_t);const q=si.value.trim();if(q.length>=2)window.location.href='dashboardpembaca.php?tab=beranda&q='+encodeURIComponent(q);}
    if(e.key==='Escape'){clearTimeout(_t);window.location.href='dashboardpembaca.php';}
  });
  si.addEventListener('focus',()=>{if(ldd&&si.value.trim().length>=2)ldd.classList.add('open');});
  document.addEventListener('click',e=>{
    const srchEl=si.closest('.srch-desktop');
    if(srchEl&&!srchEl.contains(e.target)&&ldd)ldd.classList.remove('open');
  });
}

/* ── User menu ── */
const uc=document.getElementById('uChip'),ud=document.getElementById('uDrop');
if(uc&&ud){
  uc.addEventListener('click',e=>{e.stopPropagation();const o=ud.classList.toggle('open');uc.classList.toggle('open',o);uc.setAttribute('aria-expanded',o);});
  document.addEventListener('click',e=>{const um=document.getElementById('uMenu');if(um&&!um.contains(e.target)){ud.classList.remove('open');uc.classList.remove('open');uc.setAttribute('aria-expanded','false');}});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){ud.classList.remove('open');uc.classList.remove('open');}});
}

/* ── Category nav arrows ── */
(function(){
  var nav=document.getElementById('catNavWrap'),
      btnL=document.getElementById('catLeft'),
      btnR=document.getElementById('catRight');
  if(!nav||!btnL||!btnR) return;
  var step=180;
  function upd(){
    btnL.classList.toggle('hidden',nav.scrollLeft<=4);
    btnR.classList.toggle('hidden',nav.scrollLeft+nav.offsetWidth>=nav.scrollWidth-4);
  }
  btnL.addEventListener('click',()=>nav.scrollBy({left:-step,behavior:'smooth'}));
  btnR.addEventListener('click',()=>nav.scrollBy({left:step,behavior:'smooth'}));
  nav.addEventListener('scroll',upd,{passive:true});
  var active=nav.querySelector('.cat-lnk.on');
  if(active) nav.scrollLeft=active.offsetLeft-(nav.offsetWidth/2)+(active.offsetWidth/2);
  upd();
  window.addEventListener('load',upd);
  window.addEventListener('resize',upd);
})();

/* ── Pagination jump ── */
function pgJump(){
  const inp=document.getElementById('pgJumpInput');
  if(!inp)return;
  const p=parseInt(inp.value),max=<?=$totalPages?>;
  if(p>=1&&p<=max){const url=new URL(window.location.href);url.searchParams.set('page',p);window.location.href=url.toString();}
  else{inp.focus();inp.select();}
}
document.getElementById('pgJumpInput')?.addEventListener('keydown',e=>{if(e.key==='Enter')pgJump();});
</script>

</body>
</html>