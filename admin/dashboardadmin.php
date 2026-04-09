<?php
session_start();
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
include '../config/koneksi.php';

$current = basename($_SERVER['PHP_SELF']);

$jumlahBerita   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel"))['t'] ?? 0;
$jumlahPublish  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel WHERE status='publish'"))['t'] ?? 0;
$jumlahDraft    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM artikel WHERE status='draft'"))['t'] ?? 0;
$jumlahKategori = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM kategori"))['t'] ?? 0;
$jumlahUser     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM user"))['t'] ?? 0;
$jumlahPending  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM komentar WHERE status='pending'"))['t'] ?? 0;

$recentQ = mysqli_query($koneksi, "
    SELECT a.*, k.nama_kategori, u.username AS nama_penulis
    FROM artikel a
    LEFT JOIN kategori k ON a.kategori_id = k.id_kategori
    LEFT JOIN user u ON a.penulis_id = u.id_user
    ORDER BY a.tgl_posting DESC LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — LiyNews</title>
  <script>(function(){var s=localStorage.getItem('pb_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,300;1,9..144,400;1,9..144,600;1,9..144,700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
/* ═══════════════════════════════════════════
   TOKENS
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
  --ink2:      #0f1c35;
  --muted:     #7888a8;
  --faint:     #364460;
  --border:    #1c2a48;
  --border-lt: #152035;
  --blue:      #5b9bf8;
  --blue-soft: rgba(91,155,248,.12);
  --c1s:rgba(26,86,219,.16); --c1t:rgba(26,86,219,.08);
  --c2s:rgba(5,150,105,.16); --c2t:rgba(5,150,105,.08);
  --c3s:rgba(217,119,6,.16); --c3t:rgba(217,119,6,.08);
  --c4s:rgba(124,58,237,.16); --c4t:rgba(124,58,237,.08);
  --c5s:rgba(220,38,38,.16); --c5t:rgba(220,38,38,.08);
}

/* ═══════════════════════════════════════════
   BASE
═══════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:var(--fs);
  background:var(--bg);color:var(--ink);
  min-height:100vh;
  -webkit-font-smoothing:antialiased;
  transition:background .3s,color .3s;
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

.sb-logo{
  padding:28px 24px 22px;
  border-bottom:1px solid var(--sb-border);
  position:relative;z-index:1;
}
.sb-wordmark{
  font-family:var(--fd);
  font-size:1.75rem;font-weight:700;
  color:#fff;letter-spacing:-.02em;line-height:1;
}
.sb-wordmark em{font-style:italic;color:var(--blue-panel)}
.sb-tagline{
  margin-top:6px;font-size:.6rem;
  letter-spacing:.28em;text-transform:uppercase;
  color:rgba(255,255,255,.2);font-family:var(--fs);font-weight:400;
}

.sb-nav{
  flex:1;padding:10px 0;
  overflow-y:auto;scrollbar-width:none;
  position:relative;z-index:1;
}
.sb-nav::-webkit-scrollbar{display:none}

.sb-section{
  font-size:.56rem;font-weight:600;
  letter-spacing:.25em;text-transform:uppercase;
  color:rgba(255,255,255,.17);
  padding:18px 24px 5px;font-family:var(--fs);
}

.sb-link{
  display:flex;align-items:center;gap:11px;
  padding:9px 24px;
  font-family:var(--fs);font-size:.84rem;font-weight:400;
  color:var(--sb-text);
  transition:.18s;border-left:2px solid transparent;
}
.sb-ico{
  width:30px;height:30px;border-radius:var(--r);
  display:flex;align-items:center;justify-content:center;
  font-size:.82rem;flex-shrink:0;
  background:rgba(255,255,255,.04);transition:.18s;
}
.sb-link:hover{color:rgba(255,255,255,.78);background:var(--sb-hover)}
.sb-link:hover .sb-ico{background:rgba(91,155,248,.14);color:var(--blue-panel)}
.sb-link.active{color:#fff;background:var(--sb-active);border-left-color:var(--blue-panel);font-weight:500}
.sb-link.active .sb-ico{background:rgba(91,155,248,.2);color:var(--blue-panel)}
.sb-link-lbl{flex:1}
.sb-pill{
  font-family:var(--fs);font-size:.6rem;font-weight:600;
  padding:2px 8px;border-radius:4px;
  background:rgba(91,155,248,.15);color:var(--blue-panel);
  border:1px solid rgba(91,155,248,.22);
}
/* Pill merah untuk notifikasi pending */
.sb-pill-warn{
  font-family:var(--fs);font-size:.6rem;font-weight:600;
  padding:2px 8px;border-radius:4px;
  background:rgba(220,38,38,.2);color:#f87171;
  border:1px solid rgba(220,38,38,.3);
}

.sb-bottom{border-top:1px solid var(--sb-border);padding:8px 0 4px;position:relative;z-index:1}
.sb-user{
  display:flex;align-items:center;gap:10px;
  padding:11px 24px;cursor:pointer;transition:.15s;
}
.sb-user:hover{background:rgba(255,255,255,.04)}
.sb-av{
  width:34px;height:34px;border-radius:9px;
  background:linear-gradient(135deg,#5b9bf8,#1a56db);
  display:flex;align-items:center;justify-content:center;
  font-size:.8rem;font-weight:600;font-family:var(--fd);
  font-style:italic;color:#fff;flex-shrink:0;
}
.sb-uname{font-size:.82rem;font-weight:500;color:#fff;font-family:var(--fs)}
.sb-urole{font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.25);margin-top:1px;font-family:var(--fs)}
.sb-out{
  display:flex;align-items:center;gap:10px;
  padding:9px 24px;width:100%;
  font-family:var(--fs);font-size:.82rem;font-weight:400;
  color:rgba(248,113,113,.55);
  background:none;border:none;cursor:pointer;transition:.18s;
  border-left:2px solid transparent;
}
.sb-out:hover{color:#fca5a5;background:rgba(248,113,113,.06);border-left-color:rgba(248,113,113,.4)}
.sb-out i{font-size:.8rem}

.sidebar-backdrop{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.55);z-index:150;
  opacity:0;pointer-events:none;transition:opacity .25s;
}
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
.top-crumb{
  font-family:var(--fs);font-size:.76rem;
  color:rgba(255,255,255,.3);
  display:flex;align-items:center;gap:5px;
}
.top-crumb b{color:rgba(255,255,255,.85);font-weight:500}
.top-crumb i{font-size:.62rem;color:rgba(255,255,255,.18)}
.top-date{
  font-family:var(--fd);font-style:italic;
  font-size:.8rem;color:rgba(255,255,255,.35);
  padding:4px 12px;
  border:1px solid rgba(255,255,255,.1);
  border-radius:4px;
  background:rgba(255,255,255,.04);
}
.top-right{display:flex;align-items:center;gap:6px}
.top-btn{
  width:34px;height:34px;border-radius:7px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.05);
  color:rgba(255,255,255,.45);
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  font-size:.85rem;transition:.15s;
}
.top-btn:hover{border-color:var(--blue-panel);color:var(--blue-panel);background:rgba(91,155,248,.12)}
#sidebarToggle{display:none}

/* ═══════════════════════════════════════════
   CONTENT
═══════════════════════════════════════════ */
.content{
  margin-left:var(--sidebar-w);
  padding:36px 36px 72px;
  animation:pageIn .5s cubic-bezier(.22,1,.36,1) both;
}
@keyframes pageIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

/* ═══════════════════════════════════════════
   PAGE HEADER
═══════════════════════════════════════════ */
.page-hd{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:28px;gap:20px;
  padding-bottom:24px;
  border-bottom:1px solid var(--border);
}
.page-eyebrow{
  font-family:var(--fs);font-size:.62rem;font-weight:600;
  letter-spacing:.24em;text-transform:uppercase;
  color:var(--blue);margin-bottom:7px;
  display:flex;align-items:center;gap:8px;
}
.page-eyebrow::before{content:'';width:20px;height:1.5px;background:var(--blue);border-radius:2px}
.page-title{
  font-family:var(--fd);
  font-size:1.9rem;font-weight:600;
  color:var(--ink);letter-spacing:-.02em;line-height:1.1;
}
.page-title em{font-style:italic;color:var(--blue)}
.page-sub{font-family:var(--fs);font-size:.84rem;color:var(--muted);margin-top:5px;font-weight:300}

.btn-new{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;
  background:var(--blue);color:#fff;
  border:none;border-radius:var(--r);
  font-family:var(--fs);font-size:.82rem;font-weight:600;
  letter-spacing:.02em;cursor:pointer;transition:.2s;
  white-space:nowrap;text-decoration:none;
  box-shadow:0 2px 12px rgba(26,86,219,.3);
}
.btn-new:hover{background:var(--ink2);box-shadow:0 4px 20px rgba(26,86,219,.35);transform:translateY(-1px)}
.btn-new:active{transform:translateY(0)}

/* ═══════════════════════════════════════════
   STAT CARDS
═══════════════════════════════════════════ */
.stats-grid{
  display:grid;
  grid-template-columns:repeat(5,1fr);
  gap:12px;
  margin-bottom:24px;
}
.scard{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:12px;
  padding:16px 16px 14px;
  text-decoration:none;display:block;
  position:relative;overflow:hidden;
  transition:.22s cubic-bezier(.4,0,.2,1);
}
.scard::before{
  content:'';
  position:absolute;top:0;left:0;right:0;
  height:3px;background:var(--ca);
  transform:scaleX(0);transform-origin:left;
  transition:.25s cubic-bezier(.4,0,.2,1);
}
.scard:hover::before{transform:scaleX(1)}
.scard:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(10,15,40,.09);border-color:var(--ca)}
.scard::after{
  content:'';position:absolute;top:-14px;right:-14px;
  width:54px;height:54px;border-radius:50%;
  background:var(--ca-t);transition:.22s;
}
.scard:hover::after{transform:scale(1.4)}

.sc-head{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:10px;position:relative;
}
.sc-icon{
  width:30px;height:30px;border-radius:8px;
  background:var(--ca-s);
  display:flex;align-items:center;justify-content:center;
  font-size:.8rem;color:var(--ca);flex-shrink:0;
}
.sc-tag{
  font-family:var(--fs);font-size:.55rem;font-weight:600;
  letter-spacing:.1em;text-transform:uppercase;
  color:var(--faint);background:var(--bg2);
  padding:2px 7px;border-radius:4px;
}
.sc-num{
  font-family:var(--fd);
  font-size:1.7rem;
  font-weight:700;
  color:var(--ink);line-height:1;
  letter-spacing:-.03em;
  margin-bottom:4px;position:relative;
}
.sc-lbl{
  font-family:var(--fs);font-size:.62rem;font-weight:500;
  letter-spacing:.12em;text-transform:uppercase;color:var(--muted);
}
.sc-bar{
  height:2px;border-radius:2px;
  background:var(--border);margin-top:12px;overflow:hidden;
}
.sc-bar-f{height:100%;border-radius:2px;background:var(--ca);transition:.6s ease}

.scard.s1{--ca:var(--c1);--ca-s:var(--c1s);--ca-t:var(--c1t)}
.scard.s2{--ca:var(--c2);--ca-s:var(--c2s);--ca-t:var(--c2t)}
.scard.s3{--ca:var(--c3);--ca-s:var(--c3s);--ca-t:var(--c3t)}
.scard.s4{--ca:var(--c4);--ca-s:var(--c4s);--ca-t:var(--c4t)}
.scard.s5{--ca:var(--c5);--ca-s:var(--c5s);--ca-t:var(--c5t)}

/* ═══════════════════════════════════════════
   MINI METRIC ROW
═══════════════════════════════════════════ */
.mrow{display:flex;gap:12px;margin-bottom:28px}
.mc{
  flex:1;background:var(--card);
  border:1px solid var(--border);border-radius:var(--r);
  padding:12px 16px;
  display:flex;align-items:center;gap:12px;transition:.15s;
}
.mc:hover{border-color:var(--blue);background:var(--blue-soft)}
.mc-stripe{width:3px;height:28px;border-radius:2px;flex-shrink:0}
.mc-num{
  font-family:var(--fd);font-size:1.2rem;font-weight:600;
  color:var(--ink);line-height:1;
}
.mc-lbl{
  font-family:var(--fs);font-size:.6rem;font-weight:500;
  letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-top:2px;
}

/* ═══════════════════════════════════════════
   SECTION HEADER
═══════════════════════════════════════════ */
.sec-hd{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:14px;gap:16px;flex-wrap:wrap;
}
.sec-title{
  font-family:var(--fd);font-size:1.2rem;font-weight:600;
  color:var(--ink);display:flex;align-items:center;gap:10px;
}
.sec-title::before{content:'';width:4px;height:18px;background:var(--blue);border-radius:2px;display:block}
.sec-caption{font-family:var(--fd);font-style:italic;font-size:.8rem;color:var(--faint);margin-left:2px}
.btn-more{
  display:inline-flex;align-items:center;gap:6px;
  padding:6px 16px;
  border:1.5px solid var(--border);border-radius:var(--r);
  background:var(--card);
  font-family:var(--fs);font-size:.75rem;font-weight:500;
  color:var(--muted);cursor:pointer;transition:.15s;text-decoration:none;
}
.btn-more:hover{border-color:var(--blue);color:var(--blue);background:var(--blue-soft)}

/* ═══════════════════════════════════════════
   TABLE
═══════════════════════════════════════════ */
.tcard{
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--rl);overflow:hidden;
  box-shadow:0 1px 4px rgba(10,15,40,.04);
}
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:700px}
thead{background:var(--bg2);border-bottom:1.5px solid var(--border)}
th{
  font-family:var(--fs);font-size:.6rem;font-weight:600;
  letter-spacing:.16em;text-transform:uppercase;color:var(--muted);
  padding:12px 16px;text-align:left;white-space:nowrap;
}
td{
  font-family:var(--fs);font-size:.84rem;font-weight:400;color:var(--ink);
  padding:12px 16px;border-bottom:1px solid var(--border-lt);vertical-align:middle;
}
tbody tr:last-child td{border-bottom:none}
tbody tr{transition:.1s}
tbody tr:nth-child(even) td{background:rgba(0,0,0,.013)}
[data-theme="dark"] tbody tr:nth-child(even) td{background:rgba(255,255,255,.017)}
tbody tr:hover td{background:var(--blue-soft) !important}

.no-col{font-family:var(--fd);font-style:italic;font-size:.78rem;color:var(--faint)}
.td-title{font-weight:500;font-family:var(--fs);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.td-meta{font-size:.78rem;color:var(--muted)}

.badge{
  display:inline-flex;align-items:center;gap:5px;
  font-family:var(--fs);font-size:.62rem;font-weight:600;
  padding:3px 9px;border-radius:5px;letter-spacing:.05em;text-transform:uppercase;
}
.badge-dot{width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0}
.badge-pub{background:rgba(5,150,105,.1);color:#059669;border:1px solid rgba(5,150,105,.22)}
.badge-draft{background:rgba(217,119,6,.1);color:#d97706;border:1px solid rgba(217,119,6,.22)}
[data-theme="dark"] .badge-pub{background:rgba(5,150,105,.16);color:#34d399;border-color:rgba(52,211,153,.22)}
[data-theme="dark"] .badge-draft{background:rgba(217,119,6,.16);color:#fbbf24;border-color:rgba(251,191,36,.22)}

.act-row{display:flex;gap:5px}
.act-btn{
  width:28px;height:28px;border-radius:6px;
  border:1px solid var(--border);color:var(--muted);background:transparent;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  font-size:.75rem;transition:.14s;text-decoration:none;
}
.act-btn.edit:hover{border-color:#3b82f6;color:#3b82f6;background:rgba(59,130,246,.07)}
.act-btn.del:hover{border-color:#ef4444;color:#ef4444;background:rgba(239,68,68,.07)}

.empty-row td{
  text-align:center;padding:48px;color:var(--faint);
  font-size:.9rem;font-style:italic;font-family:var(--fd);
}
.empty-row td i{display:block;font-size:1.8rem;margin-bottom:10px;opacity:.2}

/* ═══════════════════════════════════════════
   MODAL
═══════════════════════════════════════════ */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(5,8,20,.65);backdrop-filter:blur(8px);
  z-index:999;align-items:center;justify-content:center;padding:20px;
}
.modal-overlay.show{display:flex}
.modal-box{
  background:var(--card);border:1px solid var(--border);border-radius:var(--rl);
  padding:0;width:100%;max-width:360px;
  box-shadow:0 24px 60px rgba(0,0,0,.3);overflow:hidden;
  animation:popIn .22s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
.modal-top{padding:36px 28px 22px;text-align:center;border-bottom:1px solid var(--border)}
.modal-ico{
  width:50px;height:50px;background:var(--blue-soft);
  border:1.5px solid rgba(26,86,219,.2);border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;color:var(--blue);margin:0 auto 14px;
}
.modal-box h3{font-family:var(--fd);font-size:1.25rem;font-weight:600;color:var(--ink);margin-bottom:6px;letter-spacing:-.01em}
.modal-box p{font-family:var(--fs);font-size:.82rem;color:var(--muted);line-height:1.65}
.modal-acts{display:flex}
.btn-cancel{
  flex:1;background:none;border:none;color:var(--muted);
  padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:500;
  cursor:pointer;transition:.15s;border-right:1px solid var(--border);
}
.btn-cancel:hover{background:var(--bg);color:var(--ink)}
.btn-confirm{
  flex:1;background:var(--ink2);color:#fff;border:none;
  padding:14px;font-family:var(--fs);font-size:.84rem;font-weight:600;
  cursor:pointer;transition:.15s;
  display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;
}
.btn-confirm:hover{background:var(--blue)}

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media(max-width:1400px){
  .sc-num{font-size:1.55rem}
}
@media(max-width:1280px){
  .stats-grid{grid-template-columns:repeat(3,1fr)}
  .sc-num{font-size:1.7rem}
}
@media(max-width:1024px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .topbar,.content{margin-left:0}
  .content{padding:26px 20px 56px}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  #sidebarToggle{display:flex !important}
}
@media(max-width:640px){
  .topbar{padding:0 18px;height:50px}
  .top-date{display:none}
  .page-hd{flex-direction:column;align-items:flex-start;margin-bottom:24px}
  .page-title{font-size:1.6rem}
  .stats-grid{grid-template-columns:1fr 1fr}
  .mrow{display:none}
  .content{padding:20px 14px 48px}
}
@media(max-width:420px){
  .stats-grid{grid-template-columns:1fr}
  .td-title{max-width:120px}
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

    <!-- ▼▼▼ MENU KELOLA KOMENTAR ▼▼▼ -->
    <a href="kelola_komentar.php" class="sb-link <?= $current==='kelola_komentar.php'?'active':'' ?>">
      <span class="sb-ico"><i class="bi bi-chat-dots"></i></span>
      <span class="sb-link-lbl">Komentar</span>
      <?php if($jumlahPending > 0): ?>
      <span class="sb-pill-warn"><?= $jumlahPending ?></span>
      <?php endif; ?>
    </a>
    <!-- ▲▲▲ END MENU KELOLA KOMENTAR ▲▲▲ -->

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
      <b>Dashboard</b>
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
    <div class="page-hd-l">
      <div class="page-eyebrow">Ringkasan Hari Ini</div>
      <h1 class="page-title">Selamat Datang, <em><?= htmlspecialchars($_SESSION['user_username']??'Admin') ?></em></h1>
      <p class="page-sub">Semua aktivitas LiyNews dalam satu tampilan.</p>
    </div>
    <a href="berita_tambah.php" class="btn-new">
      <i class="bi bi-plus-lg"></i> Tambah Berita
    </a>
  </div>

  <!-- Stat Cards -->
  <div class="stats-grid">

    <a href="kelola_berita.php" class="scard s1">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-newspaper"></i></div>
        <span class="sc-tag">Total</span>
      </div>
      <div class="sc-num"><?= $jumlahBerita ?></div>
      <div class="sc-lbl">Semua Berita</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:<?= $jumlahBerita>0?75:0 ?>%"></div></div>
    </a>

    <a href="kelola_berita.php?status=publish" class="scard s2">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-broadcast"></i></div>
        <span class="sc-tag">Live</span>
      </div>
      <div class="sc-num"><?= $jumlahPublish ?></div>
      <div class="sc-lbl">Dipublikasi</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:<?= $jumlahBerita>0?round(($jumlahPublish/$jumlahBerita)*100):0 ?>%"></div></div>
    </a>

    <a href="kelola_berita.php?status=draft" class="scard s3">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-pencil-square"></i></div>
        <span class="sc-tag">Pending</span>
      </div>
      <div class="sc-num"><?= $jumlahDraft ?></div>
      <div class="sc-lbl">Draft</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:<?= $jumlahBerita>0?round(($jumlahDraft/$jumlahBerita)*100):0 ?>%"></div></div>
    </a>

    <a href="kategori.php" class="scard s4">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-bookmark-fill"></i></div>
        <span class="sc-tag">Aktif</span>
      </div>
      <div class="sc-num"><?= $jumlahKategori ?></div>
      <div class="sc-lbl">Kategori</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:60%"></div></div>
    </a>

    <a href="kelola_user.php" class="scard s5">
      <div class="sc-head">
        <div class="sc-icon"><i class="bi bi-people-fill"></i></div>
        <span class="sc-tag">Member</span>
      </div>
      <div class="sc-num"><?= $jumlahUser ?></div>
      <div class="sc-lbl">Pengguna</div>
      <div class="sc-bar"><div class="sc-bar-f" style="width:45%"></div></div>
    </a>

  </div>

  <!-- Mini Metrics -->
  <div class="mrow">
    <div class="mc">
      <div class="mc-stripe" style="background:var(--c2)"></div>
      <div>
        <div class="mc-num"><?= $jumlahBerita>0?round(($jumlahPublish/$jumlahBerita)*100):0 ?>%</div>
        <div class="mc-lbl">Tingkat Publish</div>
      </div>
    </div>
    <div class="mc">
      <div class="mc-stripe" style="background:var(--blue)"></div>
      <div>
        <div class="mc-num"><?= $jumlahKategori>0?round($jumlahBerita/$jumlahKategori,1):'—' ?></div>
        <div class="mc-lbl">Berita / Kategori</div>
      </div>
    </div>
    <div class="mc">
      <div class="mc-stripe" style="background:var(--c3)"></div>
      <div>
        <div class="mc-num"><?= $jumlahDraft ?></div>
        <div class="mc-lbl">Menunggu Review</div>
      </div>
    </div>
    <div class="mc">
      <div class="mc-stripe" style="background:var(--c5)"></div>
      <div>
        <div class="mc-num"><?= $jumlahUser ?></div>
        <div class="mc-lbl">Total Member</div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="sec-hd">
    <div class="sec-title">
      Berita Terbaru
      <span class="sec-caption">8 artikel terakhir</span>
    </div>
    <a href="kelola_berita.php" class="btn-more">
      Lihat Semua <i class="bi bi-arrow-right"></i>
    </a>
  </div>

  <div class="tcard">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>Judul Berita</th>
            <th>Kategori</th>
            <th>Penulis</th>
            <th>Status</th>
            <th>Tanggal</th>
            <th style="width:76px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if(mysqli_num_rows($recentQ)==0): ?>
          <tr class="empty-row">
            <td colspan="7">
              <i class="bi bi-inbox"></i>
              Belum ada berita yang dibuat.
            </td>
          </tr>
          <?php else: $no=1; while($row=mysqli_fetch_assoc($recentQ)): ?>
          <tr>
            <td class="no-col"><?= str_pad($no++,2,'0',STR_PAD_LEFT) ?></td>
            <td class="td-title" title="<?= htmlspecialchars($row['judul']??'-') ?>"><?= htmlspecialchars($row['judul']??'-') ?></td>
            <td><span class="td-meta"><?= htmlspecialchars($row['nama_kategori']??'—') ?></span></td>
            <td><span class="td-meta"><?= htmlspecialchars($row['nama_penulis']??'—') ?></span></td>
            <td>
              <?php if($row['status']==='publish'): ?>
              <span class="badge badge-pub"><span class="badge-dot"></span> Publish</span>
              <?php else: ?>
              <span class="badge badge-draft"><span class="badge-dot"></span> Draft</span>
              <?php endif; ?>
            </td>
            <td><span class="td-meta" style="white-space:nowrap"><?= date('d M Y',strtotime($row['tgl_posting'])) ?></span></td>
            <td>
              <div class="act-row">
                <a href="berita_edit.php?id=<?= $row['id_artikel'] ?>" class="act-btn edit" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                <a href="berita_hapus.php?id=<?= $row['id_artikel'] ?>" class="act-btn del" title="Hapus" onclick="return confirm('Hapus artikel ini?')"><i class="bi bi-trash-fill"></i></a>
              </div>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ═══ MODAL ═══ -->
<div class="modal-overlay" id="logoutModal" onclick="handleOverlayClick(event)">
  <div class="modal-box">
    <div class="modal-top">
      <div class="modal-ico"><i class="bi bi-power"></i></div>
      <h3>Keluar dari Panel?</h3>
      <p>Sesi aktif kamu akan diakhiri dan kamu akan diarahkan ke halaman login.</p>
    </div>
    <div class="modal-acts">
      <button class="btn-cancel" onclick="hideLogoutModal()">Batal</button>
      <a href="../public/logout.php" class="btn-confirm">
        <i class="bi bi-box-arrow-right"></i> Ya, Keluar
      </a>
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

const sidebar=document.getElementById('sidebar'),
      toggle=document.getElementById('sidebarToggle'),
      backdrop=document.getElementById('sidebarBackdrop');
const open=()=>{sidebar.classList.add('open');backdrop.classList.add('show');document.body.style.overflow='hidden'};
const close=()=>{sidebar.classList.remove('open');backdrop.classList.remove('show');document.body.style.overflow=''};
function chk(){toggle.style.display=window.innerWidth<=1024?'flex':'none'}
chk();window.addEventListener('resize',chk);
toggle.addEventListener('click',e=>{e.stopPropagation();sidebar.classList.contains('open')?close():open()});
backdrop.addEventListener('click',close);
document.addEventListener('keydown',e=>{if(e.key==='Escape'){close();hideLogoutModal()}});

function showLogoutModal(){document.getElementById('logoutModal').classList.add('show')}
function hideLogoutModal(){document.getElementById('logoutModal').classList.remove('show')}
function handleOverlayClick(e){if(e.target===document.getElementById('logoutModal'))hideLogoutModal()}
</script>
</body>
</html>