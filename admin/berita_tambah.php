<?php
/**
 * berita_tambah.php  –  Multi-Kategori Edition (Unified Design)
 */

session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$current = basename($_SERVER['PHP_SELF']);
$error   = '';

$katQ = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$allKats = [];
while ($k = mysqli_fetch_assoc($katQ)) $allKats[] = $k;

// ─── Stats for sidebar pill ───
$totalQ = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM artikel");
$jumlahBerita = $totalQ ? (int)mysqli_fetch_assoc($totalQ)['c'] : 0;

// ─── PROSES FORM ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul      = mysqli_real_escape_string($koneksi, trim($_POST['judul']));
    $isi        = mysqli_real_escape_string($koneksi, $_POST['isi']);
    $status     = in_array($_POST['status'], ['publish','draft']) ? $_POST['status'] : 'draft';
    $penulis_id = (int)$_SESSION['user_id'];
    $thumbnail  = '';

    $kat_ids_raw = isset($_POST['kategori_ids']) ? (array)$_POST['kategori_ids'] : [];
    $kat_ids     = array_values(array_unique(array_map('intval', $kat_ids_raw)));
    $kat_ids     = array_filter($kat_ids);

    if (empty($judul) || empty($isi)) {
        $error = "Judul dan isi wajib diisi.";
    } elseif (empty($kat_ids)) {
        $error = "Pilih minimal satu kategori.";
    } else {
        if (!empty($_FILES['thumbnail']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                $error = "Format gambar tidak valid. Gunakan JPG, PNG, atau WEBP.";
            } elseif ($_FILES['thumbnail']['size'] > 2 * 1024 * 1024) {
                $error = "Ukuran gambar maksimal 2MB.";
            } else {
                $namaFile  = uniqid('img_') . '.' . $ext;
                $uploadDir = '../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $namaFile);
                $thumbnail = $namaFile;
            }
        }

        if (!$error) {
            $thumbVal     = mysqli_real_escape_string($koneksi, $thumbnail);
            $katIdsJson   = mysqli_real_escape_string($koneksi, json_encode(array_values($kat_ids)));
            $primaryKatId = (int)$kat_ids[0];

            mysqli_query($koneksi,
                "INSERT INTO artikel (judul, isi, thumbnail, kategori_id, kategori_ids, penulis_id, status, tgl_posting)
                 VALUES ('$judul', '$isi', '$thumbVal', $primaryKatId, '$katIdsJson', $penulis_id, '$status', NOW())"
            );
            header("Location: kelola_berita.php?msg=tambah"); exit;
        }
    }
}

function kat_color($nama) {
    $map = [
        'Teknologi'    => ['#dbeafe','#1d4ed8','⚙'],
        'Olahraga'     => ['#dcfce7','#15803d','⚽'],
        'Bisnis'       => ['#fef9c3','#a16207','💼'],
        'Hiburan'      => ['#fce7f3','#be185d','🎬'],
        'Politik'      => ['#e0e7ff','#4338ca','🏛'],
        'Pendidikan'   => ['#f3e8ff','#7e22ce','📚'],
        'Otomotif'     => ['#ffedd5','#c2410c','🚗'],
        'Berita Utama' => ['#fee2e2','#b91c1c','📰'],
        'Internasional'=> ['#cffafe','#0e7490','🌍'],
        'Gaya Hidup'   => ['#fdf4ff','#a21caf','✨'],
        'Kesehatan'    => ['#f0fdf4','#16a34a','🏥'],
        'Kuliner'      => ['#fff7ed','#c2410c','🍜'],
        'Travel'       => ['#ecfdf5','#059669','✈'],
        'Religi'       => ['#fefce8','#854d0e','🕌'],
        'Cuaca'        => ['#e0f2fe','#0369a1','🌤'],
        'Hukum'        => ['#f1f5f9','#334155','⚖'],
        'Nasional'     => ['#fef2f2','#991b1b','🇮🇩'],
        'Ekonomi'      => ['#fffbeb','#92400e','📈'],
        'Sains'        => ['#f0fdf4','#166534','🔬'],
    ];
    return $map[trim($nama)] ?? ['#f1f5f9','#475569','📄'];
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Berita — LiyNews Admin</title>
  <script>
    (function(){
      var s = localStorage.getItem('pb_theme');
      if (!s) s = matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', s);
    })();
  </script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;0,9..144,700;1,9..144,300;1,9..144,600;1,9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
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
  --red:        #dc2626;

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

/* ─── SIDEBAR ─── */
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
.sb-out{display:flex;align-items:center;gap:10px;padding:9px 24px;width:100%;font-family:var(--fs);font-size:.82rem;font-weight:400;color:rgba(248,113,113,.55);background:none;border:none;cursor:pointer;transition:.18s;border-left:2px solid transparent;text-decoration:none}
.sb-out:hover{color:#fca5a5;background:rgba(248,113,113,.06);border-left-color:rgba(248,113,113,.4)}
.sidebar-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:150;opacity:0;pointer-events:none;transition:opacity .25s}
.sidebar-backdrop.show{display:block;opacity:1;pointer-events:auto}

/* ─── TOPBAR ─── */
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
.top-crumb a{color:rgba(255,255,255,.55);transition:.15s}
.top-crumb a:hover{color:#fff}
.top-crumb b{color:rgba(255,255,255,.85);font-weight:500}
.top-crumb i{font-size:.62rem;color:rgba(255,255,255,.18)}
.top-date{font-family:var(--fd);font-style:italic;font-size:.8rem;color:rgba(255,255,255,.35);padding:4px 12px;border:1px solid rgba(255,255,255,.1);border-radius:4px;background:rgba(255,255,255,.04)}
.top-right{display:flex;align-items:center;gap:6px}
.top-btn{width:34px;height:34px;border-radius:7px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:rgba(255,255,255,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:.15s}
.top-btn:hover{border-color:var(--blue-panel);color:var(--blue-panel);background:rgba(91,155,248,.12)}
#sidebarToggle{display:none}

/* ─── CONTENT ─── */
.content{margin-left:var(--sidebar-w);padding:36px 36px 72px;animation:pageIn .5s cubic-bezier(.22,1,.36,1) both}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* ─── PAGE HEADER ─── */
.page-hd{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;gap:20px;padding-bottom:24px;border-bottom:1px solid var(--border)}
.page-eyebrow{font-family:var(--fs);font-size:.62rem;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:var(--blue);margin-bottom:7px;display:flex;align-items:center;gap:8px}
.page-eyebrow::before{content:'';width:20px;height:1.5px;background:var(--blue);border-radius:2px}
.page-title{font-family:var(--fd);font-size:1.9rem;font-weight:600;color:var(--ink);letter-spacing:-.02em;line-height:1.1}
.page-title em{font-style:italic;color:var(--blue)}
.page-sub{font-family:var(--fs);font-size:.84rem;color:var(--muted);margin-top:5px;font-weight:300}

/* ─── LAYOUT ─── */
.edit-layout{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}

/* ─── CARD ─── */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;box-shadow:0 1px 4px rgba(10,15,40,.04)}
.card-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center}
.card-head-label{font-family:var(--fs);font-size:.6rem;font-weight:600;text-transform:uppercase;letter-spacing:.18em;color:var(--blue);display:flex;align-items:center;gap:7px;justify-content:space-between;width:100%}
.card-head-label::before{content:'';width:3px;height:13px;background:var(--blue);border-radius:2px;display:block;flex-shrink:0}
.card-head-label-inner{display:flex;align-items:center;gap:7px;flex:1}

/* ─── FORM FIELDS ─── */
.field{margin-bottom:18px}
.field:last-child{margin-bottom:0}
.field label{display:block;font-size:.62rem;font-weight:600;color:var(--muted);margin-bottom:7px;text-transform:uppercase;letter-spacing:.18em;font-family:var(--fs)}
.field label .req{color:var(--red);margin-left:2px}
.field input,
.field textarea{
  width:100%;background:var(--bg2);border:1.5px solid var(--border);
  color:var(--ink);border-radius:var(--r);padding:10px 14px;
  font-size:.9rem;font-family:var(--fs);outline:none;transition:.15s;
}
.field input:focus,.field textarea:focus{
  border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft);background:var(--card);
}
.field textarea{resize:vertical;min-height:320px;line-height:1.7}

/* ─── STATUS TOGGLE ─── */
.status-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.status-opt input{display:none}
.status-label{display:flex;align-items:center;justify-content:center;gap:7px;padding:10px;border:1.5px solid var(--border);border-radius:var(--r);cursor:pointer;font-size:.8rem;font-weight:600;color:var(--muted);background:var(--bg2);transition:.15s;user-select:none;font-family:var(--fs)}
.status-label i{font-size:.9rem}
.status-opt input:checked + .status-label{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}

/* ─── MULTI-KATEGORI ─── */
.kat-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:6px;
  max-height:280px;overflow-y:auto;padding-right:4px;
  scrollbar-width:thin;
}
.kat-grid::-webkit-scrollbar{width:4px}
.kat-grid::-webkit-scrollbar-track{background:transparent}
.kat-grid::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}
.kat-cb-wrap{display:none}
.kat-cb-label{
  display:flex;align-items:center;gap:7px;
  padding:7px 10px;border:1.5px solid var(--border);border-radius:var(--r);
  cursor:pointer;font-size:.78rem;font-weight:600;
  color:var(--muted);background:var(--bg2);
  transition:.15s;user-select:none;line-height:1.3;font-family:var(--fs);
}
.kat-cb-label:hover{border-color:var(--blue);color:var(--ink)}
.kat-cb-wrap:checked + .kat-cb-label{border-width:2px}
.kat-cb-label .kat-ico{font-size:.9rem;flex-shrink:0}
.kat-cb-label .kat-check{
  margin-left:auto;width:16px;height:16px;
  border:1.5px solid var(--border);border-radius:4px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:.65rem;color:transparent;transition:.15s;background:var(--card);
}
.kat-cb-wrap:checked + .kat-cb-label .kat-check{background:var(--blue);border-color:var(--blue);color:#fff}
.kat-selected-strip{display:flex;flex-wrap:wrap;gap:5px;margin-top:10px;min-height:24px}
.kat-sel-badge{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:99px;white-space:nowrap;font-family:var(--fs)}
.kat-hint{font-size:.7rem;color:var(--muted);margin-top:8px;display:flex;align-items:center;gap:5px;font-family:var(--fs)}
.kat-none{font-size:.75rem;color:var(--faint);font-style:italic;padding:2px 0;font-family:var(--fd)}

/* ─── UPLOAD AREA ─── */
.upload-area{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;border:2px dashed var(--border);border-radius:var(--r);padding:24px 16px;text-align:center;cursor:pointer;background:var(--bg2);transition:.2s}
.upload-area:hover{border-color:var(--blue);background:var(--blue-soft)}
.upload-area i{font-size:1.8rem;color:var(--muted)}
.upload-area p{font-size:.78rem;color:var(--muted);line-height:1.5;font-family:var(--fs)}
.upload-area input{display:none}
#previewWrap{margin-top:10px;display:none}
#previewWrap img{width:100%;border-radius:var(--r);border:1px solid var(--border)}

/* ─── ALERT ─── */
.alert-err{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:var(--r);padding:11px 16px;font-size:.84rem;margin-bottom:20px;display:flex;align-items:center;gap:9px;font-family:var(--fs)}
[data-theme="dark"] .alert-err{background:#1a0505;border-color:#7f1d1d;color:#fca5a5}

/* ─── BUTTONS ─── */
.form-actions{display:flex;gap:10px;flex-wrap:wrap}
.btn-primary{background:var(--blue);color:#fff;border:none;border-radius:var(--r);padding:10px 22px;font-size:.85rem;font-weight:600;font-family:var(--fs);cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.2s;white-space:nowrap;box-shadow:0 2px 12px rgba(26,86,219,.3)}
.btn-primary:hover{background:var(--ink2);box-shadow:0 4px 20px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-ghost{background:none;border:1.5px solid var(--border);color:var(--ink);border-radius:var(--r);padding:10px 20px;font-size:.85rem;font-weight:600;font-family:var(--fs);cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.15s;text-decoration:none}
.btn-ghost:hover{border-color:var(--blue);color:var(--blue)}
.side-stack{display:flex;flex-direction:column;gap:16px}
.divider{height:1px;background:var(--border-lt);margin:12px 0}

/* ─── MODAL LOGOUT ─── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(5,8,20,.65);backdrop-filter:blur(8px);z-index:999;align-items:center;justify-content:center;padding:20px}
.modal-overlay.show{display:flex}
.modal-box{background:var(--card);border:1px solid var(--border);border-radius:var(--rl);width:100%;max-width:360px;box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;animation:popIn .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-top{padding:36px 28px 22px;text-align:center;border-bottom:1px solid var(--border)}
.modal-ico{width:50px;height:50px;background:var(--blue-soft);border:1.5px solid rgba(26,86,219,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--blue);margin:0 auto 14px}
.modal-box h3{font-family:var(--fd);font-size:1.25rem;font-weight:600;color:var(--ink);margin-bottom:6px}
.modal-box p{font-family:var(--fs);font-size:.82rem;color:var(--muted);line-height:1.65}
.modal-acts{display:flex}
.btn-cancel{flex:1;background:none;border:none;color:var(--muted);padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:500;cursor:pointer;transition:.15s;border-right:1px solid var(--border)}
.btn-cancel:hover{background:var(--bg);color:var(--ink)}
.btn-confirm{flex:1;background:var(--ink2);color:#fff;border:none;padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:600;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none}
.btn-confirm:hover{background:var(--blue)}

/* ─── RESPONSIVE ─── */
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  .content{padding:26px 24px 56px}
  #sidebarToggle{display:flex!important}
}
@media(max-width:900px){
  .edit-layout{grid-template-columns:1fr}
}
@media(max-width:640px){
  .topbar{padding:0 18px;height:50px}
  .top-date{display:none}
  .page-hd{flex-direction:column;align-items:flex-start;margin-bottom:22px}
  .page-title{font-size:1.6rem}
  .content{padding:20px 14px 48px}
  .form-actions .btn-primary,
  .form-actions .btn-ghost{width:100%;justify-content:center}
  .kat-grid{grid-template-columns:1fr}
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
      <span class="sb-pill"><?= $jumlahBerita ?></span>
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
    <a href="../public/logout.php" class="sb-out"><i class="bi bi-box-arrow-right"></i> Keluar dari Panel</a>
  </div>
</nav>

<!-- ═══ TOPBAR ═══ -->
<div class="topbar">
  <div class="top-left">
    <div class="top-crumb">
      <a href="kelola_berita.php">Berita</a>
      <i class="bi bi-chevron-right"></i>
      <b>Tambah Berita</b>
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
  <div class="page-hd">
    <div>
      <div class="page-eyebrow">Manajemen Konten</div>
      <h1 class="page-title">Tambah <em>Berita</em></h1>
      <p class="page-sub">Tulis dan publikasikan artikel berita baru — bisa pilih lebih dari satu kategori.</p>
    </div>
    <a href="kelola_berita.php" class="btn-ghost" style="margin-top:10px">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <?php if ($error): ?>
  <div class="alert-err"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="tambahForm">
    <div class="edit-layout">

      <!-- KIRI: Konten utama -->
      <div>
        <div class="card">
          <div class="card-head">
            <div class="card-head-label">
              <div class="card-head-label-inner"><span>Konten Artikel</span></div>
            </div>
          </div>
          <div style="padding:20px">
            <div class="field">
              <label>Judul Berita <span class="req">*</span></label>
              <input type="text" name="judul" required autofocus
                     placeholder="Masukkan judul berita…"
                     value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
            </div>
            <div class="field" style="margin-bottom:0">
              <label>Isi Berita <span class="req">*</span></label>
              <textarea name="isi" required
                        placeholder="Tulis isi berita di sini…"><?= htmlspecialchars($_POST['isi'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- KANAN: Pengaturan -->
      <div class="side-stack">

        <!-- Status -->
        <div class="card">
          <div class="card-head">
            <div class="card-head-label">
              <div class="card-head-label-inner"><span>Status Publikasi</span></div>
            </div>
          </div>
          <div style="padding:20px">
            <div class="status-grid">
              <label class="status-opt">
                <input type="radio" name="status" value="publish"
                  <?= ($_POST['status'] ?? 'publish') === 'publish' ? 'checked' : '' ?>>
                <span class="status-label"><i class="bi bi-globe"></i> Publish</span>
              </label>
              <label class="status-opt">
                <input type="radio" name="status" value="draft"
                  <?= ($_POST['status'] ?? '') === 'draft' ? 'checked' : '' ?>>
                <span class="status-label"><i class="bi bi-file-earmark"></i> Draft</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Multi-Kategori -->
        <div class="card">
          <div class="card-head">
            <div class="card-head-label">
              <div class="card-head-label-inner">
                <span>Kategori <span style="color:var(--red);margin-left:2px">*</span></span>
              </div>
              <span id="katSelectedCount" style="font-size:.65rem;font-weight:600;color:var(--muted);text-transform:none;letter-spacing:0">0 dipilih</span>
            </div>
          </div>
          <div style="padding:20px">
            <div class="kat-selected-strip" id="katStrip">
              <span class="kat-none">Belum ada yang dipilih</span>
            </div>
            <div class="divider"></div>
            <div class="kat-grid" id="katGrid">
              <?php foreach ($allKats as $k):
                $col = kat_color($k['nama_kategori']);
                $bg = $col[0]; $fg = $col[1]; $ico = $col[2];
                $checked = in_array((int)$k['id_kategori'], array_map('intval', $_POST['kategori_ids'] ?? []));
              ?>
              <div>
                <input type="checkbox"
                       class="kat-cb-wrap"
                       name="kategori_ids[]"
                       id="kat_<?= $k['id_kategori'] ?>"
                       value="<?= $k['id_kategori'] ?>"
                       data-nama="<?= htmlspecialchars($k['nama_kategori']) ?>"
                       data-bg="<?= $bg ?>"
                       data-fg="<?= $fg ?>"
                       data-ico="<?= $ico ?>"
                       <?= $checked ? 'checked' : '' ?>>
                <label class="kat-cb-label"
                       for="kat_<?= $k['id_kategori'] ?>"
                       style="<?= $checked ? "border-color:{$fg};color:{$fg};background:{$bg}33" : '' ?>">
                  <span class="kat-ico"><?= $ico ?></span>
                  <span style="flex:1;font-size:.74rem"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                  <span class="kat-check"><i class="bi bi-check2"></i></span>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="kat-hint">
              <i class="bi bi-info-circle"></i>
              Pilih satu atau lebih. Kategori pertama jadi utama.
            </div>
          </div>
        </div>

        <!-- Thumbnail -->
        <div class="card">
          <div class="card-head">
            <div class="card-head-label">
              <div class="card-head-label-inner"><span>Gambar / Thumbnail</span></div>
            </div>
          </div>
          <div style="padding:20px">
            <label class="upload-area" for="thumbnailInput">
              <i class="bi bi-cloud-arrow-up"></i>
              <p><strong>Klik untuk unggah</strong> gambar</p>
              <p>JPG, PNG, WEBP · Maks 2 MB</p>
              <input type="file" name="thumbnail" id="thumbnailInput" accept="image/*">
            </label>
            <div id="previewWrap"><img id="previewImg" src="" alt="preview"></div>
          </div>
        </div>

        <!-- Actions -->
        <div class="card">
          <div style="padding:20px">
            <div class="form-actions">
              <button type="submit" class="btn-primary">
                <i class="bi bi-plus-lg"></i> Simpan Berita
              </button>
              <a href="kelola_berita.php" class="btn-ghost">
                <i class="bi bi-arrow-left"></i> Batal
              </a>
            </div>
          </div>
        </div>

      </div><!-- /side-stack -->
    </div><!-- /edit-layout -->
  </form>
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
      <a href="../public/logout.php" class="btn-confirm"><i class="bi bi-box-arrow-right"></i> Ya, Keluar</a>
    </div>
  </div>
</div>

<script>
  // ── Theme ──
  const html=document.documentElement,thBtn=document.getElementById('themeBtn');
  function applyTheme(t){html.setAttribute('data-theme',t);thBtn.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';}
  applyTheme(html.getAttribute('data-theme'));
  thBtn.addEventListener('click',function(){const n=html.getAttribute('data-theme')==='dark'?'light':'dark';localStorage.setItem('pb_theme',n);applyTheme(n);});

  // ── Sidebar ──
  const sidebar=document.getElementById('sidebar'),backdrop=document.getElementById('sidebarBackdrop'),toggle=document.getElementById('sidebarToggle');
  const openSb=()=>{sidebar.classList.add('open');backdrop.classList.add('show');document.body.style.overflow='hidden'};
  const closeSb=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');document.body.style.overflow=''};
  toggle?.addEventListener('click',e=>{e.stopPropagation();sidebar.classList.contains('open')?closeSb():openSb();});
  backdrop?.addEventListener('click',closeSb);
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeSb();hideLogoutModal();}});
  function chk(){if(toggle)toggle.style.display=window.innerWidth<=1024?'flex':'none';}
  chk();window.addEventListener('resize',chk);

  // ── Logout Modal ──
  function showLogoutModal(){document.getElementById('logoutModal').classList.add('show')}
  function hideLogoutModal(){document.getElementById('logoutModal').classList.remove('show')}
  function handleOverlayClick(e){if(e.target===document.getElementById('logoutModal'))hideLogoutModal();}

  // ── Kategori checkbox UI ──
  const strip=document.getElementById('katStrip'),countLbl=document.getElementById('katSelectedCount');
  function escHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
  function refreshKatUI(){
    const checkboxes=document.querySelectorAll('.kat-cb-wrap'),selected=[];
    checkboxes.forEach(cb=>{
      const label=cb.nextElementSibling,fg=cb.dataset.fg,bg=cb.dataset.bg;
      if(cb.checked){
        label.style.borderColor=fg;label.style.color=fg;label.style.background=bg+'33';
        selected.push({nama:cb.dataset.nama,fg,bg,ico:cb.dataset.ico});
      } else {
        label.style.borderColor='';label.style.color='';label.style.background='';
      }
    });
    strip.innerHTML=selected.length===0
      ?'<span class="kat-none">Belum ada yang dipilih</span>'
      :selected.map((s,i)=>`<span class="kat-sel-badge" style="background:${s.bg};color:${s.fg}">${s.ico} ${escHtml(s.nama)}${i===0?' <span style="font-size:.58rem;opacity:.7;margin-left:2px">(utama)</span>':''}</span>`).join('');
    countLbl.textContent=selected.length+' dipilih';
  }
  document.querySelectorAll('.kat-cb-wrap').forEach(cb=>cb.addEventListener('change',refreshKatUI));
  document.getElementById('tambahForm').addEventListener('submit',function(e){
    if(!document.querySelectorAll('.kat-cb-wrap:checked').length){e.preventDefault();alert('Pilih minimal satu kategori!');}
  });
  refreshKatUI();

  // ── Image preview ──
  document.getElementById('thumbnailInput').addEventListener('change',function(){
    const file=this.files[0];
    if(file){const r=new FileReader();r.onload=e=>{document.getElementById('previewImg').src=e.target.result;document.getElementById('previewWrap').style.display='block';};r.readAsDataURL(file);}
  });
</script>
</body>
</html>