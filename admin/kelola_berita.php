<?php
/**
 * kelola_berita.php  –  Multi-Kategori Edition (Unified Design)
 */

session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$current = basename($_SERVER['PHP_SELF']);
$msg     = $_GET['msg'] ?? '';

function parseKatIds($row) {
    if (!empty($row['kategori_ids'])) {
        $arr = json_decode($row['kategori_ids'], true);
        if (is_array($arr) && !empty($arr)) return array_map('intval', $arr);
    }
    if (!empty($row['kategori_id'])) return [(int)$row['kategori_id']];
    return [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_massal'])) {
    $ids = $_POST['ids'] ?? [];
    if (!empty($ids)) {
        $ids_clean = array_map('intval', $ids);
        $ids_str   = implode(',', $ids_clean);
        mysqli_query($koneksi, "DELETE FROM artikel WHERE id_artikel IN ($ids_str)");
        $jumlah = count($ids_clean);
        header("Location: kelola_berita.php?msg=massal&jml=$jumlah"); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_kategori'])) {
    $ids      = $_POST['ids'] ?? [];
    $kat_baru = isset($_POST['kategori_baru']) ? array_map('intval', (array)$_POST['kategori_baru']) : [];
    $kat_baru = array_filter($kat_baru);
    $mode     = $_POST['kat_mode'] ?? 'replace';
    if (!empty($ids) && !empty($kat_baru)) {
        $ids_clean = array_map('intval', $ids);
        foreach ($ids_clean as $artId) {
            $row = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT kategori_ids, kategori_id FROM artikel WHERE id_artikel=$artId"));
            if (!$row) continue;
            $current_ids = parseKatIds($row);
            if ($mode === 'append') {
                foreach ($kat_baru as $kb) { if (!in_array($kb, $current_ids)) $current_ids[] = $kb; }
                $new_ids = $current_ids;
            } else { $new_ids = array_values($kat_baru); }
            $new_json = mysqli_real_escape_string($koneksi, json_encode(array_values($new_ids)));
            $primary  = (int)$new_ids[0];
            mysqli_query($koneksi, "UPDATE artikel SET kategori_ids='$new_json', kategori_id=$primary, kategori_manual=1 WHERE id_artikel=$artId");
        }
        $jumlah = count($ids_clean);
        $scroll = isset($_POST['scroll_y']) ? (int)$_POST['scroll_y'] : 0;
        header("Location: kelola_berita.php?msg=kategori&jml=$jumlah&scroll=$scroll"); exit;
    }
}

if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_duplikat') {
    $dupQ = mysqli_query($koneksi, "SELECT MIN(id_artikel) as keep_id, judul, COUNT(*) as total FROM artikel GROUP BY judul HAVING COUNT(*) > 1");
    $deleted = 0;
    while ($dup = mysqli_fetch_assoc($dupQ)) {
        $judul_esc = mysqli_real_escape_string($koneksi, $dup['judul']);
        $keep      = (int)$dup['keep_id'];
        mysqli_query($koneksi, "DELETE FROM artikel WHERE judul='$judul_esc' AND id_artikel != $keep");
        $deleted += mysqli_affected_rows($koneksi);
    }
    header("Location: kelola_berita.php?msg=duplikat&jml=$deleted"); exit;
}

$filterStatus = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$filterKatId  = isset($_GET['kat'])    ? (int)$_GET['kat'] : 0;
$search       = isset($_GET['q'])      ? mysqli_real_escape_string($koneksi, trim($_GET['q'])): '';
$where = "WHERE 1=1";
if ($filterStatus) $where .= " AND a.status='$filterStatus'";
if ($search)       $where .= " AND a.judul LIKE '%$search%'";
if ($filterKatId)  $where .= " AND (JSON_CONTAINS(COALESCE(a.kategori_ids,'[]'), '$filterKatId', '\$') OR (a.kategori_ids IS NULL AND a.kategori_id=$filterKatId))";

$beritaQ = mysqli_query($koneksi, "SELECT a.*, u.username AS nama_penulis, COALESCE(a.kategori_manual, 0) AS kategori_manual FROM artikel a LEFT JOIN user u ON a.penulis_id = u.id_user $where ORDER BY a.tgl_posting DESC");

$katQ = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kats = []; $katsById = [];
while ($k = mysqli_fetch_assoc($katQ)) { $kats[] = $k; $katsById[(int)$k['id_kategori']] = $k; }

$katCountQ = mysqli_query($koneksi, "SELECT k.id_kategori, k.nama_kategori, (SELECT COUNT(*) FROM artikel a WHERE (JSON_CONTAINS(COALESCE(a.kategori_ids,'[]'), CAST(k.id_kategori AS CHAR), '\$') OR (a.kategori_ids IS NULL AND a.kategori_id = k.id_kategori))) AS jumlah FROM kategori k ORDER BY k.nama_kategori ASC");
$katCounts = []; $totalArtikel = 0;
$totalUnikQ = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM artikel");
if ($totalUnikQ) $totalArtikel = (int)mysqli_fetch_assoc($totalUnikQ)['c'];
while ($kc = mysqli_fetch_assoc($katCountQ)) $katCounts[(int)$kc['id_kategori']] = $kc;

$allRows = [];
while ($r = mysqli_fetch_assoc($beritaQ)) $allRows[] = $r;
$judulCount = array_count_values(array_column($allRows, 'judul'));

$dupCount = 0;
$dupCheck = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM (SELECT judul FROM artikel GROUP BY judul HAVING COUNT(*) > 1) x");
if ($dupCheck) $dupCount = (int)mysqli_fetch_assoc($dupCheck)['c'];

$jumlahBerita = $totalArtikel;

function kat_color($nama) {
    $map = [
        'Teknologi'=>['#dbeafe','#1d4ed8','⚙'],'Olahraga'=>['#dcfce7','#15803d','⚽'],
        'Bisnis'=>['#fef9c3','#a16207','💼'],'Hiburan'=>['#fce7f3','#be185d','🎬'],
        'Politik'=>['#e0e7ff','#4338ca','🏛'],'Pendidikan'=>['#f3e8ff','#7e22ce','📚'],
        'Otomotif'=>['#ffedd5','#c2410c','🚗'],'Berita Utama'=>['#fee2e2','#b91c1c','📰'],
        'Internasional'=>['#cffafe','#0e7490','🌍'],'Gaya Hidup'=>['#fdf4ff','#a21caf','✨'],
        'Kesehatan'=>['#f0fdf4','#16a34a','🏥'],'Kuliner'=>['#fff7ed','#c2410c','🍜'],
        'Travel'=>['#ecfdf5','#059669','✈'],'Religi'=>['#fefce8','#854d0e','🕌'],
        'Cuaca'=>['#e0f2fe','#0369a1','🌤'],'Hukum'=>['#f1f5f9','#334155','⚖'],
        'Nasional'=>['#fef2f2','#991b1b','🇮🇩'],'Ekonomi'=>['#fffbeb','#92400e','📈'],
        'Sains'=>['#f0fdf4','#166534','🔬'],
    ];
    return $map[trim($nama)] ?? ['#f1f5f9','#475569','📄'];
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Berita — LiyNews</title>
  <script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,300;1,9..144,400;1,9..144,600;1,9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
/* ═══════════════════════════════════════════
   TOKENS  —  identik dengan dashboardadmin.php
═══════════════════════════════════════════ */
:root {
  --sb-bg:      #1c2540;
  --sb-border:  rgba(255,255,255,.08);
  --sb-text:    rgba(255,255,255,.40);
  --sb-hover:   rgba(255,255,255,.055);
  --sb-active:  rgba(91,155,248,.15);
  --blue-panel: #5b9bf8;

  --bg:         #f5f4ef;
  --bg2:        #edecea;
  --card:       #ffffff;
  --ink:        #0d1221;
  --ink2:       #1c2540;
  --muted:      #5e6673;
  --faint:      #a8a49e;
  --border:     #dbd8d0;
  --border-lt:  #eeece6;

  --blue:       #1a56db;
  --blue-soft:  rgba(26,86,219,.09);

  /* ← level ketiga --c*t ditambahkan agar sepenuhnya sinkron */
  --c1: #1a56db; --c1s: rgba(26,86,219,.09); --c1t: rgba(26,86,219,.04);
  --c2: #059669; --c2s: rgba(5,150,105,.09); --c2t: rgba(5,150,105,.04);
  --c3: #d97706; --c3s: rgba(217,119,6,.09); --c3t: rgba(217,119,6,.04);
  --c4: #7c3aed; --c4s: rgba(124,58,237,.09); --c4t: rgba(124,58,237,.04);
  --c5: #dc2626; --c5s: rgba(220,38,38,.09); --c5t: rgba(220,38,38,.04);

  --fd: 'Fraunces', Georgia, serif;
  --fs: 'Outfit', system-ui, sans-serif;
  --sidebar-w: 252px;
  --r: 8px;
  --rl: 14px;
}
[data-theme="dark"] {
  --bg:        #090c17;
  --bg2:       #0d1225;
  --card:      #101828;
  --ink:       #e2e8f4;
  --ink2:      #0f1c35;;
  --muted:     #7888a8;
  --faint:     #364460;
  --border:    #1c2a48;
  --border-lt: #152035;
  --blue:      #5b9bf8;
  --blue-soft: rgba(91,155,248,.12);
  /* ← level ketiga dark juga disinkronkan */
  --c1s:rgba(26,86,219,.16);  --c1t:rgba(26,86,219,.08);
  --c2s:rgba(5,150,105,.16);  --c2t:rgba(5,150,105,.08);
  --c3s:rgba(217,119,6,.16);  --c3t:rgba(217,119,6,.08);
  --c4s:rgba(124,58,237,.16); --c4t:rgba(124,58,237,.08);
  --c5s:rgba(220,38,38,.16);  --c5t:rgba(220,38,38,.08);
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:var(--fs);
  background:var(--bg);
  color:var(--ink);
  min-height:100vh;
  -webkit-font-smoothing:antialiased;
  
}

/* ── Theme transition: hanya container utama, bukan cell/row ── */
body { transition: background-color .3s, color .3s; }
.tcard, .topbar, .content,
.ftab, .kat-tab, .search-box,
.alert, .dup-banner,
.modal-box,
.badge-pub, .badge-draft,
.act-btn, .btn-new, .btn-dup,
.mob-item, .mob-btn,
.btn-cancel, .btn-confirm,
.page-title, .sec-title {
  transition: background-color .3s, color .3s, border-color .3s;
}

a{color:inherit;text-decoration:none}

/* ═══════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════ */
.sidebar{
  position:fixed;left:0;top:0;bottom:0;
  width:var(--sidebar-w);
  background:var(--sb-bg);
  display:flex;flex-direction:column;
  z-index:200;
  border-right:1px solid var(--sb-border);
  transition:transform .3s cubic-bezier(.4,0,.2,1);
  overflow:hidden;
}
.sidebar::before{
  content:'';position:absolute;inset:0;pointer-events:none;
  background:
    radial-gradient(ellipse 90% 55% at 15% 12%, rgba(26,86,219,.22) 0%,transparent 58%),
    radial-gradient(ellipse 70% 50% at 85% 90%, rgba(91,155,248,.08) 0%,transparent 52%);
}
.sidebar::after{
  content:'';position:absolute;inset:0;pointer-events:none;opacity:.4;
  background-image:radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px);
  background-size:20px 20px;
  mask-image:linear-gradient(to bottom, transparent 0%, black 30%, black 70%, transparent 100%);
}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--sb-border);position:relative;z-index:1}
.sb-wordmark{font-family:var(--fd);font-size:1.75rem;font-weight:700;color:#fff;letter-spacing:-.02em;line-height:1}
.sb-wordmark em{font-style:italic;color:var(--blue-panel)}
.sb-tagline{margin-top:6px;font-size:.6rem;letter-spacing:.28em;text-transform:uppercase;color:rgba(255,255,255,.2);font-family:var(--fs);font-weight:400}
.sb-nav{flex:1;padding:10px 0;overflow-y:auto;scrollbar-width:none;position:relative;z-index:1}
.sb-nav::-webkit-scrollbar{display:none}
.sb-section{font-size:.56rem;font-weight:600;letter-spacing:.25em;text-transform:uppercase;color:rgba(255,255,255,.17);padding:18px 24px 5px;font-family:var(--fs)}
.sb-link{display:flex;align-items:center;gap:11px;padding:9px 24px;font-family:var(--fs);font-size:.84rem;font-weight:400;color:var(--sb-text);transition:color .15s,background-color .15s;border-left:2px solid transparent}
.sb-ico{width:30px;height:30px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:.82rem;flex-shrink:0;background:rgba(255,255,255,.04);}
.sb-link:hover{color:rgba(255,255,255,.78);background:var(--sb-hover)}
.sb-link:hover .sb-ico{background:rgba(91,155,248,.14);color:var(--blue-panel)}
.sb-link.active{color:#fff;background:var(--sb-active);border-left-color:var(--blue-panel);font-weight:500}
.sb-link.active .sb-ico{background:rgba(91,155,248,.2);color:var(--blue-panel)}
.sb-link-lbl{flex:1}
.sb-pill{font-family:var(--fs);font-size:.6rem;font-weight:600;padding:2px 8px;border-radius:4px;background:rgba(91,155,248,.15);color:var(--blue-panel);border:1px solid rgba(91,155,248,.22)}
.sb-bottom{border-top:1px solid var(--sb-border);padding:8px 0 4px;position:relative;z-index:1}
.sb-user{display:flex;align-items:center;gap:10px;padding:11px 24px;cursor:pointer;}
.sb-user:hover{background:rgba(255,255,255,.04)}
.sb-av{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#5b9bf8,#1a56db);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;font-family:var(--fd);font-style:italic;color:#fff;flex-shrink:0}
.sb-uname{font-size:.82rem;font-weight:500;color:#fff;font-family:var(--fs)}
.sb-urole{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.25);margin-top:1px;font-family:var(--fs)}
.sb-out{display:flex;align-items:center;gap:10px;padding:9px 24px;width:100%;font-family:var(--fs);font-size:.82rem;font-weight:400;color:rgba(248,113,113,.55);background:none;border:none;cursor:pointer;;border-left:2px solid transparent}
.sb-out:hover{color:#fca5a5;background:rgba(248,113,113,.06);border-left-color:rgba(248,113,113,.4)}
.sb-out i{font-size:.8rem}
.sidebar-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:150;opacity:0;pointer-events:none;transition:opacity .25s}
.sidebar-backdrop.show{display:block;opacity:1;pointer-events:auto}

/* ═══════════════════════════════════════════
   TOPBAR
═══════════════════════════════════════════ */
.topbar{
  margin-left:var(--sidebar-w);
  height:56px;
  background:var(--ink2);
  border-bottom:3px solid var(--blue-panel);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 36px;
  position:sticky;top:0;z-index:50;
  box-shadow:0 2px 24px rgba(8,12,30,.22);
}
.top-left{display:flex;align-items:center;gap:14px}
.top-crumb{font-family:var(--fs);font-size:.76rem;color:rgba(255,255,255,.3);display:flex;align-items:center;gap:5px}
.top-crumb b{color:rgba(255,255,255,.85);font-weight:500}
.top-crumb i{font-size:.62rem;color:rgba(255,255,255,.18)}
.top-date{font-family:var(--fd);font-style:italic;font-size:.8rem;color:rgba(255,255,255,.35);padding:4px 12px;border:1px solid rgba(255,255,255,.1);border-radius:4px;background:rgba(255,255,255,.04)}
.top-right{display:flex;align-items:center;gap:6px}
.top-btn{width:34px;height:34px;border-radius:7px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:rgba(255,255,255,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.top-btn:hover{border-color:var(--blue-panel);color:var(--blue-panel);background:rgba(91,155,248,.12)}
#sidebarToggle{display:none}

/* ═══════════════════════════════════════════
   CONTENT
═══════════════════════════════════════════ */
.content{margin-left:var(--sidebar-w);padding:36px 36px 72px;animation:pageIn .5s cubic-bezier(.22,1,.36,1) both}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* ═══════════════════════════════════════════
   PAGE HEADER
═══════════════════════════════════════════ */
.page-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:20px;padding-bottom:24px;border-bottom:1px solid var(--border)}
.page-eyebrow{font-family:var(--fs);font-size:.62rem;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:var(--blue);margin-bottom:7px;display:flex;align-items:center;gap:8px}
.page-eyebrow::before{content:'';width:20px;height:1.5px;background:var(--blue);border-radius:2px}
.page-title{font-family:var(--fd);font-size:1.9rem;font-weight:600;color:var(--ink);letter-spacing:-.02em;line-height:1.1}
.page-title em{font-style:italic;color:var(--blue)}
.page-sub{font-family:var(--fs);font-size:.84rem;color:var(--muted);margin-top:5px;font-weight:300}
.btn-new{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:var(--blue);color:#fff;border:none;border-radius:var(--r);font-family:var(--fs);font-size:.82rem;font-weight:600;letter-spacing:.02em;cursor:pointer;;white-space:nowrap;text-decoration:none;box-shadow:0 2px 12px rgba(26,86,219,.3)}
.btn-new:hover{background:var(--ink2);box-shadow:0 4px 20px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-new:active{transform:translateY(0)}

/* ═══════════════════════════════════════════
   ALERTS
═══════════════════════════════════════════ */
.alert{padding:11px 16px;border-radius:var(--r);font-family:var(--fs);font-size:.82rem;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
[data-theme="dark"] .alert-ok{background:#052e16;border-color:#14532d;color:#4ade80}

/* ═══════════════════════════════════════════
   DUP BANNER
═══════════════════════════════════════════ */
.dup-banner{background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);border-radius:var(--r);padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.dup-banner-txt{font-size:.82rem;color:var(--blue);display:flex;align-items:center;gap:8px;font-family:var(--fs)}
.btn-dup{background:var(--blue);color:#fff;border:none;border-radius:var(--r);padding:7px 16px;font-size:.76rem;font-weight:600;cursor:pointer;font-family:var(--fs);display:inline-flex;align-items:center;gap:6px;;text-decoration:none}
.btn-dup:hover{background:var(--ink2)}

/* ═══════════════════════════════════════════
   SECTION HEADER
═══════════════════════════════════════════ */
.sec-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:16px;flex-wrap:wrap}
.sec-title{font-family:var(--fd);font-size:1.2rem;font-weight:600;color:var(--ink);display:flex;align-items:center;gap:10px}
.sec-title::before{content:'';width:4px;height:18px;background:var(--blue);border-radius:2px;display:block}
.sec-caption{font-family:var(--fd);font-style:italic;font-size:.8rem;color:var(--faint);margin-left:2px}

/* ═══════════════════════════════════════════
   TOOLBAR
═══════════════════════════════════════════ */
.toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px;flex-wrap:wrap}
.toolbar-left{display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0}
.ftab{font-family:var(--fs);font-size:.76rem;font-weight:600;padding:7px 16px;border-radius:var(--r);border:1.5px solid var(--border);color:var(--muted);cursor:pointer;;background:var(--card);text-decoration:none;display:inline-block;white-space:nowrap}
.ftab:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}
.ftab.on{background:var(--blue);border-color:var(--blue);color:#fff;box-shadow:0 2px 8px rgba(26,86,219,.25)}
.search-box{display:flex;align-items:center;border:1.5px solid var(--border);border-radius:var(--r);overflow:hidden;background:var(--card);}
.search-box:focus-within{border-color:var(--blue)}
.search-box input{border:none;outline:none;padding:7px 12px;font-size:.82rem;background:transparent;color:var(--ink);width:180px;font-family:var(--fs)}
.search-box input::placeholder{color:var(--faint)}
.search-box button{background:none;border:none;padding:7px 10px;color:var(--muted);cursor:pointer;font-size:.88rem;}
.search-box button:hover{color:var(--blue)}

/* ═══════════════════════════════════════════
   KAT FILTER TABS
═══════════════════════════════════════════ */
.kat-filter-wrap{margin-bottom:16px}
.kat-filter-label{font-family:var(--fs);font-size:.62rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:6px}
.kat-tabs{display:flex;flex-wrap:wrap;gap:6px}
.kat-tab{display:inline-flex;align-items:center;gap:5px;padding:5px 13px;border-radius:99px;border:1.5px solid var(--border);font-family:var(--fs);font-size:.74rem;font-weight:600;cursor:pointer;;text-decoration:none;background:var(--card);color:var(--muted);white-space:nowrap}
.kat-tab:hover{opacity:.85}
.kat-tab.all{background:var(--blue);border-color:var(--blue);color:#fff}
.kat-tab.active{color:#fff!important;border-color:transparent!important;box-shadow:0 2px 10px rgba(0,0,0,.18)}
.kat-tab-count{font-size:.62rem;font-weight:700;background:rgba(0,0,0,.12);padding:1px 6px;border-radius:99px;min-width:18px;text-align:center}
.kat-tab.all .kat-tab-count{background:rgba(255,255,255,.25)}

/* ═══════════════════════════════════════════
   RESULT INFO
═══════════════════════════════════════════ */
.result-info{font-family:var(--fs);font-size:.78rem;color:var(--muted);margin-bottom:12px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.result-info strong{color:var(--ink);font-weight:600}

/* ═══════════════════════════════════════════
   BULK BAR
═══════════════════════════════════════════ */
.bulk-bar{display:none;align-items:center;gap:8px;background:var(--ink2);border-bottom:3px solid var(--blue-panel);padding:10px 36px;flex-wrap:wrap;position:fixed;top:56px;left:var(--sidebar-w);right:0;z-index:45;box-shadow:0 4px 20px rgba(8,12,30,.3);transition:opacity .2s,transform .2s;transform:translateY(-8px);opacity:0}
.bulk-bar.show{display:flex;transform:translateY(0);opacity:1}
.bulk-count{font-family:var(--fs);font-size:.82rem;font-weight:700;color:#fff;white-space:nowrap}
.bulk-sep{width:1px;height:20px;background:rgba(255,255,255,.15);flex-shrink:0}
.bulk-spacer{display:none;height:50px;margin-bottom:14px}
.bulk-spacer.show{display:block}
.btn-bulk-del{background:#ef4444;color:#fff;border:none;border-radius:var(--r);padding:6px 14px;font-size:.76rem;font-weight:600;cursor:pointer;font-family:var(--fs);display:inline-flex;align-items:center;gap:5px;;white-space:nowrap}
.btn-bulk-del:hover{background:#dc2626}
.btn-bulk-cancel{background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.18);color:rgba(255,255,255,.8);border-radius:var(--r);padding:6px 14px;font-size:.76rem;font-weight:600;cursor:pointer;font-family:var(--fs);;white-space:nowrap}
.btn-bulk-cancel:hover{background:rgba(255,255,255,.18)}
.kat-dd-wrap{position:relative}
.kat-dd-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border:1.5px solid rgba(255,255,255,.18);border-radius:var(--r);background:rgba(255,255,255,.08);color:rgba(255,255,255,.85);font-size:.78rem;font-weight:600;font-family:var(--fs);cursor:pointer;;white-space:nowrap;min-width:140px}
.kat-dd-btn:hover{background:rgba(255,255,255,.16)}
.kat-dd-btn .dd-arrow{margin-left:auto;font-size:.7rem;transition:transform .2s}
.kat-dd-btn.open .dd-arrow{transform:rotate(180deg)}
.kat-dd-panel{display:none;position:absolute;top:calc(100% + 6px);left:0;background:var(--ink2);border:1px solid rgba(255,255,255,.12);border-radius:var(--rl);padding:6px;z-index:300;min-width:210px;max-height:260px;overflow-y:auto;box-shadow:0 8px 28px rgba(0,0,0,.4)}
.kat-dd-panel.open{display:block}
.kat-dd-item{display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;;user-select:none}
.kat-dd-item:hover{background:rgba(255,255,255,.07)}
.kat-dd-item input[type="checkbox"]{width:14px;height:14px;accent-color:var(--blue-panel);cursor:pointer;flex-shrink:0}
.kat-dd-item-label{font-family:var(--fs);font-size:.78rem;font-weight:500;color:rgba(255,255,255,.65);flex:1}
.kat-dd-item.checked .kat-dd-item-label{color:#fff;font-weight:600}
.kat-dd-badge{font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:99px;flex-shrink:0}
.kat-mode-wrap{display:flex;align-items:center;gap:3px;background:rgba(255,255,255,.07);border:1.5px solid rgba(255,255,255,.14);border-radius:var(--r);padding:3px}
.kat-mode-btn{font-family:var(--fs);font-size:.7rem;font-weight:600;padding:3px 11px;border-radius:5px;border:none;background:none;color:rgba(255,255,255,.45);cursor:pointer;}
.kat-mode-btn.on{background:rgba(255,255,255,.18);color:#fff}
.btn-kat-apply{background:rgba(91,155,248,.2);color:var(--blue-panel);border:1.5px solid rgba(91,155,248,.3);border-radius:var(--r);padding:6px 14px;font-size:.76rem;font-weight:600;cursor:pointer;font-family:var(--fs);display:inline-flex;align-items:center;gap:5px;;white-space:nowrap}
.btn-kat-apply:hover{background:rgba(91,155,248,.35)}

/* ═══════════════════════════════════════════
   TABLE CARD
═══════════════════════════════════════════ */
.tcard{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;box-shadow:0 1px 4px rgba(10,15,40,.04)}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;min-width:860px;border-collapse:collapse}
thead{background:var(--bg2);border-bottom:1.5px solid var(--border)}
th{font-family:var(--fs);font-size:.6rem;font-weight:600;text-transform:uppercase;letter-spacing:.16em;color:var(--muted);padding:12px 16px;text-align:left;white-space:nowrap}
td{font-family:var(--fs);font-size:.84rem;color:var(--ink);padding:12px 16px;border-bottom:1px solid var(--border-lt);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr{}
tbody tr:nth-child(even) td{background:rgba(0,0,0,.013)}
[data-theme="dark"] tbody tr:nth-child(even) td{background:rgba(255,255,255,.017)}
tbody tr:hover td{background:var(--blue-soft)!important;cursor:pointer}
tbody tr.selected td{background:var(--blue-soft)}
.cb{width:15px;height:15px;accent-color:var(--blue);cursor:pointer}

.badge-pub{display:inline-flex;align-items:center;gap:4px;background:rgba(5,150,105,.1);color:#059669;font-family:var(--fs);font-size:.64rem;font-weight:600;padding:3px 9px;border-radius:5px;border:1px solid rgba(5,150,105,.22);letter-spacing:.05em;text-transform:uppercase}
.badge-draft{display:inline-flex;align-items:center;gap:4px;background:rgba(217,119,6,.1);color:#d97706;font-family:var(--fs);font-size:.64rem;font-weight:600;padding:3px 9px;border-radius:5px;border:1px solid rgba(217,119,6,.22);letter-spacing:.05em;text-transform:uppercase}
[data-theme="dark"] .badge-pub{background:rgba(5,150,105,.16);color:#34d399;border-color:rgba(52,211,153,.22)}
[data-theme="dark"] .badge-draft{background:rgba(217,119,6,.16);color:#fbbf24;border-color:rgba(251,191,36,.22)}
.badge-dot{width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}

.kat-badge{display:inline-flex;align-items:center;gap:4px;font-family:var(--fs);font-size:.66rem;font-weight:700;padding:3px 10px;border-radius:99px;white-space:nowrap}
.kat-badges{display:flex;flex-wrap:wrap;gap:4px;max-width:210px}
.td-title{font-weight:500;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dup-badge{display:inline-flex;align-items:center;gap:3px;background:var(--blue-soft);color:var(--blue);font-family:var(--fs);font-size:.6rem;font-weight:600;padding:2px 7px;border-radius:99px;margin-left:5px}
.lock-badge{display:inline-flex;align-items:center;gap:3px;background:rgba(217,119,6,.1);color:#d97706;font-family:var(--fs);font-size:.6rem;font-weight:600;padding:2px 7px;border-radius:99px;margin-left:5px;cursor:help}
[data-theme="dark"] .lock-badge{background:rgba(217,119,6,.16);color:#fbbf24}
.kat-more{display:inline-flex;align-items:center;padding:3px 8px;border-radius:99px;font-family:var(--fs);font-size:.63rem;font-weight:600;background:var(--bg2);border:1.5px solid var(--border);color:var(--muted);cursor:default;position:relative}
.kat-more:hover .kat-tooltip{display:block}
.kat-tooltip{display:none;position:absolute;top:calc(100% + 6px);left:0;background:var(--ink2);border:1px solid var(--border);border-radius:var(--r);padding:8px 10px;z-index:200;min-width:150px;box-shadow:0 6px 20px rgba(0,0,0,.2)}

.act-row{display:flex;gap:6px;justify-content:flex-end}
.act-btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;height:28px;border-radius:6px;border:1px solid var(--border);color:var(--muted);background:transparent;;cursor:pointer;text-decoration:none;padding:4px 11px;font-family:var(--fs);font-size:.74rem;font-weight:500;white-space:nowrap}
.act-btn.edit{border-color:rgba(26,86,219,.3);color:var(--blue);background:var(--blue-soft)}
.act-btn.del{border-color:rgba(220,38,38,.3);color:#dc2626;background:rgba(220,38,38,.07)}
.act-btn.edit:hover{background:rgba(26,86,219,.16);border-color:var(--blue)}
.act-btn.del:hover{background:rgba(220,38,38,.15);border-color:#dc2626}

.empty-row td{text-align:center;padding:52px;color:var(--faint);font-size:.88rem;font-style:italic;font-family:var(--fd)}
.empty-row td i{display:block;font-size:1.6rem;margin-bottom:10px;opacity:.2}

/* ═══════════════════════════════════════════
   MOBILE CARD LIST
═══════════════════════════════════════════ */
.mobile-list{display:none;flex-direction:column}
.mob-item{padding:14px 16px;border-bottom:1px solid var(--border-lt);background:var(--card);;display:grid;grid-template-columns:18px 1fr auto;gap:10px;align-items:start}
.mob-item:last-child{border-bottom:none}
.mob-item.selected{background:var(--blue-soft)}
.mob-cb{padding-top:2px;display:flex;align-items:flex-start;justify-content:center}
.mob-body{min-width:0}
.mob-title{font-family:var(--fs);font-size:.88rem;font-weight:500;color:var(--ink);margin-bottom:5px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.mob-meta{display:flex;flex-wrap:wrap;align-items:center;gap:5px;margin-bottom:5px}
.mob-kat-badges{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:5px}
.mob-info{font-family:var(--fs);font-size:.72rem;color:var(--muted)}
.mob-actions{display:flex;flex-direction:column;gap:6px;flex-shrink:0}
.mob-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:7px;border:1.5px solid;font-size:.85rem;text-decoration:none;}
.mob-btn.edit{border-color:rgba(26,86,219,.3);color:var(--blue);background:var(--blue-soft)}
.mob-btn.del{border-color:rgba(220,38,38,.3);color:#dc2626;background:rgba(220,38,38,.07)}
.mob-empty{text-align:center;padding:44px 20px;color:var(--faint);font-size:.88rem;font-family:var(--fd);font-style:italic}

/* ═══════════════════════════════════════════
   MODAL LOGOUT
═══════════════════════════════════════════ */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,8,20,.65);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;padding:20px}
.modal-overlay.show{display:flex}
.modal-box{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);padding:0;width:100%;max-width:360px;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;animation:popIn .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-top{padding:36px 28px 22px;text-align:center;border-bottom:1px solid var(--border)}
.modal-ico{width:50px;height:50px;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--blue);margin:0 auto 14px}
.modal-box h3{font-family:var(--fd);font-size:1.25rem;font-weight:600;color:var(--ink);margin-bottom:6px;letter-spacing:-.01em}
.modal-box p{font-family:var(--fs);font-size:.82rem;color:var(--muted);line-height:1.65}
.modal-acts{display:flex}
.btn-cancel{flex:1;background:none;border:none;color:var(--muted);padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:500;cursor:pointer;;border-right:1px solid var(--border)}
.btn-cancel:hover{background:var(--bg);color:var(--ink)}
.btn-confirm{flex:1;background:var(--ink2);color:#fff;border:none;padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:600;cursor:pointer;;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none}
.btn-confirm:hover{background:var(--blue)}

/* ═══════════════════════════════════════════
   RESPONSIVE  —  breakpoint identik dengan dashboard
═══════════════════════════════════════════ */
@media(max-width:1400px){
  /* slot kosong — dipertahankan agar urutan breakpoint sama */
}
@media(max-width:1280px){
  /* slot kosong */
}
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  .content{padding:26px 20px 56px}
  .bulk-bar{left:0;padding:10px 20px}
  #sidebarToggle{display:flex!important}
}
@media(max-width:768px){
  .table-wrap{display:none}
  .mobile-list{display:flex}
  .search-box input{width:130px}
  .kat-tabs{gap:5px}
  .kat-tab{font-size:.7rem;padding:4px 10px}
}
@media(max-width:640px){
  .topbar{padding:0 18px;height:50px}
  .top-date{display:none}
  .page-hd{flex-direction:column;align-items:flex-start;margin-bottom:22px}
  .page-title{font-size:1.6rem}
  .content{padding:20px 14px 48px}
  .dup-banner{padding:10px 12px}
  .bulk-bar{padding:8px 14px;gap:6px}
}
@media(max-width:420px){
  .search-box input{width:110px}
  .td-title{max-width:130px}
}
</style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- ═══ SIDEBAR ═══ -->
<nav class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="sb-wordmark">Liy<em>News</em></div>
    <div class="sb-tagline">Panel Administrasi</div>
  </div>

  <div class="sb-nav">
    <div class="sb-section">Utama</div>

    <a href="dashboardadmin.php" class="sb-link <?= $current==='dashboardadmin.php'?'active':'' ?>">
      <span class="sb-ico"><i class="bi bi-grid-1x2"></i></span>
      <span class="sb-link-lbl">Dashboard</span>
    </a>
    <a href="kelola_berita.php" class="sb-link <?= $current==='kelola_berita.php'?'active':'' ?>">
      <span class="sb-ico"><i class="bi bi-newspaper"></i></span>
      <span class="sb-link-lbl">Berita</span>
      <span class="sb-pill"><?= $jumlahBerita ?></span>
    </a>
    <a href="kategori.php" class="sb-link <?= $current==='kategori.php'?'active':'' ?>">
      <span class="sb-ico"><i class="bi bi-bookmark"></i></span>
      <span class="sb-link-lbl">Kategori</span>
    </a>
    <a href="kelola_user.php" class="sb-link <?= $current==='kelola_user.php'?'active':'' ?>">
      <span class="sb-ico"><i class="bi bi-people"></i></span>
      <span class="sb-link-lbl">Pengguna</span>
    </a>

    <div class="sb-section">Lainnya</div>
    <a href="profileadmin.php" class="sb-link <?= $current==='profileadmin.php'?'active':'' ?>">
      <span class="sb-ico"><i class="bi bi-person-circle"></i></span>
      <span class="sb-link-lbl">Profil Saya</span>
    </a>
    <a href="../public/index.php" class="sb-link">
      <span class="sb-ico"><i class="bi bi-box-arrow-up-right"></i></span>
      <span class="sb-link-lbl">Lihat Website</span>
    </a>
  </div>

  <div class="sb-bottom">
    <div class="sb-user" onclick="location.href='profileadmin.php'">
      <div class="sb-av"><?= strtoupper(substr($_SESSION['user_username']??'A',0,1)) ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars($_SESSION['user_username']??'Admin') ?></div>
        <div class="sb-urole">Administrator</div>
      </div>
    </div>
    <button class="sb-out" onclick="showLogoutModal()">
      <i class="bi bi-box-arrow-right"></i> Keluar dari Panel
    </button>
  </div>
</nav>

<!-- ═══ TOPBAR ═══ -->
<div class="topbar">
  <div class="top-left">
    <div class="top-crumb">
      <span>LiyNews</span>
      <i class="bi bi-chevron-right"></i>
      <b>Kelola Berita</b>
    </div>
    <div class="top-date"><?= date('d F Y') ?></div>
  </div>
  <div class="top-right">
    <button class="top-btn" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
    <button class="top-btn" id="themeBtn" aria-label="Tema"><i class="bi bi-moon-fill"></i></button>
  </div>
</div>

<!-- ═══ CONTENT ═══ -->
<div class="content">

  <!-- Page Header -->
  <div class="page-hd">
    <div>
      <div class="page-eyebrow">Manajemen Konten</div>
      <h1 class="page-title">Kelola <em>Berita</em></h1>
      <p class="page-sub">Tambah, edit, atau hapus artikel berita.</p>
    </div>
    <a href="berita_tambah.php" class="btn-new">
      <i class="bi bi-plus-lg"></i> Tambah Berita
    </a>
  </div>

  <!-- Alerts -->
  <?php if ($msg==='hapus'): ?><div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Berita berhasil dihapus.</div>
  <?php elseif ($msg==='massal'): ?><div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> <?= (int)($_GET['jml']??0) ?> berita berhasil dihapus.</div>
  <?php elseif ($msg==='kategori'): ?><div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Kategori <?= (int)($_GET['jml']??0) ?> berita berhasil diubah.</div>
  <?php elseif ($msg==='duplikat'): ?><div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> <?= (int)($_GET['jml']??0) ?> berita duplikat berhasil dihapus.</div>
  <?php elseif ($msg==='edit'): ?><div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Berita berhasil diperbarui.</div>
  <?php endif; ?>

  <!-- Dup Banner -->
  <?php if ($dupCount > 0): ?>
  <div class="dup-banner">
    <div class="dup-banner-txt"><i class="bi bi-copy"></i> Ditemukan <strong><?= $dupCount ?> judul duplikat</strong>. Hapus otomatis?</div>
    <a href="kelola_berita.php?aksi=hapus_duplikat" class="btn-dup" onclick="return confirm('Hapus semua duplikat? Artikel pertama (ID terkecil) akan dipertahankan.')">
      <i class="bi bi-trash3"></i> Hapus Duplikat
    </a>
  </div>
  <?php endif; ?>

  <?php
    $baseUrl      = 'kelola_berita.php';
    $qStr         = $search      ? '&q='.urlencode($search) : '';
    $katStr       = $filterKatId ? '&kat='.$filterKatId     : '';
    $statStr      = $filterStatus ? 'status='.$filterStatus.'&' : '';
    $qStrClean    = $search      ? '&q='.urlencode($search) : '';
  ?>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar-left">
      <a href="<?= $baseUrl . '?' . ltrim($katStr.$qStr,'&') ?>" class="ftab <?= $filterStatus===''?'on':'' ?>">Semua</a>
      <a href="<?= $baseUrl . '?status=publish' . $katStr . $qStr ?>" class="ftab <?= $filterStatus==='publish'?'on':'' ?>">Publish</a>
      <a href="<?= $baseUrl . '?status=draft' . $katStr . $qStr ?>"  class="ftab <?= $filterStatus==='draft'?'on':'' ?>">Draft</a>
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Cari judul…" value="<?= htmlspecialchars($_GET['q']??'') ?>">
        <button type="button" onclick="doSearch()"><i class="bi bi-search"></i></button>
      </div>
    </div>
  </div>

  <!-- Kategori Filter -->
  <div class="kat-filter-wrap">
    <div class="kat-filter-label"><i class="bi bi-funnel"></i> Filter Kategori</div>
    <div class="kat-tabs">
      <a href="<?= $baseUrl . '?' . $statStr . ltrim($qStrClean,'&') ?>" class="kat-tab all <?= $filterKatId===0?'active':'' ?>">
        <i class="bi bi-grid-3x3-gap-fill"></i> Semua
        <span class="kat-tab-count"><?= $totalArtikel ?></span>
      </a>
      <?php foreach ($katCounts as $kc):
        $col = kat_color($kc['nama_kategori']);
        $isActive = $filterKatId === (int)$kc['id_kategori'];
        $href = $baseUrl . '?' . $statStr . 'kat=' . $kc['id_kategori'] . $qStrClean;
        if ((int)$kc['jumlah'] === 0 && !$isActive) continue;
      ?>
      <a href="<?= $href ?>" class="kat-tab <?= $isActive?'active':'' ?>"
         style="<?= $isActive ? "background:{$col[1]};border-color:{$col[1]};color:#fff" : "background:{$col[0]};border-color:{$col[0]};color:{$col[1]}" ?>">
        <?= $col[2] ?> <?= htmlspecialchars($kc['nama_kategori']) ?>
        <span class="kat-tab-count" style="<?= $isActive ? 'background:rgba(255,255,255,.25)' : "background:{$col[1]}22;color:{$col[1]}" ?>"><?= $kc['jumlah'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Result Info -->
  <div class="result-info">
    <i class="bi bi-list-ul"></i>
    Menampilkan <strong><?= count($allRows) ?></strong> berita
    <?php if ($filterKatId && isset($katCounts[$filterKatId])): ?> dalam <strong><?= htmlspecialchars($katCounts[$filterKatId]['nama_kategori']??'-') ?></strong><?php endif; ?>
    <?php if ($filterStatus): ?> · <strong><?= htmlspecialchars($filterStatus) ?></strong><?php endif; ?>
    <?php if ($search): ?> · "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
  </div>

  <form method="POST" id="bulkForm">
    <input type="hidden" name="scroll_y" id="scrollY" value="0">

    <!-- BULK BAR -->
    <div class="bulk-bar" id="bulkBar">
      <span class="bulk-count" id="bulkCount">0 dipilih</span>
      <div class="bulk-sep"></div>

      <div class="kat-dd-wrap" id="katDdWrap">
        <button type="button" class="kat-dd-btn" id="katDdBtn" onclick="toggleKatDd()">
          <i class="bi bi-check2-square"></i>
          <span id="katDdLabel">Kategori</span>
          <i class="bi bi-chevron-down dd-arrow"></i>
        </button>
        <div class="kat-dd-panel" id="katDdPanel">
          <?php foreach($kats as $k): $col = kat_color($k['nama_kategori']); ?>
          <label class="kat-dd-item" id="dditem_<?= $k['id_kategori'] ?>">
            <input type="checkbox" class="kat-dd-cb" value="<?= $k['id_kategori'] ?>"
                   data-nama="<?= htmlspecialchars($k['nama_kategori']) ?>"
                   data-bg="<?= $col[0] ?>" data-fg="<?= $col[1] ?>" data-ico="<?= $col[2] ?>"
                   onchange="updateKatDdLabel()">
            <span class="kat-dd-badge" style="background:<?= $col[0] ?>;color:<?= $col[1] ?>"><?= $col[2] ?></span>
            <span class="kat-dd-item-label"><?= htmlspecialchars($k['nama_kategori']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="kat-mode-wrap">
        <button type="button" class="kat-mode-btn on" id="modeReplace" onclick="setKatMode('replace')">Ganti</button>
        <button type="button" class="kat-mode-btn" id="modeAppend" onclick="setKatMode('append')">Tambah</button>
      </div>
      <button type="button" class="btn-kat-apply" onclick="submitKategori()"><i class="bi bi-check2-circle"></i> Terapkan</button>
      <div class="bulk-sep"></div>
      <button type="submit" name="hapus_massal" class="btn-bulk-del" onclick="return confirm('Hapus semua berita yang dipilih?')"><i class="bi bi-trash3"></i> Hapus</button>
      <button type="button" class="btn-bulk-cancel" onclick="clearAll()"><i class="bi bi-x"></i> Batal</button>
    </div>
    <div class="bulk-spacer" id="bulkSpacer"></div>

    <?php $rows = $allRows; ?>

    <div class="tcard">
      <!-- DESKTOP TABLE -->
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:36px"><input type="checkbox" class="cb" id="cbAll"></th>
              <th>#</th><th>Judul</th><th>Kategori</th><th>Penulis</th><th>Status</th><th>Tanggal</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <?php if (empty($rows)): ?>
            <tr class="empty-row">
              <td colspan="8">
                <i class="bi bi-inbox"></i>
                Belum ada berita<?= $filterKatId?' dalam kategori ini':'' ?>.
                <?php if (!$filterKatId): ?><br><a href="berita_tambah.php" style="color:var(--blue);font-family:var(--fs);font-size:.82rem">Tambah sekarang</a><?php endif; ?>
              </td>
            </tr>
            <?php else: $no=1; foreach ($rows as $row):
              $artKatIds = parseKatIds($row);
              $artKats   = [];
              foreach ($artKatIds as $kid) { if (isset($katsById[$kid])) $artKats[] = $katsById[$kid]; }
            ?>
            <tr data-id="<?= $row['id_artikel'] ?>" data-edit-url="berita_edit.php?id=<?= $row['id_artikel'] ?>">
              <td><input type="checkbox" class="cb cb-row" name="ids[]" value="<?= $row['id_artikel'] ?>"></td>
              <td style="color:var(--faint);font-family:var(--fd);font-style:italic;font-size:.76rem"><?= str_pad($no++,2,'0',STR_PAD_LEFT) ?></td>
              <td class="td-title">
                <?= htmlspecialchars($row['judul']??'-') ?>
                <?php if (($judulCount[$row['judul']]??1)>1): ?><span class="dup-badge"><i class="bi bi-copy"></i> duplikat</span><?php endif; ?>
                <?php if (!empty($row['kategori_manual'])): ?><span class="lock-badge" title="Kategori dikunci manual"><i class="bi bi-lock-fill"></i></span><?php endif; ?>
              </td>
              <td>
                <?php if (empty($artKats)): ?>
                  <span style="color:var(--faint);font-size:.78rem">—</span>
                <?php elseif (count($artKats)===1): $col=kat_color($artKats[0]['nama_kategori']); ?>
                  <span class="kat-badge" style="background:<?=$col[0]?>;color:<?=$col[1]?>"><?=$col[2]?> <?=htmlspecialchars($artKats[0]['nama_kategori'])?></span>
                <?php else: ?>
                  <div class="kat-badges">
                    <?php foreach(array_slice($artKats,0,2) as $ak): $col=kat_color($ak['nama_kategori']); ?>
                    <span class="kat-badge" style="background:<?=$col[0]?>;color:<?=$col[1]?>"><?=$col[2]?> <?=htmlspecialchars($ak['nama_kategori'])?></span>
                    <?php endforeach; ?>
                    <?php if(count($artKats)>2): ?>
                    <span class="kat-more">+<?=count($artKats)-2?><span class="kat-tooltip"><?php foreach(array_slice($artKats,2) as $ak): $col=kat_color($ak['nama_kategori']); ?><span class="kat-badge" style="background:<?=$col[0]?>;color:<?=$col[1]?>;margin-bottom:4px;display:inline-flex"><?=$col[2]?> <?=htmlspecialchars($ak['nama_kategori'])?></span><?php endforeach; ?></span></span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($row['nama_penulis']??'-') ?></td>
              <td>
                <?php if($row['status']==='publish'): ?>
                <span class="badge-pub"><span class="badge-dot"></span> Publish</span>
                <?php else: ?>
                <span class="badge-draft"><span class="badge-dot"></span> Draft</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--muted);font-size:.78rem;white-space:nowrap"><?= date('d M Y', strtotime($row['tgl_posting'])) ?></td>
              <td>
                <div class="act-row">
                  <a href="berita_edit.php?id=<?= $row['id_artikel'] ?>" class="act-btn edit"><i class="bi bi-pencil-fill"></i> Edit</a>
                  <a href="berita_hapus.php?id=<?= $row['id_artikel'] ?>" class="act-btn del" onclick="return confirm('Hapus berita ini?')"><i class="bi bi-trash-fill"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- MOBILE LIST -->
      <div class="mobile-list" id="mobileList">
        <?php if (empty($rows)): ?>
        <div class="mob-empty">
          <i class="bi bi-newspaper" style="font-size:1.5rem;display:block;margin-bottom:10px;opacity:.2"></i>
          Belum ada berita<?= $filterKatId?' dalam kategori ini':'' ?>.
        </div>
        <?php else: foreach ($rows as $row):
          $artKatIds = parseKatIds($row);
          $artKats   = [];
          foreach ($artKatIds as $kid) { if (isset($katsById[$kid])) $artKats[] = $katsById[$kid]; }
        ?>
        <div class="mob-item" data-id="<?= $row['id_artikel'] ?>">
          <div class="mob-cb">
            <input type="checkbox" class="cb cb-row-mob" value="<?= $row['id_artikel'] ?>" style="width:15px;height:15px;accent-color:var(--blue);cursor:pointer;margin-top:2px">
          </div>
          <div class="mob-body">
            <div class="mob-title"><?= htmlspecialchars($row['judul']??'-') ?></div>
            <?php if (!empty($artKats)): ?>
            <div class="mob-kat-badges">
              <?php foreach (array_slice($artKats,0,2) as $ak): $col=kat_color($ak['nama_kategori']); ?>
              <span class="kat-badge" style="background:<?=$col[0]?>;color:<?=$col[1]?>;font-size:.63rem;padding:2px 8px"><?=$col[2]?> <?=htmlspecialchars($ak['nama_kategori'])?></span>
              <?php endforeach; ?>
              <?php if (count($artKats)>2): ?><span style="font-size:.65rem;color:var(--muted)">+<?=count($artKats)-2?> lagi</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="mob-meta">
              <?php if($row['status']==='publish'): ?><span class="badge-pub" style="font-size:.62rem;padding:2px 8px"><span class="badge-dot"></span> Publish</span><?php else: ?><span class="badge-draft" style="font-size:.62rem;padding:2px 8px"><span class="badge-dot"></span> Draft</span><?php endif; ?>
              <?php if(($judulCount[$row['judul']]??1)>1): ?><span class="dup-badge"><i class="bi bi-copy"></i> duplikat</span><?php endif; ?>
            </div>
            <div class="mob-info"><?= htmlspecialchars($row['nama_penulis']??'-') ?> · <?= date('d M Y', strtotime($row['tgl_posting'])) ?></div>
          </div>
          <div class="mob-actions">
            <a href="berita_edit.php?id=<?= $row['id_artikel'] ?>" class="mob-btn edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>
            <a href="berita_hapus.php?id=<?= $row['id_artikel'] ?>" class="mob-btn del" title="Hapus" onclick="return confirm('Hapus berita ini?')"><i class="bi bi-trash-fill"></i></a>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div><!-- .tcard -->
  </form>

</div><!-- .content -->

<!-- MODAL LOGOUT -->
<div class="modal-overlay" id="logoutModal" onclick="handleOverlayClick(event)">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-ico"><i class="bi bi-power"></i></div>
      <h3>Keluar dari Panel?</h3>
      <p>Sesi aktif kamu akan diakhiri dan kamu akan diarahkan ke halaman login.</p>
    </div>
    <div class="modal-acts">
      <button class="btn-cancel" onclick="hideLogoutModal()">Batal</button>
      <a href="../public/logout.php" class="btn-confirm"><i class="bi bi-box-arrow-right"></i> Ya, Keluar</a>
    </div>
  </div>
</div>

<script>
const html=document.documentElement,thBtn=document.getElementById('themeBtn');
function applyTheme(t){
  html.setAttribute('data-theme',t);
  thBtn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';
}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click',()=>{
  const n=html.getAttribute('data-theme')==='dark'?'light':'dark';
  localStorage.setItem('pb_theme',n);applyTheme(n);
});

const sidebar=document.getElementById('sidebar'),backdrop=document.getElementById('sidebarBackdrop'),toggle=document.getElementById('sidebarToggle');
const open=()=>{sidebar.classList.add('open');backdrop.classList.add('show');document.body.style.overflow='hidden'};
const close=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');document.body.style.overflow=''};
function chk(){toggle.style.display=window.innerWidth<=1024?'flex':'none';}
chk();window.addEventListener('resize',chk);
toggle.addEventListener('click',e=>{e.stopPropagation();sidebar.classList.contains('open')?close():open();});
backdrop.addEventListener('click',close);
document.addEventListener('keydown',e=>{if(e.key==='Escape'){close();hideLogoutModal();}});

function showLogoutModal(){document.getElementById('logoutModal').classList.add('show')}
function hideLogoutModal(){document.getElementById('logoutModal').classList.remove('show')}
function handleOverlayClick(e){if(e.target===document.getElementById('logoutModal'))hideLogoutModal();}

// ── Bulk select ──
const cbAll=document.getElementById('cbAll'),bulkBar=document.getElementById('bulkBar'),bulkSpacer=document.getElementById('bulkSpacer'),bulkCount=document.getElementById('bulkCount');
function getCheckedVals(){const vals=new Set();document.querySelectorAll('.cb-row:checked,.cb-row-mob:checked').forEach(c=>vals.add(c.value));return vals;}
function syncPairCheckboxes(val,state){document.querySelectorAll('.cb-row,.cb-row-mob').forEach(cb=>{if(cb.value===val)cb.checked=state;});}
function updateBulk(){
  const vals=getCheckedVals(),n=vals.size;
  bulkCount.textContent=n+' dipilih';
  bulkBar.classList.toggle('show',n>0);
  bulkSpacer.classList.toggle('show',n>0);
  const total=document.querySelectorAll('.cb-row').length;
  cbAll.indeterminate=n>0&&n<total;cbAll.checked=n>0&&n===total;
  document.querySelectorAll('tr[data-id]').forEach(tr=>{const cb=tr.querySelector('.cb-row');if(cb)tr.classList.toggle('selected',cb.checked);});
  document.querySelectorAll('.mob-item[data-id]').forEach(item=>{const cb=item.querySelector('.cb-row-mob');if(cb)item.classList.toggle('selected',cb.checked);});
}
cbAll.addEventListener('change',function(){document.querySelectorAll('.cb-row,.cb-row-mob').forEach(cb=>cb.checked=this.checked);updateBulk();});
document.addEventListener('change',e=>{if(e.target.classList.contains('cb-row')||e.target.classList.contains('cb-row-mob')){syncPairCheckboxes(e.target.value,e.target.checked);updateBulk();}});
document.getElementById('bulkForm').addEventListener('submit',function(){
  this.querySelectorAll('.inject-id').forEach(el=>el.remove());
  getCheckedVals().forEach(v=>{const inp=document.createElement('input');inp.type='hidden';inp.name='ids[]';inp.value=v;inp.className='inject-id';this.appendChild(inp);});
  document.querySelectorAll('.cb-row').forEach(cb=>cb.removeAttribute('name'));
});
document.getElementById('tableBody')?.addEventListener('click',function(e){
  const tr=e.target.closest('tr[data-id]');if(!tr)return;if(e.target.closest('a,button,input'))return;window.location.href=tr.dataset.editUrl;
});

// ── Kat dropdown ──
let katMode='replace';
function setKatMode(mode){katMode=mode;document.getElementById('modeReplace').classList.toggle('on',mode==='replace');document.getElementById('modeAppend').classList.toggle('on',mode==='append');}
function toggleKatDd(){const btn=document.getElementById('katDdBtn'),panel=document.getElementById('katDdPanel'),o=panel.classList.toggle('open');btn.classList.toggle('open',o);}
document.addEventListener('click',function(e){const w=document.getElementById('katDdWrap');if(w&&!w.contains(e.target)){document.getElementById('katDdPanel').classList.remove('open');document.getElementById('katDdBtn').classList.remove('open');}});
function updateKatDdLabel(){
  const checked=document.querySelectorAll('.kat-dd-cb:checked'),label=document.getElementById('katDdLabel');
  document.querySelectorAll('.kat-dd-item').forEach(item=>item.classList.toggle('checked',item.querySelector('.kat-dd-cb').checked));
  if(!checked.length)label.textContent='Kategori';
  else if(checked.length===1)label.textContent=checked[0].dataset.nama;
  else label.textContent=checked.length+' kategori';
}
function submitKategori(){
  const checked=document.querySelectorAll('.kat-dd-cb:checked');
  if(!checked.length){alert('Pilih minimal satu kategori!');return;}
  const artVals=getCheckedVals();
  if(!artVals.size){alert('Pilih berita yang ingin diubah!');return;}
  const names=Array.from(checked).map(c=>c.dataset.nama).join(', ');
  const modeLabel=katMode==='append'?'Tambahkan':'Ganti ke';
  if(!confirm(modeLabel+' kategori: '+names+'\npada '+artVals.size+' berita?'))return;
  const form=document.getElementById('bulkForm');
  form.querySelectorAll('.inject-kat,.inject-id').forEach(el=>el.remove());
  artVals.forEach(v=>{const inp=document.createElement('input');inp.type='hidden';inp.name='ids[]';inp.value=v;inp.className='inject-kat';form.appendChild(inp);});
  checked.forEach(cb=>{const inp=document.createElement('input');inp.type='hidden';inp.name='kategori_baru[]';inp.value=cb.value;inp.className='inject-kat';form.appendChild(inp);});
  ['kat_mode','ubah_kategori'].forEach((n,i)=>{const inp=document.createElement('input');inp.type='hidden';inp.name=n;inp.value=i===0?katMode:'1';inp.className='inject-kat';form.appendChild(inp);});
  document.getElementById('scrollY').value=window.scrollY;
  form.submit();
}

// ── Search ──
const searchInput=document.getElementById('searchInput');
searchInput.addEventListener('input',function(){
  const q=this.value.toLowerCase().trim();
  document.querySelectorAll('#tableBody tr').forEach(tr=>{if(tr.classList.contains('empty-row'))return;const td=tr.querySelector('.td-title');let txt='';if(td)td.childNodes.forEach(n=>{if(n.nodeType===3)txt+=n.textContent;});tr.style.display=(!q||txt.toLowerCase().includes(q))?'':'none';});
  document.querySelectorAll('.mob-item').forEach(item=>{const t=item.querySelector('.mob-title')?.textContent?.toLowerCase()||'';item.style.display=(!q||t.includes(q))?'':'none';});
});
function doSearch(){
  const q=searchInput.value.trim(),status='<?= htmlspecialchars($filterStatus) ?>',kat='<?= $filterKatId ?>';
  let url='kelola_berita.php',p=[];
  if(status)p.push('status='+encodeURIComponent(status));
  if(kat)p.push('kat='+kat);
  if(q)p.push('q='+encodeURIComponent(q));
  if(p.length)url+='?'+p.join('&');
  window.location.href=url;
}
searchInput.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();doSearch();}});
function clearAll(){document.querySelectorAll('.cb-row,.cb-row-mob').forEach(cb=>cb.checked=false);cbAll.checked=false;updateBulk();}
(function(){const p=new URLSearchParams(window.location.search),sc=parseInt(p.get('scroll')||'0');if(sc>0)window.scrollTo({top:sc,behavior:'instant'});})();
</script>
</body>
</html>