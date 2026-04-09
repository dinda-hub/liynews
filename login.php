<?php
session_start();
include 'config/koneksi.php';

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi.";
    } else {
        $username_esc = mysqli_real_escape_string($koneksi, $username);
        $query = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username_esc' LIMIT 1");

        if ($query && mysqli_num_rows($query) > 0) {
            $data = mysqli_fetch_assoc($query);
            if (password_verify($password, $data['password'])) {
                $_SESSION['user_login']    = true;
                $_SESSION['user_username'] = $data['username'];
                $_SESSION['user_nama']     = $data['nama_lengkap'];
                $_SESSION['user_role']     = $data['role'];
                $_SESSION['user_id']       = $data['id_user'];
                ob_clean();
                if ($data['role'] == 'admin') {
                    header("Location: admin/dashboardadmin.php"); exit;
                } else {
                    header("Location: pembaca/dashboardpembaca.php"); exit;
                }
            } else {
                $error = "Password salah.";
            }
        } else {
            $error = "Username tidak ditemukan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover">
<title>Masuk — LiyNews</title>
<script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,800&family=Lora:ital,wght@0,400;0,500;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════
   LIGHT THEME
══════════════════════════════════════════ */
:root {
  --panel-bg:     #1c2540;
  --panel-text:   #ffffff;
  --panel-muted:  rgba(255,255,255,.50);
  --panel-faint:  rgba(255,255,255,.30);

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
  --gold:      #c9a227;
  --gold-soft: rgba(201,162,39,.12);

  --sh1: 0 1px 4px rgba(10,15,30,.07);
  --sh2: 0 6px 24px rgba(10,15,30,.12);
  --sh3: 0 20px 60px rgba(10,15,30,.18);

  --err:       #dc2626;
  --err-bg:    rgba(220,38,38,.07);
  --err-bdr:   rgba(220,38,38,.22);

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
  --gold:      #d4ae3a;
  --gold-soft: rgba(212,174,58,.10);

  --sh1: 0 1px 4px rgba(0,0,0,.40);
  --sh2: 0 6px 24px rgba(0,0,0,.50);
  --sh3: 0 20px 60px rgba(0,0,0,.65);

  --err:     #f87171;
  --err-bg:  rgba(248,113,113,.10);
  --err-bdr: rgba(248,113,113,.25);
}

/* ══════════════════════════════════════════
   RESET & BASE
══════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{overflow-x:hidden;-webkit-text-size-adjust:100%}
body{
  font-family:var(--fs);
  background:var(--paper);
  color:var(--ink);
  min-height:100vh;
  -webkit-tap-highlight-color:transparent;
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
  background:var(--panel-bg);
  position:relative;overflow:hidden;
  display:flex;flex-direction:column;justify-content:space-between;
  padding:40px 48px;
}
.panel-l::before{
  content:'';position:absolute;inset:0;
  background:
    radial-gradient(ellipse 80% 60% at 20% 30%, rgba(26,86,219,.28) 0%, transparent 60%),
    radial-gradient(ellipse 60% 80% at 80% 80%, rgba(201,162,39,.14) 0%, transparent 55%);
}
.panel-l::after{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.pl-top{position:relative;z-index:2;animation:fadeUp .7s ease both}
.pl-logo{
  font-family:var(--fd);font-size:2.4rem;font-weight:900;
  color:#ffffff;letter-spacing:-.04em;line-height:1;margin-bottom:4px;
}
.pl-logo em{color:#5b9bf8;font-style:normal}
.pl-tagline{
  font-family:var(--fl);font-size:.72rem;font-style:italic;
  color:rgba(255,255,255,.45);letter-spacing:.18em;text-transform:uppercase;
}
.pl-divider{width:48px;height:2px;background:#5b9bf8;border-radius:2px;margin:28px 0}
.pl-headline{
  font-family:var(--fd);font-size:2.05rem;font-weight:800;
  color:#ffffff;line-height:1.22;margin-bottom:14px;
  animation:fadeUp .7s .15s ease both;
}
.pl-desc{
  font-family:var(--fl);font-size:.92rem;
  color:rgba(255,255,255,.55);line-height:1.78;
  animation:fadeUp .7s .25s ease both;
}
.pl-features{display:flex;flex-direction:column;gap:11px;margin-top:28px;animation:fadeUp .7s .35s ease both}
.pl-feat{display:flex;align-items:center;gap:11px;font-size:.83rem;color:rgba(255,255,255,.60)}
.pl-feat i{
  width:28px;height:28px;border-radius:6px;
  background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
  display:flex;align-items:center;justify-content:center;
  font-size:.82rem;color:#5b9bf8;flex-shrink:0;
}
.pl-bottom{position:relative;z-index:2;animation:fadeUp .7s .45s ease both}
.pl-quote{
  font-family:var(--fl);font-size:.8rem;font-style:italic;
  color:rgba(255,255,255,.35);line-height:1.7;
  padding-left:14px;border-left:2px solid #c9a227;
}
.pl-dots{display:flex;gap:6px;margin-top:20px}
.pl-dot{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.18)}
.pl-dot.on{background:#5b9bf8;width:20px;border-radius:3px}

/* ══════════════════════════════════════════
   RIGHT PANEL
══════════════════════════════════════════ */
.panel-r{
  background:var(--paper);
  display:flex;flex-direction:column;
  position:relative;overflow:hidden;
  transition:background .3s;
}
.panel-r::before{
  content:'';position:absolute;top:0;right:0;width:340px;height:340px;
  background:radial-gradient(circle, var(--blue-xs) 0%, transparent 70%);
  pointer-events:none;
}
.pr-inner{
  flex:1;display:flex;flex-direction:column;justify-content:center;
  padding:48px 52px;max-width:500px;width:100%;margin:0 auto;
  animation:fadeUp .7s .2s ease both;
}
.pr-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:40px}
.pr-back{
  display:flex;align-items:center;gap:6px;
  font-size:.78rem;color:var(--muted);transition:.15s;
}
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

.pr-head{margin-bottom:30px}
.pr-eyebrow{
  font-size:.68rem;font-style:italic;
  color:var(--muted);letter-spacing:.18em;text-transform:uppercase;
  margin-bottom:8px;display:flex;align-items:center;gap:7px;
}
.pr-eyebrow::before{content:'';width:20px;height:1px;background:var(--bdr)}
.pr-head h1{
  font-family:var(--fd);font-size:2.1rem;font-weight:800;
  color:var(--ink);letter-spacing:-.03em;line-height:1.15;margin-bottom:6px;
}
.pr-head p{font-family:var(--fl);font-size:.87rem;color:var(--muted);line-height:1.6}

/* ── FORM FIELDS ── */
.field{margin-bottom:18px}
.field label{
  display:block;font-size:.68rem;font-weight:700;
  color:var(--muted);margin-bottom:7px;
  text-transform:uppercase;letter-spacing:.10em;
}
.input-wrap{position:relative}
.f-ic{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:var(--faint);font-size:.85rem;pointer-events:none;transition:.2s;
}
.field input{
  width:100%;
  background:var(--paper2);
  border:1.5px solid var(--bdr);
  color:var(--ink);
  border-radius:var(--r);
  padding:12px 44px 12px 40px;
  font-size:.9rem;font-family:var(--fs);
  outline:none;transition:.2s;-webkit-appearance:none;
}
.field input::placeholder{color:var(--faint)}
.field input:focus{
  border-color:var(--blue);
  background:var(--card);
  box-shadow:0 0 0 3px var(--blue-soft);
}
.field input:focus + .f-ic,
.input-wrap:focus-within .f-ic{color:var(--blue)}
.field input.err{border-color:var(--err);background:var(--err-bg)}
.field input.err:focus{box-shadow:0 0 0 3px var(--err-bdr)}

.pw-toggle{
  position:absolute;right:0;top:0;bottom:0;width:42px;
  display:flex;align-items:center;justify-content:center;
  color:var(--faint);cursor:pointer;font-size:.88rem;
  background:none;border:none;
  border-left:1.5px solid var(--bdr);
  border-radius:0 var(--r) var(--r) 0;transition:.15s;
}
.pw-toggle:hover{color:var(--ink)}

/* ── ALERT ERROR ── */
.alert-err{
  background:var(--err-bg);
  border:1.5px solid var(--err-bdr);
  color:var(--err);
  border-radius:var(--r);
  padding:11px 14px;font-size:.82rem;
  margin-bottom:20px;
  display:flex;align-items:center;gap:8px;
  animation:shake .4s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-4px)}40%,80%{transform:translateX(4px)}}

/* ── REMEMBER ROW ── */
.remember-row{display:flex;align-items:center;gap:9px;margin:2px 0 20px}
.toggle-check{position:relative;width:36px;height:20px;flex-shrink:0}
.toggle-check input{opacity:0;width:0;height:0;position:absolute}
.toggle-slider{
  position:absolute;inset:0;border-radius:20px;
  background:var(--bdr);cursor:pointer;transition:.2s;
}
.toggle-slider::after{
  content:'';position:absolute;left:3px;top:3px;
  width:14px;height:14px;border-radius:50%;
  background:#fff;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25);
}
.toggle-check input:checked + .toggle-slider{background:var(--blue)}
.toggle-check input:checked + .toggle-slider::after{left:19px}
.remember-lbl{font-size:.82rem;color:var(--muted);cursor:pointer;user-select:none}

/* ── SUBMIT BUTTON ── */
.btn-submit{
  width:100%;
  background:var(--blue);        /* ← FIX: pakai --blue agar konsisten di semua tema */
  color:#ffffff;
  border:none;border-radius:var(--r);
  padding:14px;font-size:.92rem;font-weight:600;font-family:var(--fs);
  cursor:pointer;transition:.2s;
  display:flex;align-items:center;justify-content:center;gap:8px;
  position:relative;overflow:hidden;
}
.btn-submit::before{
  content:'';position:absolute;inset:0;
  background:var(--blue-d);opacity:0;transition:.2s;
}
.btn-submit:hover::before{opacity:1}
.btn-submit:hover{color:#ffffff}
.btn-submit span,.btn-submit i{position:relative;z-index:1;color:#ffffff}
.btn-submit:active{transform:scale(.98)}

/* ── DIVIDER ── */
.divider-or{
  display:flex;align-items:center;gap:12px;
  margin:20px 0;color:var(--faint);
  font-family:var(--fl);font-size:.76rem;font-style:italic;
}
.divider-or::before,.divider-or::after{content:'';flex:1;height:1px;background:var(--bdr)}

.link-register{text-align:center;font-size:.83rem;color:var(--muted)}
.link-register a{color:var(--blue);font-weight:600}
.link-register a:hover{color:var(--blue-d)}

/* ══════════════════════════════════════════
   AUTOCOMPLETE DROPDOWN
══════════════════════════════════════════ */
.ac-trigger{
  position:absolute;right:0;top:0;bottom:0;width:42px;
  display:flex;align-items:center;justify-content:center;
  color:var(--faint);cursor:pointer;font-size:.75rem;
  background:none;border:none;
  border-left:1.5px solid var(--bdr);
  border-radius:0 var(--r) var(--r) 0;transition:.15s;
}
.ac-trigger:hover{color:var(--blue)}
.ac-trigger i{transition:transform .2s}
.ac-trigger.open i{transform:rotate(180deg)}
.ac-dd{
  display:none;position:absolute;
  top:calc(100% + 7px);left:0;right:0;
  background:var(--card);
  border:1.5px solid var(--bdr);
  border-radius:var(--rl);
  box-shadow:var(--sh3);z-index:600;overflow:hidden;
  animation:slideDown .15s ease;
  max-height:min(300px,50vh);overflow-y:auto;
}
.ac-dd.open{display:block}
@keyframes slideDown{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.ac-hdr{
  display:flex;align-items:center;justify-content:space-between;
  padding:8px 13px;
  font-size:.64rem;font-weight:700;color:var(--muted);
  text-transform:uppercase;letter-spacing:.1em;
  border-bottom:1px solid var(--bdr);
  position:sticky;top:0;background:var(--card);z-index:1;
}
.ac-clr{
  background:none;border:none;color:var(--muted);
  cursor:pointer;font-size:.68rem;padding:3px 8px;
  border-radius:4px;font-family:var(--fs);transition:.12s;
}
.ac-clr:hover{color:var(--err)}
.ac-item{
  display:flex;align-items:center;gap:10px;
  padding:10px 13px;cursor:pointer;transition:.12s;
  border-bottom:1px solid var(--bdr-lt);
}
.ac-item:last-child{border-bottom:none}
.ac-item:hover{background:var(--paper2)}
.ac-av{
  width:30px;height:30px;border-radius:6px;
  background:var(--blue);color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-size:.68rem;font-weight:700;flex-shrink:0;
}
.ac-info{flex:1;min-width:0}
.ac-uname{font-size:.83rem;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ac-meta{font-size:.67rem;color:var(--muted);margin-top:1px}
.ac-del{
  background:none;border:none;color:var(--faint);
  cursor:pointer;width:26px;height:26px;
  border-radius:4px;display:flex;align-items:center;justify-content:center;
  font-size:.75rem;transition:.12s;flex-shrink:0;
}
.ac-del:hover{color:var(--err);background:var(--err-bg)}
.ac-empty{padding:20px 13px;text-align:center;font-size:.8rem;color:var(--muted)}
.ac-confirm{
  padding:10px 13px;
  display:flex;align-items:center;justify-content:space-between;gap:8px;
  background:var(--err-bg);font-size:.78rem;color:var(--ink);
  flex-wrap:wrap;border-top:1px solid var(--bdr);
}
.ac-yes{
  background:var(--err);color:#fff;border:none;
  border-radius:4px;padding:5px 12px;
  font-size:.72rem;cursor:pointer;font-family:var(--fs);
}
.ac-no{
  background:none;border:1px solid var(--bdr);
  color:var(--muted);border-radius:4px;padding:5px 12px;
  font-size:.72rem;cursor:pointer;font-family:var(--fs);
}

/* ══════════════════════════════════════════
   FOOTER
══════════════════════════════════════════ */
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

/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
@media(max-width:960px){
  .split{grid-template-columns:1fr}
  .panel-l{display:none}
}
@media(max-width:580px){
  .pr-inner{padding:36px 24px}
  .pr-footer{padding:0 24px 24px}
  .pr-head h1{font-size:1.75rem}
}
@media(max-width:380px){
  .pr-inner{padding:28px 18px}
  .pr-footer{padding:0 18px 20px}
}
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
      <div class="pl-headline">Dunia dalam genggamanmu, setiap hari.</div>
      <div class="pl-desc">Bergabunglah dengan jutaan pembaca yang mendapatkan berita akurat, mendalam, dan terpercaya langsung dari redaksi kami.</div>
      <div class="pl-features">
        <div class="pl-feat"><i class="bi bi-lightning-charge-fill"></i> Berita real-time dari seluruh Indonesia</div>
        <div class="pl-feat"><i class="bi bi-patch-check-fill"></i> Terverifikasi &amp; bebas hoaks</div>
        <div class="pl-feat"><i class="bi bi-bookmark-heart-fill"></i> Simpan &amp; baca kapan saja</div>
      </div>
    </div>
    <div class="pl-bottom">
      <div class="pl-quote">"Jurnalisme bukan hanya profesi — ini adalah tanggung jawab kepada kebenaran dan masyarakat."</div>
      <div class="pl-dots">
        <div class="pl-dot on"></div>
        <div class="pl-dot"></div>
        <div class="pl-dot"></div>
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
        <div class="pr-eyebrow">Portal Anggota</div>
        <h1>Selamat<br><em style="font-style:italic;color:var(--blue)">Datang Kembali</em></h1>
        <p>Masuk untuk melanjutkan membaca berita terpercaya.</p>
      </div>

      <?php if ($error): ?>
      <div class="alert-err" role="alert">
        <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <form method="POST" autocomplete="off" id="loginForm" novalidate>

        <div class="field" id="usernameField">
          <label for="usernameInput">Username</label>
          <div class="input-wrap">
            <i class="bi bi-person f-ic"></i>
            <input type="text" name="username" id="usernameInput"
                   placeholder="Masukkan username"
                   value="<?php echo htmlspecialchars($username); ?>"
                   required autofocus autocomplete="off" inputmode="text"
                   <?php if ($error && (str_contains($error,'Username') || str_contains($error,'wajib'))): ?>class="err"<?php endif; ?>>
            <button type="button" class="ac-trigger" id="acTrigger" aria-label="Akun tersimpan" aria-expanded="false">
              <i class="bi bi-chevron-down"></i>
            </button>
            <div class="ac-dd" id="acDropdown" role="listbox">
              <div class="ac-hdr">
                <span><i class="bi bi-person-check"></i> Tersimpan</span>
                <button type="button" class="ac-clr" id="clearAllBtn"><i class="bi bi-trash"></i> Hapus semua</button>
              </div>
              <div id="acList"></div>
              <div id="acConfirm" class="ac-confirm" style="display:none">
                <span>Hapus semua akun?</span>
                <div style="display:flex;gap:6px">
                  <button type="button" class="ac-yes" id="confirmYes">Ya</button>
                  <button type="button" class="ac-no" id="confirmNo">Batal</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="field">
          <label for="pwInput">Password</label>
          <div class="input-wrap">
            <i class="bi bi-lock f-ic"></i>
            <input type="password" name="password" id="pwInput"
                   placeholder="Masukkan password"
                   required autocomplete="current-password"
                   <?php if ($error && str_contains($error,'Password')): ?>class="err"<?php endif; ?>>
            <button type="button" class="pw-toggle" id="pwEye" aria-label="Tampilkan password">
              <i class="bi bi-eye" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <div class="remember-row">
          <label class="toggle-check">
            <input type="checkbox" id="rememberMe" checked>
            <span class="toggle-slider"></span>
          </label>
          <label class="remember-lbl" for="rememberMe">Simpan akun ini</label>
        </div>

        <button type="submit" class="btn-submit">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Masuk Sekarang</span>
        </button>
      </form>

      <div class="divider-or">atau</div>
      <div class="link-register">Belum punya akun? <a href="register.php">Daftar gratis</a></div>
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
  const i = document.getElementById('pwInput');
  const ic = document.getElementById('pwIcon');
  i.type = i.type === 'password' ? 'text' : 'password';
  ic.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

/* ── Saved accounts ── */
const STORAGE_KEY = 'pb_accounts';
const usernameInput = document.getElementById('usernameInput');
const dropdown = document.getElementById('acDropdown');
const acList = document.getElementById('acList');
const acConfirm = document.getElementById('acConfirm');
const acTrigger = document.getElementById('acTrigger');

function getAccounts() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
  catch(e) { return []; }
}
function saveAccounts(arr) { localStorage.setItem(STORAGE_KEY, JSON.stringify(arr)); }
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function timeAgo(ts) {
  if (!ts) return '';
  const d = Date.now() - ts, m = Math.floor(d / 60000);
  if (m < 1) return 'Baru saja';
  if (m < 60) return m + ' menit lalu';
  const h = Math.floor(m / 60);
  if (h < 24) return h + ' jam lalu';
  return Math.floor(h / 24) + ' hari lalu';
}

function renderAccounts(filter) {
  acConfirm.style.display = 'none';
  let accounts = getAccounts();
  if (filter) accounts = accounts.filter(a => a.username.toLowerCase().startsWith(filter.toLowerCase()));
  if (accounts.length === 0) {
    acList.innerHTML = '<div class="ac-empty"><i class="bi bi-person-x" style="display:block;font-size:1.1rem;margin-bottom:5px;opacity:.35"></i>Tidak ada akun tersimpan</div>';
    return;
  }
  acList.innerHTML = accounts.map(acc =>
    `<div class="ac-item" role="option" data-username="${escHtml(acc.username)}" tabindex="-1">
      <div class="ac-av">${escHtml(acc.username.substring(0,2).toUpperCase())}</div>
      <div class="ac-info">
        <div class="ac-uname">${escHtml(acc.username)}</div>
        <div class="ac-meta"><i class="bi bi-clock"></i> ${timeAgo(acc.lastLogin)}</div>
      </div>
      <button type="button" class="ac-del" data-del="${escHtml(acc.username)}" aria-label="Hapus"><i class="bi bi-x-lg"></i></button>
    </div>`
  ).join('');
  acList.querySelectorAll('.ac-item').forEach(el => {
    el.addEventListener('mousedown', function(e) {
      if (e.target.closest('.ac-del')) return;
      e.preventDefault();
      usernameInput.value = this.dataset.username;
      closeDropdown();
      document.getElementById('pwInput').focus();
    });
  });
  acList.querySelectorAll('.ac-del').forEach(btn => {
    btn.addEventListener('mousedown', function(e) {
      e.preventDefault(); e.stopPropagation();
      saveAccounts(getAccounts().filter(a => a.username !== this.dataset.del));
      renderAccounts(usernameInput.value);
      if (getAccounts().length === 0) closeDropdown();
    });
  });
}

function openDropdown() {
  if (getAccounts().length === 0) return;
  renderAccounts(usernameInput.value);
  dropdown.classList.add('open');
  acTrigger.classList.add('open');
  acTrigger.setAttribute('aria-expanded', 'true');
}
function closeDropdown() {
  dropdown.classList.remove('open');
  acTrigger.classList.remove('open');
  acTrigger.setAttribute('aria-expanded', 'false');
  acConfirm.style.display = 'none';
}

acTrigger.addEventListener('mousedown', function(e) {
  e.preventDefault();
  dropdown.classList.contains('open') ? closeDropdown() : openDropdown();
  usernameInput.focus();
});
usernameInput.addEventListener('input', function() {
  if (getAccounts().length > 0 && this.value) openDropdown();
  else if (!this.value) closeDropdown();
});
document.addEventListener('mousedown', function(e) {
  if (!document.getElementById('usernameField').contains(e.target)) closeDropdown();
});
document.getElementById('clearAllBtn').addEventListener('click', function(e) {
  e.stopPropagation();
  acConfirm.style.display = acConfirm.style.display === 'none' ? 'flex' : 'none';
});
document.getElementById('confirmYes').addEventListener('click', function() {
  saveAccounts([]); closeDropdown();
});
document.getElementById('confirmNo').addEventListener('click', function() {
  acConfirm.style.display = 'none';
});
usernameInput.addEventListener('keydown', function(e) {
  if (!dropdown.classList.contains('open')) return;
  if (e.key === 'Escape') closeDropdown();
});

document.getElementById('loginForm').addEventListener('submit', function() {
  const uname = usernameInput.value.trim();
  if (!uname || !document.getElementById('rememberMe').checked) return;
  let accounts = getAccounts();
  const idx = accounts.findIndex(a => a.username === uname);
  const entry = { username: uname, lastLogin: Date.now() };
  if (idx >= 0) accounts[idx] = entry;
  else { accounts.unshift(entry); if (accounts.length > 10) accounts = accounts.slice(0, 10); }
  saveAccounts(accounts);
});

usernameInput.addEventListener('input', function() { this.classList.remove('err'); });
document.getElementById('pwInput').addEventListener('input', function() { this.classList.remove('err'); });
</script>
</body>
</html>