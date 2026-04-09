<?php
include 'config/koneksi.php';

$error   = '';
$success = '';
$old     = $_POST ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = isset($_POST['role']) && in_array($_POST['role'], ['admin', 'pembaca']) ? $_POST['role'] : '';

    if (empty($username)) {
        $error = "Username wajib diisi.";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter.";
    } elseif (empty($email)) {
        $error = "Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (empty($password)) {
        $error = "Password wajib diisi.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif (empty($role)) {
        $error = "Pilih salah satu role (Pembaca atau Admin).";
    } else {
        $username_esc = mysqli_real_escape_string($koneksi, $username);
        $email_esc    = mysqli_real_escape_string($koneksi, $email);

        $cekUser  = mysqli_query($koneksi, "SELECT id_user FROM user WHERE username='$username_esc'");
        $cekEmail = mysqli_query($koneksi, "SELECT id_user FROM user WHERE email='$email_esc'");

        if (!$cekUser || !$cekEmail) {
            $error = "Terjadi kesalahan sistem. Coba lagi.";
        } elseif (mysqli_num_rows($cekUser) > 0) {
            $error = "Username sudah digunakan.";
        } elseif (mysqli_num_rows($cekEmail) > 0) {
            $error = "Email sudah terdaftar.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $q = mysqli_query($koneksi, "INSERT INTO user (username, email, password, role) VALUES ('$username_esc', '$email_esc', '$hash', '$role')");
            if ($q) { $success = true; }
            else { $error = "Gagal mendaftar: " . mysqli_error($koneksi); }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<title>Daftar — LiyNews</title>
<script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,800&family=Lora:ital,wght@0,400;0,500;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════
   LIGHT THEME
══════════════════════════════════════════ */
:root {
  --panel-bg:    #1c2540;
  --panel-text:  #ffffff;
  --panel-muted: rgba(255,255,255,.50);
  --panel-faint: rgba(255,255,255,.30);

  --paper:  #fafaf7;
  --paper2: #f2f1ec;
  --card:   #ffffff;
  --ink:    #0a0f1e;
  --ink2:   #1c2540;
  --muted:  #5a6270;
  --faint:  #9a9590;
  --bdr:    #d4d0c4;
  --bdr-lt: #eae8e0;

  --blue:      #1a56db;
  --blue-d:    #1044b8;
  --blue-soft: rgba(26,86,219,.10);
  --blue-xs:   rgba(26,86,219,.05);

  --sh1: 0 1px 4px rgba(10,15,30,.07);
  --sh2: 0 6px 24px rgba(10,15,30,.12);
  --sh3: 0 20px 60px rgba(10,15,30,.18);

  --err:     #dc2626;
  --err-bg:  rgba(220,38,38,.07);
  --err-bdr: rgba(220,38,38,.22);

  --ok:      #15803d;
  --ok-bg:   rgba(22,163,74,.08);
  --ok-bdr:  rgba(22,163,74,.25);
  --ok-icon: rgba(22,163,74,.14);

  --fd: 'Playfair Display',Georgia,serif;
  --fl: 'Lora',Georgia,serif;
  --fs: 'DM Sans',system-ui,sans-serif;
  --r:  6px;
  --rl: 10px;
}

/* ══════════════════════════════════════════
   DARK THEME
══════════════════════════════════════════ */
[data-theme=dark] {
  --paper:  #080e1c;
  --paper2: #0d1428;
  --card:   #0f1a2e;
  --ink:    #e8ecf5;
  --ink2:   #c8d4ee;
  --muted:  #8a9ab8;
  --faint:  #3a4a68;
  --bdr:    #1e2d48;
  --bdr-lt: #162240;

  --blue:      #5b9bf8;
  --blue-d:    #4a8cf0;
  --blue-soft: rgba(91,155,248,.14);
  --blue-xs:   rgba(91,155,248,.07);

  --sh1: 0 1px 4px rgba(0,0,0,.40);
  --sh2: 0 6px 24px rgba(0,0,0,.50);
  --sh3: 0 20px 60px rgba(0,0,0,.65);

  --err:     #f87171;
  --err-bg:  rgba(248,113,113,.10);
  --err-bdr: rgba(248,113,113,.25);

  --ok:      #4ade80;
  --ok-bg:   rgba(74,222,128,.08);
  --ok-bdr:  rgba(74,222,128,.25);
  --ok-icon: rgba(74,222,128,.12);
}

/* ══════════════════════════════════════════
   RESET & BASE
══════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{overflow-x:hidden;-webkit-text-size-adjust:100%}
body{
  font-family:var(--fs);
  background:var(--paper);color:var(--ink);
  min-height:100vh;-webkit-tap-highlight-color:transparent;
  transition:background .3s,color .3s;
}
a{color:var(--blue);text-decoration:none}

/* ══════════════════════════════════════════
   LAYOUT
══════════════════════════════════════════ */
.split{display:grid;grid-template-columns:1fr 1fr;min-height:100vh}

/* ══════════════════════════════════════════
   LEFT PANEL
══════════════════════════════════════════ */
.panel-l{
  background:#1c2540;
  position:relative;overflow:hidden;
  display:flex;flex-direction:column;justify-content:space-between;
  padding:40px 48px;
}
.panel-l::before{
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse 80% 60% at 80% 20%, rgba(26,86,219,.22) 0%, transparent 60%),
    radial-gradient(ellipse 60% 80% at 20% 85%, rgba(91,155,248,.10) 0%, transparent 55%);
}
.panel-l::after{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.pl-top{position:relative;z-index:2;animation:fadeUp .7s ease both}
.pl-logo{font-family:var(--fd);font-size:2.4rem;font-weight:900;color:#ffffff;letter-spacing:-.04em;line-height:1;margin-bottom:4px}
.pl-logo em{color:#5b9bf8;font-style:normal}
.pl-tagline{font-family:var(--fl);font-size:.72rem;font-style:italic;color:rgba(255,255,255,.45);letter-spacing:.18em;text-transform:uppercase}
.pl-divider{width:48px;height:2px;background:#5b9bf8;border-radius:2px;margin:28px 0}
.pl-headline{font-family:var(--fd);font-size:2.05rem;font-weight:800;color:#ffffff;line-height:1.22;margin-bottom:14px;animation:fadeUp .7s .15s ease both}
.pl-desc{font-family:var(--fl);font-size:.92rem;color:rgba(255,255,255,.55);line-height:1.78;animation:fadeUp .7s .25s ease both}

/* ── STEPS ── */
.pl-steps{display:flex;flex-direction:column;gap:0;margin-top:30px;animation:fadeUp .7s .35s ease both}
.pl-step{display:flex;align-items:flex-start;gap:14px}
.pl-step-num{
  width:28px;height:28px;border-radius:50%;
  border:1.5px solid rgba(255,255,255,.20);
  display:flex;align-items:center;justify-content:center;
  font-size:.68rem;font-weight:700;color:rgba(255,255,255,.45);
  flex-shrink:0;margin-top:1px;
  transition:background .3s, border-color .3s, color .3s;
}
.pl-step.done .pl-step-num{
  background:#5b9bf8;border-color:#5b9bf8;color:#fff;
}
.pl-step-title{font-size:.84rem;font-weight:600;color:rgba(255,255,255,.78);margin-bottom:2px;transition:color .3s}
.pl-step.done .pl-step-title{color:#5b9bf8}
.pl-step-desc{font-family:var(--fl);font-size:.74rem;color:rgba(255,255,255,.38)}
.pl-step-connector{
  width:1px;height:18px;
  background:rgba(255,255,255,.10);
  margin-left:13px;
  transition:background .3s;
}
.pl-step-connector.done{background:#5b9bf8}

.pl-bottom{position:relative;z-index:2;animation:fadeUp .7s .45s ease both}
.pl-trust{display:flex;align-items:center;gap:10px;font-size:.76rem;color:rgba(255,255,255,.40)}
.pl-trust i{color:#5b9bf8}

/* ══════════════════════════════════════════
   RIGHT PANEL
══════════════════════════════════════════ */
.panel-r{
  background:var(--paper);display:flex;flex-direction:column;
  position:relative;overflow:hidden;transition:background .3s;
}
.panel-r::before{
  content:'';position:absolute;top:0;left:0;width:320px;height:320px;
  background:radial-gradient(circle, var(--blue-xs) 0%, transparent 70%);
  pointer-events:none;
}
.pr-inner{
  flex:1;display:flex;flex-direction:column;justify-content:center;
  padding:48px 52px;max-width:520px;width:100%;margin:0 auto;
  animation:fadeUp .7s .2s ease both;
}
.pr-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:36px}
.pr-back{display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--muted);transition:.15s}
.pr-back:hover{color:var(--blue)}
.pr-back i{font-size:.7rem}
.theme-btn{
  width:34px;height:34px;border-radius:8px;
  border:1.5px solid var(--bdr);background:var(--paper2);
  color:var(--muted);cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  font-size:.85rem;transition:.15s;
}
.theme-btn:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}

.pr-head{margin-bottom:26px}
.pr-eyebrow{
  font-family:var(--fl);font-size:.68rem;font-style:italic;
  color:var(--muted);letter-spacing:.18em;text-transform:uppercase;
  margin-bottom:8px;display:flex;align-items:center;gap:7px;
}
.pr-eyebrow::before{content:'';width:20px;height:1px;background:var(--bdr)}
.pr-head h1{font-family:var(--fd);font-size:2.1rem;font-weight:800;color:var(--ink);letter-spacing:-.03em;line-height:1.15;margin-bottom:6px}
.pr-head p{font-family:var(--fl);font-size:.87rem;color:var(--muted);line-height:1.6}

/* ── FORM FIELDS ── */
.field{margin-bottom:15px}
.field label{display:block;font-size:.68rem;font-weight:700;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.10em}
.input-wrap{position:relative}
.f-ic{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--faint);font-size:.85rem;pointer-events:none;transition:.2s}
.field input{
  width:100%;background:var(--paper2);border:1.5px solid var(--bdr);color:var(--ink);
  border-radius:var(--r);padding:12px 44px 12px 40px;
  font-size:.9rem;font-family:var(--fs);outline:none;transition:.2s;-webkit-appearance:none;
}
.field input::placeholder{color:var(--faint)}
.field input:focus{border-color:var(--blue);background:var(--card);box-shadow:0 0 0 3px var(--blue-soft)}
.input-wrap:focus-within .f-ic{color:var(--blue)}
.field input.err{border-color:var(--err);background:var(--err-bg)}
.field input.err:focus{box-shadow:0 0 0 3px var(--err-bdr)}
.pw-toggle{
  position:absolute;right:0;top:0;bottom:0;width:42px;
  display:flex;align-items:center;justify-content:center;
  color:var(--faint);cursor:pointer;font-size:.88rem;
  background:none;border:none;border-left:1.5px solid var(--bdr);
  border-radius:0 var(--r) var(--r) 0;transition:.15s;
}
.pw-toggle:hover{color:var(--ink)}

/* ── PASSWORD STRENGTH ── */
.pw-meter{margin-top:7px}
.pw-meter-bar{height:3px;border-radius:2px;background:var(--bdr);overflow:hidden}
.pw-meter-fill{height:100%;width:0;border-radius:2px;transition:width .3s,background .3s}
.pw-meter-row{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
.pw-hint{font-size:.7rem;color:var(--muted)}
.pw-strength{font-size:.68rem;font-weight:600}

/* ── ROLE CARDS ── */
.role-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px}
.role-opt input{display:none}
.role-card{
  display:flex;align-items:center;gap:10px;padding:12px 14px;
  border:1.5px solid var(--bdr);border-radius:var(--r);cursor:pointer;
  transition:.15s;background:var(--paper2);
  -webkit-user-select:none;user-select:none;
}
.role-card-name{font-size:.83rem;font-weight:600;color:var(--ink);margin-bottom:1px;transition:.15s}
.role-card-desc{font-size:.67rem;color:var(--muted)}
.role-card i{font-size:1.1rem;flex-shrink:0;transition:.15s;color:var(--blue)}
.role-opt input:checked + .role-card{border-color:var(--blue);background:var(--blue-xs)}
.role-opt input:checked + .role-card .role-card-name{color:var(--blue)}
.role-opt input:checked + .role-card i{color:var(--blue)}
.role-grid.err-role .role-card{border-color:var(--err-bdr)}

/* ── ALERTS ── */
.alert-err{
  background:var(--err-bg);border:1.5px solid var(--err-bdr);color:var(--err);
  border-radius:var(--r);padding:11px 14px;font-size:.82rem;
  margin-bottom:18px;display:flex;align-items:center;gap:8px;
  animation:shake .4s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-4px)}40%,80%{transform:translateX(4px)}}

.alert-ok{
  background:var(--ok-bg);border:1.5px solid var(--ok-bdr);
  border-radius:var(--rl);padding:28px;text-align:center;
}
.alert-ok-icon{
  width:56px;height:56px;border-radius:50%;
  background:var(--ok-icon);border:2px solid var(--ok-bdr);
  display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;color:var(--ok);margin:0 auto 14px;
}
.alert-ok strong{font-family:var(--fd);font-size:1.1rem;display:block;margin-bottom:5px;color:var(--ink)}
.alert-ok span{font-family:var(--fl);font-size:.83rem;color:var(--muted)}

/* ── BUTTONS ── */
.btn-submit{
  width:100%;
  background:var(--blue);        /* ← FIX: pakai --blue agar konsisten di semua tema */
  color:#ffffff;
  border:none;border-radius:var(--r);
  padding:14px;font-size:.92rem;font-weight:600;font-family:var(--fs);
  cursor:pointer;transition:.2s;
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:6px;position:relative;overflow:hidden;
}
.btn-submit::before{content:'';position:absolute;inset:0;background:var(--blue-d);opacity:0;transition:.2s}
.btn-submit:hover::before{opacity:1}
.btn-submit:hover{color:#ffffff}
.btn-submit span,.btn-submit i{position:relative;z-index:1;color:#ffffff}
.btn-submit:active{transform:scale(.98)}

.btn-login{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;background:var(--blue);color:#ffffff !important;
  border:none;border-radius:var(--r);
  padding:14px;font-size:.92rem;font-weight:600;
  cursor:pointer;transition:.15s;margin-top:16px;
  text-decoration:none;font-family:var(--fs);
}
.btn-login:hover{background:var(--blue-d)}

.divider-or{
  display:flex;align-items:center;gap:12px;
  margin:18px 0;color:var(--faint);
  font-family:var(--fl);font-size:.76rem;font-style:italic;
}
.divider-or::before,.divider-or::after{content:'';flex:1;height:1px;background:var(--bdr)}

.link-login{text-align:center;font-size:.83rem;color:var(--muted)}
.link-login a{color:var(--blue);font-weight:600}
.link-login a:hover{color:var(--blue-d)}

/* ── FOOTER ── */
.pr-footer{
  padding:0 52px 28px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  animation:fadeUp .7s .4s ease both;
}
.pr-footer-copy{font-size:.68rem;color:var(--faint)}
.pr-footer-links{display:flex;gap:14px}
.pr-footer-links a{font-size:.68rem;color:var(--faint);transition:.15s}
.pr-footer-links a:hover{color:var(--blue)}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}

/* ── RESPONSIVE ── */
@media(max-width:960px){.split{grid-template-columns:1fr}.panel-l{display:none}}
@media(max-width:580px){.pr-inner{padding:36px 24px}.pr-footer{padding:0 24px 24px}.pr-head h1{font-size:1.75rem}}
@media(max-width:380px){.pr-inner{padding:28px 18px}.pr-footer{padding:0 18px 20px}.role-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<div class="split">

  <!-- ── LEFT PANEL ── -->
  <div class="panel-l">
    <div class="pl-top">
      <div class="pl-logo">Liy<em>News</em></div>
      <div class="pl-tagline">Berita Terpercaya Indonesia</div>
      <div class="pl-divider"></div>
      <div class="pl-headline">Mulai perjalananmu bersama kami.</div>
      <div class="pl-desc">Buat akun gratis dan nikmati akses penuh ke ribuan artikel berita terpercaya dari seluruh Indonesia.</div>
      <div class="pl-steps">
        <div class="pl-step" id="step1">
          <div class="pl-step-num" id="step1-num">1</div>
          <div class="pl-step-body">
            <div class="pl-step-title">Isi data diri</div>
            <div class="pl-step-desc">Username, email, dan password</div>
          </div>
        </div>
        <div class="pl-step-connector" id="conn1"></div>
        <div class="pl-step" id="step2">
          <div class="pl-step-num" id="step2-num">2</div>
          <div class="pl-step-body">
            <div class="pl-step-title">Pilih role akun</div>
            <div class="pl-step-desc">Pembaca atau Admin</div>
          </div>
        </div>
        <div class="pl-step-connector" id="conn2"></div>
        <div class="pl-step" id="step3">
          <div class="pl-step-num" id="step3-num">3</div>
          <div class="pl-step-body">
            <div class="pl-step-title">Mulai membaca</div>
            <div class="pl-step-desc">Akses semua konten LiyNews</div>
          </div>
        </div>
      </div>
    </div>
    <div class="pl-bottom">
      <div class="pl-trust">
        <i class="bi bi-patch-check-fill"></i>
        Data kamu aman &amp; terenkripsi. Kami tidak pernah membagikan data pribadi.
      </div>
    </div>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="panel-r">
    <div class="pr-inner">
      <div class="pr-top">
        <a href="public/index.php" class="pr-back"><i class="bi bi-arrow-left"></i> Ke Beranda</a>
        <button class="theme-btn" id="themeBtn" aria-label="Ganti tema"><i class="bi bi-moon-fill"></i></button>
      </div>

      <div class="pr-head">
        <div class="pr-eyebrow">Bergabung Gratis</div>
        <h1>Buat <em style="font-style:italic;color:var(--blue)">Akun</em><br>Baru</h1>
        <p>Daftar dalam hitungan detik, mulai membaca sekarang.</p>
      </div>

      <?php if ($error): ?>
      <div class="alert-err" role="alert">
        <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="alert-ok">
        <div class="alert-ok-icon"><i class="bi bi-check-lg"></i></div>
        <strong>Pendaftaran Berhasil!</strong>
        <span>Akun kamu sudah dibuat dan siap digunakan.</span>
      </div>
      <a href="login.php" class="btn-login"><i class="bi bi-box-arrow-in-right"></i> Masuk Sekarang</a>

      <?php else: ?>
      <?php
        $errUser  = $error && (str_contains($error,'Username') || (str_contains($error,'wajib') && empty($old['username'] ?? '')));
        $errEmail = $error && (str_contains($error,'Email') || str_contains($error,'email'));
        $errPass  = $error && (str_contains($error,'Password') || str_contains($error,'karakter'));
        $errRole  = $error && str_contains($error,'role');
      ?>
      <form method="POST" autocomplete="off" novalidate>

        <div class="field">
          <label for="reg-username">Username</label>
          <div class="input-wrap">
            <i class="bi bi-person f-ic"></i>
            <input type="text" id="reg-username" name="username"
                   placeholder="Minimal 3 karakter"
                   value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
                   required minlength="3" maxlength="50" autofocus autocomplete="username"
                   <?php if ($errUser): ?>class="err"<?php endif; ?>>
          </div>
        </div>

        <div class="field">
          <label for="reg-email">Email</label>
          <div class="input-wrap">
            <i class="bi bi-envelope f-ic"></i>
            <input type="email" id="reg-email" name="email"
                   placeholder="contoh@email.com"
                   value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                   required maxlength="100" autocomplete="email" inputmode="email"
                   <?php if ($errEmail): ?>class="err"<?php endif; ?>>
          </div>
        </div>

        <div class="field">
          <label for="reg-pw">Password</label>
          <div class="input-wrap">
            <i class="bi bi-lock f-ic"></i>
            <input type="password" id="reg-pw" name="password"
                   placeholder="Minimal 6 karakter"
                   required minlength="6" autocomplete="new-password"
                   <?php if ($errPass): ?>class="err"<?php endif; ?>>
            <button type="button" class="pw-toggle" id="pwEye" aria-label="Tampilkan password">
              <i class="bi bi-eye" id="pwIcon"></i>
            </button>
          </div>
          <div class="pw-meter">
            <div class="pw-meter-bar"><div class="pw-meter-fill" id="sFill"></div></div>
            <div class="pw-meter-row">
              <div class="pw-hint" id="sText">Minimal 6 karakter</div>
              <div class="pw-strength" id="sLabel"></div>
            </div>
          </div>
        </div>

        <div class="field">
          <label>Daftar Sebagai</label>
          <div class="role-grid <?php echo $errRole ? 'err-role' : ''; ?>">
            <label class="role-opt">
              <input type="radio" name="role" value="pembaca"
                     <?php echo ($old['role'] ?? '') === 'pembaca' ? 'checked' : ''; ?> required>
              <div class="role-card">
                <i class="bi bi-person-circle"></i>
                <div class="role-card-info">
                  <div class="role-card-name">Pembaca</div>
                  <div class="role-card-desc">Baca &amp; simpan berita</div>
                </div>
              </div>
            </label>
            <label class="role-opt">
              <input type="radio" name="role" value="admin"
                     <?php echo ($old['role'] ?? '') === 'admin' ? 'checked' : ''; ?>>
              <div class="role-card">
                <i class="bi bi-shield-check"></i>
                <div class="role-card-info">
                  <div class="role-card-name">Admin</div>
                  <div class="role-card-desc">Kelola konten &amp; user</div>
                </div>
              </div>
            </label>
          </div>
          <?php if ($errRole): ?>
          <div style="font-size:.72rem;color:var(--err);margin-top:6px"><i class="bi bi-exclamation-circle"></i> Pilih salah satu role.</div>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">
          <i class="bi bi-person-plus"></i>
          <span>Daftar Sekarang</span>
        </button>
      </form>
      <?php endif; ?>

      <?php if (!$success): ?>
      <div class="divider-or">atau</div>
      <div class="link-login">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
      <?php endif; ?>
    </div>

    <div class="pr-footer">
      <div class="pr-footer-copy">&copy; <?php echo date('Y'); ?> LiyNews</div>
      <div class="pr-footer-links">
        <a href="#">Privasi</a>
        <a href="#">Syarat</a>
        <a href="#">Bantuan</a>
      </div>
    </div>
  </div>

</div>

<script>
/* ── Theme ── */
const html = document.documentElement;
const thBtn = document.getElementById('themeBtn');
function applyTheme(t) {
  html.setAttribute('data-theme', t);
  thBtn.innerHTML = t === 'dark'
    ? '<i class="bi bi-sun-fill"></i>'
    : '<i class="bi bi-moon-fill"></i>';
}
applyTheme(html.getAttribute('data-theme'));
thBtn.addEventListener('click', function() {
  const n = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  localStorage.setItem('pb_theme', n);
  applyTheme(n);
});

/* ── Password toggle ── */
document.getElementById('pwEye').addEventListener('click', function() {
  const i = document.getElementById('reg-pw');
  const ic = document.getElementById('pwIcon');
  i.type = i.type === 'password' ? 'text' : 'password';
  ic.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

/* ── Password strength ── */
document.getElementById('reg-pw').addEventListener('input', function() {
  const v = this.value;
  let s = 0;
  if (v.length >= 6) s++;
  if (v.length >= 10) s++;
  if (v.length >= 14) s++;
  if (/[A-Z]/.test(v)) s++;
  if (/[0-9]/.test(v)) s++;
  if (/[^a-zA-Z0-9]/.test(v)) s++;
  s = Math.min(s, 5);
  const colors = ['#3a4a68','#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
  const labels = ['','Sangat Lemah','Lemah','Cukup','Kuat','Sangat Kuat'];
  document.getElementById('sFill').style.width = ((s / 5) * 100) + '%';
  document.getElementById('sFill').style.background = colors[s];
  document.getElementById('sText').textContent = v ? 'Kekuatan password' : 'Minimal 6 karakter';
  document.getElementById('sLabel').textContent = v ? labels[s] : '';
  document.getElementById('sLabel').style.color = colors[s];
  this.classList.remove('err');
  updateSteps();
});

/* ── Dynamic step highlight ── */
function setStep(el, numEl, connEl, active) {
  if (active) {
    el.classList.add('done');
    numEl.innerHTML = '<i class="bi bi-check" style="font-size:.65rem"></i>';
    if (connEl) connEl.classList.add('done');
  } else {
    el.classList.remove('done');
    numEl.textContent = numEl.dataset.num;
    if (connEl) connEl.classList.remove('done');
  }
}

const step1El    = document.getElementById('step1');
const step1Num   = document.getElementById('step1-num');
const step2El    = document.getElementById('step2');
const step2Num   = document.getElementById('step2-num');
const step3El    = document.getElementById('step3');
const step3Num   = document.getElementById('step3-num');
const conn1      = document.getElementById('conn1');
const conn2      = document.getElementById('conn2');

step1Num.dataset.num = '1';
step2Num.dataset.num = '2';
step3Num.dataset.num = '3';

function updateSteps() {
  const uname  = document.getElementById('reg-username').value.trim();
  const email  = document.getElementById('reg-email').value.trim();
  const pass   = document.getElementById('reg-pw').value;
  const role   = document.querySelector('input[name="role"]:checked');

  const dataOk = uname.length >= 3 && email.length > 0 && pass.length >= 6;
  const roleOk = !!role;

  const anyInput = uname.length > 0 || email.length > 0 || pass.length > 0;
  setStep(step1El, step1Num, null, anyInput);
  setStep(step2El, step2Num, conn1, dataOk);
  setStep(step3El, step3Num, conn2, dataOk && roleOk);
}

['reg-username', 'reg-email', 'reg-pw'].forEach(function(id) {
  document.getElementById(id).addEventListener('input', updateSteps);
});
document.querySelectorAll('input[name="role"]').forEach(function(el) {
  el.addEventListener('change', updateSteps);
});

/* ── Clear errors ── */
document.getElementById('reg-username').addEventListener('input', function() { this.classList.remove('err'); });
document.getElementById('reg-email').addEventListener('input', function() { this.classList.remove('err'); });
document.querySelectorAll('input[name="role"]').forEach(function(el) {
  el.addEventListener('change', function() { document.querySelector('.role-grid').classList.remove('err-role'); });
});

updateSteps();
</script>
</body>
</html>