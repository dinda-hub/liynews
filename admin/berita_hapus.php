<?php
session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: kelola_berita.php"); exit; }

$art = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM artikel WHERE id_artikel=$id"));
if (!$art) { header("Location: kelola_berita.php"); exit; }

// Proses hapus jika sudah dikonfirmasi
if (isset($_POST['konfirmasi'])) {
    if (!empty($art['thumbnail']) && file_exists('../uploads/' . $art['thumbnail'])) {
        unlink('../uploads/' . $art['thumbnail']);
    }
    mysqli_query($koneksi, "DELETE FROM artikel WHERE id_artikel=$id");
    header("Location: kelola_berita.php?msg=hapus"); exit;
}

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hapus Berita — LiyNews</title>
  <script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,300;1,9..144,400;1,9..144,600;1,9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
/* ═══════════════════════════════════════════
   TOKENS — identik dengan kelola_berita.php
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
  --ink2:      #bfcde8;
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
.content{
  margin-left:var(--sidebar-w);
  padding:36px 36px 72px;
  min-height:calc(100vh - 56px);
  display:flex;align-items:center;justify-content:center;
  animation:pageIn .5s cubic-bezier(.22,1,.36,1) both;
}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* ═══════════════════════════════════════════
   CONFIRM CARD
═══════════════════════════════════════════ */
.confirm-wrap{width:100%;max-width:540px}

.page-eyebrow{font-family:var(--fs);font-size:.62rem;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:var(--blue);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.page-eyebrow::before{content:'';width:20px;height:1.5px;background:var(--blue);border-radius:2px}

.warn-card{
  background:var(--card);
  border:1.5px solid rgba(26,86,219,.25);
  border-radius:var(--rl);
  overflow:hidden;
  box-shadow:0 4px 24px rgba(26,86,219,.08), 0 1px 4px rgba(10,15,40,.06);
}
[data-theme="dark"] .warn-card{
  border-color:rgba(91,155,248,.3);
  box-shadow:0 4px 24px rgba(91,155,248,.1);
}

.warn-header{
  background:linear-gradient(135deg, rgba(26,86,219,.07) 0%, rgba(26,86,219,.03) 100%);
  border-bottom:1px solid rgba(26,86,219,.15);
  padding:28px 28px 22px;
  display:flex;align-items:flex-start;gap:16px;
}
[data-theme="dark"] .warn-header{
  background:linear-gradient(135deg, rgba(91,155,248,.1) 0%, rgba(91,155,248,.04) 100%);
}
.warn-ico{
  width:48px;height:48px;border-radius:12px;
  background:rgba(26,86,219,.1);
  border:1.5px solid rgba(26,86,219,.2);
  display:flex;align-items:center;justify-content:center;
  font-size:1.3rem;color:var(--blue);
  flex-shrink:0;
  animation:warnPulse 2s ease-in-out infinite;
}
@keyframes warnPulse{0%,100%{box-shadow:0 0 0 0 rgba(26,86,219,.2)}50%{box-shadow:0 0 0 6px rgba(26,86,219,0)}}
.warn-title{font-family:var(--fd);font-size:1.35rem;font-weight:600;color:var(--ink);letter-spacing:-.01em;line-height:1.2;margin-bottom:5px}
.warn-sub{font-family:var(--fs);font-size:.82rem;color:var(--muted);font-weight:300;line-height:1.55}

.warn-body{padding:22px 28px}

/* Artikel preview */
.article-preview{
  background:var(--bg2);
  border:1px solid var(--border);
  border-radius:var(--r);
  padding:14px 16px;
  margin-bottom:20px;
  display:flex;align-items:flex-start;gap:14px;
}
[data-theme="dark"] .article-preview{background:rgba(255,255,255,.03)}
.preview-thumb{
  width:60px;height:60px;border-radius:8px;
  object-fit:cover;border:1px solid var(--border);
  flex-shrink:0;background:var(--border);
}
.preview-thumb-placeholder{
  width:60px;height:60px;border-radius:8px;
  background:var(--bg);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;color:var(--faint);flex-shrink:0;
}
.preview-info{}
.preview-title{font-family:var(--fd);font-size:.95rem;font-weight:600;color:var(--ink);line-height:1.3;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.preview-meta{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.pmeta-item{font-family:var(--fs);font-size:.7rem;color:var(--muted);display:flex;align-items:center;gap:4px}
.pmeta-sep{color:var(--faint);font-size:.65rem}

/* Status badge */
.badge-pub{display:inline-flex;align-items:center;gap:4px;background:rgba(5,150,105,.1);color:#059669;font-family:var(--fs);font-size:.64rem;font-weight:600;padding:3px 9px;border-radius:5px;border:1px solid rgba(5,150,105,.22);text-transform:uppercase;letter-spacing:.05em}
.badge-draft{display:inline-flex;align-items:center;gap:4px;background:rgba(217,119,6,.1);color:#d97706;font-family:var(--fs);font-size:.64rem;font-weight:600;padding:3px 9px;border-radius:5px;border:1px solid rgba(217,119,6,.22);text-transform:uppercase;letter-spacing:.05em}
.badge-dot{width:5px;height:5px;border-radius:50%;background:currentColor}
[data-theme="dark"] .badge-pub{background:rgba(5,150,105,.16);color:#34d399;border-color:rgba(52,211,153,.22)}
[data-theme="dark"] .badge-draft{background:rgba(217,119,6,.16);color:#fbbf24;border-color:rgba(251,191,36,.22)}

/* Warning list */
.warn-list{margin-bottom:20px}
.warn-list-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-lt);font-family:var(--fs);font-size:.82rem;color:var(--muted)}
.warn-list-item:last-child{border-bottom:none}
.warn-list-item i{color:var(--blue);font-size:.85rem;flex-shrink:0;opacity:.8}

/* Actions */
.warn-actions{
  padding:18px 28px;
  border-top:1px solid var(--border-lt);
  display:flex;gap:10px;
  background:var(--bg2);
}
[data-theme="dark"] .warn-actions{background:rgba(255,255,255,.02)}
.btn-delete{
  flex:1;
  background:var(--blue);
  color:#fff;border:none;border-radius:var(--r);
  padding:11px 20px;font-family:var(--fs);font-size:.85rem;font-weight:600;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;
  transition:.2s;
}
.btn-delete:hover{background:var(--ink2);box-shadow:0 4px 16px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-delete:active{transform:translateY(0)}
.btn-cancel{
  flex:1;
  background:none;border:1.5px solid var(--border);color:var(--ink);
  border-radius:var(--r);padding:11px 20px;font-family:var(--fs);
  font-size:.85rem;font-weight:500;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:7px;
  transition:.15s;text-decoration:none;
}
.btn-cancel:hover{border-color:var(--blue);color:var(--blue)}

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  #sidebarToggle{display:flex!important}
}
@media(max-width:640px){
  .topbar{padding:0 18px;height:50px}
  .top-date{display:none}
  .content{padding:20px 14px 48px;align-items:flex-start;padding-top:32px}
  .warn-header{padding:20px 18px 16px}
  .warn-body{padding:18px 18px}
  .warn-actions{padding:14px 18px;flex-direction:column}
  .btn-delete,.btn-cancel{flex:none;width:100%;justify-content:center}
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
    <a href="dashboardadmin.php" class="sb-link">
      <span class="sb-ico"><i class="bi bi-grid-1x2"></i></span>
      <span class="sb-link-lbl">Dashboard</span>
    </a>
    <a href="kelola_berita.php" class="sb-link active">
      <span class="sb-ico"><i class="bi bi-newspaper"></i></span>
      <span class="sb-link-lbl">Berita</span>
    </a>
    <a href="kategori.php" class="sb-link">
      <span class="sb-ico"><i class="bi bi-bookmark"></i></span>
      <span class="sb-link-lbl">Kategori</span>
    </a>
    <a href="kelola_user.php" class="sb-link">
      <span class="sb-ico"><i class="bi bi-people"></i></span>
      <span class="sb-link-lbl">Pengguna</span>
    </a>
    <div class="sb-section">Lainnya</div>
    <a href="profileadmin.php" class="sb-link">
      <span class="sb-ico"><i class="bi bi-person-circle"></i></span>
      <span class="sb-link-lbl">Profil Saya</span>
    </a>
    <a href="../public/index.php" class="sb-link" target="_blank">
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
      <a href="kelola_berita.php" style="color:rgba(255,255,255,.5);text-decoration:none;transition:.15s" onmouseover="this.style.color='#5b9bf8'" onmouseout="this.style.color='rgba(255,255,255,.5)'">Kelola Berita</a>
      <i class="bi bi-chevron-right"></i>
      <b>Hapus Berita</b>
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
  <div class="confirm-wrap">

    <div class="page-eyebrow">Hapus Konten</div>

    <div class="warn-card">
      <div class="warn-header">
        <div class="warn-ico"><i class="bi bi-trash3-fill"></i></div>
        <div>
          <div class="warn-title">Hapus Berita Ini?</div>
          <div class="warn-sub">Tindakan ini tidak dapat dibatalkan. Semua data berita termasuk gambar thumbnail akan dihapus secara permanen.</div>
        </div>
      </div>

      <div class="warn-body">
        <!-- Artikel Preview -->
        <div class="article-preview">
          <?php if (!empty($art['thumbnail']) && file_exists('../uploads/'.$art['thumbnail'])): ?>
          <img src="../uploads/<?= htmlspecialchars($art['thumbnail']) ?>"
               class="preview-thumb" alt="Thumbnail">
          <?php else: ?>
          <div class="preview-thumb-placeholder"><i class="bi bi-image"></i></div>
          <?php endif; ?>
          <div class="preview-info">
            <div class="preview-title"><?= htmlspecialchars($art['judul']??'-') ?></div>
            <div class="preview-meta">
              <?php if ($art['status']==='publish'): ?>
              <span class="badge-pub"><span class="badge-dot"></span> Publish</span>
              <?php else: ?>
              <span class="badge-draft"><span class="badge-dot"></span> Draft</span>
              <?php endif; ?>
              <span class="pmeta-sep">·</span>
              <span class="pmeta-item"><i class="bi bi-calendar3"></i><?= date('d M Y', strtotime($art['tgl_posting']??'now')) ?></span>
              <span class="pmeta-sep">·</span>
              <span class="pmeta-item"><i class="bi bi-hash"></i>ID <?= $id ?></span>
            </div>
          </div>
        </div>

        <!-- Warning list -->
        <div class="warn-list">
          <div class="warn-list-item">
            <i class="bi bi-exclamation-circle-fill"></i>
            Data artikel akan dihapus dari database secara permanen
          </div>
          <?php if (!empty($art['thumbnail']) && file_exists('../uploads/'.$art['thumbnail'])): ?>
          <div class="warn-list-item">
            <i class="bi bi-image-fill"></i>
            File thumbnail <strong style="color:var(--ink)"><?= htmlspecialchars($art['thumbnail']) ?></strong> akan ikut dihapus
          </div>
          <?php endif; ?>
          <div class="warn-list-item">
            <i class="bi bi-arrow-counterclockwise"></i>
            Proses ini tidak bisa dibatalkan atau dipulihkan
          </div>
        </div>
      </div>

      <div class="warn-actions">
        <form method="POST" style="display:contents">
          <button type="submit" name="konfirmasi" class="btn-delete">
            <i class="bi bi-trash3-fill"></i> Ya, Hapus Sekarang
          </button>
        </form>
        <a href="kelola_berita.php" class="btn-cancel">
          <i class="bi bi-arrow-left"></i> Batal, Kembali
        </a>
      </div>
    </div>

  </div>
</div>

<!-- MODAL LOGOUT -->
<div id="logoutModal" style="display:none;position:fixed;inset:0;background:rgba(5,8,20,.65);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)hideLogoutModal()">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--rl);width:100%;max-width:360px;overflow:hidden;animation:popIn .22s cubic-bezier(.34,1.56,.64,1)">
    <div style="padding:36px 28px 22px;text-align:center;border-bottom:1px solid var(--border)">
      <div style="width:50px;height:50px;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--blue);margin:0 auto 14px"><i class="bi bi-power"></i></div>
      <h3 style="font-family:var(--fd);font-size:1.25rem;font-weight:600;color:var(--ink);margin-bottom:6px">Keluar dari Panel?</h3>
      <p style="font-family:var(--fs);font-size:.82rem;color:var(--muted);line-height:1.65">Sesi aktif kamu akan diakhiri dan diarahkan ke halaman login.</p>
    </div>
    <div style="display:flex">
      <button onclick="hideLogoutModal()" style="flex:1;background:none;border:none;border-right:1px solid var(--border);color:var(--muted);padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:500;cursor:pointer;transition:.15s" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='none'">Batal</button>
      <a href="../public/logout.php" style="flex:1;background:var(--ink2);color:#fff;border:none;padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;transition:.15s" onmouseover="this.style.background='var(--blue)'" onmouseout="this.style.background='var(--ink2)'"><i class="bi bi-box-arrow-right"></i> Ya, Keluar</a>
    </div>
  </div>
</div>
<style>@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}</style>

<script>
// ── Theme ──
const html=document.documentElement,thBtn=document.getElementById('themeBtn');
function applyTheme(t){html.setAttribute('data-theme',t);thBtn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click',()=>{const n=html.getAttribute('data-theme')==='dark'?'light':'dark';localStorage.setItem('pb_theme',n);applyTheme(n);});

// ── Sidebar ──
const sidebar=document.getElementById('sidebar'),backdrop=document.getElementById('sidebarBackdrop'),toggle=document.getElementById('sidebarToggle');
const openSb=()=>{sidebar.classList.add('open');backdrop.classList.add('show');document.body.style.overflow='hidden'};
const closeSb=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');document.body.style.overflow=''};
function chk(){toggle.style.display=window.innerWidth<=1024?'flex':'none';}
chk();window.addEventListener('resize',chk);
toggle.addEventListener('click',e=>{e.stopPropagation();sidebar.classList.contains('open')?closeSb():openSb();});
backdrop.addEventListener('click',closeSb);
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeSb();hideLogoutModal();}});

// ── Logout Modal ──
function showLogoutModal(){const m=document.getElementById('logoutModal');m.style.display='flex';}
function hideLogoutModal(){const m=document.getElementById('logoutModal');m.style.display='none';}
</script>
</body>
</html>