<?php
/**
 * _base.php — LiyNews shared bootstrap untuk halaman footer
 * Di-include di ATAS setiap halaman footer.
 *
 * Sebelum include, definisikan:
 *   $pageTitle  = "Judul Tab";
 *   $pageDesc   = "Meta description";
 *   $pageCrumb  = "Nama Halaman";  // untuk breadcrumb
 */

session_start();
include '../config/koneksi.php';

$isLogin  = isset($_SESSION['user_login']) && $_SESSION['user_login'] === true;
$userNama = $isLogin ? ($_SESSION['user_nama'] ?? $_SESSION['user_username'] ?? 'User') : '';
$userRole = $isLogin ? ($_SESSION['user_role'] ?? '') : '';
$userInit = $isLogin ? strtoupper(substr($_SESSION['user_username'] ?? 'U', 0, 1)) : '';
$dashLink = '#';
if ($userRole === 'admin')       $dashLink = '../admin/dashboardadmin.php';
elseif ($userRole === 'penulis') $dashLink = '../admin/dashboardpenulis.php';

$colCheck = mysqli_query($koneksi, "SHOW COLUMNS FROM artikel LIKE 'status'");
$w_pub    = mysqli_num_rows($colCheck) > 0 ? "WHERE a.status='publish'" : "WHERE 1=1";

$katRows = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kats = []; while ($k = mysqli_fetch_assoc($katRows)) $kats[] = $k;
$halfKats = array_chunk($kats, (int)ceil(count($kats) / 2));

/* ── Render head ── */
function liy_head(string $title, string $desc): void { ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> — LiyNews</title>
<meta name="description" content="<?= htmlspecialchars($desc) ?>">
<script>(function(){var s=localStorage.getItem('pb_theme'),t=s==='dark'||s==='light'?s:(window.matchMedia&&window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t);})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<style>
/* ── TOKENS ── */
:root{color-scheme:light dark;--blue:#1a56db;--blue-d:#1044b8;--blue-soft:rgba(26,86,219,.1);--blue-xs:rgba(26,86,219,.05);--bg:#f1f3f9;--card:#fff;--alt:#e8ebf4;--deep:#dde1ef;--bdr:#cdd3e5;--bdr-lt:#e8ecf4;--tx:#0b1320;--tx2:#253449;--muted:#5a6e88;--faint:#a8bace;--nv:#060f1d;--nv2:#091626;--nb:rgba(255,255,255,.06);--sh2:0 4px 20px rgba(8,18,36,.09);--sh3:0 14px 44px rgba(8,18,36,.14);--fd:'Playfair Display',Georgia,serif;--fs:'DM Sans',system-ui,sans-serif;--fc:'DM Serif Display',Georgia,serif;}
[data-theme=dark]{--blue:#4d8ef7;--blue-d:#3a7be8;--blue-soft:rgba(77,142,247,.11);--blue-xs:rgba(77,142,247,.05);--bg:#070d1a;--card:#0c1928;--alt:#0a1522;--bdr:#162b46;--bdr-lt:#0f2038;--tx:#dce9f8;--tx2:#8aa8c5;--muted:#4a6680;--faint:#1c3352;--nv:#030910;--nv2:#050e1b;--nb:rgba(255,255,255,.05);--sh2:0 4px 20px rgba(0,0,0,.5);--sh3:0 14px 44px rgba(0,0,0,.65);}
/* ── RESET ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{overflow-x:hidden;scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{font-family:var(--fs);background:var(--bg);color:var(--tx2);line-height:1.6;overflow-x:hidden;-webkit-tap-highlight-color:transparent;transition:background .3s,color .3s}
a{color:inherit;text-decoration:none}img{display:block;max-width:100%}button{font-family:var(--fs);cursor:pointer;border:none;background:none}
.W{width:100%;max-width:1380px;margin:0 auto;padding:0 clamp(14px,3vw,36px)}
/* ── MASTHEAD ── */
.masthead{background:var(--nv);border-bottom:3px solid var(--blue);position:sticky;top:0;z-index:700}
.mast-bar{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;height:68px;gap:12px}
.mast-l{display:flex;align-items:center;gap:8px}
.mast-date{font-size:.75rem;color:rgba(255,255,255,.65);display:flex;align-items:center;gap:5px;white-space:nowrap}
.logo-block{display:flex;flex-direction:column;align-items:center}
.logo-main{font-family:var(--fd);font-size:2.8rem;font-weight:900;color:#fff;letter-spacing:-.05em;line-height:.9;display:block}
.logo-main em{color:var(--blue);font-style:normal}
.logo-rule{width:100%;height:1px;background:var(--nb);margin:5px 0 4px}
.logo-sub{font-family:var(--fc);font-size:.65rem;font-weight:600;letter-spacing:.3em;text-transform:uppercase;color:rgba(255,255,255,.6)}
.mast-r{display:flex;align-items:center;justify-content:flex-end;gap:6px}
.ic{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:4px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.52);font-size:.82rem;transition:.15s;flex-shrink:0;text-decoration:none}
.ic:hover{background:rgba(255,255,255,.13);color:#fff}
.pb{height:32px;padding:0 13px;border-radius:4px;font-family:var(--fs);font-size:.85rem;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;border:1px solid transparent}
.pb-g{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.14);color:rgba(255,255,255,.75)}.pb-g:hover{background:rgba(255,255,255,.14);color:#fff}
.pb-s{background:var(--blue);color:#fff}.pb-s:hover{background:var(--blue-d)}
.mast-logo-mob{font-family:var(--fd);font-size:1.7rem;font-weight:900;color:#fff;letter-spacing:-.04em;line-height:1;display:none}
.mast-logo-mob em{color:var(--blue);font-style:normal}
/* User dropdown */
.u-wrap{position:relative}
.u-btn{display:flex;align-items:center;gap:5px;height:32px;padding:0 9px 0 4px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:4px;cursor:pointer;transition:.15s;user-select:none}
.u-btn:hover{background:rgba(255,255,255,.13)}
.u-av{width:22px;height:22px;border-radius:3px;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.6rem;font-weight:800;flex-shrink:0}
.u-nm{font-size:.74rem;font-weight:600;color:rgba(255,255,255,.78);max-width:68px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.u-btn i.chv{font-size:.54rem;color:rgba(255,255,255,.28);transition:transform .2s}
.u-btn.open i.chv{transform:rotate(180deg)}
.u-dd{display:none;position:absolute;right:0;top:calc(100% + 7px);background:var(--card);border:1px solid var(--bdr);border-radius:8px;min-width:186px;box-shadow:var(--sh3);overflow:hidden;z-index:900}
.u-dd.open{display:block}
.u-dd-hd{padding:10px 13px 8px;border-bottom:1px solid var(--bdr)}
.u-dd-nm{font-size:.82rem;font-weight:700;color:var(--tx)}
.u-dd-rl{font-size:.62rem;color:var(--muted);margin-top:2px;display:flex;align-items:center;gap:3px}
.u-dd-a{display:flex;align-items:center;gap:8px;padding:8px 13px;font-size:.78rem;color:var(--tx2);transition:.12s}
.u-dd-a i{font-size:.8rem;color:var(--faint);width:13px}
.u-dd-a:hover{background:var(--alt);color:var(--blue)}.u-dd-a:hover i{color:var(--blue)}
.u-dd-sep{height:1px;background:var(--bdr)}
.u-dd-a.danger{color:#d63939}.u-dd-a.danger i{color:#d63939}.u-dd-a.danger:hover{background:#fff4f4}
[data-theme=dark] .u-dd-a.danger:hover{background:#1e0a0a}
/* Cat nav */
.cat-nav{background:var(--nv2);border-bottom:2px solid var(--blue)}
.cat-scroll-w{display:flex;align-items:stretch;height:38px;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.cat-scroll-w::-webkit-scrollbar{display:none}
.cat-lnk{display:inline-flex;align-items:center;padding:0 15px;font-family:var(--fc);font-size:.95rem;font-style:italic;color:rgba(255,255,255,.55);white-space:nowrap;flex-shrink:0;transition:color .15s;border-bottom:2px solid transparent;margin-bottom:-2px}
.cat-lnk:hover{color:rgba(255,255,255,.82)}
/* ── PAGE HERO ── */
.fp-hero{background:linear-gradient(135deg,var(--nv) 0%,#0d3265 100%);padding:56px 0 46px;text-align:center;border-bottom:3px solid var(--blue)}
.eyebrow{display:inline-flex;align-items:center;gap:6px;background:var(--blue-xs);border:1px solid rgba(26,86,219,.3);border-radius:20px;padding:4px 14px;font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--blue);margin-bottom:16px}
[data-theme=dark] .eyebrow{border-color:rgba(77,142,247,.25)}
.fp-hero h1{font-family:var(--fd);font-size:clamp(1.9rem,4.5vw,3rem);font-weight:900;color:#fff;margin-bottom:14px;line-height:1.15}
.fp-hero p{font-size:.97rem;color:rgba(255,255,255,.58);max-width:560px;margin:0 auto;line-height:1.8}
/* ── BREADCRUMB ── */
.breadcrumb{display:flex;align-items:center;gap:6px;font-size:.76rem;color:var(--muted);margin-bottom:28px;flex-wrap:wrap}
.breadcrumb a{color:var(--blue)}.breadcrumb a:hover{text-decoration:underline}
.breadcrumb i{font-size:.62rem;color:var(--faint)}
/* ── PAGE BODY ── */
.fp-body{padding:48px 0 80px}
.fp-grid{display:grid;grid-template-columns:1fr 310px;gap:36px;align-items:start}
.fp-full{max-width:860px} /* single-col pages */
/* ── PROSE ── */
.fp-prose h2{font-family:var(--fd);font-size:1.48rem;font-weight:800;color:var(--tx);margin:32px 0 10px}
.fp-prose h2:first-child{margin-top:0}
.fp-prose h3{font-family:var(--fd);font-size:1.1rem;font-weight:700;color:var(--tx);margin:22px 0 7px}
.fp-prose p{color:var(--tx2);line-height:1.88;margin-bottom:14px;font-size:.95rem}
.fp-prose ul,.fp-prose ol{padding-left:20px;margin-bottom:14px}
.fp-prose li{color:var(--tx2);line-height:1.85;margin-bottom:7px;font-size:.95rem}
.fp-prose a{color:var(--blue);text-decoration:underline;text-underline-offset:3px}
.fp-prose strong{color:var(--tx);font-weight:700}
.fp-prose blockquote{border-left:3px solid var(--blue);padding:10px 18px;margin:18px 0;background:var(--alt);border-radius:0 6px 6px 0}
.fp-prose blockquote p{font-family:var(--fd);font-style:italic;font-size:1rem;color:var(--tx);margin:0}
.fp-divider{height:1px;background:var(--bdr);margin:30px 0}
/* ── SIDEBAR CARD ── */
.sb-card{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:20px;margin-bottom:16px;box-shadow:var(--sh2)}
.sb-card:last-child{margin-bottom:0}
.sb-card-hd{font-family:var(--fc);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);margin-bottom:14px;display:flex;align-items:center;gap:6px;padding-bottom:10px;border-bottom:1px solid var(--bdr)}
/* Stats */
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.stat-item{background:var(--alt);border-radius:7px;padding:14px 10px;text-align:center}
.stat-num{font-family:var(--fd);font-size:1.7rem;font-weight:900;color:var(--blue);line-height:1}
.stat-lbl{font-size:.66rem;color:var(--muted);margin-top:3px}
/* Values */
.val-item{display:flex;align-items:flex-start;gap:9px;margin-bottom:10px}
.val-item:last-child{margin-bottom:0}
.val-ico{width:30px;height:30px;background:var(--blue-soft);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.val-ico i{color:var(--blue);font-size:.78rem}
.val-t strong{font-size:.8rem;color:var(--tx);display:block;margin-bottom:2px}
.val-t span{font-size:.72rem;color:var(--muted);line-height:1.5}
/* Quick links */
.quick-links a{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:6px;background:var(--alt);font-size:.8rem;color:var(--tx2);margin-bottom:6px;transition:.14s;text-decoration:none}
.quick-links a:last-child{margin-bottom:0}
.quick-links a i{color:var(--blue);font-size:.82rem;width:14px;text-align:center}
.quick-links a:hover{background:var(--blue-soft);color:var(--blue)}
/* Redaksi cards */
.redaksi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:28px}
.redaksi-card{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:20px 16px;text-align:center;transition:box-shadow .2s,transform .2s}
.redaksi-card:hover{box-shadow:var(--sh2);transform:translateY(-2px)}
.redaksi-avatar{width:64px;height:64px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;font-family:var(--fd);font-size:1.6rem;font-weight:900;color:#fff;margin:0 auto 12px;border:3px solid var(--blue-soft)}
.redaksi-name{font-family:var(--fd);font-size:1rem;font-weight:800;color:var(--tx);margin-bottom:3px}
.redaksi-role{font-size:.7rem;color:var(--blue);font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px}
.redaksi-desc{font-size:.76rem;color:var(--muted);line-height:1.55}
/* Karier */
.job-card{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:20px;margin-bottom:12px;transition:box-shadow .2s,border-color .2s}
.job-card:hover{box-shadow:var(--sh2);border-color:var(--blue)}
.job-card:last-child{margin-bottom:0}
.job-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px}
.job-title{font-family:var(--fd);font-size:1.05rem;font-weight:800;color:var(--tx)}
.job-badge{display:inline-flex;align-items:center;gap:4px;background:var(--blue-soft);color:var(--blue);border:1px solid rgba(26,86,219,.2);border-radius:20px;font-size:.65rem;font-weight:700;padding:3px 10px;white-space:nowrap;flex-shrink:0}
.job-meta{display:flex;align-items:center;gap:14px;font-size:.76rem;color:var(--muted);margin-bottom:9px;flex-wrap:wrap}
.job-meta span{display:flex;align-items:center;gap:4px}
.job-desc{font-size:.85rem;color:var(--tx2);line-height:1.7;margin-bottom:12px}
.job-btn{display:inline-flex;align-items:center;gap:5px;height:32px;padding:0 16px;background:var(--blue);color:#fff;border-radius:6px;font-size:.78rem;font-weight:600;transition:.15s}
.job-btn:hover{background:var(--blue-d)}
/* Iklan */
.ads-pkg{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-bottom:28px}
.ads-card{background:var(--card);border:1px solid var(--bdr);border-radius:10px;overflow:hidden;transition:box-shadow .2s,transform .2s}
.ads-card:hover{box-shadow:var(--sh2);transform:translateY(-2px)}
.ads-card-top{background:linear-gradient(135deg,var(--nv) 0%,#0d3265 100%);padding:20px;text-align:center}
.ads-card-ico{width:44px;height:44px;border-radius:10px;background:var(--blue);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.1rem;color:#fff}
.ads-card-name{font-family:var(--fd);font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:3px}
.ads-card-price{font-size:.78rem;color:rgba(255,255,255,.55)}
.ads-card-price strong{color:var(--blue);font-size:.95rem}
.ads-card-body{padding:16px}
.ads-feature{display:flex;align-items:center;gap:7px;font-size:.8rem;color:var(--tx2);margin-bottom:7px}
.ads-feature:last-child{margin-bottom:0}
.ads-feature i{color:var(--blue);font-size:.78rem;width:13px}
/* Kontak form */
.kontak-grid{display:grid;grid-template-columns:1fr 1fr;gap:36px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:.8rem;font-weight:600;color:var(--tx);margin-bottom:6px}
.form-control{width:100%;height:42px;background:var(--card);border:1px solid var(--bdr);border-radius:6px;font-family:var(--fs);font-size:.88rem;color:var(--tx);padding:0 14px;outline:none;transition:.2s}
.form-control:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft)}
.form-control::placeholder{color:var(--faint)}
textarea.form-control{height:120px;padding:12px 14px;resize:vertical}
.form-btn{width:100%;height:44px;background:var(--blue);color:#fff;border-radius:6px;font-family:var(--fs);font-size:.9rem;font-weight:700;transition:.15s;display:flex;align-items:center;justify-content:center;gap:7px;cursor:pointer;border:none}
.form-btn:hover{background:var(--blue-d)}
.contact-info-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}
.ci-ico{width:38px;height:38px;border-radius:8px;background:var(--blue-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ci-ico i{color:var(--blue);font-size:.9rem}
.ci-body strong{font-size:.84rem;color:var(--tx);display:block;margin-bottom:3px}
.ci-body span{font-size:.8rem;color:var(--muted);line-height:1.6}
/* Alert */
.alert{padding:14px 18px;border-radius:8px;font-size:.85rem;margin-bottom:16px;display:flex;align-items:center;gap:9px}
.alert-success{background:#e8f8ee;border:1px solid #b3e6c6;color:#1a6837}
[data-theme=dark] .alert-success{background:#0a2117;border-color:#1a4d30;color:#4eca7a}
.alert-error{background:#fef2f2;border:1px solid#fcc;color:#9b1c1c}
[data-theme=dark] .alert-error{background:#1e0a0a;border-color:#4d1515;color:#f87171}
/* Privacy / Syarat / Pedoman */
.legal-toc{background:var(--card);border:1px solid var(--bdr);border-radius:10px;padding:18px;margin-bottom:20px;position:sticky;top:calc(68px + 38px + 16px)}
.legal-toc-hd{font-family:var(--fc);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.16em;color:var(--blue);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--bdr)}
.legal-toc ol{padding-left:16px}
.legal-toc li{font-size:.78rem;color:var(--muted);margin-bottom:7px}
.legal-toc a{color:var(--tx2);transition:color .14s}
.legal-toc a:hover{color:var(--blue)}
/* ── FOOTER ── */
.site-footer{background:var(--nv);border-top:3px solid var(--blue)}
.ft-top{padding:38px 0 30px;display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr 1fr;gap:28px;align-items:start}
.ft-logo{font-family:var(--fd);font-size:1.95rem;font-weight:900;color:rgba(255,255,255,.9);letter-spacing:-.04em;margin-bottom:9px}
.ft-logo em{color:var(--blue);font-style:normal}
.ft-brand p{font-size:.74rem;color:rgba(255,255,255,.65);line-height:1.85;max-width:272px}
.ft-col h6{font-family:var(--fc);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.18em;color:rgba(255,255,255,.55);margin-bottom:13px}
.ft-col a{display:block;font-size:.74rem;color:rgba(255,255,255,.65);margin-bottom:8px;transition:color .14s}
.ft-col a:hover{color:rgba(255,255,255,.9)}
.ft-bar{border-top:1px solid rgba(255,255,255,.05);padding:14px 0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ft-copy{font-size:.66rem;color:rgba(255,255,255,.55)}
.ft-soc{display:flex;gap:6px}
.ft-soc a{width:28px;height:28px;border-radius:4px;border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.55);font-size:.8rem;transition:.14s;text-decoration:none}
.ft-soc a:hover{border-color:var(--blue);color:var(--blue)}
/* ── RESPONSIVE ── */
@media(max-width:900px){.mast-bar{grid-template-columns:1fr auto}.logo-block{display:none}.mast-logo-mob{display:block!important}.fp-grid{grid-template-columns:1fr}.kontak-grid{grid-template-columns:1fr}.ft-top{grid-template-columns:1fr 1fr;gap:22px}.ft-brand{grid-column:1/3}}
@media(max-width:680px){.redaksi-grid{grid-template-columns:1fr 1fr}.ads-pkg{grid-template-columns:1fr}}
@media(max-width:580px){.mast-bar{height:52px}.redaksi-grid{grid-template-columns:1fr}.ft-top{grid-template-columns:1fr}.ft-brand{grid-column:auto}.ft-bar{flex-direction:column;align-items:flex-start;gap:9px}}
</style>
</head>
<body>
<?php }

/* ── Render masthead ── */
function liy_header(array $kats, bool $isLogin, string $userNama, string $userRole, string $userInit, string $dashLink): void { ?>
<header class="masthead">
  <div class="W mast-bar">
    <div class="mast-l">
      <a href="index.php" class="mast-logo-mob">Liy<em>News</em></a>
      <span class="mast-date"><i class="bi bi-calendar3"></i><?= date('d F Y') ?></span>
    </div>
    <div class="logo-block">
      <a href="index.php" class="logo-main">Liy<em>News</em></a>
      <div class="logo-rule"></div>
      <div class="logo-sub">Berita Terpercaya Indonesia</div>
    </div>
    <div class="mast-r">
      <a href="index.php" class="ic" title="Beranda"><i class="bi bi-house"></i></a>
      <button class="ic" id="themeBtn" aria-label="Ganti tema"><i class="bi bi-moon-fill"></i></button>
      <?php if ($isLogin): ?>
      <div class="u-wrap" id="uWrap">
        <div class="u-btn" id="uBtn">
          <div class="u-av"><?= $userInit ?></div>
          <span class="u-nm"><?= htmlspecialchars($userNama) ?></span>
          <i class="bi bi-chevron-down chv"></i>
        </div>
        <div class="u-dd" id="uDd">
          <div class="u-dd-hd">
            <div class="u-dd-nm"><?= htmlspecialchars($userNama) ?></div>
            <div class="u-dd-rl"><i class="bi bi-shield-check"></i><?= htmlspecialchars($userRole) ?></div>
          </div>
          <?php if ($userRole === 'admin' || $userRole === 'penulis'): ?>
          <a href="<?= $dashLink ?>" class="u-dd-a"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <?php endif; ?>
          <a href="../admin/profileadmin.php" class="u-dd-a"><i class="bi bi-person-circle"></i> Profil</a>
          <div class="u-dd-sep"></div>
          <a href="logout.php" class="u-dd-a danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
      </div>
      <?php else: ?>
      <a href="../login.php" class="pb pb-g">Masuk</a>
      <a href="../register.php" class="pb pb-s">Daftar</a>
      <?php endif; ?>
    </div>
  </div>
  <nav class="cat-nav" aria-label="Kategori">
    <div class="W"><div class="cat-scroll-w">
      <a href="index.php" class="cat-lnk">Semua</a>
      <?php foreach ($kats as $k): ?>
      <a href="index.php?kategori=<?= $k['id_kategori'] ?>" class="cat-lnk"><?= htmlspecialchars($k['nama_kategori']) ?></a>
      <?php endforeach; ?>
    </div></div>
  </nav>
</header>
<?php }

/* ── Render footer ── */
function liy_footer(array $halfKats): void { ?>
<footer class="site-footer">
  <div class="W">
    <div class="ft-top">
      <div class="ft-brand">
        <div class="ft-logo">Liy<em>News</em></div>
        <p>Menyajikan berita terpercaya, akurat, dan terkini untuk seluruh masyarakat Indonesia. Independen, bertanggung jawab, dan berpihak pada fakta.</p>
      </div>
      <div class="ft-col">
        <h6>Kategori</h6>
        <a href="index.php">Beranda</a>
        <?php foreach ($halfKats[0] ?? [] as $k): ?>
        <a href="index.php?kategori=<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="ft-col">
        <h6>&nbsp;</h6>
        <?php foreach ($halfKats[1] ?? [] as $k): ?>
        <a href="index.php?kategori=<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></a>
        <?php endforeach; ?>
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
      <div class="ft-copy">&copy; <?= date('Y') ?> LiyNews — Semua hak dilindungi.</div>
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
<script>
(function(){
  var h=document.documentElement,b=document.getElementById('themeBtn');
  function apply(t){h.setAttribute('data-theme',t);if(b)b.innerHTML=t==='dark'?'<i class="bi bi-sun-fill"></i>':'<i class="bi bi-moon-fill"></i>';}
  apply(h.getAttribute('data-theme'));
  if(b)b.addEventListener('click',function(){var n=h.getAttribute('data-theme')==='dark'?'light':'dark';localStorage.setItem('pb_theme',n);apply(n);});
})();
var uB=document.getElementById('uBtn'),uD=document.getElementById('uDd');
if(uB&&uD){
  uB.addEventListener('click',function(e){e.stopPropagation();var o=uD.classList.toggle('open');uB.classList.toggle('open',o);});
  document.addEventListener('click',function(e){var uw=document.getElementById('uWrap');if(uw&&!uw.contains(e.target)){uD.classList.remove('open');uB.classList.remove('open');}});
}
</script>
</body>
</html>
<?php }