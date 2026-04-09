<?php
session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$current = basename($_SERVER['PHP_SELF']);
$msg = $_GET['msg'] ?? '';
$error = '';

// HAPUS USER
if (isset($_GET['hapus'])) {
    $hapusId = (int)$_GET['hapus'];
    if ($hapusId === (int)$_SESSION['user_id']) {
        $error = "Tidak bisa menghapus akun sendiri.";
    } else {
        mysqli_query($koneksi, "DELETE FROM user WHERE id_user=$hapusId");
        header("Location: kelola_user.php?msg=hapus"); exit;
    }
}

// TAMBAH USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah') {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $email    = mysqli_real_escape_string($koneksi, trim($_POST['email']));
    $password = $_POST['password'];
    $role     = in_array($_POST['role'], ['admin','penulis','pembaca']) ? $_POST['role'] : 'pembaca';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Semua field wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $cekU = mysqli_query($koneksi, "SELECT id_user FROM user WHERE username='$username'");
        $cekE = mysqli_query($koneksi, "SELECT id_user FROM user WHERE email='$email'");
        if (mysqli_num_rows($cekU) > 0) {
            $error = "Username sudah digunakan.";
        } elseif (mysqli_num_rows($cekE) > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "INSERT INTO user (username, email, password, role) VALUES ('$username','$email','$hash','$role')");
            header("Location: kelola_user.php?msg=tambah"); exit;
        }
    }
}

// EDIT ROLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'edit_role') {
    $editId = (int)$_POST['edit_id'];
    $role   = in_array($_POST['role'], ['admin','penulis','pembaca']) ? $_POST['role'] : 'pembaca';
    if ($editId === (int)$_SESSION['user_id']) {
        $error = "Tidak bisa mengubah role akun sendiri.";
    } else {
        mysqli_query($koneksi, "UPDATE user SET role='$role' WHERE id_user=$editId");
        header("Location: kelola_user.php?msg=edit"); exit;
    }
}

$userQ = mysqli_query($koneksi, "SELECT * FROM user ORDER BY id_user ASC");
$jumlahBerita = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel"))['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Pengguna — LiyNews</title>
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

/* SIDEBAR */
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

/* TOPBAR */
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

/* CONTENT */
.content{margin-left:var(--sidebar-w);padding:36px 36px 72px;animation:pageIn .5s cubic-bezier(.22,1,.36,1) both}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* PAGE HEADER */
.page-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:20px;padding-bottom:24px;border-bottom:1px solid var(--border)}
.page-eyebrow{font-family:var(--fs);font-size:.62rem;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:var(--blue);margin-bottom:7px;display:flex;align-items:center;gap:8px}
.page-eyebrow::before{content:'';width:20px;height:1.5px;background:var(--blue);border-radius:2px}
.page-title{font-family:var(--fd);font-size:1.9rem;font-weight:600;color:var(--ink);letter-spacing:-.02em;line-height:1.1}
.page-title em{font-style:italic;color:var(--blue)}
.page-sub{font-family:var(--fs);font-size:.84rem;color:var(--muted);margin-top:5px;font-weight:300}
.btn-new{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:var(--blue);color:#fff;border:none;border-radius:var(--r);font-family:var(--fs);font-size:.82rem;font-weight:600;letter-spacing:.02em;cursor:pointer;transition:.2s;white-space:nowrap;text-decoration:none;box-shadow:0 2px 12px rgba(26,86,219,.3)}
.btn-new:hover{background:var(--ink2);box-shadow:0 4px 20px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-new:active{transform:translateY(0)}

/* ALERTS */
.alert{padding:11px 16px;border-radius:var(--r);font-family:var(--fs);font-size:.82rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
.alert-err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626}
[data-theme="dark"] .alert-ok{background:#052e16;border-color:#14532d;color:#4ade80}
[data-theme="dark"] .alert-err{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3);color:#f87171}

/* LAYOUT */
.layout2{display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start}

/* SECTION HEADER */
.sec-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:16px;flex-wrap:wrap}
.sec-title{font-family:var(--fd);font-size:1.2rem;font-weight:600;color:var(--ink);display:flex;align-items:center;gap:10px}
.sec-title::before{content:'';width:4px;height:18px;background:var(--blue);border-radius:2px;display:block}

/* TABLE CARD */
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
.td-muted{color:var(--muted);font-size:.82rem}

.role-badge{display:inline-flex;align-items:center;gap:5px;font-family:var(--fs);font-size:.68rem;font-weight:600;padding:3px 11px;border-radius:5px;letter-spacing:.04em}
.role-admin{background:var(--blue-soft);border:1px solid rgba(26,86,219,.18);color:var(--blue)}
.role-penulis{background:rgba(22,163,74,.08);border:1px solid rgba(22,163,74,.2);color:#16a34a}
.role-pembaca{background:var(--bg2);border:1px solid var(--border);color:var(--muted)}
[data-theme="dark"] .role-admin{background:rgba(91,155,248,.15);border-color:rgba(91,155,248,.25);color:#7eb3f8}
[data-theme="dark"] .role-penulis{background:rgba(74,222,128,.08);border-color:rgba(74,222,128,.2);color:#4ade80}

.act-row{display:flex;gap:5px;align-items:center}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--border);color:var(--muted);background:transparent;transition:.14s;cursor:pointer;text-decoration:none;font-size:.78rem}
.act-btn.edit{border-color:rgba(26,86,219,.3);color:var(--blue);background:var(--blue-soft)}
.act-btn.del{border-color:rgba(220,38,38,.3);color:#dc2626;background:rgba(220,38,38,.07)}
.act-btn.edit:hover{background:rgba(26,86,219,.16);border-color:var(--blue)}
.act-btn.del:hover{background:rgba(220,38,38,.15);border-color:#dc2626}

.self-tag{font-size:.68rem;color:var(--faint);font-style:italic;font-family:var(--fd)}
.empty-row td{text-align:center;padding:52px;color:var(--faint);font-size:.9rem;font-style:italic;font-family:var(--fd)}
.empty-row td i{display:block;font-size:1.8rem;margin-bottom:10px;opacity:.2}

/* MOBILE CARD LIST */
.mobile-list{display:none;flex-direction:column}
.mobile-item{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border-lt);background:var(--card);transition:background .1s}
.mobile-item:last-child{border-bottom:none}
.mob-num{font-family:var(--fd);font-style:italic;font-size:.72rem;color:var(--faint);font-weight:400;min-width:20px;text-align:center;flex-shrink:0}
.mob-av{width:36px;height:36px;border-radius:9px;background:var(--blue-soft);border:1px solid rgba(26,86,219,.18);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;color:var(--blue);flex-shrink:0;font-family:var(--fd);font-style:italic}
.mob-body{flex:1;min-width:0}
.mob-name{font-family:var(--fs);font-size:.88rem;font-weight:500;color:var(--ink);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mob-email{font-family:var(--fs);font-size:.74rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:5px}
.mob-actions{display:flex;gap:6px;flex-shrink:0}
.mob-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:7px;border:1.5px solid;font-size:.85rem;text-decoration:none;transition:.15s}
.mob-btn.edit{border-color:rgba(26,86,219,.3);color:var(--blue);background:var(--blue-soft)}
.mob-btn.del{border-color:rgba(220,38,38,.3);color:#dc2626;background:rgba(220,38,38,.07)}
.mob-btn.edit:active{background:rgba(26,86,219,.2)}
.mob-btn.del:active{background:rgba(220,38,38,.18)}
.mobile-empty{text-align:center;padding:44px 20px;color:var(--faint);font-size:.9rem;font-family:var(--fd);font-style:italic}

/* FORM CARD */
.form-card{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;box-shadow:0 1px 4px rgba(10,15,40,.04)}
.form-card-hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.form-card-title{font-family:var(--fd);font-size:1rem;font-weight:600;color:var(--ink);display:flex;align-items:center;gap:10px}
.form-card-title::before{content:'';width:4px;height:15px;background:var(--blue);border-radius:2px;display:block}
.form-card-body{padding:20px}
.field{margin-bottom:14px}
.field label{display:block;font-size:.62rem;font-weight:600;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.16em;font-family:var(--fs)}
.field input,.field select{width:100%;background:var(--bg2);border:1px solid var(--border);color:var(--ink);border-radius:var(--r);padding:9px 12px;font-size:.86rem;font-family:var(--fs);outline:none;transition:.15s;-webkit-appearance:none;appearance:none}
.field input:focus,.field select:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft)}
.field input::placeholder{color:var(--faint)}
.field select option{background:var(--card);color:var(--ink)}
.btn-submit{width:100%;background:var(--blue);color:#fff;border:none;border-radius:var(--r);padding:10px;font-size:.84rem;font-weight:600;font-family:var(--fs);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;transition:.2s;margin-top:4px;box-shadow:0 2px 12px rgba(26,86,219,.3)}
.btn-submit:hover{background:var(--ink2);box-shadow:0 4px 20px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-submit:active{transform:translateY(0)}

/* FORM TOGGLE (mobile) */
.form-card-toggle{display:none;width:100%;background:none;border:none;border-bottom:1px solid var(--border);color:var(--ink);padding:14px 20px;font-size:.84rem;font-weight:500;font-family:var(--fs);cursor:pointer;align-items:center;gap:10px;text-align:left;transition:.15s}
.form-card-toggle:hover{background:var(--bg2)}
.form-card-toggle i.chevron{margin-left:auto;transition:transform .2s;font-size:.75rem;color:var(--faint)}
.form-card-body.collapsed{display:none}

/* MODAL EDIT ROLE */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,8,20,.65);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;padding:20px}
.modal-overlay.show{display:flex}
.modal-box{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);padding:0;width:100%;max-width:360px;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;animation:popIn .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-top{padding:28px 28px 20px;text-align:center;border-bottom:1px solid var(--border)}
.modal-ico{width:50px;height:50px;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--blue);margin:0 auto 14px}
.modal-box h3{font-family:var(--fd);font-size:1.2rem;font-weight:600;color:var(--ink);margin-bottom:5px;letter-spacing:-.01em}
.modal-box p{font-family:var(--fs);font-size:.82rem;color:var(--muted);line-height:1.65}
.modal-body{padding:20px 28px 0}
.modal-acts{display:flex}
.btn-cancel{flex:1;background:none;border:none;color:var(--muted);padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:500;cursor:pointer;transition:.15s;border-right:1px solid var(--border)}
.btn-cancel:hover{background:var(--bg);color:var(--ink)}
.btn-save{flex:1;background:var(--ink2);color:#fff;border:none;padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:600;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;gap:6px}
.btn-save:hover{background:var(--blue)}

/* RESPONSIVE */
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  .content{padding:26px 20px 56px}
  #sidebarToggle{display:flex!important}
  .layout2{grid-template-columns:1fr}
}
@media(max-width:768px){
  .table-wrap{display:none}
  .mobile-list{display:flex}
  .form-card-toggle{display:flex}
  .form-card-body.collapsed{display:none}
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
      <b>Pengguna</b>
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

  <!-- Page Header -->
  <div class="page-hd">
    <div>
      <div class="page-eyebrow">Manajemen Pengguna</div>
      <h1 class="page-title">Kelola <em>Pengguna</em></h1>
      <p class="page-sub">Tambah pengguna baru atau ubah role pengguna yang ada.</p>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($msg==='tambah'): ?>
  <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Pengguna berhasil ditambahkan.</div>
  <?php elseif ($msg==='edit'): ?>
  <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Role pengguna berhasil diperbarui.</div>
  <?php elseif ($msg==='hapus'): ?>
  <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> Pengguna berhasil dihapus.</div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-err"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php
  $users = [];
  mysqli_data_seek($userQ, 0);
  while ($u = mysqli_fetch_assoc($userQ)) $users[] = $u;
  ?>

  <div class="layout2">

    <!-- TABEL PENGGUNA -->
    <div>
      <div class="sec-hd">
        <div class="sec-title">
          Daftar Pengguna
          <span style="font-family:var(--fd);font-style:italic;font-size:.8rem;color:var(--faint);margin-left:2px"><?= count($users) ?> pengguna</span>
        </div>
      </div>

      <div class="tcard">
        <!-- Desktop Table -->
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th style="width:48px">#</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th style="width:80px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
              <tr class="empty-row">
                <td colspan="5">
                  <i class="bi bi-people"></i>
                  Belum ada pengguna.
                </td>
              </tr>
              <?php else: foreach ($users as $i => $u): $isSelf=($u['id_user']==$_SESSION['user_id']); ?>
              <tr>
                <td class="no-col"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></td>
                <td class="td-name">
                  <?= htmlspecialchars($u['username']) ?>
                  <?php if ($isSelf): ?><span class="self-tag"> (kamu)</span><?php endif; ?>
                </td>
                <td class="td-muted"><?= htmlspecialchars($u['email']??'—') ?></td>
                <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                <td>
                  <div class="act-row">
                    <?php if (!$isSelf): ?>
                    <button class="act-btn edit" title="Ubah Role"
                      onclick="openEditRole(<?= $u['id_user'] ?>,'<?= htmlspecialchars($u['username']) ?>','<?= $u['role'] ?>')">
                      <i class="bi bi-person-gear"></i>
                    </button>
                    <a href="kelola_user.php?hapus=<?= $u['id_user'] ?>" class="act-btn del" title="Hapus"
                       onclick="return confirm('Hapus pengguna <?= htmlspecialchars($u['username']) ?>?')">
                      <i class="bi bi-trash-fill"></i>
                    </a>
                    <?php else: ?>
                    <span style="font-size:.75rem;color:var(--faint)">—</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Mobile List -->
        <div class="mobile-list">
          <?php if (empty($users)): ?>
          <div class="mobile-empty">Belum ada pengguna.</div>
          <?php else: foreach ($users as $i => $u): $isSelf=($u['id_user']==$_SESSION['user_id']); ?>
          <div class="mobile-item">
            <div class="mob-num"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></div>
            <div class="mob-av"><?= strtoupper(substr($u['username'],0,1)) ?></div>
            <div class="mob-body">
              <div class="mob-name">
                <?= htmlspecialchars($u['username']) ?>
                <?php if ($isSelf): ?><span class="self-tag"> (kamu)</span><?php endif; ?>
              </div>
              <div class="mob-email"><?= htmlspecialchars($u['email']??'—') ?></div>
              <span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
            </div>
            <?php if (!$isSelf): ?>
            <div class="mob-actions">
              <button class="mob-btn edit" title="Ubah Role"
                onclick="openEditRole(<?= $u['id_user'] ?>,'<?= htmlspecialchars($u['username']) ?>','<?= $u['role'] ?>')">
                <i class="bi bi-person-gear"></i>
              </button>
              <a href="kelola_user.php?hapus=<?= $u['id_user'] ?>" class="mob-btn del" title="Hapus"
                 onclick="return confirm('Hapus pengguna <?= htmlspecialchars($u['username']) ?>?')">
                <i class="bi bi-trash-fill"></i>
              </a>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <!-- FORM TAMBAH -->
    <div class="form-card">
      <button class="form-card-toggle" id="formToggle" onclick="toggleForm()" aria-expanded="false">
        <i class="bi bi-person-plus" style="color:var(--blue)"></i>
        Tambah Pengguna Baru
        <i class="bi bi-chevron-down chevron" id="formToggleIcon"></i>
      </button>
      <div class="form-card-hd" style="display:none" id="formDesktopHd">
        <div class="form-card-title">Tambah Pengguna</div>
      </div>
      <div class="form-card-body collapsed" id="formCardBody">
        <form method="POST">
          <input type="hidden" name="aksi" value="tambah">
          <div class="field">
            <label>Username</label>
            <input type="text" name="username" placeholder="Masukkan username" required
                   value="<?= htmlspecialchars($_POST['username']??'') ?>">
          </div>
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" placeholder="contoh@email.com" required
                   value="<?= htmlspecialchars($_POST['email']??'') ?>">
          </div>
          <div class="field">
            <label>Password</label>
            <input type="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
          </div>
          <div class="field">
            <label>Role</label>
            <select name="role">
              <option value="pembaca" <?= ($_POST['role']??'')==='pembaca'?'selected':'' ?>>Pembaca</option>
              <option value="penulis" <?= ($_POST['role']??'')==='penulis'?'selected':'' ?>>Penulis</option>
              <option value="admin"   <?= ($_POST['role']??'')==='admin'?'selected':'' ?>>Admin</option>
            </select>
          </div>
          <button type="submit" class="btn-submit"><i class="bi bi-person-plus"></i> Tambah Pengguna</button>
        </form>
      </div>
    </div>

  </div>
</div>

<!-- MODAL EDIT ROLE -->
<div class="modal-overlay" id="modalEditRole">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-ico"><i class="bi bi-person-gear"></i></div>
      <h3>Ubah Role Pengguna</h3>
      <p>Pengguna: <strong id="modalUsername"></strong></p>
    </div>
    <form method="POST" id="formEditRole">
      <input type="hidden" name="aksi" value="edit_role">
      <input type="hidden" name="edit_id" id="modalEditId">
      <div class="modal-body">
        <div class="field">
          <label>Role Baru</label>
          <select name="role" id="modalRole">
            <option value="pembaca">Pembaca</option>
            <option value="penulis">Penulis</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-acts" style="margin-top:20px">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
        <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

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
      <a href="../public/logout.php" class="btn-save"><i class="bi bi-box-arrow-right"></i> Ya, Keluar</a>
    </div>
  </div>
</div>

<script>
const html=document.documentElement,thBtn=document.getElementById('themeBtn');
function applyTheme(t){html.setAttribute('data-theme',t);thBtn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click',()=>{const n=html.getAttribute('data-theme')==='dark'?'light':'dark';localStorage.setItem('pb_theme',n);applyTheme(n);});

const sidebar=document.getElementById('sidebar'),backdrop=document.getElementById('sidebarBackdrop'),toggle=document.getElementById('sidebarToggle');
const openSb=()=>{sidebar.classList.add('open');backdrop.classList.add('show');document.body.style.overflow='hidden'};
const closeSb=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');document.body.style.overflow=''};
function chk(){if(toggle)toggle.style.display=window.innerWidth<=1024?'flex':'none';}
chk();window.addEventListener('resize',()=>{chk();handleResize();});
if(toggle)toggle.addEventListener('click',e=>{e.stopPropagation();sidebar.classList.contains('open')?closeSb():openSb();});
backdrop.addEventListener('click',closeSb);

const formBody=document.getElementById('formCardBody');
const formToggleIcon=document.getElementById('formToggleIcon');
const formToggle=document.getElementById('formToggle');
const formDesktopHd=document.getElementById('formDesktopHd');
let formOpen=false;
function toggleForm(){
  formOpen=!formOpen;
  formBody.classList.toggle('collapsed',!formOpen);
  formToggleIcon.style.transform=formOpen?'rotate(180deg)':'';
  formToggle.setAttribute('aria-expanded',formOpen);
}
function handleResize(){
  if(window.innerWidth>768){
    formBody.classList.remove('collapsed');
    formToggle.style.display='none';
    formDesktopHd.style.display='flex';
  } else {
    formToggle.style.display='flex';
    formDesktopHd.style.display='none';
    if(!formOpen) formBody.classList.add('collapsed');
  }
}
handleResize();
<?php if ($error && isset($_POST['aksi']) && $_POST['aksi'] === 'tambah'): ?>
if(window.innerWidth<=768){formOpen=true;formBody.classList.remove('collapsed');formToggleIcon.style.transform='rotate(180deg)';formToggle.setAttribute('aria-expanded','true');}
<?php endif; ?>

function openEditRole(id,username,role){
  document.getElementById('modalEditId').value=id;
  document.getElementById('modalUsername').textContent=username;
  document.getElementById('modalRole').value=role;
  document.getElementById('modalEditRole').classList.add('show');
  document.body.style.overflow='hidden';
}
function closeEditModal(){document.getElementById('modalEditRole').classList.remove('show');document.body.style.overflow='';}
document.getElementById('modalEditRole').addEventListener('click',function(e){if(e.target===this)closeEditModal();});

function showLogoutModal(){document.getElementById('logoutModal').classList.add('show');document.body.style.overflow='hidden';}
function hideLogoutModal(){document.getElementById('logoutModal').classList.remove('show');document.body.style.overflow='';}
function handleOverlayClick(e){if(e.target===document.getElementById('logoutModal'))hideLogoutModal();}

document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeEditModal();hideLogoutModal();closeSb();}});
</script>
</body>
</html>