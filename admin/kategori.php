<?php
session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$current = basename($_SERVER['PHP_SELF']);
$msg = $_GET['msg'] ?? '';

$katQ = mysqli_query($koneksi, "
    SELECT k.*, COUNT(a.id_artikel) AS jumlah_berita
    FROM kategori k
    LEFT JOIN artikel a ON a.kategori_id = k.id_kategori
    GROUP BY k.id_kategori
    ORDER BY k.nama_kategori ASC
");

$jumlahBerita = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel"))['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Kategori — LiyNews</title>
  <script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,300;1,9..144,400;1,9..144,600;1,9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
/* ═══════════════════════════════════════════
   TOKENS — identik dengan dashboardadmin.php
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
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:var(--fs);background:var(--bg);color:var(--ink);min-height:100vh;-webkit-font-smoothing:antialiased;transition:background .3s,color .3s}
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
.top-btn{width:34px;height:34px;border-radius:7px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:rgba(255,255,255,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:.15s}
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
.btn-new{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:var(--blue);color:#fff;border:none;border-radius:var(--r);font-family:var(--fs);font-size:.82rem;font-weight:600;letter-spacing:.02em;cursor:pointer;transition:.2s;white-space:nowrap;text-decoration:none;box-shadow:0 2px 12px rgba(26,86,219,.3)}
.btn-new:hover{background:var(--ink2);box-shadow:0 4px 20px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-new:active{transform:translateY(0)}

/* ═══════════════════════════════════════════
   ALERTS
═══════════════════════════════════════════ */
.alert{padding:11px 16px;border-radius:var(--r);font-family:var(--fs);font-size:.82rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
.alert-err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
[data-theme="dark"] .alert-ok{background:#052e16;border-color:#14532d;color:#4ade80}
[data-theme="dark"] .alert-err{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#f87171}

/* ═══════════════════════════════════════════
   SECTION HEADER
═══════════════════════════════════════════ */
.sec-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:16px;flex-wrap:wrap}
.sec-title{font-family:var(--fd);font-size:1.2rem;font-weight:600;color:var(--ink);display:flex;align-items:center;gap:10px}
.sec-title::before{content:'';width:4px;height:18px;background:var(--blue);border-radius:2px;display:block}
.sec-caption{font-family:var(--fd);font-style:italic;font-size:.8rem;color:var(--faint);margin-left:2px}

/* ═══════════════════════════════════════════
   TABLE CARD
═══════════════════════════════════════════ */
.tcard{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;box-shadow:0 1px 4px rgba(10,15,40,.04)}
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
table{width:100%;border-collapse:collapse;min-width:480px}
thead{background:var(--bg2);border-bottom:1.5px solid var(--border)}
th{font-family:var(--fs);font-size:.6rem;font-weight:600;text-transform:uppercase;letter-spacing:.16em;color:var(--muted);padding:12px 18px;text-align:left;white-space:nowrap}
td{font-family:var(--fs);font-size:.84rem;color:var(--ink);padding:13px 18px;border-bottom:1px solid var(--border-lt);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr{transition:.1s}
tbody tr:nth-child(even) td{background:rgba(0,0,0,.013)}
[data-theme="dark"] tbody tr:nth-child(even) td{background:rgba(255,255,255,.017)}
tbody tr:hover td{background:var(--blue-soft)!important}

.no-col{font-family:var(--fd);font-style:italic;font-size:.78rem;color:var(--faint)}
.td-name{font-weight:500}
.td-desc{color:var(--muted);font-size:.82rem;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.badge-count{
  display:inline-flex;align-items:center;gap:5px;
  background:var(--blue-soft);border:1px solid rgba(26,86,219,.18);
  color:var(--blue);font-family:var(--fs);font-size:.68rem;font-weight:600;
  padding:3px 11px;border-radius:5px;
  letter-spacing:.04em;
}
[data-theme="dark"] .badge-count{border-color:rgba(91,155,248,.25)}

.act-row{display:flex;gap:5px;align-items:center}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--border);color:var(--muted);background:transparent;transition:.14s;cursor:pointer;text-decoration:none;font-size:.78rem}
.act-btn.edit{border-color:rgba(26,86,219,.3);color:var(--blue);background:var(--blue-soft)}
.act-btn.del{border-color:rgba(220,38,38,.3);color:#dc2626;background:rgba(220,38,38,.07)}
.act-btn.edit:hover{background:rgba(26,86,219,.16);border-color:var(--blue)}
.act-btn.del:hover{background:rgba(220,38,38,.15);border-color:#dc2626}

.empty-row td{text-align:center;padding:52px;color:var(--faint);font-size:.9rem;font-style:italic;font-family:var(--fd)}
.empty-row td i{display:block;font-size:1.8rem;margin-bottom:10px;opacity:.2}

/* ═══════════════════════════════════════════
   MOBILE CARD LIST
═══════════════════════════════════════════ */
.mobile-list{display:none;flex-direction:column}
.mobile-item{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border-lt);background:var(--card);transition:background .1s}
.mobile-item:last-child{border-bottom:none}
.mobile-item:active{background:var(--blue-soft)}
.mob-num{font-family:var(--fd);font-style:italic;font-size:.72rem;color:var(--faint);font-weight:400;min-width:20px;text-align:center;flex-shrink:0}
.mob-body{flex:1;min-width:0}
.mob-name{font-family:var(--fs);font-size:.88rem;font-weight:500;color:var(--ink);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mob-desc{font-family:var(--fs);font-size:.74rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px}
.mob-badge{display:inline-flex;align-items:center;background:var(--blue-soft);border:1px solid rgba(26,86,219,.18);color:var(--blue);font-family:var(--fs);font-size:.64rem;font-weight:600;padding:2px 9px;border-radius:5px}
.mob-actions{display:flex;gap:6px;flex-shrink:0}
.mob-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:7px;border:1.5px solid;font-size:.85rem;text-decoration:none;transition:.15s}
.mob-btn.edit{border-color:rgba(26,86,219,.3);color:var(--blue);background:var(--blue-soft)}
.mob-btn.del{border-color:rgba(220,38,38,.3);color:#dc2626;background:rgba(220,38,38,.07)}
.mob-btn.edit:active{background:rgba(26,86,219,.2)}
.mob-btn.del:active{background:rgba(220,38,38,.18)}
.mobile-empty{text-align:center;padding:44px 20px;color:var(--faint);font-size:.9rem;font-family:var(--fd);font-style:italic}

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
.btn-cancel{flex:1;background:none;border:none;color:var(--muted);padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:500;cursor:pointer;transition:.15s;border-right:1px solid var(--border)}
.btn-cancel:hover{background:var(--bg);color:var(--ink)}
.btn-confirm{flex:1;background:var(--ink2);color:#fff;border:none;padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:600;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none}
.btn-confirm:hover{background:var(--blue)}

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  .content{padding:26px 20px 56px}
  #sidebarToggle{display:flex!important}
}
@media(max-width:768px){
  .table-wrap{display:none}
  .mobile-list{display:flex}
}
@media(max-width:640px){
  .topbar{padding:0 18px;height:50px}
  .top-date{display:none}
  .page-hd{flex-direction:column;align-items:flex-start;margin-bottom:22px}
  .page-title{font-size:1.6rem}
  .content{padding:20px 14px 48px}
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
      <b>Kategori</b>
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
      <h1 class="page-title">Kelola <em>Kategori</em></h1>
      <p class="page-sub">Tambah, edit, atau hapus kategori berita.</p>
    </div>
    <a href="kategori_tambah.php" class="btn-new">
      <i class="bi bi-plus-lg"></i> Tambah Kategori
    </a>
  </div>

  <!-- Alerts -->
  <?php if ($msg==='tambah'): ?>
  <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Kategori berhasil ditambahkan.</div>
  <?php elseif ($msg==='edit'): ?>
  <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Kategori berhasil diperbarui.</div>
  <?php elseif ($msg==='hapus'): ?>
  <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Kategori berhasil dihapus.</div>
  <?php elseif ($msg==='gagal'): ?>
  <div class="alert alert-err"><i class="bi bi-exclamation-circle-fill"></i> Gagal menghapus, kategori masih digunakan oleh berita.</div>
  <?php endif; ?>

  <?php
    $rows = [];
    if (mysqli_num_rows($katQ) > 0) {
      while ($row = mysqli_fetch_assoc($katQ)) $rows[] = $row;
    }
  ?>

  <!-- Section Header -->
  <div class="sec-hd">
    <div class="sec-title">
      Daftar Kategori
      <span style="font-family:var(--fd);font-style:italic;font-size:.8rem;color:var(--faint);margin-left:2px"><?= count($rows) ?> kategori</span>
    </div>
  </div>

  <div class="tcard">
    <!-- DESKTOP TABLE -->
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:48px">#</th>
            <th>Nama Kategori</th>
            <th>Deskripsi</th>
            <th>Jumlah Berita</th>
            <th style="width:80px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr class="empty-row">
            <td colspan="5">
              <i class="bi bi-bookmark-x"></i>
              Belum ada kategori. <a href="kategori_tambah.php" style="color:var(--blue);font-family:var(--fs);font-size:.82rem">Tambah sekarang</a>
            </td>
          </tr>
          <?php else: foreach ($rows as $i => $row): ?>
          <tr>
            <td class="no-col"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></td>
            <td class="td-name"><?= htmlspecialchars($row['nama_kategori']) ?></td>
            <td class="td-desc"><?= htmlspecialchars($row['deskripsi'] ?: '—') ?></td>
            <td><span class="badge-count"><i class="bi bi-newspaper" style="font-size:.6rem"></i> <?= $row['jumlah_berita'] ?> berita</span></td>
            <td>
              <div class="act-row">
                <a href="kategori_edit.php?id=<?= $row['id_kategori'] ?>" class="act-btn edit" title="Edit">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <a href="kategori_hapus.php?id=<?= $row['id_kategori'] ?>" class="act-btn del" title="Hapus"
                   onclick="return confirm('Hapus kategori \'<?= htmlspecialchars($row['nama_kategori']) ?>\'?')">
                  <i class="bi bi-trash-fill"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- MOBILE LIST -->
    <div class="mobile-list">
      <?php if (empty($rows)): ?>
      <div class="mobile-empty">
        <i class="bi bi-bookmark-x" style="font-size:1.5rem;display:block;margin-bottom:10px;opacity:.2"></i>
        Belum ada kategori. <a href="kategori_tambah.php" style="color:var(--blue)">Tambah sekarang</a>
      </div>
      <?php else: foreach ($rows as $i => $row): ?>
      <div class="mobile-item">
        <div class="mob-num"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></div>
        <div class="mob-body">
          <div class="mob-name"><?= htmlspecialchars($row['nama_kategori']) ?></div>
          <?php if (!empty($row['deskripsi'])): ?>
          <div class="mob-desc"><?= htmlspecialchars($row['deskripsi']) ?></div>
          <?php endif; ?>
          <span class="mob-badge"><?= $row['jumlah_berita'] ?> berita</span>
        </div>
        <div class="mob-actions">
          <a href="kategori_edit.php?id=<?= $row['id_kategori'] ?>" class="mob-btn edit" title="Edit">
            <i class="bi bi-pencil-fill"></i>
          </a>
          <a href="kategori_hapus.php?id=<?= $row['id_kategori'] ?>" class="mob-btn del" title="Hapus"
             onclick="return confirm('Hapus kategori \'<?= htmlspecialchars($row['nama_kategori']) ?>\'?')">
            <i class="bi bi-trash-fill"></i>
          </a>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

  </div><!-- .tcard -->

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
// ── Theme ──
const html=document.documentElement,thBtn=document.getElementById('themeBtn');
function applyTheme(t){html.setAttribute('data-theme',t);thBtn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click',()=>{const n=html.getAttribute('data-theme')==='dark'?'light':'dark';localStorage.setItem('pb_theme',n);applyTheme(n);});

// ── Sidebar ──
const sidebar=document.getElementById('sidebar'),backdrop=document.getElementById('sidebarBackdrop'),toggle=document.getElementById('sidebarToggle');
const open=()=>{sidebar.classList.add('open');backdrop.classList.add('show');document.body.style.overflow='hidden'};
const close=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');document.body.style.overflow=''};
function chk(){toggle.style.display=window.innerWidth<=1024?'flex':'none';}
chk();window.addEventListener('resize',chk);
toggle.addEventListener('click',e=>{e.stopPropagation();sidebar.classList.contains('open')?close():open();});
backdrop.addEventListener('click',close);
document.addEventListener('keydown',e=>{if(e.key==='Escape'){close();hideLogoutModal();}});

// ── Logout Modal ──
function showLogoutModal(){document.getElementById('logoutModal').classList.add('show')}
function hideLogoutModal(){document.getElementById('logoutModal').classList.remove('show')}
function handleOverlayClick(e){if(e.target===document.getElementById('logoutModal'))hideLogoutModal();}
</script>
</body>
</html>