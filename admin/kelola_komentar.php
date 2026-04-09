<?php
session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$current = basename($_SERVER['PHP_SELF']);

// ── Stats ──────────────────────────────────────────────────────────────────
$jumlahBerita   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel"))['t'] ?? 0;
$jumlahPublish  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel WHERE status='publish'"))['t'] ?? 0;
$jumlahDraft    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel WHERE status='draft'"))['t'] ?? 0;
$jumlahKategori = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM kategori"))['t'] ?? 0;
$jumlahUser     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM user"))['t'] ?? 0;

// ── Komentar stats ─────────────────────────────────────────────────────────
$jumlahKomentar  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM komentar"))['t'] ?? 0;
$jumlahApproved  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM komentar WHERE status='approved'"))['t'] ?? 0;
$jumlahPending   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM komentar WHERE status='pending'"))['t'] ?? 0;
$jumlahSpam      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM komentar WHERE status='spam'"))['t'] ?? 0;

// ── Filter & Pencarian ─────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where = [];
if ($filterStatus !== '') $where[] = "k.status = '" . mysqli_real_escape_string($koneksi, $filterStatus) . "'";
if ($search !== '')        $where[] = "(k.nama_komentator LIKE '%" . mysqli_real_escape_string($koneksi, $search) . "%' OR k.isi_komentar LIKE '%" . mysqli_real_escape_string($koneksi, $search) . "%' OR a.judul LIKE '%" . mysqli_real_escape_string($koneksi, $search) . "%')";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalQ  = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS t FROM komentar k
    LEFT JOIN artikel a ON k.artikel_id = a.id_artikel
    $whereSQL
"))['t'] ?? 0;
$totalPages = max(1, ceil($totalQ / $perPage));

$komentarQ = mysqli_query($koneksi, "
    SELECT k.*, a.judul AS judul_artikel
    FROM komentar k
    LEFT JOIN artikel a ON k.artikel_id = a.id_artikel
    $whereSQL
    ORDER BY k.tgl_komentar DESC
    LIMIT $perPage OFFSET $offset
");

// ── Aksi (approve / spam / hapus) ─────────────────────────────────────────
$aksiMsg = '';
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($koneksi, "UPDATE komentar SET status='approved' WHERE id_komentar=$id");
    header("Location: kelola_komentar.php?status=$filterStatus&q=" . urlencode($search) . "&page=$page&ok=approve"); exit;
}
if (isset($_GET['spam'])) {
    $id = (int)$_GET['spam'];
    mysqli_query($koneksi, "UPDATE komentar SET status='spam' WHERE id_komentar=$id");
    header("Location: kelola_komentar.php?status=$filterStatus&q=" . urlencode($search) . "&page=$page&ok=spam"); exit;
}
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM komentar WHERE id_komentar=$id");
    header("Location: kelola_komentar.php?status=$filterStatus&q=" . urlencode($search) . "&page=$page&ok=hapus"); exit;
}
if (isset($_GET['ok'])) {
    $map = ['approve'=>['Komentar disetujui.','c2'],'spam'=>['Komentar ditandai spam.','c3'],'hapus'=>['Komentar dihapus.','c5']];
    $aksiMsg = $map[$_GET['ok']] ?? null;
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Komentar — LiyNews</title>
  <script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,300;1,9..144,400;1,9..144,600;1,9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
/* ═══════════════════════════════════════════
   TOKENS  (sama persis dengan dashboard)
═══════════════════════════════════════════ */
:root {
  --sb-bg:#1c2540;--sb-border:rgba(255,255,255,.08);--sb-text:rgba(255,255,255,.40);
  --sb-hover:rgba(255,255,255,.055);--sb-active:rgba(91,155,248,.15);--blue-panel:#5b9bf8;
  --bg:#f5f4ef;--bg2:#edecea;--card:#ffffff;--ink:#0d1221;--ink2:#1c2540;
  --muted:#5e6673;--faint:#a8a49e;--border:#dbd8d0;--border-lt:#eeece6;
  --blue:#1a56db;--blue-soft:rgba(26,86,219,.09);
  --c1:#1a56db;--c1s:rgba(26,86,219,.09);--c1t:rgba(26,86,219,.04);
  --c2:#059669;--c2s:rgba(5,150,105,.09);--c2t:rgba(5,150,105,.04);
  --c3:#d97706;--c3s:rgba(217,119,6,.09);--c3t:rgba(217,119,6,.04);
  --c4:#7c3aed;--c4s:rgba(124,58,237,.09);--c4t:rgba(124,58,237,.04);
  --c5:#dc2626;--c5s:rgba(220,38,38,.09);--c5t:rgba(220,38,38,.04);
  --fd:'Fraunces',Georgia,serif;--fs:'Outfit',system-ui,sans-serif;
  --sidebar-w:252px;--r:8px;--rl:14px;
}
[data-theme="dark"]{
  --bg:#090c17;--bg2:#0d1225;--card:#101828;--ink:#e2e8f4;--ink2:#0f1c35;
  --muted:#7888a8;--faint:#364460;--border:#1c2a48;--border-lt:#152035;
  --blue:#5b9bf8;--blue-soft:rgba(91,155,248,.12);
  --c1s:rgba(26,86,219,.16);--c1t:rgba(26,86,219,.08);
  --c2s:rgba(5,150,105,.16);--c2t:rgba(5,150,105,.08);
  --c3s:rgba(217,119,6,.16);--c3t:rgba(217,119,6,.08);
  --c4s:rgba(124,58,237,.16);--c4t:rgba(124,58,237,.08);
  --c5s:rgba(220,38,38,.16);--c5t:rgba(220,38,38,.08);
}

/* ── BASE ───────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--fs);background:var(--bg);color:var(--ink);min-height:100vh;-webkit-font-smoothing:antialiased;transition:background .3s,color .3s}
a{color:inherit;text-decoration:none}

/* ── SIDEBAR ────────────────────────────── */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--sb-bg);display:flex;flex-direction:column;z-index:200;border-right:1px solid var(--sb-border);transition:transform .3s cubic-bezier(.4,0,.2,1);overflow:hidden}
.sidebar::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse 90% 55% at 15% 12%,rgba(26,86,219,.22) 0%,transparent 58%),radial-gradient(ellipse 70% 50% at 85% 90%,rgba(91,155,248,.08) 0%,transparent 52%)}
.sidebar::after{content:'';position:absolute;inset:0;pointer-events:none;opacity:.4;background-image:radial-gradient(rgba(255,255,255,.08) 1px,transparent 1px);background-size:20px 20px;mask-image:linear-gradient(to bottom,transparent 0%,black 30%,black 70%,transparent 100%)}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--sb-border);position:relative;z-index:1}
.sb-wordmark{font-family:var(--fd);font-size:1.75rem;font-weight:700;color:#fff;letter-spacing:-.02em;line-height:1}
.sb-wordmark em{font-style:italic;color:var(--blue-panel)}
.sb-tagline{margin-top:6px;font-size:.6rem;letter-spacing:.28em;text-transform:uppercase;color:rgba(255,255,255,.2);font-family:var(--fs);font-weight:400}
.sb-nav{flex:1;padding:10px 0;overflow-y:auto;scrollbar-width:none;position:relative;z-index:1}
.sb-nav::-webkit-scrollbar{display:none}
.sb-section{font-size:.56rem;font-weight:600;letter-spacing:.25em;text-transform:uppercase;color:rgba(255,255,255,.17);padding:18px 24px 5px;font-family:var(--fs)}
.sb-link{display:flex;align-items:center;gap:11px;padding:9px 24px;font-family:var(--fs);font-size:.84rem;font-weight:400;color:var(--sb-text);transition:.18s;border-left:2px solid transparent}
.sb-ico{width:30px;height:30px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:.82rem;flex-shrink:0;background:rgba(255,255,255,.04);transition:.18s}
.sb-link:hover{color:rgba(255,255,255,.78);background:var(--sb-hover)}
.sb-link:hover .sb-ico{background:rgba(91,155,248,.14);color:var(--blue-panel)}
.sb-link.active{color:#fff;background:var(--sb-active);border-left-color:var(--blue-panel);font-weight:500}
.sb-link.active .sb-ico{background:rgba(91,155,248,.2);color:var(--blue-panel)}
.sb-link-lbl{flex:1}
.sb-pill{font-family:var(--fs);font-size:.6rem;font-weight:600;padding:2px 8px;border-radius:4px;background:rgba(91,155,248,.15);color:var(--blue-panel);border:1px solid rgba(91,155,248,.22)}
.sb-bottom{border-top:1px solid var(--sb-border);padding:8px 0 4px;position:relative;z-index:1}
.sb-user{display:flex;align-items:center;gap:10px;padding:11px 24px;cursor:pointer;transition:.15s}
.sb-user:hover{background:rgba(255,255,255,.04)}
.sb-av{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#5b9bf8,#1a56db);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;font-family:var(--fd);font-style:italic;color:#fff;flex-shrink:0}
.sb-uname{font-size:.82rem;font-weight:500;color:#fff;font-family:var(--fs)}
.sb-urole{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.25);margin-top:1px;font-family:var(--fs)}
.sb-out{display:flex;align-items:center;gap:10px;padding:9px 24px;width:100%;font-family:var(--fs);font-size:.82rem;font-weight:400;color:rgba(248,113,113,.55);background:none;border:none;cursor:pointer;transition:.18s;border-left:2px solid transparent}
.sb-out:hover{color:#fca5a5;background:rgba(248,113,113,.06);border-left-color:rgba(248,113,113,.4)}
.sb-out i{font-size:.8rem}
.sidebar-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:150;opacity:0;pointer-events:none;transition:opacity .25s}
.sidebar-backdrop.show{display:block;opacity:1;pointer-events:auto}

/* ── TOPBAR ─────────────────────────────── */
.topbar{margin-left:var(--sidebar-w);height:56px;background:var(--ink2);border-bottom:3px solid var(--blue-panel);display:flex;align-items:center;justify-content:space-between;padding:0 36px;position:sticky;top:0;z-index:50;box-shadow:0 2px 24px rgba(8,12,30,.22)}
.top-left{display:flex;align-items:center;gap:14px}
.top-crumb{font-family:var(--fs);font-size:.76rem;color:rgba(255,255,255,.3);display:flex;align-items:center;gap:5px}
.top-crumb b{color:rgba(255,255,255,.85);font-weight:500}
.top-crumb i{font-size:.62rem;color:rgba(255,255,255,.18)}
.top-date{font-family:var(--fd);font-style:italic;font-size:.8rem;color:rgba(255,255,255,.35);padding:4px 12px;border:1px solid rgba(255,255,255,.1);border-radius:4px;background:rgba(255,255,255,.04)}
.top-right{display:flex;align-items:center;gap:6px}
.top-btn{width:34px;height:34px;border-radius:7px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:rgba(255,255,255,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:.15s}
.top-btn:hover{border-color:var(--blue-panel);color:var(--blue-panel);background:rgba(91,155,248,.12)}
#sidebarToggle{display:none}

/* ── CONTENT ────────────────────────────── */
.content{margin-left:var(--sidebar-w);padding:36px 36px 72px;animation:pageIn .5s cubic-bezier(.22,1,.36,1) both}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* ── PAGE HEADER ────────────────────────── */
.page-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:20px;padding-bottom:24px;border-bottom:1px solid var(--border)}
.page-eyebrow{font-family:var(--fs);font-size:.62rem;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:var(--blue);margin-bottom:7px;display:flex;align-items:center;gap:8px}
.page-eyebrow::before{content:'';width:20px;height:1.5px;background:var(--blue);border-radius:2px}
.page-title{font-family:var(--fd);font-size:1.9rem;font-weight:600;color:var(--ink);letter-spacing:-.02em;line-height:1.1}
.page-title em{font-style:italic;color:var(--blue)}
.page-sub{font-family:var(--fs);font-size:.84rem;color:var(--muted);margin-top:5px;font-weight:300}

/* ── STAT CARDS ─────────────────────────── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.scard{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px 16px 14px;text-decoration:none;display:block;position:relative;overflow:hidden;transition:.22s cubic-bezier(.4,0,.2,1)}
.scard::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--ca);transform:scaleX(0);transform-origin:left;transition:.25s cubic-bezier(.4,0,.2,1)}
.scard:hover::before{transform:scaleX(1)}
.scard:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(10,15,40,.09);border-color:var(--ca)}
.scard::after{content:'';position:absolute;top:-14px;right:-14px;width:54px;height:54px;border-radius:50%;background:var(--ca-t);transition:.22s}
.scard:hover::after{transform:scale(1.4)}
.sc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;position:relative}
.sc-icon{width:30px;height:30px;border-radius:8px;background:var(--ca-s);display:flex;align-items:center;justify-content:center;font-size:.8rem;color:var(--ca);flex-shrink:0}
.sc-tag{font-family:var(--fs);font-size:.55rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);background:var(--bg2);padding:2px 7px;border-radius:4px}
.sc-num{font-family:var(--fd);font-size:1.7rem;font-weight:700;color:var(--ink);line-height:1;letter-spacing:-.03em;margin-bottom:4px;position:relative}
.sc-lbl{font-family:var(--fs);font-size:.62rem;font-weight:500;letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}
.sc-bar{height:2px;border-radius:2px;background:var(--border);margin-top:12px;overflow:hidden}
.sc-bar-f{height:100%;border-radius:2px;background:var(--ca);transition:.6s ease}
.scard.s1{--ca:var(--c1);--ca-s:var(--c1s);--ca-t:var(--c1t)}
.scard.s2{--ca:var(--c2);--ca-s:var(--c2s);--ca-t:var(--c2t)}
.scard.s3{--ca:var(--c3);--ca-s:var(--c3s);--ca-t:var(--c3t)}
.scard.s5{--ca:var(--c5);--ca-s:var(--c5s);--ca-t:var(--c5t)}

/* ── TOOLBAR (filter + search) ──────────── */
.toolbar{
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;flex-wrap:wrap;margin-bottom:16px;
}
.filter-tabs{display:flex;gap:6px;flex-wrap:wrap}
.ftab{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 16px;border-radius:var(--r);
  border:1.5px solid var(--border);
  background:var(--card);
  font-family:var(--fs);font-size:.76rem;font-weight:500;
  color:var(--muted);cursor:pointer;transition:.15s;text-decoration:none;
}
.ftab:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}
.ftab.active{border-color:var(--blue);color:var(--blue);background:var(--blue-soft);font-weight:600}
.ftab .cnt{
  font-size:.6rem;padding:1px 6px;border-radius:4px;
  background:var(--blue);color:#fff;font-weight:700;
}
.ftab.f-approved.active{border-color:var(--c2);color:var(--c2);background:var(--c2s)}
.ftab.f-approved.active .cnt{background:var(--c2)}
.ftab.f-pending.active{border-color:var(--c3);color:var(--c3);background:var(--c3s)}
.ftab.f-pending.active .cnt{background:var(--c3)}
.ftab.f-spam.active{border-color:var(--c5);color:var(--c5);background:var(--c5s)}
.ftab.f-spam.active .cnt{background:var(--c5)}

.search-wrap{position:relative;flex-shrink:0}
.search-wrap i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--faint);font-size:.82rem;pointer-events:none}
.search-inp{
  font-family:var(--fs);font-size:.82rem;
  padding:7px 14px 7px 34px;
  border:1.5px solid var(--border);border-radius:var(--r);
  background:var(--card);color:var(--ink);
  width:240px;transition:.2s;
}
.search-inp:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft)}
.search-inp::placeholder{color:var(--faint)}

/* ── SECTION HEADER ─────────────────────── */
.sec-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:16px;flex-wrap:wrap}
.sec-title{font-family:var(--fd);font-size:1.2rem;font-weight:600;color:var(--ink);display:flex;align-items:center;gap:10px}
.sec-title::before{content:'';width:4px;height:18px;background:var(--blue);border-radius:2px;display:block}
.sec-caption{font-family:var(--fd);font-style:italic;font-size:.8rem;color:var(--faint);margin-left:2px}

/* ── TABLE ──────────────────────────────── */
.tcard{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;box-shadow:0 1px 4px rgba(10,15,40,.04)}
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:760px}
thead{background:var(--bg2);border-bottom:1.5px solid var(--border)}
th{font-family:var(--fs);font-size:.6rem;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);padding:12px 16px;text-align:left;white-space:nowrap}
td{font-family:var(--fs);font-size:.84rem;font-weight:400;color:var(--ink);padding:12px 16px;border-bottom:1px solid var(--border-lt);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr{transition:.1s}
tbody tr:nth-child(even) td{background:rgba(0,0,0,.013)}
[data-theme="dark"] tbody tr:nth-child(even) td{background:rgba(255,255,255,.017)}
tbody tr:hover td{background:var(--blue-soft)!important}

.no-col{font-family:var(--fd);font-style:italic;font-size:.78rem;color:var(--faint)}
.td-meta{font-size:.78rem;color:var(--muted)}
.td-article{font-size:.76rem;color:var(--blue);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block}
.td-comment{max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:400}
.td-commenter{font-weight:500}

/* Badges */
.badge{display:inline-flex;align-items:center;gap:5px;font-family:var(--fs);font-size:.62rem;font-weight:600;padding:3px 9px;border-radius:5px;letter-spacing:.05em;text-transform:uppercase}
.badge-dot{width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}
.badge-approved{background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.22)}
.badge-pending{background:rgba(217,119,6,.1);color:#d97706;border:1px solid rgba(217,119,6,.22)}
.badge-spam{background:rgba(220,38,38,.1);color:#dc2626;border:1px solid rgba(220,38,38,.22)}
[data-theme="dark"] .badge-approved{background:rgba(5,150,105,.16);color:#34d399;border-color:rgba(52,211,153,.22)}
[data-theme="dark"] .badge-pending{background:rgba(217,119,6,.16);color:#fbbf24;border-color:rgba(251,191,36,.22)}
[data-theme="dark"] .badge-spam{background:rgba(220,38,38,.16);color:#f87171;border-color:rgba(248,113,113,.22)}

/* Action buttons */
.act-row{display:flex;gap:5px}
.act-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);color:var(--muted);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;transition:.14s;text-decoration:none}
.act-btn.approve:hover{border-color:var(--c2);color:var(--c2);background:var(--c2s)}
.act-btn.spam-btn:hover{border-color:var(--c3);color:var(--c3);background:var(--c3s)}
.act-btn.del:hover{border-color:var(--c5);color:var(--c5);background:var(--c5s)}
.act-btn.view:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}

.empty-row td{text-align:center;padding:56px;color:var(--faint);font-size:.9rem;font-style:italic;font-family:var(--fd)}
.empty-row td i{display:block;font-size:2rem;margin-bottom:10px;opacity:.2}

/* ── PAGINATION ─────────────────────────── */
.pagination{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border);flex-wrap:wrap;gap:10px}
.pg-info{font-family:var(--fs);font-size:.78rem;color:var(--muted)}
.pg-info b{color:var(--ink);font-weight:600}
.pg-pages{display:flex;gap:4px}
.pg-btn{
  min-width:32px;height:32px;border-radius:6px;
  border:1.5px solid var(--border);background:var(--card);
  font-family:var(--fs);font-size:.78rem;font-weight:500;color:var(--muted);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:.14s;text-decoration:none;padding:0 8px;
}
.pg-btn:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}
.pg-btn.active{border-color:var(--blue);background:var(--blue);color:#fff;font-weight:600}
.pg-btn.disabled{opacity:.35;pointer-events:none}

/* ── TOAST ──────────────────────────────── */
.toast{
  position:fixed;bottom:28px;right:28px;z-index:9999;
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--r);padding:12px 18px;
  display:flex;align-items:center;gap:10px;
  font-family:var(--fs);font-size:.82rem;color:var(--ink);
  box-shadow:0 8px 24px rgba(0,0,0,.14);
  animation:toastIn .3s cubic-bezier(.22,1,.36,1) both;
}
@keyframes toastIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.toast-ico{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}

/* ── MODAL ──────────────────────────────── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,8,20,.65);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;padding:20px}
.modal-overlay.show{display:flex}
.modal-box{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);padding:0;width:100%;max-width:360px;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;animation:popIn .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-top{padding:36px 28px 22px;text-align:center;border-bottom:1px solid var(--border)}
.modal-ico{width:50px;height:50px;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--blue);margin:0 auto 14px}
.modal-box h3{font-family:var(--fd);font-size:1.25rem;font-weight:600;color:var(--ink);margin-bottom:6px;letter-spacing:-.01em}
.modal-box p{font-family:var(--fs);font-size:.82rem;color:var(--muted);line-height:1.65}
.modal-acts{display:flex}
.btn-cancel{flex:1;background:none;border:none;color:var(--muted);padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:500;cursor:pointer;transition:.15s;border-right:1px solid var(--border)}
.btn-cancel:hover{background:var(--bg);color:var(--ink)}
.btn-confirm{flex:1;background:var(--ink2);color:#fff;border:none;padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:600;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none}
.btn-confirm:hover{background:var(--blue)}
.btn-confirm.danger:hover{background:var(--c5)}

/* Detail modal */
.detail-modal .modal-box{max-width:480px}
.detail-top{padding:24px 28px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.detail-top h3{font-family:var(--fd);font-size:1.1rem;font-weight:600;color:var(--ink);letter-spacing:-.01em}
.detail-body{padding:20px 28px}
.detail-row{margin-bottom:14px}
.detail-lbl{font-family:var(--fs);font-size:.6rem;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--faint);margin-bottom:4px}
.detail-val{font-family:var(--fs);font-size:.84rem;color:var(--ink);line-height:1.6}
.detail-comment{font-family:var(--fd);font-size:.95rem;font-style:italic;color:var(--ink2);line-height:1.7;padding:12px 16px;background:var(--bg);border-left:3px solid var(--blue);border-radius:0 var(--r) var(--r) 0}
[data-theme="dark"] .detail-comment{color:var(--ink)}
.close-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;color:var(--muted);flex-shrink:0;transition:.14s}
.close-btn:hover{border-color:var(--c5);color:var(--c5)}

/* ── RESPONSIVE ─────────────────────────── */
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  .content{padding:26px 20px 56px}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  #sidebarToggle{display:flex!important}
}
@media(max-width:640px){
  .topbar{padding:0 18px;height:50px}
  .top-date{display:none}
  .page-hd{flex-direction:column;align-items:flex-start;margin-bottom:24px}
  .page-title{font-size:1.6rem}
  .stats-grid{grid-template-columns:1fr 1fr}
  .content{padding:20px 14px 48px}
  .search-inp{width:180px}
}
@media(max-width:420px){
  .stats-grid{grid-template-columns:1fr}
  .toolbar{flex-direction:column;align-items:stretch}
  .search-wrap{width:100%}
  .search-inp{width:100%}
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
    <a href="kelola_komentar.php" class="sb-link <?= $current==='kelola_komentar.php'?'active':'' ?>">
      <span class="sb-ico"><i class="bi bi-chat-dots"></i></span>
      <span class="sb-link-lbl">Komentar</span>
      <?php if($jumlahPending > 0): ?>
      <span class="sb-pill"><?= $jumlahPending ?></span>
      <?php endif; ?>
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
      <b>Kelola Komentar</b>
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
      <div class="page-eyebrow">Moderasi Konten</div>
      <h1 class="page-title">Kelola <em>Komentar</em></h1>
      <p class="page-sub">Tinjau, setujui, atau hapus komentar dari pembaca.</p>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="stats-grid">
    <a href="kelola_komentar.php" class="scard s1">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-chat-square-dots"></i></div>
        <span class="sc-tag">Total</span>
      </div>
      <div class="sc-num"><?= $jumlahKomentar ?></div>
      <div class="sc-lbl">Semua Komentar</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:<?= $jumlahKomentar>0?75:0 ?>%"></div></div>
    </a>
    <a href="kelola_komentar.php?status=approved" class="scard s2">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-check-circle"></i></div>
        <span class="sc-tag">Live</span>
      </div>
      <div class="sc-num"><?= $jumlahApproved ?></div>
      <div class="sc-lbl">Disetujui</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:<?= $jumlahKomentar>0?round(($jumlahApproved/$jumlahKomentar)*100):0 ?>%"></div></div>
    </a>
    <a href="kelola_komentar.php?status=pending" class="scard s3">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-hourglass-split"></i></div>
        <span class="sc-tag">Antrian</span>
      </div>
      <div class="sc-num"><?= $jumlahPending ?></div>
      <div class="sc-lbl">Menunggu Review</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:<?= $jumlahKomentar>0?round(($jumlahPending/$jumlahKomentar)*100):0 ?>%"></div></div>
    </a>
    <a href="kelola_komentar.php?status=spam" class="scard s5">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <span class="sc-tag">Filter</span>
      </div>
      <div class="sc-num"><?= $jumlahSpam ?></div>
      <div class="sc-lbl">Ditandai Spam</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:<?= $jumlahKomentar>0?round(($jumlahSpam/$jumlahKomentar)*100):0 ?>%"></div></div>
    </a>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="filter-tabs">
      <a href="kelola_komentar.php" class="ftab <?= $filterStatus===''?'active':'' ?>">
        Semua <span class="cnt"><?= $jumlahKomentar ?></span>
      </a>
      <a href="kelola_komentar.php?status=approved" class="ftab f-approved <?= $filterStatus==='approved'?'active':'' ?>">
        <i class="bi bi-check-circle"></i> Disetujui <span class="cnt"><?= $jumlahApproved ?></span>
      </a>
      <a href="kelola_komentar.php?status=pending" class="ftab f-pending <?= $filterStatus==='pending'?'active':'' ?>">
        <i class="bi bi-hourglass-split"></i> Pending <span class="cnt"><?= $jumlahPending ?></span>
      </a>
      <a href="kelola_komentar.php?status=spam" class="ftab f-spam <?= $filterStatus==='spam'?'active':'' ?>">
        <i class="bi bi-exclamation-triangle"></i> Spam <span class="cnt"><?= $jumlahSpam ?></span>
      </a>
    </div>

    <form method="GET" class="search-wrap" style="display:flex;gap:6px">
      <?php if($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
      <div style="position:relative">
        <i class="bi bi-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--faint);font-size:.82rem;pointer-events:none"></i>
        <input class="search-inp" type="text" name="q" placeholder="Cari komentar atau nama…" value="<?= htmlspecialchars($search) ?>">
      </div>
    </form>
  </div>

  <!-- Section Header -->
  <div class="sec-hd">
    <div class="sec-title">
      Daftar Komentar
      <span class="sec-caption"><?= $totalQ ?> ditemukan</span>
    </div>
  </div>

  <!-- Table -->
  <div class="tcard">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>Nama Komentator</th>
            <th>Komentar</th>
            <th>Artikel</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th style="width:100px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$komentarQ || mysqli_num_rows($komentarQ) === 0): ?>
          <tr class="empty-row">
            <td colspan="7">
              <i class="bi bi-chat-square"></i>
              Tidak ada komentar<?= $filterStatus?' berstatus '.$filterStatus:'' ?><?= $search?' yang cocok dengan pencarian':'' ?>.
            </td>
          </tr>
          <?php else: $no = $offset+1; while($row = mysqli_fetch_assoc($komentarQ)): ?>
          <tr>
            <td class="no-col"><?= str_pad($no++, 2, '0', STR_PAD_LEFT) ?></td>
            <td>
              <span class="td-commenter"><?= htmlspecialchars($row['nama_komentator'] ?? '—') ?></span>
              <?php if(!empty($row['email_komentator'])): ?>
              <div class="td-meta"><?= htmlspecialchars($row['email_komentator']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="td-comment" title="<?= htmlspecialchars($row['isi_komentar'] ?? '') ?>">
                <?= htmlspecialchars($row['isi_komentar'] ?? '—') ?>
              </span>
            </td>
            <td>
              <a href="../public/artikel.php?id=<?= $row['artikel_id'] ?>" target="_blank" class="td-article" title="<?= htmlspecialchars($row['judul_artikel'] ?? '') ?>">
                <?= htmlspecialchars($row['judul_artikel'] ?? '—') ?>
              </a>
            </td>
            <td>
              <?php if($row['status']==='approved'): ?>
              <span class="badge badge-approved"><span class="badge-dot"></span> Approved</span>
              <?php elseif($row['status']==='spam'): ?>
              <span class="badge badge-spam"><span class="badge-dot"></span> Spam</span>
              <?php else: ?>
              <span class="badge badge-pending"><span class="badge-dot"></span> Pending</span>
              <?php endif; ?>
            </td>
            <td><span class="td-meta" style="white-space:nowrap"><?= date('d M Y', strtotime($row['tgl_komentar'])) ?></span></td>
            <td>
              <div class="act-row">
                <!-- Detail -->
                <button class="act-btn view" title="Lihat Detail"
                  onclick="showDetail(
                    '<?= addslashes(htmlspecialchars($row['nama_komentator']??'')) ?>',
                    '<?= addslashes(htmlspecialchars($row['email_komentator']??'')) ?>',
                    '<?= addslashes(htmlspecialchars($row['isi_komentar']??'')) ?>',
                    '<?= addslashes(htmlspecialchars($row['judul_artikel']??'')) ?>',
                    '<?= date('d F Y, H:i', strtotime($row['tgl_komentar'])) ?>',
                    '<?= $row['status'] ?>'
                  )">
                  <i class="bi bi-eye-fill"></i>
                </button>
                <!-- Approve (tampil jika bukan approved) -->
                <?php if($row['status'] !== 'approved'): ?>
                <a href="kelola_komentar.php?approve=<?= $row['id_komentar'] ?>&status=<?= $filterStatus ?>&q=<?= urlencode($search) ?>&page=<?= $page ?>"
                   class="act-btn approve" title="Setujui">
                  <i class="bi bi-check-lg"></i>
                </a>
                <?php endif; ?>
                <!-- Spam (tampil jika bukan spam) -->
                <?php if($row['status'] !== 'spam'): ?>
                <a href="kelola_komentar.php?spam=<?= $row['id_komentar'] ?>&status=<?= $filterStatus ?>&q=<?= urlencode($search) ?>&page=<?= $page ?>"
                   class="act-btn spam-btn" title="Tandai Spam">
                  <i class="bi bi-exclamation-triangle-fill"></i>
                </a>
                <?php endif; ?>
                <!-- Hapus -->
                <button class="act-btn del" title="Hapus"
                  onclick="showDeleteModal(<?= $row['id_komentar'] ?>,'<?= addslashes(htmlspecialchars($row['nama_komentator']??'')) ?>')">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1 || $totalQ > 0): ?>
    <div class="pagination">
      <div class="pg-info">
        Menampilkan <b><?= min($offset+1, $totalQ) ?>–<?= min($offset+$perPage, $totalQ) ?></b>
        dari <b><?= $totalQ ?></b> komentar
      </div>
      <div class="pg-pages">
        <?php
          $baseUrl = 'kelola_komentar.php?status='.urlencode($filterStatus).'&q='.urlencode($search);
          $prev = $page - 1;
          $next = $page + 1;
        ?>
        <a href="<?= $baseUrl ?>&page=<?= $prev ?>" class="pg-btn <?= $page<=1?'disabled':'' ?>">
          <i class="bi bi-chevron-left"></i>
        </a>
        <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);
          if($start > 1){ echo "<a href='{$baseUrl}&page=1' class='pg-btn'>1</a>"; if($start>2) echo "<span class='pg-btn disabled'>…</span>"; }
          for($p=$start;$p<=$end;$p++){
            echo "<a href='{$baseUrl}&page={$p}' class='pg-btn ".($p==$page?'active':'')."'>{$p}</a>";
          }
          if($end < $totalPages){ if($end<$totalPages-1) echo "<span class='pg-btn disabled'>…</span>"; echo "<a href='{$baseUrl}&page={$totalPages}' class='pg-btn'>{$totalPages}</a>"; }
        ?>
        <a href="<?= $baseUrl ?>&page=<?= $next ?>" class="pg-btn <?= $page>=$totalPages?'disabled':'' ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /content -->

<!-- ═══ TOAST ═══ -->
<?php if($aksiMsg): ?>
<div class="toast" id="toastMsg" style="border-color:var(--<?= $aksiMsg[1] ?>)">
  <div class="toast-ico" style="background:var(--<?= $aksiMsg[1] ?>s);color:var(--<?= $aksiMsg[1] ?>)">
    <i class="bi bi-<?= $aksiMsg[1]==='c5'?'trash':'check' ?>-lg"></i>
  </div>
  <?= $aksiMsg[0] ?>
</div>
<script>setTimeout(()=>{const t=document.getElementById('toastMsg');if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400)}},3500)</script>
<?php endif; ?>

<!-- ═══ MODAL HAPUS ═══ -->
<div class="modal-overlay" id="deleteModal" onclick="handleOverlayClick(event,'deleteModal')">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-ico" style="background:var(--c5s);border-color:rgba(220,38,38,.2);color:var(--c5)">
        <i class="bi bi-trash3"></i>
      </div>
      <h3>Hapus Komentar?</h3>
      <p>Komentar dari <b id="delName"></b> akan dihapus permanen dan tidak bisa dipulihkan.</p>
    </div>
    <div class="modal-acts">
      <button class="btn-cancel" onclick="hideModal('deleteModal')">Batal</button>
      <a href="#" id="delLink" class="btn-confirm danger">
        <i class="bi bi-trash-fill"></i> Ya, Hapus
      </a>
    </div>
  </div>
</div>

<!-- ═══ MODAL LOGOUT ═══ -->
<div class="modal-overlay" id="logoutModal" onclick="handleOverlayClick(event,'logoutModal')">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-ico"><i class="bi bi-power"></i></div>
      <h3>Keluar dari Panel?</h3>
      <p>Sesi aktif kamu akan diakhiri dan kamu akan diarahkan ke halaman login.</p>
    </div>
    <div class="modal-acts">
      <button class="btn-cancel" onclick="hideModal('logoutModal')">Batal</button>
      <a href="../public/logout.php" class="btn-confirm"><i class="bi bi-box-arrow-right"></i> Ya, Keluar</a>
    </div>
  </div>
</div>

<!-- ═══ MODAL DETAIL ═══ -->
<div class="modal-overlay detail-modal" id="detailModal" onclick="handleOverlayClick(event,'detailModal')">
  <div class="modal-box">
    <div class="detail-top">
      <h3>Detail Komentar</h3>
      <button class="close-btn" onclick="hideModal('detailModal')"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="detail-body">
      <div class="detail-row">
        <div class="detail-lbl">Komentar</div>
        <div class="detail-comment" id="d-isi"></div>
      </div>
      <div class="detail-row">
        <div class="detail-lbl">Nama Komentator</div>
        <div class="detail-val" id="d-nama"></div>
      </div>
      <div class="detail-row">
        <div class="detail-lbl">Email</div>
        <div class="detail-val" id="d-email"></div>
      </div>
      <div class="detail-row">
        <div class="detail-lbl">Artikel</div>
        <div class="detail-val" id="d-artikel"></div>
      </div>
      <div style="display:flex;gap:20px">
        <div class="detail-row" style="flex:1">
          <div class="detail-lbl">Tanggal</div>
          <div class="detail-val" id="d-tgl"></div>
        </div>
        <div class="detail-row" style="flex:1">
          <div class="detail-lbl">Status</div>
          <div class="detail-val" id="d-status"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
/* ── THEME ──────────────────────────────── */
const html=document.documentElement,thBtn=document.getElementById('themeBtn');
function applyTheme(t){html.setAttribute('data-theme',t);thBtn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>'}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click',()=>{const n=html.getAttribute('data-theme')==='dark'?'light':'dark';localStorage.setItem('pb_theme',n);applyTheme(n)});

/* ── SIDEBAR ────────────────────────────── */
const sidebar=document.getElementById('sidebar'),toggle=document.getElementById('sidebarToggle'),backdrop=document.getElementById('sidebarBackdrop');
const open=()=>{sidebar.classList.add('open');backdrop.classList.add('show');document.body.style.overflow='hidden'};
const close=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');document.body.style.overflow=''};
function chk(){toggle.style.display=window.innerWidth<=1024?'flex':'none'}
chk();window.addEventListener('resize',chk);
toggle.addEventListener('click',e=>{e.stopPropagation();sidebar.classList.contains('open')?close():open()});
backdrop.addEventListener('click',close);

/* ── MODALS ─────────────────────────────── */
function hideModal(id){document.getElementById(id).classList.remove('show')}
function handleOverlayClick(e,id){if(e.target===document.getElementById(id))hideModal(id)}
function showLogoutModal(){document.getElementById('logoutModal').classList.add('show')}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){close();['deleteModal','logoutModal','detailModal'].forEach(id=>{const m=document.getElementById(id);if(m)m.classList.remove('show')})}});

/* ── DELETE MODAL ───────────────────────── */
function showDeleteModal(id, nama){
  document.getElementById('delName').textContent = nama;
  document.getElementById('delLink').href = 'kelola_komentar.php?hapus='+id+'&status=<?= urlencode($filterStatus) ?>&q=<?= urlencode($search) ?>&page=<?= $page ?>';
  document.getElementById('deleteModal').classList.add('show');
}

/* ── DETAIL MODAL ───────────────────────── */
const statusBadge={
  approved:'<span class="badge badge-approved"><span class="badge-dot"></span> Approved</span>',
  pending:'<span class="badge badge-pending"><span class="badge-dot"></span> Pending</span>',
  spam:'<span class="badge badge-spam"><span class="badge-dot"></span> Spam</span>'
};
function showDetail(nama, email, isi, artikel, tgl, status){
  document.getElementById('d-nama').textContent    = nama || '—';
  document.getElementById('d-email').textContent   = email || '—';
  document.getElementById('d-isi').textContent     = isi || '—';
  document.getElementById('d-artikel').textContent = artikel || '—';
  document.getElementById('d-tgl').textContent     = tgl || '—';
  document.getElementById('d-status').innerHTML    = statusBadge[status] || status;
  document.getElementById('detailModal').classList.add('show');
}
</script>
</body>
</html>