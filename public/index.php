<?php
session_start();
include '../config/koneksi.php';

$isLogin  = isset($_SESSION['user_login']) && $_SESSION['user_login'] === true;
$userNama = $isLogin ? ($_SESSION['user_nama'] ?? $_SESSION['user_username'] ?? 'User') : '';
$userRole = $isLogin ? ($_SESSION['user_role'] ?? '') : '';
$userInit = $isLogin ? strtoupper(substr($_SESSION['user_username'] ?? 'U', 0, 1)) : '';

$dashLink = '#';
if ($userRole === 'admin')       $dashLink = '../admin/dashboardadmin.php';
elseif ($userRole === 'penulis') $dashLink = '../admin/dashboardpenulis.php';

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
        if ($d < 0)       return 'Baru saja';
        if ($d < 60)      return 'Baru saja';
        if ($d < 3600)    return floor($d / 60) . ' menit lalu';
        if ($d < 86400)   return floor($d / 3600) . ' jam lalu';
        if ($d < 2592000) return floor($d / 86400) . ' hari lalu';
        return floor($d / 2592000) . ' bulan lalu';
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

$filter   = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$search   = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchEs = $search ? mysqli_real_escape_string($koneksi, $search) : '';

$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 21;

$colCheck = mysqli_query($koneksi, "SHOW COLUMNS FROM artikel LIKE 'status'");
$w  = mysqli_num_rows($colCheck) > 0 ? "WHERE a.status='publish'" : "WHERE 1=1";
$wk = $filter   ? " AND a.kategori_id='$filter'" : "";
$ws = $searchEs ? " AND (a.judul LIKE '%$searchEs%' OR a.isi LIKE '%$searchEs%')" : "";

$countRes   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM artikel a $w $wk $ws");
$totalCount = (int)mysqli_fetch_assoc($countRes)['total'];

$fetchLimit  = $perPage;
$fetchOffset = ($page - 1) * $perPage;
$totalPages  = max(1, ceil($totalCount / $perPage));

$rows = mysqli_query($koneksi,
    "SELECT a.*, k.nama_kategori FROM artikel a
     JOIN kategori k ON a.kategori_id = k.id_kategori
     $w $wk $ws ORDER BY a.tgl_posting DESC LIMIT $fetchLimit OFFSET $fetchOffset");
$berita = [];
while ($r = mysqli_fetch_assoc($rows)) $berita[] = $r;

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
    $ghPerPage = 21; $ghOffset = ($page - 1) * $ghPerPage;
    $ghKatIds = [];
    if ($filter) { $ghKatIds[] = $filter; }
    else if ($gayaHidupId) { $ghKatIds[] = $gayaHidupId; foreach ($subKatGayaHidup as $sk) $ghKatIds[] = $sk['id_kategori']; }
    $wkGH = !empty($ghKatIds) ? " AND a.kategori_id IN (" . implode(',', array_map('intval', $ghKatIds)) . ")" : "";
    $wsGH = $searchEs ? " AND (a.judul LIKE '%$searchEs%' OR a.isi LIKE '%$searchEs%')" : "";
    $ghTotal = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM artikel a $w $wkGH $wsGH"))['t'];
    $ghPages  = max(1, ceil($ghTotal / $ghPerPage));
    $ghRows   = mysqli_query($koneksi, "SELECT a.*, k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id = k.id_kategori $w $wkGH $wsGH ORDER BY a.tgl_posting DESC LIMIT $ghPerPage OFFSET $ghOffset");
    $berita = [];
    while ($r = mysqli_fetch_assoc($ghRows)) $berita[] = $r;
    $totalCount = $ghTotal; $totalPages = $ghPages;
}

/* ── Sidebar kanan ── */
// Jika ada filter kategori aktif, tampilkan berita dari kategori tsb
$wSide = $filter ? " AND a.kategori_id='$filter'" : "";
$sideRows = mysqli_query($koneksi,
    "SELECT a.id_artikel, a.judul, a.tgl_posting, a.thumbnail, k.nama_kategori
     FROM artikel a JOIN kategori k ON a.kategori_id = k.id_kategori
     $w $wSide ORDER BY a.tgl_posting DESC LIMIT 7");

/* ── Footer: bagi kategori jadi 2 kolom ── */
$halfKats = array_chunk($kats, (int)ceil(count($kats) / 2));

$ghIcons = ['Gaya Hidup'=>'bi-stars','Kesehatan'=>'bi-heart-pulse','Kuliner'=>'bi-cup-hot','Travel'=>'bi-airplane','Religi'=>'bi-moon-stars'];
$ghDescs  = ['Gaya Hidup'=>'Kesehatan, kuliner, travel & tren terkini','Kesehatan'=>'Info kesehatan, medis & tips hidup sehat','Kuliner'=>'Resep, restoran & dunia kuliner Indonesia','Travel'=>'Destinasi wisata & tips perjalanan terbaik','Religi'=>'Kajian Islam, doa, ibadah & info keagamaan'];

if (!function_exists('pageUrl')) { function pageUrl($p,$f,$s){$params=['page'=>$p];if($f)$params['kategori']=$f;if($s)$params['q']=$s;return 'index.php?'.http_build_query($params);} }
if (!function_exists('pgUrl'))   { function pgUrl($p,$bp){return 'index.php?'.http_build_query(array_merge($bp,['page'=>$p]));} }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<title>LiyNews — Berita Terpercaya Indonesia</title>
<script>(function(){var s=localStorage.getItem('pb_theme'),t=s==='dark'||s==='light'?s:(window.matchMedia&&window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
/* ══ TOKENS ══ */
:root{
  color-scheme:light dark;
  --blue:#1a56db;--blue-d:#1044b8;--blue-soft:rgba(26,86,219,.1);--blue-xs:rgba(26,86,219,.05);--orange:#f97316;--orange-d:#ea6c08;--orange-soft:rgba(249,115,22,.13);
  --bg:#f1f3f9;--card:#fff;--alt:#e8ebf4;--deep:#dde1ef;
  --bdr:#cdd3e5;--bdr-lt:#e8ecf4;
  --tx:#0b1320;--tx2:#253449;--muted:#5a6e88;--faint:#a8bace;
  --nv:#060f1d;--nv2:#091626;--nv3:#0b1f38;--nb:rgba(255,255,255,.06);
  --sh1:0 1px 3px rgba(8,18,36,.06);--sh2:0 4px 20px rgba(8,18,36,.09);--sh3:0 14px 44px rgba(8,18,36,.14);
  --fd:'Playfair Display',Georgia,serif;--fs:'DM Sans',system-ui,sans-serif;--fc:'DM Serif Display',Georgia,serif;
  --header-h:128px;
}
[data-theme=dark]{
  --blue:#4d8ef7;--blue-d:#3a7be8;--blue-soft:rgba(77,142,247,.11);--blue-xs:rgba(77,142,247,.05);
  --bg:#070d1a;--card:#0c1928;--alt:#0a1522;--deep:#081020;
  --bdr:#162b46;--bdr-lt:#0f2038;
  --tx:#dce9f8;--tx2:#8aa8c5;--muted:#4a6680;--faint:#1c3352;
  --nv:#030910;--nv2:#050e1b;--nv3:#081628;--nb:rgba(255,255,255,.05);
  --sh1:0 1px 3px rgba(0,0,0,.4);--sh2:0 4px 20px rgba(0,0,0,.5);--sh3:0 14px 44px rgba(0,0,0,.65);
}

/* ══ RESET ══ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{overflow-x:hidden;scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--fs);background:var(--bg);color:var(--tx2);line-height:1.6;overflow-x:hidden;-webkit-tap-highlight-color:transparent;transition:background .3s,color .3s}
a{color:inherit;text-decoration:none}
img{display:block;max-width:100%;object-fit:cover}
button{font-family:var(--fs);cursor:pointer;border:none;background:none}
.W{width:100%;max-width:1380px;margin:0 auto;padding:0 clamp(14px,3vw,36px)}

/* ══ TICKER ══ */
.ticker{background:var(--nv);border-bottom:1px solid var(--nb);height:28px;overflow:hidden}
.ticker .W{display:flex;align-items:center;height:100%}
.t-pill{background:var(--blue);color:#fff;font-family:var(--fc);font-size:.78rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:0 20px 0 12px;height:100%;display:flex;align-items:center;gap:5px;flex-shrink:0;clip-path:polygon(0 0,calc(100% - 7px) 0,100% 50%,calc(100% - 7px) 100%,0 100%)}
.t-scroll{flex:1;overflow:hidden;height:100%;display:flex;align-items:center;mask-image:linear-gradient(to right,transparent,#000 28px,#000 calc(100% - 28px),transparent)}
.t-track{display:flex;width:max-content;animation:tick 75s linear infinite}
.t-track:hover{animation-play-state:paused}
.t-item{display:flex;align-items:center;gap:7px;padding:0 36px 0 0;white-space:nowrap;color:rgba(255,255,255,.75);font-size:.82rem;transition:color .15s}
.t-item:hover{color:rgba(255,255,255,.82)}
.t-dot{width:3px;height:3px;background:var(--blue);border-radius:50%;opacity:.55;flex-shrink:0}
@keyframes tick{to{transform:translateX(-50%)}}

/* ══ MASTHEAD ══ */
.masthead{background:var(--nv);border-bottom:3px solid var(--blue);position:sticky;top:0;z-index:700}
.mast-bar{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;height:68px;gap:12px}
.mast-l{display:flex;align-items:center;gap:8px}
.mast-date{font-size:.75rem;color:rgba(255,255,255,.65);letter-spacing:.04em;white-space:nowrap;display:flex;align-items:center;gap:5px}
.mast-date i{color:rgba(255,255,255,.2);font-size:.68rem}
.logo-block{display:flex;flex-direction:column;align-items:center;gap:0}
.logo-main{font-family:var(--fd);font-size:2.8rem;font-weight:900;color:#fff;letter-spacing:-.05em;line-height:.9}
.logo-main em{color:var(--blue);font-style:normal}
.logo-rule{width:100%;height:1px;background:var(--nb);margin:5px 0 4px}
.logo-sub{font-family:var(--fc);font-size:.65rem;font-weight:600;letter-spacing:.3em;text-transform:uppercase;color:rgba(255,255,255,.6)}
.mast-r{display:flex;align-items:center;justify-content:flex-end;gap:6px}
.mast-search-wrap{position:relative}
.mast-search-wrap input{height:32px;width:192px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:4px;font-family:var(--fs);font-size:.88rem;color:#fff;padding:0 34px 0 12px;outline:none;transition:.2s}
.mast-search-wrap input:focus{border-color:var(--blue);background:rgba(255,255,255,.12);box-shadow:0 0 0 3px var(--blue-soft)}
.mast-search-wrap input::placeholder{color:rgba(255,255,255,.26)}
.srch-ico{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.35);font-size:.8rem;padding:0;display:flex;align-items:center}
.srch-ico:hover{color:#fff}
.live-dd{display:none;position:absolute;top:calc(100% + 7px);left:0;right:0;background:var(--card);border:1px solid var(--bdr);border-radius:var(--rl);box-shadow:var(--sh3);z-index:900;overflow:hidden;animation:fdd .13s ease;min-width:305px}
.live-dd.open{display:block}
@keyframes fdd{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
.ldd-row{display:flex;align-items:center;gap:10px;padding:10px 13px;border-bottom:1px solid var(--bdr-lt);transition:.12s}
.ldd-row:last-of-type{border-bottom:none}
.ldd-row:hover{background:var(--alt)}
.ldd-row:hover .ldd-ttl{color:var(--blue)}
.ldd-th{width:46px;height:34px;border-radius:4px;object-fit:cover;flex-shrink:0}
.ldd-ph{width:46px;height:34px;border-radius:4px;background:var(--alt);border:1px solid var(--bdr);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--faint);font-size:.72rem}
.ldd-info{flex:1;min-width:0}
.ldd-cat{font-size:.55rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--blue);margin-bottom:2px}
.ldd-ttl{font-size:.79rem;font-weight:600;color:var(--tx);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:color .12s}
.ldd-ttl mark{background:none;color:var(--blue);font-weight:700;padding:0}
.ldd-time{font-size:.63rem;color:var(--muted);margin-top:2px}
.ldd-more{padding:9px 13px;text-align:center;background:var(--alt);border-top:1px solid var(--bdr);font-size:.72rem;color:var(--muted);cursor:pointer;transition:.12s}
.ldd-more:hover{color:var(--blue)}
.ldd-more strong{color:var(--blue)}
.ldd-empty{padding:20px;text-align:center}
.ldd-empty-i{width:38px;height:38px;border-radius:50%;background:var(--blue-xs);display:flex;align-items:center;justify-content:center;margin:0 auto 7px}
.ldd-empty-i i{color:var(--blue);opacity:.65;font-size:.95rem}
.ldd-empty-t{font-size:.84rem;font-weight:700;color:var(--tx);margin-bottom:3px}
.ldd-empty-s{font-size:.7rem;color:var(--muted)}
.ic{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:4px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.52);font-size:.82rem;transition:.15s;flex-shrink:0}
.ic:hover{background:rgba(255,255,255,.13);border-color:rgba(255,255,255,.2);color:#fff}
.pb{height:32px;padding:0 13px;border-radius:4px;font-family:var(--fs);font-size:.85rem;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;border:1px solid transparent}
.pb-g{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.14);color:rgba(255,255,255,.75)}
.pb-g:hover{background:rgba(255,255,255,.14);color:#fff}
.pb-s{background:var(--blue);color:#fff}
.pb-s:hover{background:var(--blue-d)}
.mob-s-btn{display:none;width:32px;height:32px;border-radius:4px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.52);font-size:.82rem;align-items:center;justify-content:center;transition:.15s}
.mob-s-btn:hover{color:#fff}
.mob-sbar{display:none;background:var(--nv2);padding:9px clamp(14px,3vw,36px);border-top:1px solid var(--nb)}
.mob-sbar.open{display:block}
.mob-sbar form{display:flex;gap:6px}
.mob-sbar input{flex:1;height:34px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);color:#fff;border-radius:4px;font-family:var(--fs);font-size:.8rem;padding:0 14px;outline:none;transition:.2s;min-width:0}
.mob-sbar input:focus{border-color:var(--blue)}
.mob-sbar input::placeholder{color:rgba(255,255,255,.28)}
.mob-sbar button{height:34px;padding:0 14px;background:var(--blue);color:#fff;border-radius:4px;font-family:var(--fs);font-size:.76rem;font-weight:600;white-space:nowrap;flex-shrink:0}
.u-wrap{position:relative}
.u-btn{display:flex;align-items:center;gap:5px;height:32px;padding:0 9px 0 4px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:4px;cursor:pointer;transition:.15s;user-select:none}
.u-btn:hover{background:rgba(255,255,255,.13)}
.u-av{width:22px;height:22px;border-radius:3px;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.6rem;font-weight:800;flex-shrink:0}
.u-nm{font-size:.74rem;font-weight:600;color:rgba(255,255,255,.78);max-width:68px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.u-btn i.chv{font-size:.54rem;color:rgba(255,255,255,.28);transition:transform .2s}
.u-btn.open i.chv{transform:rotate(180deg)}
.u-dd{display:none;position:absolute;right:0;top:calc(100% + 7px);background:var(--card);border:1px solid var(--bdr);border-radius:var(--rl);min-width:186px;box-shadow:var(--sh3);overflow:hidden;animation:fdd .13s ease;z-index:900}
.u-dd.open{display:block}
.u-dd-hd{padding:10px 13px 8px;border-bottom:1px solid var(--bdr)}
.u-dd-nm{font-size:.82rem;font-weight:700;color:var(--tx)}
.u-dd-rl{font-size:.62rem;color:var(--muted);margin-top:2px;display:flex;align-items:center;gap:3px}
.u-dd-a{display:flex;align-items:center;gap:8px;padding:8px 13px;font-size:.78rem;color:var(--tx2);transition:.12s;text-decoration:none}
.u-dd-a i{font-size:.8rem;color:var(--faint);width:13px;text-align:center}
.u-dd-a:hover{background:var(--alt);color:var(--blue)}
.u-dd-a:hover i{color:var(--blue)}
.u-dd-sep{height:1px;background:var(--bdr)}
.u-dd-a.danger{color:#d63939}
.u-dd-a.danger i{color:#d63939}
.u-dd-a.danger:hover{background:#fff4f4}
[data-theme=dark] .u-dd-a.danger:hover{background:#1e0a0a}

/* ══ CATEGORY NAV ══ */
.cat-nav{background:var(--nv2);border-bottom:2px solid var(--blue)}
.cat-nav-inner{display:flex;align-items:stretch;height:38px}
.cat-scroll{display:flex;align-items:stretch;overflow-x:auto;flex:1;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.cat-scroll::-webkit-scrollbar{display:none}
.cat-lnk{display:inline-flex;align-items:center;padding:0 15px;font-family:var(--fc);font-size:.95rem;font-weight:400;font-style:italic;text-transform:none;letter-spacing:.04em;color:rgba(255,255,255,.55);border-bottom:2px solid transparent;white-space:nowrap;flex-shrink:0;transition:color .15s,border-color .15s;margin-bottom:-2px}
.cat-lnk:hover{color:rgba(255,255,255,.82)}
.cat-lnk.on{color:#fff;border-bottom-color:var(--blue)}
.cat-arr{width:28px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.38);font-size:.78rem;transition:color .15s,opacity .2s}
.cat-arr:hover{color:#fff}
.cat-arr.L{background:linear-gradient(to right,var(--nv2) 60%,transparent)}
.cat-arr.R{background:linear-gradient(to left,var(--nv2) 60%,transparent)}
.cat-arr.gone{opacity:0;pointer-events:none}

/* ══ TRENDING ROW ══ */
.trend-bar{background:var(--card);border-bottom:1px solid var(--bdr);height:30px;position:sticky;top:calc(68px + 38px);z-index:680}
.trend-inner{display:flex;align-items:center;height:100%;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.trend-inner::-webkit-scrollbar{display:none}
.trend-lbl{font-family:var(--fc);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);display:flex;align-items:center;gap:5px;white-space:nowrap;padding-right:12px;border-right:1px solid var(--bdr);flex-shrink:0}
.trend-item{font-size:.82rem;color:var(--tx2);white-space:nowrap;padding:0 12px;border-right:1px solid var(--bdr-lt);line-height:30px;flex-shrink:0;transition:color .15s}
.trend-item:last-of-type{border-right:none}
.trend-item:hover{color:var(--blue)}

/* ══ PAGE BODY ══ */
.page-body{padding:26px 0 0}

/* ══ 3-COL GRID ══ */
.site-grid{display:grid;grid-template-columns:248px 1fr 276px;gap:0;align-items:start}
.col-L{padding-right:22px;border-right:1px solid var(--bdr)}
.col-M{padding:0 22px;min-width:0}
.col-R{padding-left:22px;border-left:1px solid var(--bdr)}

/* ══ LEFT COL ══ */
.ls-head{font-family:var(--fc);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.17em;color:var(--faint);padding-bottom:10px;border-bottom:1px solid var(--bdr)}
.ls-item{display:block;padding:12px 0;border-bottom:1px solid var(--bdr-lt);text-decoration:none}
.ls-item:last-child{border-bottom:none}
.ls-item:hover .ls-ttl{color:var(--blue)}
.ls-n{font-family:var(--fc);font-size:2rem;font-weight:700;color:var(--bdr);line-height:1;margin-bottom:3px;transition:color .15s}
.ls-item:hover .ls-n{color:var(--blue-soft)}
.ls-cat{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--blue);margin-bottom:3px}
.ls-ttl{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--tx);line-height:1.34;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;transition:color .15s}
.ls-time{font-size:.78rem;color:var(--muted)}
.ls-img{width:100%;aspect-ratio:3/2;object-fit:cover;border-radius:6px;margin-bottom:8px}
.ls-img-ph{width:100%;aspect-ratio:3/2;background:var(--alt);border-radius:6px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;color:var(--faint);font-size:1.3rem}
.gh-nav{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden}
.gh-nav-hd{padding:10px 13px;background:var(--blue);font-family:var(--fc);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:#fff}
.gh-nav-a{display:flex;align-items:center;gap:7px;padding:9px 13px;font-size:.8rem;font-weight:500;color:var(--tx2);border-bottom:1px solid var(--bdr-lt);transition:.14s;text-decoration:none;border-left:2px solid transparent}
.gh-nav-a:last-child{border-bottom:none}
.gh-nav-a:hover{background:var(--alt);color:var(--blue);border-left-color:var(--blue)}
.gh-nav-a.on{color:var(--blue);background:var(--blue-xs);border-left-color:var(--blue);font-weight:700}
.gh-nav-a i{font-size:.82rem;width:13px;text-align:center;opacity:.55}
.gh-nav-a.on i,.gh-nav-a:hover i{opacity:1}
.gh-mob{display:none;overflow-x:auto;scrollbar-width:none;gap:5px;padding-bottom:12px;-webkit-overflow-scrolling:touch}
.gh-mob::-webkit-scrollbar{display:none}
.gh-pill{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border-radius:4px;border:1px solid var(--bdr);font-size:.7rem;font-weight:600;color:var(--muted);white-space:nowrap;transition:.14s;text-decoration:none;flex-shrink:0}
.gh-pill:hover,.gh-pill.on{background:var(--blue);border-color:var(--blue);color:#fff}

/* ══ BADGES ══ */
.bdg{display:inline-block;font-family:var(--fc);font-size:.57rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;background:var(--blue);color:#fff;padding:2px 8px;border-radius:3px;line-height:1.65;flex-shrink:0}
.bdg-g{background:transparent;color:var(--blue);border:1px solid var(--blue)}

/* ══ HERO ══ */
.hero-wrap{margin-bottom:22px}
.hero-main{position:relative;display:block;border-radius:var(--rl);overflow:hidden;aspect-ratio:16/7;background:var(--alt);margin-bottom:3px}
.hero-main img{width:100%;height:100%;object-fit:cover;transition:transform .65s cubic-bezier(.25,.46,.45,.94)}
.hero-main:hover img{transform:scale(1.03)}
.hero-main::after{content:'';position:absolute;inset:0;background:linear-gradient(170deg,rgba(4,10,26,.03) 30%,rgba(4,10,26,.97) 100%)}
.hero-main-body{position:absolute;bottom:0;left:0;right:0;padding:26px 24px 22px;z-index:2}
.hero-main-body h2{font-family:var(--fd);font-size:clamp(1.4rem,2.8vw,2.2rem);font-weight:800;color:#fff;line-height:1.2;margin:8px 0 7px;text-shadow:0 2px 12px rgba(0,0,0,.55)}
.hero-main-meta{font-size:.68rem;color:rgba(255,255,255,.48);display:flex;align-items:center;gap:8px}
.hero-subs{display:grid;grid-template-columns:repeat(3,1fr);gap:3px}
.hero-sub{position:relative;display:block;border-radius:5px;overflow:hidden;aspect-ratio:16/8;background:var(--alt)}
.hero-sub img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.hero-sub:hover img{transform:scale(1.06)}
.hero-sub::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(4,10,26,.9) 0%,transparent 55%)}
.hero-sub-body{position:absolute;bottom:0;left:0;right:0;padding:9px 10px;z-index:2}
.hero-sub-body h4{font-family:var(--fd);font-size:.92rem;font-weight:700;color:#fff;line-height:1.27;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-shadow:0 1px 4px rgba(0,0,0,.6)}
.hero-sub-meta{font-size:.85rem;color:rgba(255,255,255,.42);margin-top:3px}
.hero-txt{display:block;background:linear-gradient(130deg,var(--nv) 0%,#1a3a6b 100%);border-radius:var(--rl);padding:30px 26px;margin-bottom:22px}
.hero-txt h2{font-family:var(--fd);font-size:clamp(1.1rem,4vw,2.1rem);font-weight:900;color:#fff;line-height:1.18;margin:8px 0 10px}
.hero-txt h2:hover{color:var(--blue)}
.hero-txt .m{font-size:.74rem;color:rgba(255,255,255,.36);display:flex;align-items:center;gap:6px}

/* ══ SECTION LABEL ══ */
.sec{display:flex;align-items:center;gap:10px;margin:20px 0 13px}
.sec-lbl{font-family:var(--fc);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);display:flex;align-items:center;gap:5px;white-space:nowrap}
.sec-lbl::before{content:'';width:2px;height:10px;background:var(--blue);border-radius:2px;display:block}
.sec-line{flex:1;height:1px;background:var(--bdr)}
.divider{display:flex;align-items:center;gap:10px;margin:18px 0 14px}
.div-l{flex:1;height:1px;background:var(--bdr)}
.div-d{width:4px;height:4px;border-radius:50%;background:var(--blue)}

/* ══ CARD GRID ══ */
.cg{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px}
.card{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden;transition:box-shadow .2s,transform .2s;text-decoration:none}
.card:hover{box-shadow:var(--sh2);transform:translateY(-2px)}
.card-img{width:100%;height:128px;object-fit:cover;display:block;transition:transform .4s}
.card:hover .card-img{transform:scale(1.05)}
.card-no-img{height:128px;background:var(--alt);display:flex;align-items:center;justify-content:center;color:var(--faint);font-size:1.55rem}
.card-body{padding:10px 12px 12px;flex:1;display:flex;flex-direction:column}
.card-cat{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--blue);margin-bottom:4px}
.card-ttl{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--tx);line-height:1.35;flex:1;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:6px;transition:color .15s}
.card:hover .card-ttl{color:var(--blue)}
.card-meta{font-size:.78rem;color:var(--muted);display:flex;align-items:center;gap:4px}

/* ══ NEWS LIST ══ */
.nlist{display:flex;flex-direction:column}
.nli{display:flex;gap:12px;padding:11px 0;border-bottom:1px solid var(--bdr-lt);text-decoration:none}
.nli:first-child{padding-top:0}
.nli:last-child{border-bottom:none}
.nli:hover .nli-ttl{color:var(--blue)}
.nli-th{width:92px;height:62px;border-radius:5px;overflow:hidden;flex-shrink:0}
.nli-th img{width:100%;height:100%;object-fit:cover;transition:transform .35s}
.nli:hover .nli-th img{transform:scale(1.07)}
.nli-ph{width:92px;height:62px;border-radius:5px;background:var(--alt);border:1px solid var(--bdr);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--faint);font-size:.88rem}
.nli-body{flex:1;min-width:0}
.nli-cat{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--blue);display:block;margin-bottom:3px}
.nli-ttl{font-family:var(--fd);font-size:1rem;font-weight:700;color:var(--tx);line-height:1.33;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;transition:color .15s}
.nli-meta{font-size:.78rem;color:var(--muted)}

/* ══ GH SECTION ══ */
.gh-banner{background:linear-gradient(118deg,var(--nv) 0%,#0d3265 100%);border-radius:var(--r);padding:13px 15px;margin-bottom:14px;display:flex;align-items:center;gap:11px}
.gh-banner-i{width:35px;height:35px;background:rgba(255,255,255,.1);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.98rem;color:#fff;flex-shrink:0}
.gh-banner-t h3{font-family:var(--fd);font-size:.94rem;font-weight:800;color:#fff;margin-bottom:2px}
.gh-banner-t p{font-size:.68rem;color:rgba(255,255,255,.42)}
.gh-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.gh-card{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden;transition:box-shadow .2s,transform .2s;text-decoration:none}
.gh-card:hover{box-shadow:var(--sh2);transform:translateY(-2px)}
.gh-card-img{width:100%;aspect-ratio:16/10;object-fit:cover;display:block;transition:transform .4s}
.gh-card:hover .gh-card-img{transform:scale(1.06)}
.gh-card-no-img{width:100%;aspect-ratio:16/10;background:var(--alt);display:flex;align-items:center;justify-content:center;color:var(--faint);font-size:1.2rem}
.gh-card-body{padding:9px 11px 11px;flex:1;display:flex;flex-direction:column}
.gh-card-cat{font-size:.54rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--blue);margin-bottom:3px}
.gh-card-ttl{font-family:var(--fd);font-size:.86rem;font-weight:700;color:var(--tx);line-height:1.34;flex:1;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:6px;transition:color .15s}
.gh-card:hover .gh-card-ttl{color:var(--blue)}
.gh-card-meta{font-size:.62rem;color:var(--muted)}

/* ══ SEARCH ══ */
.sr-notice{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);padding:10px 13px;margin-bottom:13px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.sr-txt{font-size:.8rem;color:var(--tx2)}
.sr-txt strong{color:var(--blue)}
.sr-clr{font-size:.7rem;color:var(--muted);border:1px solid var(--bdr);border-radius:4px;padding:5px 11px;background:none;cursor:pointer;transition:.14s;display:flex;align-items:center;gap:4px;text-decoration:none}
.sr-clr:hover{border-color:var(--blue);color:var(--blue)}
.sr-item{display:flex;gap:11px;padding:11px 0;border-bottom:1px solid var(--bdr-lt);text-decoration:none}
.sr-item:first-child{padding-top:0}
.sr-item:last-child{border-bottom:none}
.sr-item:hover .sr-ttl{color:var(--blue)}
.sr-th{width:92px;height:62px;border-radius:5px;overflow:hidden;flex-shrink:0}
.sr-th img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.sr-item:hover .sr-th img{transform:scale(1.06)}
.sr-ph{width:92px;height:62px;border-radius:5px;background:var(--alt);border:1px solid var(--bdr);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--faint);font-size:.88rem}
.sr-body{flex:1;min-width:0}
.sr-cat{font-size:.53rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--blue);display:block;margin-bottom:3px}
.sr-ttl{font-family:var(--fd);font-size:.88rem;font-weight:700;color:var(--tx);line-height:1.33;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;transition:color .15s}
.sr-ttl mark{background:var(--blue-soft);color:var(--blue);border-radius:2px;padding:0 2px}
.sr-meta{font-size:.63rem;color:var(--muted)}
.sr-empty{text-align:center;padding:48px 0;color:var(--muted)}
.sr-empty i{font-size:1.9rem;display:block;margin-bottom:10px;opacity:.18}
.empty{text-align:center;padding:52px 0;color:var(--muted)}
.empty i{font-size:2.2rem;display:block;margin-bottom:11px;opacity:.17}
.empty p{font-family:var(--fd);font-size:.94rem;font-weight:700;color:var(--tx);margin-bottom:4px}

/* ══ RIGHT SIDEBAR ══ */
.sb-blk{margin-bottom:22px}
.sb-blk:last-child{margin-bottom:0}
.sb-hd{font-family:var(--fc);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.17em;color:var(--faint);padding-bottom:8px;border-bottom:2px solid var(--blue);margin-bottom:0;display:flex;align-items:center;gap:5px}
.sb-hd i{color:var(--blue);font-size:.75rem}
.sb-li{display:flex;gap:8px;padding:9px 0;border-bottom:1px solid var(--bdr-lt);text-decoration:none}
.sb-li:last-child{border-bottom:none}
.sb-li:hover .sb-li-ttl{color:var(--blue)}
.sb-li-th{width:50px;height:37px;border-radius:4px;overflow:hidden;flex-shrink:0}
.sb-li-th img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.sb-li:hover .sb-li-th img{transform:scale(1.08)}
.sb-li-ph{width:50px;height:37px;border-radius:4px;background:var(--alt);border:1px solid var(--bdr);flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--faint);font-size:.68rem}
.sb-li-body{flex:1;min-width:0}
.sb-li-cat{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--blue);margin-bottom:2px}
.sb-li-ttl{font-family:var(--fd);font-size:.88rem;font-weight:700;color:var(--tx);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;transition:color .15s}
.sb-li-t{font-size:.75rem;color:var(--muted);margin-top:2px}
.sb-pi{display:flex;align-items:flex-start;gap:8px;padding:9px 0;border-bottom:1px solid var(--bdr-lt);text-decoration:none}
.sb-pi:last-child{border-bottom:none}
.sb-pi:hover .sb-pi-ttl,.sb-pi:hover .sb-pi-n{color:var(--blue)}
.sb-pi-n{font-family:var(--fc);font-size:1.55rem;font-weight:700;color:var(--bdr);line-height:1;flex-shrink:0;width:22px;text-align:right;transition:color .15s;margin-top:-2px}
.sb-pi-ttl{font-family:var(--fd);font-size:.88rem;font-weight:700;color:var(--tx);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:2px;transition:color .15s}
.sb-pi-m{font-size:.75rem;color:var(--muted)}
.sb-topics{display:flex;flex-wrap:wrap;gap:4px;padding-top:9px}
.sb-tpc{font-size:.78rem;font-weight:600;padding:4px 10px;border-radius:4px;border:1px solid var(--bdr);color:var(--muted);transition:.14s;text-decoration:none;display:inline-block}
.sb-tpc:hover,.sb-tpc.on{background:var(--blue);border-color:var(--blue);color:#fff}

/* ══ PAGINATION — di luar grid, full width ══ */
.pages-outer{
  padding:22px clamp(14px,3vw,36px) 5px;
  border-top:1px solid var(--bdr);
  margin-top:22px;
}
/* Desktop: tampil pagination-desktop, sembunyikan pagination-mobile */
.pages-mobile{ display:none; }
.pages-desktop{ display:block; }
.pages{display:flex;align-items:center;justify-content:center;gap:4px;flex-wrap:wrap}
.pg-inf{font-size:.7rem;color:var(--muted);margin-right:4px}
.pg-inf strong{color:var(--tx2)}
.pg{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 7px;border-radius:4px;border:1px solid var(--bdr);background:var(--card);color:var(--muted);font-size:.74rem;font-weight:600;transition:.14s;text-decoration:none}
.pg:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}
.pg.on{background:var(--blue);border-color:var(--blue);color:#fff;pointer-events:none}
.pg.off{opacity:.3;pointer-events:none}
.pg-dots{font-size:.8rem;color:var(--faint);padding:0 1px;align-self:center}
.pg-jump{display:flex;align-items:center;gap:4px;margin-left:4px}
.pg-jump input{width:44px;height:32px;border-radius:4px;border:1px solid var(--bdr);background:var(--card);color:var(--tx);font-size:.74rem;text-align:center;outline:none;transition:.14s}
.pg-jump input:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft)}
.pg-go{height:32px;padding:0 10px;border-radius:4px;border:1px solid var(--bdr);background:var(--card);color:var(--muted);font-size:.7rem;font-weight:600;transition:.14s}
.pg-go:hover{border-color:var(--blue);color:var(--blue)}

/* ══ MOBILE SIDEBAR ══ */
.mob-sb{display:none;margin-top:22px}
.mob-sb-g{display:grid;grid-template-columns:1fr 1fr;gap:14px}

/* ══ FOOTER ══ */
.site-footer{background:var(--nv);border-top:3px solid var(--blue);margin-top:14px}
.ft-top{
  padding:38px 0 30px;
  display:grid;
  grid-template-columns:1.4fr 1fr 1fr 1fr 1fr;
  gap:28px;
  align-items:start;
}
.ft-logo{font-family:var(--fd);font-size:1.95rem;font-weight:900;color:rgba(255,255,255,.9);letter-spacing:-.04em;margin-bottom:9px}
.ft-logo em{color:var(--blue);font-style:normal}
.ft-brand p{font-size:.74rem;color:rgba(255,255,255,.65);line-height:1.85;max-width:272px}
.ft-col h6{font-family:var(--fc);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.18em;color:rgba(255,255,255,.55);margin-bottom:13px}
.ft-col a{
  display:block;
  font-size:.74rem;
  color:rgba(255,255,255,.65);
  margin-bottom:8px;
  transition:color .15s, padding-left .15s;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  text-decoration:none;
  cursor:pointer;
}
.ft-col a:hover{color:#fff;padding-left:4px;}
.ft-col a.ft-active{color:var(--blue);font-weight:600;}
.ft-col a.ft-active:hover{color:var(--blue-d);}
.ft-bar{border-top:1px solid rgba(255,255,255,.05);padding:14px 0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ft-copy{font-size:.66rem;color:rgba(255,255,255,.55)}
.ft-soc{display:flex;gap:6px}
.ft-soc a{width:28px;height:28px;border-radius:4px;border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.55);font-size:.8rem;transition:.14s;text-decoration:none}
.ft-soc a:hover{border-color:var(--blue);color:var(--blue)}

/* ══ MODAL LOGOUT ══ */
.logout-modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.65);backdrop-filter:blur(5px);
  z-index:9999;align-items:center;justify-content:center;padding:16px;
}
.logout-modal-overlay.show{display:flex}
.logout-modal-box{
  background:var(--card);border:1px solid var(--bdr);
  border-radius:16px;padding:36px 28px 28px;
  width:100%;max-width:340px;text-align:center;
  box-shadow:var(--sh3);
  animation:popIn .22s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popIn{from{opacity:0;transform:scale(.88)}to{opacity:1;transform:scale(1)}}
.logout-modal-icon{
  width:68px;height:68px;border-radius:50%;
  background:var(--blue-soft);border:2px solid rgba(26,86,219,.22);
  display:flex;align-items:center;justify-content:center;
  font-size:1.7rem;color:var(--blue);margin:0 auto 18px;
}
[data-theme=dark] .logout-modal-icon{background:rgba(77,142,247,.12);border-color:rgba(77,142,247,.28)}
.logout-modal-box h3{font-family:var(--fd);font-size:1.18rem;font-weight:800;color:var(--tx);margin-bottom:8px}
.logout-modal-box p{font-size:.83rem;color:var(--muted);line-height:1.65;margin-bottom:26px}
.logout-modal-actions{display:flex;gap:10px}
.logout-btn-cancel{
  flex:1;background:none;border:1.5px solid var(--bdr);color:var(--tx);
  border-radius:7px;padding:10px 0;font-size:.85rem;font-weight:600;
  font-family:var(--fs);cursor:pointer;transition:.15s;
  display:inline-flex;align-items:center;justify-content:center;gap:5px;
}
.logout-btn-cancel:hover{border-color:var(--muted)}
.logout-btn-confirm{
  flex:1;background:var(--blue);color:#fff;border:none;
  border-radius:7px;padding:10px 0;font-size:.85rem;font-weight:600;
  font-family:var(--fs);cursor:pointer;transition:.15s;
  display:inline-flex;align-items:center;justify-content:center;gap:5px;
  text-decoration:none;
}
.logout-btn-confirm:hover{background:var(--blue-d);color:#fff}

/* ══ RESPONSIVE ══ */
@media(max-width:1280px){
  .site-grid{grid-template-columns:224px 1fr 258px}
  .ft-top{grid-template-columns:1.2fr 1fr 1fr 1fr;gap:22px}
  .ft-top .ft-col:nth-child(5){display:none}
}
@media(max-width:1080px){
  .site-grid{grid-template-columns:200px 1fr 238px}
  .col-L{padding-right:18px}.col-M{padding:0 18px}.col-R{padding-left:18px}
}
@media(max-width:900px){
  .mast-bar{grid-template-columns:1fr auto}
  .logo-block{display:none}
  .mast-logo-mob{font-family:var(--fd);font-size:1.7rem;font-weight:900;color:#fff;letter-spacing:-.04em;line-height:1}
  .mast-logo-mob em{color:var(--blue);font-style:normal}
  .site-grid{grid-template-columns:1fr}
  .col-L,.col-R{display:none}
  .col-M{padding:0}
  .mob-sb{display:block}
  .gh-mob{display:flex}
  .ft-top{grid-template-columns:1fr 1fr;gap:22px}
  .ft-brand{grid-column:1/3}
  /* Mobile: sembunyikan pagination-desktop, tampilkan pagination-mobile */
  .pages-desktop{ display:none; }
  .pages-mobile{ display:block; border-top:1px solid var(--bdr); padding:16px 0 5px; margin-top:0; }
}
@media(max-width:768px){
  .mast-search-wrap{display:none}
  .mob-s-btn{display:flex}
  .mast-date{display:none}
  .trend-bar{top:calc(54px + 38px)}
  .gh-grid{grid-template-columns:repeat(2,1fr)}
  .mob-sb-g{grid-template-columns:1fr}
  .hero-subs{grid-template-columns:repeat(2,1fr)}
  .hero-subs .hero-sub:last-child{display:none}
  .ft-top{grid-template-columns:1fr 1fr}
  .ft-brand{grid-column:1/3}
}
@media(max-width:580px){
  .mast-bar{height:52px}
  .cat-nav{top:52px}
  .trend-bar{top:calc(52px + 38px);display:block}
  .mast-logo-mob{font-size:1.46rem}
  .hero-subs{display:none}
  .hero-main{aspect-ratio:16/9}
  .hero-main-body{padding:14px}
  .hero-main-body h2{font-size:clamp(.95rem,5vw,1.35rem)}
  .cg{grid-template-columns:1fr}
  .gh-grid{grid-template-columns:1fr}
  .ft-top{grid-template-columns:1fr}
  .ft-brand{grid-column:auto}
  .ft-top .ft-col{display:block!important}
  .ft-bar{flex-direction:column;align-items:flex-start;gap:9px}
  /* Pagination mobile */
  .pages-outer{padding:18px clamp(14px,3vw,36px) 4px}
  .pg-jump{display:none}
  .pg-inf{display:none}
  .pg-dots{display:none}
  .pg{min-width:38px;height:38px}
}
@media(max-width:420px){
  .mast-bar{height:46px;gap:6px}
  .cat-nav{top:46px}
  .trend-bar{top:calc(46px + 38px)}
  .mast-logo-mob{font-size:1.28rem}
  .ic,.mob-s-btn{width:30px;height:30px;font-size:.76rem}
  .pb{height:30px;padding:0 10px;font-size:.7rem}
  .cat-lnk{padding:0 11px;font-size:.65rem}
}
@media(hover:none) and (pointer:coarse){
  .cat-lnk{min-height:44px}
  .nli,.sr-item{padding:13px 0}
  .sb-li,.sb-pi{padding:10px 0}
  .sb-tpc{padding:7px 12px;min-height:36px}
  .pg{min-width:40px;height:40px}
  .card:hover,.gh-card:hover{transform:none}
}
@media print{
  .ticker,.site-footer,.col-L,.col-R,.mob-sb,.cat-nav,.trend-bar,.mast-search-wrap,.mast-r,.pages-outer{display:none!important}
  body{color:#000;background:#fff}
  .masthead{background:#fff}
  .logo-main,.logo-main em,.mast-logo-mob,.mast-logo-mob em{color:#000!important}
  .site-grid{grid-template-columns:1fr}
  .col-M{padding:0}
}
</style>
</head>
<body>

<!-- TICKER -->
<div class="ticker">
  <div class="W">
    <div class="t-pill"><i class="bi bi-lightning-charge-fill"></i> Breaking</div>
    <div class="t-scroll">
      <div class="t-track">
        <?php
        $tq=mysqli_query($koneksi,"SELECT id_artikel,judul FROM artikel a $w ORDER BY tgl_posting DESC LIMIT 8");
        $ti=[];while($tr=mysqli_fetch_assoc($tq))$ti[]=$tr;
        foreach(array_merge($ti,$ti) as $t):?>
        <a href="detail.php?id=<?=$t['id_artikel']?>" class="t-item"><span class="t-dot"></span><?=htmlspecialchars($t['judul'])?></a>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</div>

<!-- MASTHEAD -->
<header class="masthead">
  <div class="W mast-bar">
    <div class="mast-l">
      <a href="index.php" class="mast-logo-mob" style="display:none">Liy<em>News</em></a>
      <span class="mast-date"><i class="bi bi-calendar3"></i><?=date('d F Y')?></span>
    </div>
    <div class="logo-block">
      <a href="index.php" class="logo-main">Liy<em>News</em></a>
      <div class="logo-rule"></div>
      <div class="logo-sub">Berita Terpercaya Indonesia</div>
    </div>
    <div class="mast-r">
      <div class="mast-search-wrap">
        <form method="GET" action="index.php" id="searchForm">
          <input type="text" name="q" id="searchInput" placeholder="Cari berita…" value="<?=htmlspecialchars($search)?>" autocomplete="off">
          <button type="submit" class="srch-ico"><i class="bi bi-search"></i></button>
        </form>
        <div class="live-dd <?=$search?'open':''?>" id="liveDD">
          <?php if($search):
            $sdq=mysqli_query($koneksi,"SELECT a.id_artikel,a.judul,a.tgl_posting,a.thumbnail,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w AND (a.judul LIKE '%$searchEs%' OR a.isi LIKE '%$searchEs%') ORDER BY a.tgl_posting DESC LIMIT 6");
            $sdArr=[];while($sd=mysqli_fetch_assoc($sdq))$sdArr[]=$sd;
            if(empty($sdArr)):?>
            <div class="ldd-empty">
              <div class="ldd-empty-i"><i class="bi bi-search"></i></div>
              <div class="ldd-empty-t">Tidak ditemukan</div>
              <div class="ldd-empty-s">Tidak ada hasil untuk "<?=htmlspecialchars($search)?>"</div>
            </div>
            <?php else:foreach($sdArr as $sd):
              $sdImg=null;$sdf=!empty($sd['thumbnail'])?$sd['thumbnail']:'';
              if($sdf){if(filter_var($sdf,FILTER_VALIDATE_URL))$sdImg=$sdf;elseif(file_exists("../uploads/$sdf"))$sdImg="../uploads/$sdf";}
              $hl=preg_replace('/('.preg_quote(htmlspecialchars($search),'/').')/i','<mark>$1</mark>',htmlspecialchars($sd['judul']));?>
            <a href="detail.php?id=<?=$sd['id_artikel']?>" class="ldd-row">
              <?php if($sdImg):?><img class="ldd-th" src="<?=htmlspecialchars($sdImg)?>" alt="">
              <?php else:?><div class="ldd-ph"><i class="bi bi-image"></i></div><?php endif;?>
              <div class="ldd-info">
                <div class="ldd-cat"><?=htmlspecialchars($sd['nama_kategori'])?></div>
                <div class="ldd-ttl"><?=$hl?></div>
                <div class="ldd-time"><i class="bi bi-clock"></i> <?=ago($sd['tgl_posting'])?></div>
              </div>
            </a>
            <?php endforeach;?>
            <div class="ldd-more" onclick="document.getElementById('searchForm').submit()">Semua hasil untuk "<strong><?=htmlspecialchars($search)?></strong>" →</div>
            <?php endif;endif;?>
        </div>
      </div>
      <button class="mob-s-btn ic" id="mobSBtn" aria-label="Cari"><i class="bi bi-search"></i></button>
      <button class="ic" id="themeBtn" aria-label="Ganti tema"><i class="bi bi-moon-fill"></i></button>
      <?php if($isLogin):?>
      <div class="u-wrap" id="uWrap">
        <div class="u-btn" id="uBtn" role="button" aria-haspopup="true" aria-expanded="false">
          <div class="u-av"><?=$userInit?></div>
          <span class="u-nm"><?=htmlspecialchars($userNama)?></span>
          <i class="bi bi-chevron-down chv"></i>
        </div>
        <div class="u-dd" id="uDd" role="menu">
          <div class="u-dd-hd">
            <div class="u-dd-nm"><?=htmlspecialchars($userNama)?></div>
            <div class="u-dd-rl"><i class="bi bi-shield-check"></i><?=htmlspecialchars($userRole)?></div>
          </div>
          <?php if($userRole==='admin'||$userRole==='penulis'):?>
          <a href="<?=$dashLink?>" class="u-dd-a"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <?php endif;?>
          <a href="../admin/profileadmin.php" class="u-dd-a"><i class="bi bi-person-circle"></i> Profil</a>
          <div class="u-dd-sep"></div>
          <!-- TOMBOL LOGOUT — diganti ke modal konfirmasi -->
          <button type="button" onclick="showLogoutModal()" class="u-dd-a danger" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;font-family:var(--fs);font-size:.78rem">
            <i class="bi bi-box-arrow-right"></i> Logout
          </button>
        </div>
      </div>
      <?php else:?>
      <a href="../login.php" class="pb pb-g">Masuk</a>
      <a href="../register.php" class="pb pb-s">Daftar</a>
      <?php endif;?>
    </div>
  </div>
  <div class="mob-sbar" id="mobSbar">
    <form method="GET" action="index.php">
      <input type="text" name="q" id="mobSIn" placeholder="Cari berita…" value="<?=htmlspecialchars($search)?>" autocomplete="off" aria-label="Cari">
      <button type="submit">Cari</button>
    </form>
  </div>
  <nav class="cat-nav" aria-label="Kategori">
    <div class="W cat-nav-inner">
      <button class="cat-arr L gone" id="catL" aria-label="Geser kiri"><i class="bi bi-chevron-left"></i></button>
      <div class="cat-scroll" id="catScroll" role="list">
        <a href="index.php" class="cat-lnk <?=$filter===''?'on':''?>" role="listitem">Semua</a>
        <?php
        $subH=array_map('strtolower',array_column($subKatGayaHidup,'nama_kategori'));
        foreach($kats as $k):
          if(in_array(strtolower($k['nama_kategori']),$subH))continue;?>
        <a href="index.php?kategori=<?=$k['id_kategori']?>"
           class="cat-lnk <?=($filter==$k['id_kategori']||($isGayaHidupPage&&strcasecmp($k['nama_kategori'],'Gaya Hidup')===0))?'on':''?>"
           role="listitem"><?=htmlspecialchars($k['nama_kategori'])?></a>
        <?php endforeach;?>
      </div>
      <button class="cat-arr R gone" id="catR" aria-label="Geser kanan"><i class="bi bi-chevron-right"></i></button>
    </div>
  </nav>
</header>

<!-- TRENDING -->
<?php
$trendQ=mysqli_query($koneksi,"SELECT id_artikel,judul FROM artikel a $w ORDER BY tgl_posting DESC LIMIT 8");
$trendArr=[];while($tr=mysqli_fetch_assoc($trendQ))$trendArr[]=$tr;
if(!empty($trendArr)):?>
<div class="trend-bar">
  <div class="W trend-inner">
    <span class="trend-lbl"><i class="bi bi-fire"></i> Trending</span>
    <?php foreach($trendArr as $ti):?>
    <a href="detail.php?id=<?=$ti['id_artikel']?>" class="trend-item"><?=htmlspecialchars($ti['judul'])?></a>
    <?php endforeach;?>
  </div>
</div>
<?php endif;?>

<!-- PAGE BODY -->
<div class="page-body">
  <div class="W">
    <div class="site-grid">

      <!-- ── LEFT COLUMN ── -->
      <aside class="col-L">
        <?php if($isGayaHidupPage):?>
        <div class="gh-nav">
          <div class="gh-nav-hd">Gaya Hidup</div>
          <?php $ghAL=$gayaHidupId?"index.php?kategori=$gayaHidupId":"index.php";?>
          <a href="<?=$ghAL?>" class="gh-nav-a <?=$activeKatNama==='Gaya Hidup'?'on':''?>"><i class="bi bi-grid-3x3-gap"></i> Semua</a>
          <?php foreach($subKatGayaHidup as $sk):$ic=$ghIcons[$sk['nama_kategori']]??'bi-tag';?>
          <a href="index.php?kategori=<?=$sk['id_kategori']?>" class="gh-nav-a <?=$filter==$sk['id_kategori']?'on':''?>">
            <i class="bi <?=$ic?>"></i> <?=htmlspecialchars($sk['nama_kategori'])?>
          </a>
          <?php endforeach;?>
        </div>
        <?php else:?>
        <?php
        $lQ=mysqli_query($koneksi,"SELECT a.*,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w $wSide ORDER BY a.tgl_posting DESC LIMIT 10");
        $lArr=[];while($lr=mysqli_fetch_assoc($lQ))$lArr[]=$lr;?>
        <div class="ls-head">Berita Terkini</div>
        <?php foreach($lArr as $i=>$ln):$li=img($ln);?>
        <a href="detail.php?id=<?=$ln['id_artikel']?>" class="ls-item">
          <?php if($i===0&&$li):?><img class="ls-img" src="<?=htmlspecialchars($li)?>" alt="">
          <?php elseif($i===0):?><div class="ls-img-ph"><i class="bi bi-image"></i></div>
          <?php endif;?>
          <div class="ls-n"><?=str_pad($i+1,2,'0',STR_PAD_LEFT)?></div>
          <div class="ls-cat"><?=htmlspecialchars($ln['nama_kategori'])?></div>
          <div class="ls-ttl"><?=htmlspecialchars($ln['judul'])?></div>
          <div class="ls-time"><i class="bi bi-clock"></i> <?=ago($ln['tgl_posting'])?></div>
        </a>
        <?php endforeach;?>
        <?php endif;?>
      </aside>

      <!-- ── MAIN COLUMN ── -->
      <main class="col-M">

        <?php if($isGayaHidupPage):?>
        <div class="gh-mob" style="margin-bottom:4px">
          <?php $ghAL=$gayaHidupId?"index.php?kategori=$gayaHidupId":"index.php";?>
          <a href="<?=$ghAL?>" class="gh-pill <?=$activeKatNama==='Gaya Hidup'?'on':''?>"><i class="bi bi-grid-3x3-gap"></i> Semua</a>
          <?php foreach($subKatGayaHidup as $sk):$ic=$ghIcons[$sk['nama_kategori']]??'bi-tag';?>
          <a href="index.php?kategori=<?=$sk['id_kategori']?>" class="gh-pill <?=$filter==$sk['id_kategori']?'on':''?>">
            <i class="bi <?=$ic?>"></i><?=htmlspecialchars($sk['nama_kategori'])?>
          </a>
          <?php endforeach;?>
        </div>
        <?php $bD=$ghDescs[$activeKatNama]??'Kesehatan, kuliner, travel & tren terkini';
              $bI=$ghIcons[$activeKatNama]??'bi-stars';?>
        <div class="gh-banner">
          <div class="gh-banner-i"><i class="bi <?=$bI?>"></i></div>
          <div class="gh-banner-t"><h3><?=htmlspecialchars($activeKatNama)?></h3><p><?=$bD?></p></div>
        </div>
        <?php if(empty($berita)):?><div class="empty"><i class="bi bi-newspaper"></i><p>Belum ada berita.</p></div>
        <?php else:?>
        <div class="sec"><div class="sec-lbl">Berita Terbaru</div><div class="sec-line"></div></div>
        <div class="gh-grid">
          <?php foreach($berita as $n):$ni=img($n);?>
          <a href="detail.php?id=<?=$n['id_artikel']?>" class="gh-card">
            <?php if($ni):?><img class="gh-card-img" src="<?=htmlspecialchars($ni)?>" alt="">
            <?php else:?><div class="gh-card-no-img"><i class="bi bi-image"></i></div><?php endif;?>
            <div class="gh-card-body">
              <div class="gh-card-cat"><?=htmlspecialchars($n['nama_kategori'])?></div>
              <div class="gh-card-ttl"><?=htmlspecialchars($n['judul'])?></div>
              <div class="gh-card-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?></div>
            </div>
          </a>
          <?php endforeach;?>
        </div>
        <?php endif;?>

        <?php elseif($search):?>
        <div class="sr-notice">
          <div class="sr-txt">
            <?php if($totalCount>0):?>Ditemukan <strong><?=$totalCount?> berita</strong> untuk &ldquo;<strong><?=htmlspecialchars($search)?></strong>&rdquo;<?php if($totalPages>=1):?> · Hal. <strong><?=$page?>/<?=$totalPages?></strong><?php endif;?>
            <?php else:?>Tidak ada hasil untuk &ldquo;<strong><?=htmlspecialchars($search)?></strong>&rdquo;<?php endif;?>
          </div>
          <a href="index.php" class="sr-clr"><i class="bi bi-x-lg"></i> Hapus</a>
        </div>
        <?php if(empty($berita)):?><div class="sr-empty"><i class="bi bi-search"></i><p>Berita tidak ditemukan.</p></div>
        <?php else:foreach($berita as $n):$ni=img($n);
          $jhl=htmlspecialchars($n['judul']);
          $jhl=preg_replace('/('.preg_quote(htmlspecialchars($search),'/').')/i','<mark>$1</mark>',$jhl);?>
        <a href="detail.php?id=<?=$n['id_artikel']?>" class="sr-item">
          <?php if($ni):?><div class="sr-th"><img src="<?=htmlspecialchars($ni)?>" alt=""></div>
          <?php else:?><div class="sr-ph"><i class="bi bi-image"></i></div><?php endif;?>
          <div class="sr-body">
            <span class="sr-cat"><?=htmlspecialchars($n['nama_kategori'])?></span>
            <div class="sr-ttl"><?=$jhl?></div>
            <div class="sr-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?> · <?=tgl($n['tgl_posting'])?></div>
          </div>
        </a>
        <?php endforeach;endif;?>

        <?php else:?>
        <?php if(empty($berita)):?><div class="empty"><i class="bi bi-newspaper"></i><p>Belum ada berita.</p></div>
        <?php else:
          if($page===1):
            $h=$berita[0];$himg=img($h);
            $subs=[];for($i=1;$i<count($berita)&&count($subs)<3;$i++){if(img($berita[$i]))$subs[]=['idx'=>$i,'d'=>$berita[$i]];}
            $used=[0];foreach($subs as $s)$used[]=$s['idx'];
            $rem=[];for($i=0;$i<count($berita);$i++){if(!in_array($i,$used))$rem[]=$berita[$i];}
            $cards=array_slice($rem,0,4);$listIt=array_slice($rem,4,9);?>

          <div class="hero-wrap">
            <?php if($himg):?>
            <a href="detail.php?id=<?=$h['id_artikel']?>" class="hero-main">
              <img src="<?=htmlspecialchars($himg)?>" alt="<?=htmlspecialchars($h['judul'])?>">
              <div class="hero-main-body">
                <span class="bdg"><?=htmlspecialchars($h['nama_kategori'])?></span>
                <h2><?=htmlspecialchars($h['judul'])?></h2>
                <div class="hero-main-meta"><i class="bi bi-clock"></i><?=ago($h['tgl_posting'])?><span style="opacity:.35">·</span><?=tgl($h['tgl_posting'])?></div>
              </div>
            </a>
            <?php else:?>
            <a href="detail.php?id=<?=$h['id_artikel']?>" class="hero-txt">
              <span class="bdg"><?=htmlspecialchars($h['nama_kategori'])?></span>
              <h2><?=htmlspecialchars($h['judul'])?></h2>
              <div class="m"><i class="bi bi-clock"></i> <?=ago($h['tgl_posting'])?></div>
            </a>
            <?php endif;?>
            <?php if(!empty($subs)):?>
            <div class="hero-subs">
              <?php foreach($subs as $sb):$s=$sb['d'];$si=img($s);?>
              <a href="detail.php?id=<?=$s['id_artikel']?>" class="hero-sub">
                <img src="<?=htmlspecialchars($si)?>" alt="">
                <div class="hero-sub-body">
                  <span class="bdg" style="font-size:.48rem;padding:2px 7px"><?=htmlspecialchars($s['nama_kategori'])?></span>
                  <h4><?=htmlspecialchars($s['judul'])?></h4>
                  <div class="hero-sub-meta"><?=ago($s['tgl_posting'])?></div>
                </div>
              </a>
              <?php endforeach;?>
            </div>
            <?php endif;?>
          </div>

          <?php if(!empty($cards)):?>
          <div class="sec"><div class="sec-lbl">Berita Pilihan</div><div class="sec-line"></div></div>
          <div class="cg">
            <?php foreach($cards as $c):$ci=img($c);?>
            <a href="detail.php?id=<?=$c['id_artikel']?>" class="card">
              <?php if($ci):?><img class="card-img" src="<?=htmlspecialchars($ci)?>" alt="">
              <?php else:?><div class="card-no-img"><i class="bi bi-image"></i></div><?php endif;?>
              <div class="card-body">
                <div class="card-cat"><?=htmlspecialchars($c['nama_kategori'])?></div>
                <div class="card-ttl"><?=htmlspecialchars($c['judul'])?></div>
                <div class="card-meta"><i class="bi bi-clock"></i> <?=ago($c['tgl_posting'])?></div>
              </div>
            </a>
            <?php endforeach;?>
          </div>
          <?php endif;?>

          <?php if(!empty($listIt)):?>
          <div class="divider"><div class="div-l"></div><div class="div-d"></div><div class="div-l"></div></div>
          <div class="sec"><div class="sec-lbl">Berita Terkini</div><div class="sec-line"></div></div>
          <div class="nlist">
            <?php foreach($listIt as $n):$ni=img($n);?>
            <a href="detail.php?id=<?=$n['id_artikel']?>" class="nli">
              <?php if($ni):?><div class="nli-th"><img src="<?=htmlspecialchars($ni)?>" alt=""></div>
              <?php else:?><div class="nli-ph"><i class="bi bi-image"></i></div><?php endif;?>
              <div class="nli-body">
                <span class="nli-cat"><?=htmlspecialchars($n['nama_kategori'])?></span>
                <div class="nli-ttl"><?=htmlspecialchars($n['judul'])?></div>
                <div class="nli-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?> · <?=tgl($n['tgl_posting'])?></div>
              </div>
            </a>
            <?php endforeach;?>
          </div>
          <?php endif;?>

          <?php else:
            $beritaPage = array_slice($berita, 0, 21);?>
          <div class="sec" style="margin-top:0">
            <div class="sec-lbl">Hal. <?=$page?></div>
            <div class="sec-line"></div>
          </div>
          <div class="nlist">
            <?php foreach($beritaPage as $n):$ni=img($n);?>
            <a href="detail.php?id=<?=$n['id_artikel']?>" class="nli">
              <?php if($ni):?><div class="nli-th"><img src="<?=htmlspecialchars($ni)?>" alt=""></div>
              <?php else:?><div class="nli-ph"><i class="bi bi-image"></i></div><?php endif;?>
              <div class="nli-body">
                <span class="nli-cat"><?=htmlspecialchars($n['nama_kategori'])?></span>
                <div class="nli-ttl"><?=htmlspecialchars($n['judul'])?></div>
                <div class="nli-meta"><i class="bi bi-clock"></i> <?=ago($n['tgl_posting'])?> · <?=tgl($n['tgl_posting'])?></div>
              </div>
            </a>
            <?php endforeach;?>
          </div>
          <?php endif;endif;?>
        <?php endif;?>

      </main>

      <!-- ── RIGHT COLUMN ── -->
      <aside class="col-R">
        <div class="sb-blk">
          <div class="sb-hd"><i class="bi bi-clock-history"></i> Terbaru <?=$activeKatNama ? '— '.htmlspecialchars($activeKatNama) : ''?></div>
          <?php mysqli_data_seek($sideRows,0);while($s=mysqli_fetch_assoc($sideRows)):
            $sImg=null;$sf=!empty($s['thumbnail'])?$s['thumbnail']:'';
            if($sf){if(filter_var($sf,FILTER_VALIDATE_URL))$sImg=$sf;elseif(file_exists("../uploads/$sf"))$sImg="../uploads/$sf";}?>
          <a href="detail.php?id=<?=$s['id_artikel']?>" class="sb-li">
            <?php if($sImg):?><div class="sb-li-th"><img src="<?=htmlspecialchars($sImg)?>" alt=""></div>
            <?php else:?><div class="sb-li-ph"><i class="bi bi-image"></i></div><?php endif;?>
            <div class="sb-li-body">
              <div class="sb-li-cat"><?=htmlspecialchars($s['nama_kategori'])?></div>
              <div class="sb-li-ttl"><?=htmlspecialchars($s['judul'])?></div>
              <div class="sb-li-t"><i class="bi bi-clock"></i> <?=ago($s['tgl_posting'])?></div>
            </div>
          </a>
          <?php endwhile;?>
        </div>

        <?php $pq=mysqli_query($koneksi,
        "SELECT a.id_artikel,a.judul,a.tgl_posting,k.nama_kategori
        FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori
        $w $wSide ORDER BY a.tgl_posting DESC LIMIT 10");?>
        <div class="sb-blk">
          <div class="sb-hd"><i class="bi bi-bar-chart-fill"></i> Terpopuler <?=$activeKatNama ? '— '.htmlspecialchars($activeKatNama) : ''?></div>
          <?php $pn=1;while($p=mysqli_fetch_assoc($pq)):?>
          <a href="detail.php?id=<?=$p['id_artikel']?>" class="sb-pi">
            <div class="sb-pi-n"><?=str_pad($pn++,2,'0',STR_PAD_LEFT)?></div>
            <div>
              <div class="sb-pi-ttl"><?=htmlspecialchars($p['judul'])?></div>
              <div class="sb-pi-m"><?=htmlspecialchars($p['nama_kategori'])?> · <?=ago($p['tgl_posting'])?></div>
            </div>
          </a>
          <?php endwhile;?>
        </div>

        <div class="sb-blk">
          <div class="sb-hd"><i class="bi bi-tag-fill"></i> Topik</div>
          <div class="sb-topics">
            <a href="index.php" class="sb-tpc <?=$filter===''?'on':''?>">Semua</a>
            <?php foreach($kats as $k):?>
            <a href="index.php?kategori=<?=$k['id_kategori']?>" class="sb-tpc <?=$filter==$k['id_kategori']?'on':''?>"><?=htmlspecialchars($k['nama_kategori'])?></a>
            <?php endforeach;?>
          </div>
        </div>
      </aside>

    </div><!-- /site-grid -->

    <!-- ══ PAGINATION DESKTOP — di luar grid ══ -->
    <?php if($totalPages>1):
      $bp=[];if($filter)$bp['kategori']=$filter;if($search)$bp['q']=$search;
      $rng=2;$st=max(1,$page-$rng);$ed=min($totalPages,$page+$rng);?>
    <div class="pages-outer pages-desktop">
      <nav class="pages" aria-label="Halaman">
        <span class="pg-inf">Total <strong><?=number_format($totalCount)?></strong></span>
        <?php if($page>1):?><a href="<?=pgUrl($page-1,$bp)?>" class="pg" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></a>
        <?php else:?><span class="pg off"><i class="bi bi-chevron-left"></i></span><?php endif;?>
        <?php if($st>1):?><a href="<?=pgUrl(1,$bp)?>" class="pg">1</a><?php if($st>2):?><span class="pg-dots">…</span><?php endif;endif;?>
        <?php for($i=$st;$i<=$ed;$i++):?><?php if($i===$page):?><span class="pg on"><?=$i?></span><?php else:?><a href="<?=pgUrl($i,$bp)?>" class="pg"><?=$i?></a><?php endif;?><?php endfor;?>
        <?php if($ed<$totalPages):?><?php if($ed<$totalPages-1):?><span class="pg-dots">…</span><?php endif;?><a href="<?=pgUrl($totalPages,$bp)?>" class="pg"><?=$totalPages?></a><?php endif;?>
        <?php if($page<$totalPages):?><a href="<?=pgUrl($page+1,$bp)?>" class="pg" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></a>
        <?php else:?><span class="pg off"><i class="bi bi-chevron-right"></i></span><?php endif;?>
        <div class="pg-jump">
          <input type="number" id="pgJ" min="1" max="<?=$totalPages?>" placeholder="<?=$page?>" aria-label="Halaman">
          <button class="pg-go" onclick="pgJump()">Pergi</button>
        </div>
      </nav>
    </div>
    <?php endif;?>

    <!-- MOBILE SIDEBAR -->
    <div class="mob-sb">
      <div class="mob-sb-g">
        <div>
          <div class="sb-blk">
            <div class="sb-hd"><i class="bi bi-clock-history"></i> Terbaru</div>
            <?php $sr2=mysqli_query($koneksi,"SELECT a.id_artikel,a.judul,a.tgl_posting,a.thumbnail,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w $wSide ORDER BY a.tgl_posting DESC LIMIT 7");
            while($s=mysqli_fetch_assoc($sr2)):
              $sImg=null;$sf=!empty($s['thumbnail'])?$s['thumbnail']:'';
              if($sf){if(filter_var($sf,FILTER_VALIDATE_URL))$sImg=$sf;elseif(file_exists("../uploads/$sf"))$sImg="../uploads/$sf";}?>
            <a href="detail.php?id=<?=$s['id_artikel']?>" class="sb-li">
              <?php if($sImg):?><div class="sb-li-th"><img src="<?=htmlspecialchars($sImg)?>" alt=""></div>
              <?php else:?><div class="sb-li-ph"><i class="bi bi-image"></i></div><?php endif;?>
              <div class="sb-li-body">
                <div class="sb-li-cat"><?=htmlspecialchars($s['nama_kategori'])?></div>
                <div class="sb-li-ttl"><?=htmlspecialchars($s['judul'])?></div>
                <div class="sb-li-t"><i class="bi bi-clock"></i> <?=ago($s['tgl_posting'])?></div>
              </div>
            </a>
            <?php endwhile;?>
          </div>
        </div>
        <div>
          <?php $pq2=mysqli_query($koneksi,"SELECT a.id_artikel,a.judul,a.tgl_posting,k.nama_kategori FROM artikel a JOIN kategori k ON a.kategori_id=k.id_kategori $w $wSide ORDER BY a.tgl_posting DESC LIMIT 8");?>
          <div class="sb-blk">
            <div class="sb-hd"><i class="bi bi-bar-chart-fill"></i> Terpopuler</div>
            <?php $pn2=1;while($p2=mysqli_fetch_assoc($pq2)):?>
            <a href="detail.php?id=<?=$p2['id_artikel']?>" class="sb-pi">
              <div class="sb-pi-n"><?=str_pad($pn2++,2,'0',STR_PAD_LEFT)?></div>
              <div>
                <div class="sb-pi-ttl"><?=htmlspecialchars($p2['judul'])?></div>
                <div class="sb-pi-m"><?=htmlspecialchars($p2['nama_kategori'])?> · <?=ago($p2['tgl_posting'])?></div>
              </div>
            </a>
            <?php endwhile;?>
          </div>
        </div>
      </div>
      <!-- PAGINATION MOBILE — setelah Terpopuler -->
      <?php if($totalPages>1):
        $bp2=[];if($filter)$bp2['kategori']=$filter;if($search)$bp2['q']=$search;
        $rng2=2;$st2=max(1,$page-$rng2);$ed2=min($totalPages,$page+$rng2);?>
      <div class="pages-outer pages-mobile" style="margin-top:16px">
        <nav class="pages" aria-label="Halaman">
          <?php if($page>1):?><a href="<?=pgUrl($page-1,$bp2)?>" class="pg" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></a>
          <?php else:?><span class="pg off"><i class="bi bi-chevron-left"></i></span><?php endif;?>
          <?php if($st2>1):?><a href="<?=pgUrl(1,$bp2)?>" class="pg">1</a><?php if($st2>2):?><span class="pg-dots">…</span><?php endif;endif;?>
          <?php for($i=$st2;$i<=$ed2;$i++):?><?php if($i===$page):?><span class="pg on"><?=$i?></span><?php else:?><a href="<?=pgUrl($i,$bp2)?>" class="pg"><?=$i?></a><?php endif;?><?php endfor;?>
          <?php if($ed2<$totalPages):?><?php if($ed2<$totalPages-1):?><span class="pg-dots">…</span><?php endif;?><a href="<?=pgUrl($totalPages,$bp2)?>" class="pg"><?=$totalPages?></a><?php endif;?>
          <?php if($page<$totalPages):?><a href="<?=pgUrl($page+1,$bp2)?>" class="pg" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></a>
          <?php else:?><span class="pg off"><i class="bi bi-chevron-right"></i></span><?php endif;?>
        </nav>
      </div>
      <?php endif;?>

      <div class="sb-blk" style="margin-top:16px">
        <div class="sb-hd"><i class="bi bi-tag-fill"></i> Topik</div>
        <div class="sb-topics">
          <a href="index.php" class="sb-tpc <?=$filter===''?'on':''?>">Semua</a>
          <?php foreach($kats as $k):?>
          <a href="index.php?kategori=<?=$k['id_kategori']?>" class="sb-tpc <?=$filter==$k['id_kategori']?'on':''?>"><?=htmlspecialchars($k['nama_kategori'])?></a>
          <?php endforeach;?>
        </div>
      </div>
    </div>

  </div><!-- /W -->
</div><!-- /page-body -->

<!-- FOOTER -->
<footer class="site-footer">
  <div class="W">
    <div class="ft-top">

      <div class="ft-brand">
        <div class="ft-logo">Liy<em>News</em></div>
        <p>Menyajikan berita terpercaya, akurat, dan terkini untuk seluruh masyarakat Indonesia. Independen, bertanggung jawab, dan berpihak pada fakta.</p>
      </div>

      <div class="ft-col">
        <h6>Kategori</h6>
        <a href="index.php" class="<?=$filter===''?'ft-active':''?>">Beranda</a>
        <?php foreach($halfKats[0] ?? [] as $k):?>
        <a href="index.php?kategori=<?=(int)$k['id_kategori']?>"
           class="<?=$filter==(string)$k['id_kategori']?'ft-active':''?>">
          <?=htmlspecialchars($k['nama_kategori'])?>
        </a>
        <?php endforeach;?>
      </div>

      <div class="ft-col">
        <h6>&nbsp;</h6>
        <?php foreach($halfKats[1] ?? [] as $k):?>
        <a href="index.php?kategori=<?=(int)$k['id_kategori']?>"
           class="<?=$filter==(string)$k['id_kategori']?'ft-active':''?>">
          <?=htmlspecialchars($k['nama_kategori'])?>
        </a>
        <?php endforeach;?>
      </div>

      <div class="ft-col">
        <h6>Perusahaan</h6>
        <a href="tentang.php">Tentang Kami</a>
        <a href="redaksi.php">Redaksi</a>
        <a href="karier.php">Karier</a>
        <a href="iklan.php">Iklan</a>
        <a href="kontak.php">Kontak</a>
      </div>

      <div class="ft-col">
        <h6>Legal</h6>
        <a href="privasi.php">Kebijakan Privasi</a>
        <a href="syarat.php">Syarat &amp; Ketentuan</a>
        <a href="pedoman.php">Pedoman Media Siber</a>
      </div>

    </div>

    <div class="ft-bar">
      <div class="ft-copy">&copy; <?=date('Y')?> LiyNews — Semua hak dilindungi.</div>
      <div class="ft-soc">
        <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" aria-label="Twitter/X"><i class="bi bi-twitter-x"></i></a>
        <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
        <a href="#" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
      </div>
    </div>
  </div>
</footer>

<!-- ══ MODAL KONFIRMASI LOGOUT ══ -->
<div class="logout-modal-overlay" id="logoutModal" onclick="if(event.target===this)hideLogoutModal()">
  <div class="logout-modal-box">
    <div class="logout-modal-icon">
      <i class="bi bi-box-arrow-right"></i>
    </div>
    <h3>Yakin ingin logout?</h3>
    <p>Kamu akan keluar dari LiyNews.<br>Sesi aktif akan diakhiri.</p>
    <div class="logout-modal-actions">
      <button type="button" class="logout-btn-cancel" onclick="hideLogoutModal()">
        <i class="bi bi-x-lg"></i> Batal
      </button>
      <a href="logout.php" class="logout-btn-confirm">
        <i class="bi bi-box-arrow-right"></i> Ya, Logout
      </a>
    </div>
  </div>
</div>

<script>
/* Theme */
(function(){
  var h=document.documentElement,b=document.getElementById('themeBtn');
  function apply(t){h.setAttribute('data-theme',t);b.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';b.setAttribute('aria-label',t==='dark'?'Mode terang':'Mode gelap');}
  apply(h.getAttribute('data-theme'));
  b.addEventListener('click',function(){var n=h.getAttribute('data-theme')==='dark'?'light':'dark';localStorage.setItem('pb_theme',n);apply(n);});
  if(window.matchMedia)window.matchMedia('(prefers-color-scheme:dark)').addEventListener('change',function(e){if(!localStorage.getItem('pb_theme'))apply(e.matches?'dark':'light');});
})();

/* Mobile logo swap */
(function(){
  var m=document.querySelector('.mast-logo-mob');
  if(!m)return;
  function u(){m.style.display=window.innerWidth<900?'block':'none';}
  u();window.addEventListener('resize',u);
})();

/* Mobile search */
var ms=document.getElementById('mobSBtn'),mb=document.getElementById('mobSbar'),mi=document.getElementById('mobSIn');
if(ms&&mb){
  ms.addEventListener('click',function(e){e.stopPropagation();var o=mb.classList.toggle('open');ms.innerHTML=o?'<i class="bi bi-x-lg"></i>':'<i class="bi bi-search"></i>';if(o&&mi)setTimeout(function(){mi.focus();},80);});
  document.addEventListener('click',function(e){if(!mb.contains(e.target)&&e.target!==ms&&!ms.contains(e.target)){mb.classList.remove('open');ms.innerHTML='<i class="bi bi-search"></i>';}});
}

/* Desktop live search */
var si=document.getElementById('searchInput'),ldd=document.getElementById('liveDD');
if(si){
  if(ldd)ldd.addEventListener('mousedown',function(e){e.preventDefault();});
  <?php if($search):?>requestAnimationFrame(function(){si.focus();var l=si.value.length;si.setSelectionRange(l,l);});<?php endif;?>
  var _t;
  si.addEventListener('input',function(){clearTimeout(_t);var q=this.value.trim();if(q.length<2){if(ldd)ldd.classList.remove('open');return;}_t=setTimeout(function(){window.location.href='index.php?q='+encodeURIComponent(q);},700);});
  si.addEventListener('keydown',function(e){if(e.key==='Enter'){clearTimeout(_t);var q=si.value.trim();if(q.length>=2)window.location.href='index.php?q='+encodeURIComponent(q);}if(e.key==='Escape'){clearTimeout(_t);window.location.href='index.php';}});
  si.addEventListener('focus',function(){if(ldd&&si.value.trim().length>=2)ldd.classList.add('open');});
  document.addEventListener('click',function(e){var sw=si.closest('.mast-search-wrap');if(sw&&!sw.contains(e.target)&&ldd)ldd.classList.remove('open');});
}

/* User dropdown */
var uB=document.getElementById('uBtn'),uD=document.getElementById('uDd');
if(uB&&uD){
  uB.addEventListener('click',function(e){e.stopPropagation();var o=uD.classList.toggle('open');uB.classList.toggle('open',o);uB.setAttribute('aria-expanded',o);});
  document.addEventListener('click',function(e){var uw=document.getElementById('uWrap');if(uw&&!uw.contains(e.target)){uD.classList.remove('open');uB.classList.remove('open');uB.setAttribute('aria-expanded','false');}});
}

/* Pagination jump */
function pgJump(){var inp=document.getElementById('pgJ');if(!inp)return;var p=parseInt(inp.value),max=<?=$totalPages?>;if(p>=1&&p<=max){var u=new URL(window.location.href);u.searchParams.set('page',p);window.location.href=u.toString();}else{inp.focus();inp.select();}}
var pgEl=document.getElementById('pgJ');if(pgEl)pgEl.addEventListener('keydown',function(e){if(e.key==='Enter')pgJump();});

/* Category nav arrows */
(function(){
  var nav=document.getElementById('catScroll'),l=document.getElementById('catL'),r=document.getElementById('catR');
  if(!nav||!l||!r)return;
  var step=200;
  function upd(){l.classList.toggle('gone',nav.scrollLeft<=4);r.classList.toggle('gone',nav.scrollLeft+nav.offsetWidth>=nav.scrollWidth-4);}
  l.addEventListener('click',function(){nav.scrollBy({left:-step,behavior:'smooth'});});
  r.addEventListener('click',function(){nav.scrollBy({left:step,behavior:'smooth'});});
  nav.addEventListener('scroll',upd,{passive:true});
  var act=nav.querySelector('.cat-lnk.on');
  if(act)nav.scrollLeft=act.offsetLeft-(nav.offsetWidth/2)+(act.offsetWidth/2);
  upd();window.addEventListener('load',upd);window.addEventListener('resize',upd);
})();

/* ══ MODAL LOGOUT ══ */
function showLogoutModal(){
  // Tutup dropdown user terlebih dahulu
  if(uD){uD.classList.remove('open');}
  if(uB){uB.classList.remove('open');uB.setAttribute('aria-expanded','false');}
  // Tampilkan modal
  var m=document.getElementById('logoutModal');
  m.classList.add('show');
  document.body.style.overflow='hidden';
}
function hideLogoutModal(){
  var m=document.getElementById('logoutModal');
  m.classList.remove('show');
  document.body.style.overflow='';
}
// Tutup modal dengan Escape
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){
    hideLogoutModal();
    // Tutup user dropdown juga
    if(uD){uD.classList.remove('open');}
    if(uB){uB.classList.remove('open');uB.setAttribute('aria-expanded','false');}
  }
});
</script>
</body>
</html>