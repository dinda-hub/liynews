<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

$current    = basename($_SERVER['PHP_SELF']);
$user_id    = $_SESSION['user_username'];
$error      = '';
$success    = '';
$active_tab = 'info';

$query = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$user_id' LIMIT 1");
$admin = mysqli_fetch_assoc($query);

$user_count    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM user"))['t'] ?? 0;
$artikel_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM artikel"))['t'] ?? 0;

$komentar_count = 0;
$cekTbl = mysqli_query($koneksi, "SHOW TABLES LIKE 'komentar'");
if (mysqli_num_rows($cekTbl) > 0) {
    $komentar_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM komentar"))['t'] ?? 0;
}

$jumlahBerita = $artikel_count;

/* ── HANDLE POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── UPDATE PROFIL ── */
    if ($action === 'update_profile') {
        $active_tab    = 'edit';
        $username_baru = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
        $email         = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
        $bio           = mysqli_real_escape_string($koneksi, trim($_POST['bio'] ?? ''));

        if (empty($username_baru)) {
            $error = "Username tidak boleh kosong.";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format email tidak valid.";
        } elseif (strlen($username_baru) > 50) {
            $error = "Username maksimal 50 karakter.";
        } else {
            $cekUser = mysqli_fetch_assoc(mysqli_query($koneksi,
                "SELECT username FROM user WHERE username='$username_baru' AND username != '$user_id' LIMIT 1"));
            if ($cekUser) {
                $error = "Username sudah digunakan, coba yang lain.";
            } else {
                $upd = mysqli_query($koneksi,
                    "UPDATE user SET username='$username_baru', email='$email', bio='$bio' WHERE username='$user_id'");
                if ($upd) {
                    $_SESSION['user_username'] = $username_baru;
                    $user_id = $username_baru;
                    $success = "Profil berhasil diperbarui.";
                    $admin = mysqli_fetch_assoc(mysqli_query($koneksi,
                        "SELECT * FROM user WHERE username='$user_id' LIMIT 1"));
                } else {
                    $error = "Gagal memperbarui profil: " . mysqli_error($koneksi);
                }
            }
        }

    /* ── UBAH PASSWORD ── */
    } elseif ($action === 'change_password') {
        $active_tab = 'password';
        $pw_lama    = $_POST['password_lama']   ?? '';
        $pw_baru    = $_POST['password_baru']   ?? '';
        $pw_konfirm = $_POST['password_konfirm'] ?? '';

        if (empty($pw_lama)) {
            $error = "Password lama tidak boleh kosong.";
        } elseif (!password_verify($pw_lama, $admin['password'])) {
            $error = "Password lama tidak sesuai.";
        } elseif (empty($pw_baru)) {
            $error = "Password baru tidak boleh kosong.";
        } elseif (strlen($pw_baru) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } elseif ($pw_baru !== $pw_konfirm) {
            $error = "Konfirmasi password tidak cocok.";
        } else {
            $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $upd  = mysqli_query($koneksi, "UPDATE user SET password='$hash' WHERE username='$user_id'");
            if ($upd) {
                $success = "Password berhasil diubah.";
                $admin   = mysqli_fetch_assoc(mysqli_query($koneksi,
                    "SELECT * FROM user WHERE username='$user_id' LIMIT 1"));
            } else {
                $error = "Gagal mengubah password: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil Admin — LiyNews</title>
  <script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,300;1,9..144,400;1,9..144,600;1,9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
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
  --ink2:      #0f1c35;
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

/* SIDEBAR */
.sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:var(--sb-bg);display:flex;flex-direction:column;z-index:200;border-right:1px solid var(--sb-border);transition:transform .3s cubic-bezier(.4,0,.2,1);overflow:hidden}
.sidebar::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse 90% 55% at 15% 12%,rgba(26,86,219,.22) 0%,transparent 58%),radial-gradient(ellipse 70% 50% at 85% 90%,rgba(91,155,248,.08) 0%,transparent 52%)}
.sidebar::after{content:'';position:absolute;inset:0;pointer-events:none;opacity:.4;background-image:radial-gradient(rgba(255,255,255,.08) 1px,transparent 1px);background-size:20px 20px;mask-image:linear-gradient(to bottom,transparent 0%,black 30%,black 70%,transparent 100%)}
.sb-logo{padding:28px 24px 22px;border-bottom:1px solid var(--sb-border);position:relative;z-index:1}
.sb-wordmark{font-family:var(--fd);font-size:1.75rem;font-weight:700;color:#fff;letter-spacing:-.02em;line-height:1}
.sb-wordmark em{font-style:italic;color:var(--blue-panel)}
.sb-tagline{margin-top:6px;font-size:.6rem;letter-spacing:.28em;text-transform:uppercase;color:rgba(255,255,255,.2);font-weight:400}
.sb-nav{flex:1;padding:10px 0;overflow-y:auto;scrollbar-width:none;position:relative;z-index:1}
.sb-nav::-webkit-scrollbar{display:none}
.sb-section{font-size:.56rem;font-weight:600;letter-spacing:.25em;text-transform:uppercase;color:rgba(255,255,255,.17);padding:18px 24px 5px}
.sb-link{display:flex;align-items:center;gap:11px;padding:9px 24px;font-size:.84rem;font-weight:400;color:var(--sb-text);transition:.18s;border-left:2px solid transparent}
.sb-ico{width:30px;height:30px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:.82rem;flex-shrink:0;background:rgba(255,255,255,.04);transition:.18s}
.sb-link:hover{color:rgba(255,255,255,.78);background:var(--sb-hover)}
.sb-link:hover .sb-ico{background:rgba(91,155,248,.14);color:var(--blue-panel)}
.sb-link.active{color:#fff;background:var(--sb-active);border-left-color:var(--blue-panel);font-weight:500}
.sb-link.active .sb-ico{background:rgba(91,155,248,.2);color:var(--blue-panel)}
.sb-link-lbl{flex:1}
.sb-pill{font-size:.6rem;font-weight:600;padding:2px 8px;border-radius:4px;background:rgba(91,155,248,.15);color:var(--blue-panel);border:1px solid rgba(91,155,248,.22)}
.sb-bottom{border-top:1px solid var(--sb-border);padding:8px 0 4px;position:relative;z-index:1}
.sb-user{display:flex;align-items:center;gap:10px;padding:11px 24px;cursor:pointer;transition:.15s}
.sb-user:hover{background:rgba(255,255,255,.04)}
.sb-av{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#5b9bf8,#1a56db);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;font-family:var(--fd);font-style:italic;color:#fff;flex-shrink:0}
.sb-uname{font-size:.82rem;font-weight:500;color:#fff}
.sb-urole{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.25);margin-top:1px}
.sb-out{display:flex;align-items:center;gap:10px;padding:9px 24px;width:100%;font-size:.82rem;font-weight:400;color:rgba(248,113,113,.55);background:none;border:none;cursor:pointer;transition:.18s;border-left:2px solid transparent}
.sb-out:hover{color:#fca5a5;background:rgba(248,113,113,.06);border-left-color:rgba(248,113,113,.4)}
.sidebar-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:150;opacity:0;pointer-events:none;transition:opacity .25s}
.sidebar-backdrop.show{display:block;opacity:1;pointer-events:auto}

/* TOPBAR */
.topbar{margin-left:var(--sidebar-w);height:56px;background:var(--ink2);border-bottom:3px solid var(--blue-panel);display:flex;align-items:center;justify-content:space-between;padding:0 36px;position:sticky;top:0;z-index:50;box-shadow:0 2px 24px rgba(8,12,30,.22)}
.top-left{display:flex;align-items:center;gap:14px}
.top-crumb{font-size:.76rem;color:rgba(255,255,255,.3);display:flex;align-items:center;gap:5px}
.top-crumb b{color:rgba(255,255,255,.85);font-weight:500}
.top-crumb i{font-size:.62rem;color:rgba(255,255,255,.18)}
.top-date{font-family:var(--fd);font-style:italic;font-size:.8rem;color:rgba(255,255,255,.35);padding:4px 12px;border:1px solid rgba(255,255,255,.1);border-radius:4px;background:rgba(255,255,255,.04)}
.top-right{display:flex;align-items:center;gap:6px}
.top-btn{width:34px;height:34px;border-radius:7px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:rgba(255,255,255,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:.15s}
.top-btn:hover{border-color:var(--blue-panel);color:var(--blue-panel);background:rgba(91,155,248,.12)}
#sidebarToggle{display:none}

/* CONTENT */
.content{margin-left:var(--sidebar-w);padding-bottom:72px;animation:pageIn .5s cubic-bezier(.22,1,.36,1) both}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* HERO */
.profile-hero{background:var(--sb-bg);border-bottom:3px solid var(--blue-panel);padding:36px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;position:relative;overflow:hidden}
.profile-hero::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse 80% 100% at 5% 50%,rgba(26,86,219,.2) 0%,transparent 60%),radial-gradient(ellipse 50% 80% at 95% 20%,rgba(91,155,248,.08) 0%,transparent 55%)}
.profile-hero::after{content:'';position:absolute;inset:0;pointer-events:none;opacity:.3;background-image:radial-gradient(rgba(255,255,255,.07) 1px,transparent 1px);background-size:20px 20px}
.hero-avatar{width:78px;height:78px;border-radius:14px;background:linear-gradient(135deg,#5b9bf8,#1a56db);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;font-weight:700;font-family:var(--fd);font-style:italic;flex-shrink:0;border:2px solid rgba(255,255,255,.15);position:relative;z-index:1}
.hero-info{min-width:0;position:relative;z-index:1}
.hero-info h2{font-family:var(--fd);font-size:1.6rem;font-weight:700;color:#fff;margin-bottom:4px;letter-spacing:-.02em}
.hero-info p{font-size:.82rem;color:rgba(255,255,255,.4);margin-bottom:12px}
.hero-badges{display:flex;gap:8px;flex-wrap:wrap}
.hero-badge{display:inline-flex;align-items:center;gap:5px;font-size:.66rem;font-weight:600;padding:4px 12px;border-radius:5px;letter-spacing:.06em;text-transform:uppercase}
.hero-badge.blue{background:rgba(91,155,248,.15);border:1px solid rgba(91,155,248,.3);color:#93c0fb}
.hero-badge.green{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25);color:#4ade80}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;padding:28px 36px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);padding:18px 20px;box-shadow:0 1px 4px rgba(10,15,40,.04);display:flex;align-items:center;gap:14px}
.stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.stat-info p{font-size:.6rem;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);margin-bottom:4px}
.stat-info h3{font-family:var(--fd);font-size:1.5rem;font-weight:700;color:var(--ink);line-height:1}

/* TABS WRAP */
.tabs-wrap{padding:0 36px 36px}

/* ALERT */
.alert{padding:11px 16px;border-radius:var(--r);font-size:.82rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
.alert-err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
[data-theme="dark"] .alert-ok{background:#052e16;border-color:#14532d;color:#4ade80}
[data-theme="dark"] .alert-err{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#f87171}

/* TABS */
.tabs{display:flex;border-bottom:1.5px solid var(--border);margin-bottom:20px;overflow-x:auto;scrollbar-width:none}
.tabs::-webkit-scrollbar{display:none}
.tab-btn{background:none;border:none;border-bottom:2.5px solid transparent;margin-bottom:-1.5px;padding:11px 20px;font-size:.76rem;font-weight:600;color:var(--muted);cursor:pointer;transition:.15s;text-transform:uppercase;letter-spacing:.1em;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0}
.tab-btn:hover{color:var(--ink)}
.tab-btn.active{color:var(--blue);border-bottom-color:var(--blue)}
.tab-pane{display:none}
.tab-pane.active{display:block}

/* CARD */
.section-card{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;box-shadow:0 1px 4px rgba(10,15,40,.04)}
.section-card-body{padding:24px}

/* INFO ROWS */
.info-row{display:flex;justify-content:space-between;align-items:flex-start;padding:14px 24px;border-bottom:1px solid var(--border-lt);gap:16px}
.info-row:last-child{border-bottom:none}
.info-lbl{font-size:.62rem;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);flex-shrink:0;padding-top:2px}
.info-val{font-size:.86rem;color:var(--ink);font-weight:500;text-align:right;word-break:break-word}
.info-val.empty{color:var(--faint);font-style:italic;font-weight:400}
.role-pill{display:inline-flex;align-items:center;gap:5px;background:var(--blue-soft);border:1px solid rgba(26,86,219,.18);color:var(--blue);font-size:.68rem;font-weight:600;padding:3px 11px;border-radius:5px;letter-spacing:.06em;text-transform:uppercase}
[data-theme="dark"] .role-pill{background:rgba(91,155,248,.15);border-color:rgba(91,155,248,.25);color:#7eb3f8}

/* FORM */
.field{margin-bottom:18px}
.field label{display:block;font-size:.62rem;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.field input,.field textarea{width:100%;background:var(--bg2);border:1.5px solid var(--border);color:var(--ink);border-radius:var(--r);padding:10px 13px;font-size:.86rem;font-family:var(--fs);outline:none;transition:.15s;-webkit-appearance:none;appearance:none}
.field input:focus,.field textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft)}
.field input::placeholder,.field textarea::placeholder{color:var(--faint)}
.field textarea{resize:vertical;min-height:90px}
.field-hint{font-size:.72rem;color:var(--muted);margin-top:5px}
.field-req{color:#dc2626}

/* PASSWORD INPUT WRAP */
.input-wrap{position:relative}
.input-wrap input{padding-right:42px}
.eye-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:.95rem;padding:2px;line-height:1;transition:.15s}
.eye-btn:hover{color:var(--blue)}

/* DIVIDER */
.form-divider{border:none;border-top:1px solid var(--border);margin:20px 0}

/* PASSWORD STRENGTH */
.s-bar{height:4px;border-radius:2px;margin-top:8px;background:var(--border);overflow:hidden}
.s-fill{height:100%;width:0;border-radius:2px;transition:width .3s,background .3s}
.s-label{font-size:.72rem;color:var(--muted);margin-top:5px;min-height:1.2em}
.s-label.match-ok{color:#16a34a}
.s-label.match-no{color:#dc2626}

/* BTN */
.btn-primary{background:var(--blue);color:#fff;border:none;border-radius:var(--r);padding:10px 22px;font-size:.84rem;font-weight:600;font-family:var(--fs);cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.2s;box-shadow:0 2px 12px rgba(26,86,219,.3)}
.btn-primary:hover{background:var(--ink2);box-shadow:0 4px 20px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-primary:active{transform:translateY(0)}

/* LOGOUT MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,8,20,.65);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;padding:20px}
.modal-overlay.show{display:flex}
.modal-box{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);width:100%;max-width:360px;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;animation:popIn .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-top{padding:36px 28px 22px;text-align:center;border-bottom:1px solid var(--border)}
.modal-ico{width:50px;height:50px;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--blue);margin:0 auto 14px}
.modal-box h3{font-family:var(--fd);font-size:1.2rem;font-weight:600;color:var(--ink);margin-bottom:6px}
.modal-box p{font-size:.82rem;color:var(--muted);line-height:1.65}
.modal-acts{display:flex}
.btn-cancel{flex:1;background:none;border:none;color:var(--muted);padding:14px;font-size:.84rem;font-weight:500;cursor:pointer;transition:.15s;border-right:1px solid var(--border)}
.btn-cancel:hover{background:var(--bg);color:var(--ink)}
.btn-confirm{flex:1;background:var(--ink2);color:#fff;border:none;padding:14px;font-size:.84rem;font-weight:600;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none}
.btn-confirm:hover{background:var(--blue)}

/* RESPONSIVE */
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  #sidebarToggle{display:flex!important}
  .profile-hero{padding:28px 24px}
  .stats-row{padding:20px 24px}
  .tabs-wrap{padding:0 24px 36px}
}
@media(max-width:768px){
  .stats-row{gap:10px;padding:16px}
  .stat-card{padding:12px 10px;gap:8px;flex-direction:column;align-items:flex-start}
  .stat-icon{width:34px;height:34px;font-size:.85rem}
  .stat-info h3{font-size:1.15rem}
  .tabs-wrap{padding:0 16px 28px}
  .topbar{padding:0 16px;height:50px}
  .top-date{display:none}
  .info-row{flex-direction:column;gap:4px;align-items:flex-start;padding:12px 16px}
  .info-val{text-align:left}
  .section-card-body{padding:16px}
  .profile-hero{padding:22px 18px;gap:18px}
  .hero-avatar{width:64px;height:64px;font-size:1.6rem}
  .hero-info h2{font-size:1.3rem}
}
@media(max-width:640px){
  .tab-btn{padding:9px 13px;font-size:.7rem}
  .tab-btn i{display:none}
}
  </style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- SIDEBAR -->
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

<!-- TOPBAR -->
<div class="topbar">
  <div class="top-left">
    <div class="top-crumb">
      <span>LiyNews</span>
      <i class="bi bi-chevron-right"></i>
      <b>Profil Saya</b>
    </div>
    <div class="top-date"><?= date('d F Y') ?></div>
  </div>
  <div class="top-right">
    <button class="top-btn" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
    <button class="top-btn" id="themeBtn" aria-label="Tema"><i class="bi bi-moon-fill"></i></button>
  </div>
</div>

<!-- CONTENT -->
<div class="content">

  <!-- HERO -->
  <div class="profile-hero">
    <div class="hero-avatar"><?= strtoupper(substr($admin['username']??'A',0,1)) ?></div>
    <div class="hero-info">
      <h2><?= htmlspecialchars($admin['username']) ?></h2>
      <p><?= !empty($admin['email']) ? htmlspecialchars($admin['email']) : 'Email belum diisi' ?></p>
      <div class="hero-badges">
        <span class="hero-badge blue"><i class="bi bi-shield-check"></i> Administrator</span>
        <span class="hero-badge green"><i class="bi bi-circle-fill" style="font-size:.4rem"></i> Aktif</span>
      </div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(26,86,219,.09);color:var(--blue)"><i class="bi bi-people"></i></div>
      <div class="stat-info"><p>Pengguna</p><h3><?= $user_count ?></h3></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(22,163,74,.09);color:#16a34a"><i class="bi bi-newspaper"></i></div>
      <div class="stat-info"><p>Artikel</p><h3><?= $artikel_count ?></h3></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(147,51,234,.09);color:#9333ea"><i class="bi bi-chat-dots"></i></div>
      <div class="stat-info"><p>Komentar</p><h3><?= $komentar_count ?></h3></div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs-wrap">

    <?php if ($error): ?>
    <div class="alert alert-err"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="tabs" id="tabBar">
      <button class="tab-btn <?= $active_tab==='info'?'active':'' ?>" onclick="switchTab('info',this)">
        <i class="bi bi-person"></i> Informasi
      </button>
      <button class="tab-btn <?= $active_tab==='edit'?'active':'' ?>" onclick="switchTab('edit',this)">
        <i class="bi bi-pencil-square"></i> Edit Profil
      </button>
      <button class="tab-btn <?= $active_tab==='password'?'active':'' ?>" onclick="switchTab('password',this)">
        <i class="bi bi-lock"></i> Ubah Password
      </button>
    </div>

    <!-- TAB: INFORMASI -->
    <div class="tab-pane <?= $active_tab==='info'?'active':'' ?>" id="tab-info">
      <div class="section-card">
        <div class="info-row">
          <span class="info-lbl">Username</span>
          <span class="info-val"><?= htmlspecialchars($admin['username']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-lbl">Email</span>
          <span class="info-val <?= empty($admin['email'])?'empty':'' ?>">
            <?= !empty($admin['email']) ? htmlspecialchars($admin['email']) : 'Belum diisi' ?>
          </span>
        </div>
        <div class="info-row">
          <span class="info-lbl">Role</span>
          <span class="info-val">
            <span class="role-pill"><i class="bi bi-shield-check"></i> <?= htmlspecialchars($admin['role']) ?></span>
          </span>
        </div>
        <div class="info-row">
          <span class="info-lbl">Bergabung</span>
          <span class="info-val <?= empty($admin['tgl_daftar'])?'empty':'' ?>">
            <?= !empty($admin['tgl_daftar']) ? date('d F Y, H:i', strtotime($admin['tgl_daftar'])) : '—' ?>
          </span>
        </div>
        <div class="info-row">
          <span class="info-lbl">Bio</span>
          <span class="info-val <?= empty($admin['bio'])?'empty':'' ?>">
            <?= !empty($admin['bio']) ? nl2br(htmlspecialchars($admin['bio'])) : 'Belum diisi' ?>
          </span>
        </div>
      </div>
    </div>

    <!-- TAB: EDIT PROFIL -->
    <div class="tab-pane <?= $active_tab==='edit'?'active':'' ?>" id="tab-edit">
      <div class="section-card">
        <div class="section-card-body">
          <form method="POST" id="formEdit" novalidate>
            <input type="hidden" name="action" value="update_profile">

            <div class="field">
              <label for="f-username">Username <span class="field-req">*</span></label>
              <input
                type="text" id="f-username" name="username"
                required maxlength="50" autocomplete="username"
                placeholder="Masukkan username"
                value="<?= htmlspecialchars($_POST['username'] ?? $admin['username'] ?? '') ?>">
              <div class="field-hint">Maks. 50 karakter.</div>
            </div>

            <div class="field">
              <label for="f-email">Email</label>
              <input
                type="email" id="f-email" name="email"
                autocomplete="email"
                placeholder="contoh@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? $admin['email'] ?? '') ?>">
            </div>

            <div class="field">
              <label for="f-bio">Bio</label>
              <textarea
                id="f-bio" name="bio"
                maxlength="300"
                placeholder="Tulis bio singkat tentang kamu..."
              ><?= htmlspecialchars($_POST['bio'] ?? $admin['bio'] ?? '') ?></textarea>
              <div class="field-hint"><span id="bioCount">0</span> / 300 karakter</div>
            </div>

            <button type="submit" class="btn-primary">
              <i class="bi bi-check-lg"></i> Simpan Perubahan
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- TAB: UBAH PASSWORD -->
    <div class="tab-pane <?= $active_tab==='password'?'active':'' ?>" id="tab-password">
      <div class="section-card">
        <div class="section-card-body">
          <form method="POST" id="formPassword" novalidate>
            <input type="hidden" name="action" value="change_password">

            <div class="field">
              <label for="pw-lama">Password Lama <span class="field-req">*</span></label>
              <div class="input-wrap">
                <input type="password" id="pw-lama" name="password_lama"
                  required autocomplete="current-password"
                  placeholder="Masukkan password lama">
                <button type="button" class="eye-btn" onclick="toggleEye('pw-lama',this)" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <hr class="form-divider">

            <div class="field">
              <label for="pw-baru">Password Baru <span class="field-req">*</span></label>
              <div class="input-wrap">
                <input type="password" id="pw-baru" name="password_baru"
                  required autocomplete="new-password"
                  placeholder="Minimal 6 karakter">
                <button type="button" class="eye-btn" onclick="toggleEye('pw-baru',this)" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="s-bar"><div class="s-fill" id="sFill"></div></div>
              <div class="s-label" id="sLabel">Masukkan password baru.</div>
            </div>

            <div class="field">
              <label for="pw-konfirm">Konfirmasi Password Baru <span class="field-req">*</span></label>
              <div class="input-wrap">
                <input type="password" id="pw-konfirm" name="password_konfirm"
                  required autocomplete="new-password"
                  placeholder="Ulangi password baru">
                <button type="button" class="eye-btn" onclick="toggleEye('pw-konfirm',this)" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="s-label" id="matchLabel"></div>
            </div>

            <button type="submit" class="btn-primary">
              <i class="bi bi-shield-lock"></i> Ubah Password
            </button>
          </form>
        </div>
      </div>
    </div>

  </div><!-- /tabs-wrap -->
</div><!-- /content -->

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
/* ── THEME ── */
const html  = document.documentElement;
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

/* ── SIDEBAR ── */
const sidebar  = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
const toggle   = document.getElementById('sidebarToggle');
const openSb   = () => { sidebar.classList.add('open'); backdrop.classList.add('show'); document.body.style.overflow = 'hidden'; };
const closeSb  = () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); document.body.style.overflow = ''; };
function chkToggle() { if (toggle) toggle.style.display = window.innerWidth <= 1024 ? 'flex' : 'none'; }
chkToggle();
window.addEventListener('resize', chkToggle);
if (toggle) toggle.addEventListener('click', e => { e.stopPropagation(); sidebar.classList.contains('open') ? closeSb() : openSb(); });
backdrop.addEventListener('click', closeSb);

/* ── TABS ── */
function switchTab(id, el) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  el.classList.add('active');
}

/* ── BIO COUNTER ── */
const bioEl    = document.getElementById('f-bio');
const bioCount = document.getElementById('bioCount');
function updateBio() { if (bioEl && bioCount) bioCount.textContent = bioEl.value.length; }
if (bioEl) { bioEl.addEventListener('input', updateBio); updateBio(); }

/* ── EYE TOGGLE ── */
function toggleEye(id, btn) {
  const inp = document.getElementById(id);
  const hidden = inp.type === 'password';
  inp.type = hidden ? 'text' : 'password';
  btn.innerHTML = hidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}

/* ── PASSWORD STRENGTH ── */
const pwBaru    = document.getElementById('pw-baru');
const pwKonfirm = document.getElementById('pw-konfirm');
const sFill     = document.getElementById('sFill');
const sLabel    = document.getElementById('sLabel');
const matchLabel = document.getElementById('matchLabel');

const swColors = ['#e5e7eb','#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
const swLabels = ['','Sangat lemah','Lemah','Cukup','Kuat','Sangat kuat'];

function calcStrength(v) {
  let s = 0;
  if (v.length >= 6)  s++;
  if (v.length >= 10) s++;
  if (v.length >= 14) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^a-zA-Z0-9]/.test(v)) s++;
  return Math.min(s, 5);
}

function checkMatch() {
  if (!pwKonfirm || !pwBaru) return;
  const v = pwKonfirm.value;
  if (!v) { matchLabel.textContent = ''; matchLabel.className = 's-label'; return; }
  if (pwBaru.value === v) {
    matchLabel.textContent = '✓ Password cocok';
    matchLabel.className = 's-label match-ok';
  } else {
    matchLabel.textContent = '✗ Password tidak cocok';
    matchLabel.className = 's-label match-no';
  }
}

if (pwBaru) {
  pwBaru.addEventListener('input', function () {
    const v = this.value;
    if (!v) {
      sFill.style.width = '0';
      sLabel.textContent = 'Masukkan password baru.';
      sLabel.className = 's-label';
    } else {
      const s = calcStrength(v);
      sFill.style.width = ((s / 5) * 100) + '%';
      sFill.style.background = swColors[s];
      sLabel.textContent = swLabels[s];
    }
    checkMatch();
  });
}
if (pwKonfirm) pwKonfirm.addEventListener('input', checkMatch);

/* ── CLIENT VALIDATION ── */
const formEdit = document.getElementById('formEdit');
if (formEdit) {
  formEdit.addEventListener('submit', function (e) {
    const uname = document.getElementById('f-username').value.trim();
    const emailEl = document.getElementById('f-email');
    if (!uname) { e.preventDefault(); alert('Username tidak boleh kosong.'); return; }
    if (emailEl.value && !emailEl.validity.valid) { e.preventDefault(); alert('Format email tidak valid.'); return; }
  });
}

const formPassword = document.getElementById('formPassword');
if (formPassword) {
  formPassword.addEventListener('submit', function (e) {
    const lama    = document.getElementById('pw-lama').value;
    const baru    = pwBaru ? pwBaru.value : '';
    const konfirm = pwKonfirm ? pwKonfirm.value : '';
    if (!lama) { e.preventDefault(); alert('Masukkan password lama.'); return; }
    if (baru.length < 6) { e.preventDefault(); alert('Password baru minimal 6 karakter.'); return; }
    if (baru !== konfirm) { e.preventDefault(); alert('Konfirmasi password tidak cocok.'); return; }
  });
}

/* ── LOGOUT MODAL ── */
function showLogoutModal() { document.getElementById('logoutModal').classList.add('show'); document.body.style.overflow = 'hidden'; }
function hideLogoutModal() { document.getElementById('logoutModal').classList.remove('show'); document.body.style.overflow = ''; }
function handleOverlayClick(e) { if (e.target === document.getElementById('logoutModal')) hideLogoutModal(); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') { hideLogoutModal(); closeSb(); } });
</script>
</body>
</html>