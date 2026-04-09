<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_login']) || $_SESSION['user_login'] !== true || $_SESSION['user_role'] !== 'pembaca') {
    header("Location: login.php");
    exit;
}

$userId   = $_SESSION['user_id'] ?? 0;
$userNama = $_SESSION['user_nama'] ?? $_SESSION['user_username'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'pembaca';
$userInit = strtoupper(substr($_SESSION['user_username'] ?? 'U', 0, 1));

$userRow = null;
if ($userId) {
    $res = mysqli_query($koneksi, "SELECT * FROM user WHERE id_user = $userId LIMIT 1");
    if ($res) $userRow = mysqli_fetch_assoc($res);
}

$colRes = mysqli_query($koneksi, "SHOW COLUMNS FROM user");
$cols   = [];
while ($c = mysqli_fetch_assoc($colRes)) $cols[] = $c['Field'];

$hasEmail    = in_array('email', $cols);
$hasNama     = in_array('nama', $cols);
$hasUsername = in_array('username', $cols);
$hasHp       = in_array('no_hp', $cols) || in_array('hp', $cols) || in_array('phone', $cols);
$hasAlamat   = in_array('alamat', $cols);
$hasBio      = in_array('bio', $cols);
$hpCol       = in_array('no_hp', $cols) ? 'no_hp' : (in_array('hp', $cols) ? 'hp' : 'phone');

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    if ($_POST['aksi'] === 'update_profil') {
        $newNama   = trim(mysqli_real_escape_string($koneksi, $_POST['nama'] ?? ''));
        $newEmail  = trim(mysqli_real_escape_string($koneksi, $_POST['email'] ?? ''));
        $newHp     = trim(mysqli_real_escape_string($koneksi, $_POST['hp'] ?? ''));
        $newAlamat = trim(mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? ''));
        $newBio    = trim(mysqli_real_escape_string($koneksi, $_POST['bio'] ?? ''));
        $sets = [];
        if ($hasNama && $newNama)     $sets[] = "nama='$newNama'";
        if ($hasEmail && $newEmail)   $sets[] = "email='$newEmail'";
        if ($hasHp)                   $sets[] = "$hpCol='$newHp'";
        if ($hasAlamat && $newAlamat) $sets[] = "alamat='$newAlamat'";
        if ($hasBio && $newBio)       $sets[] = "bio='$newBio'";
        if (!empty($sets)) {
            mysqli_query($koneksi, "UPDATE user SET " . implode(',', $sets) . " WHERE id_user=$userId");
            if ($hasNama && $newNama) { $_SESSION['user_nama'] = $newNama; $userNama = $newNama; }
            $msg = 'Profil berhasil diperbarui.'; $msgType = 'success';
            $res2 = mysqli_query($koneksi, "SELECT * FROM user WHERE id_user = $userId LIMIT 1");
            if ($res2) $userRow = mysqli_fetch_assoc($res2);
        }
    }
    if ($_POST['aksi'] === 'ganti_password') {
        $oldPw  = $_POST['pw_lama'] ?? '';
        $newPw  = $_POST['pw_baru'] ?? '';
        $confPw = $_POST['pw_conf'] ?? '';
        if (strlen($newPw) < 6) {
            $msg = 'Password baru minimal 6 karakter.'; $msgType = 'error';
        } elseif ($newPw !== $confPw) {
            $msg = 'Konfirmasi password tidak cocok.'; $msgType = 'error';
        } else {
            $storedPw = $userRow['password'] ?? '';
            $valid = (password_verify($oldPw, $storedPw) || $oldPw === $storedPw);
            if (!$valid) {
                $msg = 'Password lama tidak sesuai.'; $msgType = 'error';
            } else {
                $hashed = password_hash($newPw, PASSWORD_DEFAULT);
                mysqli_query($koneksi, "UPDATE user SET password='$hashed' WHERE id_user=$userId");
                $msg = 'Password berhasil diubah.'; $msgType = 'success';
            }
        }
    }
}

$currentEmail  = $userRow['email']    ?? '';
$currentNama   = $userRow['nama']     ?? ($userRow['username'] ?? $userNama);
$currentHp     = $hasHp ? ($userRow[$hpCol] ?? '') : '';
$currentAlamat = $userRow['alamat']   ?? '';
$currentBio    = $userRow['bio']      ?? '';
$currentUser   = $userRow['username'] ?? '';
$joinDate      = $userRow['created_at'] ?? ($userRow['tgl_daftar'] ?? '');

// Helper: cari nama kolom user ID pada sebuah tabel
function getUserCol($koneksi, $table) {
    $candidates = ['id_user', 'user_id', 'id_pembaca', 'pembaca_id', 'userid'];
    $res = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table`");
    $cols = [];
    while ($c = mysqli_fetch_assoc($res)) $cols[] = strtolower($c['Field']);
    foreach ($candidates as $c) { if (in_array($c, $cols)) return $c; }
    return null;
}

// Count stats
$bookmarkCount = 0;
$cekBm = mysqli_query($koneksi, "SHOW TABLES LIKE 'bookmark'");
if (mysqli_num_rows($cekBm) > 0) {
    $col = getUserCol($koneksi, 'bookmark');
    if ($col) {
        $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM bookmark WHERE `$col`=$userId"));
        $bookmarkCount = $r['t'] ?? 0;
    }
}
$komentarCount = 0;
$cekKm = mysqli_query($koneksi, "SHOW TABLES LIKE 'komentar'");
if (mysqli_num_rows($cekKm) > 0) {
    $col = getUserCol($koneksi, 'komentar');
    if ($col) {
        $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM komentar WHERE `$col`=$userId"));
        $komentarCount = $r['t'] ?? 0;
    }
}
$artikelDibaca = 0;
$cekRh = mysqli_query($koneksi, "SHOW TABLES LIKE 'riwayat_baca'");
if (mysqli_num_rows($cekRh) > 0) {
    $col = getUserCol($koneksi, 'riwayat_baca');
    if ($col) {
        $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM riwayat_baca WHERE `$col`=$userId"));
        $artikelDibaca = $r['t'] ?? 0;
    }
}

function fmtDate($t) {
    if (!$t) return '-';
    $b=[1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    $p=explode('-',date('Y-m-d',strtotime($t)));
    return $p[2].' '.$b[(int)$p[1]].' '.$p[0];
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Profil Saya — LiyNews</title>
<script>(function(){var s=localStorage.getItem('ln_theme');if(!s)s=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',s);})();</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --blue:#1a56db;--blue-d:#1044b8;--blue-soft:rgba(26,86,219,.1);
    --navy:#0d1f3c;--navy2:#132040;
    --bg:#f7f8fa;--bg-card:#fff;--bg-input:#eef1f7;
    --border:#dde3ef;--border-lt:#eaecf5;
    --text:#0f1923;--text2:#374151;--text-muted:#6b7a99;--text-faint:#b0bdd0;
    --sh1:0 1px 4px rgba(15,25,60,.06);--sh2:0 4px 20px rgba(15,25,60,.1);
    --sh3:0 12px 40px rgba(15,25,60,.14);
    --fd:'Playfair Display',Georgia,serif;--fs:'Inter',system-ui,sans-serif;
    --r:6px;--rm:10px;
    --sidebar-w:240px;
    --topbar-h:56px;
  }
  [data-theme="dark"]{
    --blue:#4d8ef7;--blue-d:#3a7be8;--blue-soft:rgba(77,142,247,.12);
    --navy:#060e1c;--navy2:#0a1628;
    --bg:#0c1628;--bg-card:#111f38;--bg-input:#162040;
    --border:#1d3058;--border-lt:#172848;
    --text:#e8eef8;--text2:#b8c8de;--text-muted:#6a86aa;--text-faint:#253a58;
    --sh1:0 1px 4px rgba(0,0,0,.3);--sh2:0 4px 20px rgba(0,0,0,.4);
    --sh3:0 12px 40px rgba(0,0,0,.5);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--fs);background:var(--bg);color:var(--text);min-height:100vh;transition:background .2s,color .2s}
  a{color:inherit;text-decoration:none}
  button,input,textarea,select{font-family:var(--fs)}

  /* ── SIDEBAR ── */
  .sidebar{
    position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);
    background:var(--navy);display:flex;flex-direction:column;
    z-index:200;border-right:1px solid rgba(255,255,255,.06);
    transition:transform .3s cubic-bezier(.4,0,.2,1);
  }
  .sb-logo{padding:22px 20px 16px;border-bottom:1px solid rgba(255,255,255,.07)}
  .sb-logo-text{font-family:var(--fd);font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-.02em}
  .sb-logo-text em{color:var(--blue);font-style:normal}
  .sb-logo-sub{font-size:.62rem;color:rgba(255,255,255,.25);text-transform:uppercase;letter-spacing:.15em;margin-top:2px}
  .sb-nav{flex:1;padding:10px 0;overflow-y:auto;scrollbar-width:none}
  .sb-nav::-webkit-scrollbar{display:none}
  .sb-section{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.13em;color:rgba(255,255,255,.2);padding:14px 20px 5px}
  .sb-link{display:flex;align-items:center;gap:10px;padding:9px 20px;font-size:.82rem;font-weight:500;color:rgba(255,255,255,.45);transition:.15s;border-left:3px solid transparent;white-space:nowrap}
  .sb-link i{font-size:.95rem;width:17px;text-align:center;flex-shrink:0}
  .sb-link:hover{color:rgba(255,255,255,.85);background:rgba(255,255,255,.05)}
  .sb-link.active{color:#fff;background:rgba(26,86,219,.2);border-left-color:var(--blue)}
  [data-theme="dark"] .sb-link.active{background:rgba(77,142,247,.15)}
  .sb-bottom{padding:12px 0;border-top:1px solid rgba(255,255,255,.07)}
  .sb-logout{display:flex;align-items:center;gap:10px;padding:9px 20px;font-size:.82rem;font-weight:500;color:#f87171;transition:.15s;background:none;border:none;width:100%;cursor:pointer;font-family:var(--fs)}
  .sb-logout:hover{color:#fca5a5;background:rgba(248,113,113,.08)}
  .sb-close-btn{display:none;position:absolute;top:14px;right:14px;background:rgba(255,255,255,.1);border:none;color:rgba(255,255,255,.6);width:28px;height:28px;border-radius:6px;cursor:pointer;align-items:center;justify-content:center;font-size:1rem}

  .sidebar-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:190;backdrop-filter:blur(2px)}
  .sidebar-backdrop.show{display:block}

  /* ── TOPBAR ── */
  .topbar{
    margin-left:var(--sidebar-w);height:var(--topbar-h);
    background:var(--navy2);border-bottom:3px solid var(--blue);
    display:flex;align-items:center;justify-content:space-between;
    padding:0 28px;position:sticky;top:0;z-index:100;
    box-shadow:0 3px 14px rgba(0,0,0,.25);transition:margin .3s;
  }
  .topbar-left{display:flex;align-items:center;gap:12px;font-size:.82rem;color:rgba(255,255,255,.4)}
  .topbar-left strong{color:#fff;font-weight:600}
  .topbar-right{display:flex;align-items:center;gap:8px}
  .btn-icon{background:rgba(255,255,255,.08);border:1.5px solid rgba(255,255,255,.14);color:rgba(255,255,255,.55);width:34px;height:34px;border-radius:99px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:.15s;flex-shrink:0}
  .btn-icon:hover{border-color:var(--blue);color:#fff;background:rgba(255,255,255,.15)}
  .btn-hamburger{display:none}
  .user-chip{display:flex;align-items:center;gap:8px;padding:4px 12px 4px 5px;border:1.5px solid rgba(255,255,255,.18);border-radius:99px;background:rgba(255,255,255,.08)}
  .user-avatar{width:26px;height:26px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;font-weight:700;flex-shrink:0}
  .user-chip span{font-size:.79rem;font-weight:600;color:#fff}

  /* ── CONTENT ── */
  .content{margin-left:var(--sidebar-w);padding:0 0 60px;transition:margin .3s}

  /* ── HERO ── */
  .profile-hero{
    background:linear-gradient(135deg,var(--navy) 0%,var(--navy2) 100%);
    border-bottom:3px solid var(--blue);
    padding:36px 32px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;
  }
  .hero-avatar{width:78px;height:78px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;font-size:1.9rem;color:#fff;font-weight:800;font-family:var(--fd);flex-shrink:0;border:3px solid rgba(255,255,255,.15);position:relative}
  .hero-dot{position:absolute;bottom:3px;right:3px;width:14px;height:14px;border-radius:50%;background:#22c55e;border:2.5px solid var(--navy)}
  .hero-info{min-width:0}
  .hero-info h2{font-family:var(--fd);font-size:1.55rem;font-weight:800;color:#fff;margin-bottom:4px;word-break:break-word}
  .hero-info p{font-size:.8rem;color:rgba(255,255,255,.4);margin-bottom:10px}
  .hero-badges{display:flex;gap:8px;flex-wrap:wrap}
  .hero-badge{display:inline-flex;align-items:center;gap:5px;font-size:.67rem;font-weight:700;padding:4px 11px;border-radius:99px;border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.7);letter-spacing:.03em}
  .hero-badge.blue{background:rgba(26,86,219,.25);border-color:rgba(26,86,219,.5);color:#93b4f8}
  .hero-badge.green{background:rgba(22,163,74,.2);border-color:rgba(22,163,74,.4);color:#4ade80}
  .hero-badge.gray{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.14);color:rgba(255,255,255,.45)}

  /* ── STATS ── */
  .stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;padding:24px 32px 0}
  .stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r);padding:16px 20px;box-shadow:var(--sh1);display:flex;align-items:center;gap:14px}
  .stat-icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0}
  .stat-info p{font-size:.68rem;color:var(--text-muted);margin-bottom:2px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}
  .stat-info h3{font-size:1.45rem;font-weight:800;color:var(--text);line-height:1;font-family:var(--fd)}

  /* ── TABS ── */
  .tabs-wrap{padding:24px 32px 0}
  .tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:22px;overflow-x:auto;scrollbar-width:none}
  .tabs::-webkit-scrollbar{display:none}
  .tab-btn{background:none;border:none;border-bottom:2.5px solid transparent;margin-bottom:-2px;padding:10px 18px;font-size:.78rem;font-weight:600;color:var(--text-muted);cursor:pointer;transition:.15s;font-family:var(--fs);text-transform:uppercase;letter-spacing:.07em;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0}
  .tab-btn:hover{color:var(--text)}
  .tab-btn.active{color:var(--blue);border-bottom-color:var(--blue)}
  .tab-pane{display:none}
  .tab-pane.active{display:block;animation:tfade .2s ease}
  @keyframes tfade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

  /* ── ALERTS ── */
  .alert{padding:10px 16px;border-radius:var(--r);font-size:.82rem;margin-bottom:20px;display:flex;align-items:center;gap:8px}
  .alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
  .alert-err{background:rgba(220,38,38,.07);border:1px solid rgba(220,38,38,.2);color:#dc2626}
  [data-theme="dark"] .alert-ok{background:#052e16;border-color:#14532d;color:#4ade80}
  [data-theme="dark"] .alert-err{background:#2d0a0a;border-color:#7f1d1d;color:#fca5a5}

  /* ── SECTION CARD ── */
  .section-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--rm);padding:24px;box-shadow:var(--sh1)}

  /* ── INFO ROWS ── */
  .info-row{display:flex;justify-content:space-between;align-items:flex-start;padding:13px 0;border-bottom:1px solid var(--border-lt);gap:16px}
  .info-row:last-child{border-bottom:none}
  .info-lbl{font-size:.67rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.09em;flex-shrink:0;padding-top:2px}
  .info-val{font-size:.86rem;color:var(--text);font-weight:500;text-align:right;word-break:break-word}
  .info-val.empty{color:var(--text-faint);font-style:italic}
  .role-pill{display:inline-block;background:var(--blue-soft);color:var(--blue);font-size:.68rem;font-weight:700;padding:3px 11px;border-radius:99px;border:1.5px solid rgba(26,86,219,.2);letter-spacing:.04em;text-transform:uppercase}

  /* ── FORM ── */
  .fg2{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}
  .fc1{grid-column:1/-1}
  .field{margin-bottom:18px}
  .field label{display:block;font-size:.68rem;font-weight:700;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.09em}
  .field input,.field textarea{width:100%;background:var(--bg-input);border:1.5px solid var(--border);color:var(--text);border-radius:var(--r);padding:10px 13px;font-size:.88rem;font-family:var(--fs);outline:none;transition:.15s}
  .field input:focus,.field textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-soft)}
  .field input::placeholder,.field textarea::placeholder{color:var(--text-faint)}
  .field input[readonly]{opacity:.55;cursor:not-allowed}
  .field textarea{resize:vertical;min-height:90px}
  .field .fhint{font-size:.68rem;color:var(--text-muted);margin-top:4px}

  /* pw toggle */
  .pw-wrap{position:relative}
  .pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-faint);cursor:pointer;font-size:.9rem;padding:2px;transition:.15s}
  .pw-eye:hover{color:var(--blue)}

  /* strength */
  .s-bar{height:3px;border-radius:2px;margin-top:7px;background:var(--border);overflow:hidden}
  .s-fill{height:100%;width:0;border-radius:2px;transition:width .3s,background .3s}
  .hint{font-size:.7rem;color:var(--text-muted);margin-top:5px}

  /* ── BTN ── */
  .btn-primary{background:var(--blue);color:#fff;border:none;border-radius:var(--r);padding:10px 22px;font-size:.84rem;font-weight:600;font-family:var(--fs);cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.15s}
  .btn-primary:hover{background:var(--blue-d)}
  .btn-ghost{background:none;border:1.5px solid var(--border);color:var(--text-muted);border-radius:var(--r);padding:10px 18px;font-size:.84rem;font-weight:500;cursor:pointer;font-family:var(--fs);display:inline-flex;align-items:center;gap:6px;transition:.15s}
  .btn-ghost:hover{border-color:var(--blue);color:var(--blue)}
  .btn-danger{background:none;border:1.5px solid #fca5a5;color:#dc2626;border-radius:var(--r);padding:9px 17px;font-size:.83rem;font-weight:500;cursor:pointer;font-family:var(--fs);display:inline-flex;align-items:center;gap:6px;transition:.15s}
  .btn-danger:hover{background:#dc2626;border-color:#dc2626;color:#fff}
  .brow{display:flex;gap:9px;margin-top:8px;flex-wrap:wrap}

  /* ── SETTINGS ── */
  .srow{display:flex;align-items:center;justify-content:space-between;padding:17px 0;border-bottom:1px solid var(--border-lt);gap:16px;flex-wrap:wrap}
  .srow:last-child{border-bottom:none}
  .slbl{font-size:.88rem;font-weight:600;color:var(--text);margin-bottom:3px}
  .sdsc{font-size:.74rem;color:var(--text-muted)}
  .dzone{margin-top:10px;padding:17px 18px;background:var(--bg);border:1px solid rgba(252,165,165,.25);border-left:3px solid #dc2626;border-radius:var(--r);display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}
  [data-theme="dark"] .dzone{background:rgba(127,29,29,.07);border-color:rgba(252,165,165,.12);border-left-color:#dc2626}
  .dzlbl{font-size:.85rem;font-weight:700;color:#dc2626;margin-bottom:3px}
  .dzdsc{font-size:.72rem;color:var(--text-muted)}

  /* Toggle */
  .tgl{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0}
  .tgl input{opacity:0;width:0;height:0}
  .ts{position:absolute;inset:0;background:var(--border);border-radius:99px;cursor:pointer;transition:.2s}
  .ts::before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;left:3px;top:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
  .tgl input:checked+.ts{background:var(--blue)}
  .tgl input:checked+.ts::before{transform:translateX(18px)}

  /* ── MODAL ── */
  .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(5px);z-index:999;align-items:center;justify-content:center;padding:16px}
  .modal-overlay.show{display:flex}
  .modal-box{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:40px 32px 32px;width:100%;max-width:360px;box-shadow:0 24px 64px rgba(0,0,0,.4);text-align:center;animation:popIn .22s cubic-bezier(.34,1.56,.64,1)}
  @keyframes popIn{from{opacity:0;transform:scale(.88)}to{opacity:1;transform:scale(1)}}
  .modal-icon-wrap{width:68px;height:68px;border-radius:50%;background:var(--blue-soft);border:2px solid rgba(26,86,219,.25);display:flex;align-items:center;justify-content:center;font-size:1.75rem;color:var(--blue);margin:0 auto 20px}
  .modal-box h3{font-family:var(--fd);font-size:1.2rem;font-weight:800;color:var(--text);margin-bottom:8px}
  .modal-box p{font-size:.82rem;color:var(--text-muted);line-height:1.65;margin-bottom:28px}
  .modal-actions{display:flex;gap:10px}
  .btn-cancel{flex:1;background:none;border:1.5px solid var(--border);color:var(--text);border-radius:8px;padding:11px;font-size:.85rem;font-weight:600;font-family:var(--fs);cursor:pointer;transition:.15s}
  .btn-cancel:hover{background:var(--bg)}
  .btn-logout-confirm{flex:1;background:var(--blue);color:#fff;border:none;border-radius:8px;padding:11px;font-size:.85rem;font-weight:600;font-family:var(--fs);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px;transition:.15s;text-decoration:none}
  .btn-logout-confirm:hover{background:var(--blue-d);color:#fff}

  /* ── RESPONSIVE ── */
  @media(max-width:1024px){
    .stats-row{gap:12px;padding:20px 24px 0}
    .tabs-wrap{padding:20px 24px 0}
    .profile-hero{padding:28px 24px}
  }
  @media(max-width:768px){
    :root{--sidebar-w:0px}
    .sidebar{width:260px;transform:translateX(-100%);box-shadow:none}
    .sidebar.open{transform:translateX(0);box-shadow:var(--sh3)}
    .sb-close-btn{display:flex}
    .topbar{margin-left:0;padding:0 16px}
    .btn-hamburger{display:flex}
    .topbar-breadcrumb{display:none}
    .content{margin-left:0}
    .profile-hero{padding:24px 18px;gap:18px}
    .hero-avatar{width:62px;height:62px;font-size:1.5rem}
    .hero-info h2{font-size:1.25rem}
    .stats-row{grid-template-columns:repeat(3,1fr);gap:10px;padding:16px 16px 0}
    .stat-card{padding:12px 10px;gap:8px;flex-direction:column;align-items:flex-start}
    .stat-icon{width:32px;height:32px;font-size:.85rem}
    .stat-info p{font-size:.6rem}
    .stat-info h3{font-size:1.1rem}
    .tabs-wrap{padding:16px 16px 0}
    .tab-btn{padding:9px 13px;font-size:.72rem}
    .section-card{padding:16px}
    .info-row{flex-direction:column;gap:4px;align-items:flex-start}
    .info-val{text-align:left}
    .user-chip span{display:none}
    .user-chip{padding:4px}
    .modal-box{padding:28px 20px 22px}
  }
  @media(max-width:480px){
    .fg2{grid-template-columns:1fr}
    .profile-hero{padding:18px 14px}
    .stats-row{padding:12px 12px 0;gap:8px}
    .stat-card{padding:10px 8px}
    .tabs-wrap{padding:12px 12px 0}
    .tab-btn{padding:8px 10px;font-size:.68rem;gap:4px}
    .tab-btn i{display:none}
    .section-card{padding:14px}
  }
</style>
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
  <button class="sb-close-btn" onclick="closeSidebar()"><i class="bi bi-x-lg"></i></button>
  <div class="sb-logo">
    <div class="sb-logo-text">Liy<em>News</em></div>
    <div class="sb-logo-sub">Portal Berita</div>
  </div>
  <div class="sb-nav">
    <div class="sb-section">Navigasi</div>
    <a href="pembaca/dashboardpembaca.php" class="sb-link" onclick="closeSidebar()"><i class="bi bi-house-door"></i> Beranda</a>
    <a href="profilepembaca.php" class="sb-link active" onclick="closeSidebar()"><i class="bi bi-person-circle"></i> Profil Saya</a>
  </div>
  <div class="sb-bottom">
    <button type="button" class="sb-logout" onclick="showLogoutModal()">
      <i class="bi bi-box-arrow-right"></i> Keluar
    </button>
  </div>
</nav>

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-left">
    <button class="btn-icon btn-hamburger" onclick="openSidebar()"><i class="bi bi-list"></i></button>
    <span class="topbar-breadcrumb"><strong>Profil</strong> &nbsp;·&nbsp; Pengaturan Akun</span>
  </div>
  <div class="topbar-right">
    <button class="btn-icon" id="themeBtn"><i class="bi bi-moon-fill"></i></button>
    <div class="user-chip">
      <div class="user-avatar"><?= $userInit ?></div>
      <span><?= htmlspecialchars($userNama) ?></span>
    </div>
  </div>
</div>

<!-- CONTENT -->
<div class="content">

  <!-- HERO -->
  <div class="profile-hero">
    <div class="hero-avatar" style="position:relative">
      <?= $userInit ?>
      <div class="hero-dot"></div>
    </div>
    <div class="hero-info">
      <h2><?= htmlspecialchars($currentNama ?: $userNama) ?></h2>
      <p><?= $currentEmail ? htmlspecialchars($currentEmail) : ($currentUser ? '@'.htmlspecialchars($currentUser) : 'Pembaca LiyNews') ?></p>
      <div class="hero-badges">
        <span class="hero-badge blue"><i class="bi bi-person-check"></i> Pembaca</span>
        <span class="hero-badge green"><i class="bi bi-circle-fill" style="font-size:.4rem"></i> Aktif</span>
        <?php if ($joinDate): ?>
        <span class="hero-badge gray"><i class="bi bi-calendar3"></i> <?= fmtDate($joinDate) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(26,86,219,.1);color:var(--blue)"><i class="bi bi-bookmark"></i></div>
      <div class="stat-info"><p>Disimpan</p><h3><?= $bookmarkCount ?></h3></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(22,163,74,.1);color:#16a34a"><i class="bi bi-eye"></i></div>
      <div class="stat-info"><p>Dibaca</p><h3><?= $artikelDibaca ?></h3></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(147,51,234,.1);color:#9333ea"><i class="bi bi-chat-dots"></i></div>
      <div class="stat-info"><p>Komentar</p><h3><?= $komentarCount ?></h3></div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs-wrap">
    <?php if ($msg): ?>
    <div class="alert <?= $msgType === 'success' ? 'alert-ok' : 'alert-err' ?>" style="margin-top:20px">
      <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
      <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <div class="tabs">
      <button class="tab-btn active" onclick="switchTab('info',this)"><i class="bi bi-person"></i> Informasi</button>
      <button class="tab-btn" onclick="switchTab('edit',this)"><i class="bi bi-pencil-square"></i> Edit Profil</button>
      <button class="tab-btn" onclick="switchTab('password',this)"><i class="bi bi-lock"></i> Ubah Password</button>
      <button class="tab-btn" onclick="switchTab('setting',this)"><i class="bi bi-sliders2"></i> Setelan</button>
    </div>

    <!-- TAB INFORMASI -->
    <div class="tab-pane active" id="tab-info">
      <div class="section-card">
        <div class="info-row">
          <span class="info-lbl">Nama Lengkap</span>
          <span class="info-val <?= !$currentNama ? 'empty' : '' ?>"><?= $currentNama ? htmlspecialchars($currentNama) : 'Belum diisi' ?></span>
        </div>
        <?php if ($hasUsername && $currentUser): ?>
        <div class="info-row">
          <span class="info-lbl">Username</span>
          <span class="info-val">@<?= htmlspecialchars($currentUser) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($hasEmail): ?>
        <div class="info-row">
          <span class="info-lbl">Email</span>
          <span class="info-val <?= !$currentEmail ? 'empty' : '' ?>"><?= $currentEmail ? htmlspecialchars($currentEmail) : 'Belum diisi' ?></span>
        </div>
        <?php endif; ?>
        <?php if ($hasHp && $currentHp): ?>
        <div class="info-row">
          <span class="info-lbl">No. HP</span>
          <span class="info-val"><?= htmlspecialchars($currentHp) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($hasAlamat && $currentAlamat): ?>
        <div class="info-row">
          <span class="info-lbl">Alamat</span>
          <span class="info-val"><?= htmlspecialchars($currentAlamat) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($hasBio && $currentBio): ?>
        <div class="info-row">
          <span class="info-lbl">Bio</span>
          <span class="info-val"><?= nl2br(htmlspecialchars($currentBio)) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($joinDate): ?>
        <div class="info-row">
          <span class="info-lbl">Bergabung</span>
          <span class="info-val"><?= fmtDate($joinDate) ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
          <span class="info-lbl">Peran</span>
          <span class="info-val"><span class="role-pill"><?= htmlspecialchars($userRole) ?></span></span>
        </div>
      </div>
    </div>

    <!-- TAB EDIT PROFIL -->
    <div class="tab-pane" id="tab-edit">
      <div class="section-card">
        <form method="POST">
          <input type="hidden" name="aksi" value="update_profil">
          <div class="fg2">
            <?php if ($hasNama): ?>
            <div class="field">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" value="<?= htmlspecialchars($currentNama) ?>" placeholder="Masukkan nama lengkap">
            </div>
            <?php endif; ?>
            <?php if ($hasUsername): ?>
            <div class="field">
              <label>Username</label>
              <input type="text" value="<?= htmlspecialchars($currentUser) ?>" readonly>
              <div class="fhint">Tidak dapat diubah</div>
            </div>
            <?php endif; ?>
            <?php if ($hasEmail): ?>
            <div class="field">
              <label>Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($currentEmail) ?>" placeholder="contoh@email.com">
            </div>
            <?php endif; ?>
            <?php if ($hasHp): ?>
            <div class="field">
              <label>No. HP / WhatsApp</label>
              <input type="text" name="hp" value="<?= htmlspecialchars($currentHp) ?>" placeholder="08xxxxxxxxxx">
            </div>
            <?php endif; ?>
            <?php if ($hasAlamat): ?>
            <div class="field fc1">
              <label>Alamat</label>
              <input type="text" name="alamat" value="<?= htmlspecialchars($currentAlamat) ?>" placeholder="Kota, Provinsi">
            </div>
            <?php endif; ?>
            <?php if ($hasBio): ?>
            <div class="field fc1">
              <label>Bio</label>
              <textarea name="bio" placeholder="Ceritakan tentang dirimu…"><?= htmlspecialchars($currentBio) ?></textarea>
            </div>
            <?php endif; ?>
          </div>
          <div class="brow">
            <button type="submit" class="btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
            <button type="button" class="btn-ghost" onclick="switchTab('info',document.querySelectorAll('.tab-btn')[0])">Batal</button>
          </div>
        </form>
      </div>
    </div>

    <!-- TAB UBAH PASSWORD -->
    <div class="tab-pane" id="tab-password">
      <div class="section-card" style="max-width:460px">
        <form method="POST">
          <input type="hidden" name="aksi" value="ganti_password">
          <div class="field">
            <label>Password Lama <span style="color:#dc2626">*</span></label>
            <div class="pw-wrap">
              <input type="password" name="pw_lama" id="pwL" style="padding-right:40px" placeholder="Password saat ini" required>
              <button type="button" class="pw-eye" onclick="tpw('pwL',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <div class="field">
            <label>Password Baru <span style="color:#dc2626">*</span></label>
            <div class="pw-wrap">
              <input type="password" name="pw_baru" id="pwB" style="padding-right:40px" placeholder="Minimal 6 karakter" required oninput="cstr(this.value)">
              <button type="button" class="pw-eye" onclick="tpw('pwB',this)"><i class="bi bi-eye"></i></button>
            </div>
            <div class="s-bar"><div class="s-fill" id="sFill"></div></div>
            <div class="hint" id="sText">Minimal 6 karakter.</div>
          </div>
          <div class="field">
            <label>Konfirmasi Password <span style="color:#dc2626">*</span></label>
            <div class="pw-wrap">
              <input type="password" name="pw_conf" id="pwC" style="padding-right:40px" placeholder="Ulangi password baru" required>
              <button type="button" class="pw-eye" onclick="tpw('pwC',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <button type="submit" class="btn-primary"><i class="bi bi-shield-lock"></i> Ubah Password</button>
        </form>
      </div>
    </div>

    <!-- TAB SETELAN -->
    <div class="tab-pane" id="tab-setting">
      <div class="section-card">
        <div class="srow">
          <div>
            <div class="slbl">Tema Tampilan</div>
            <div class="sdsc">Aktifkan untuk mode gelap</div>
          </div>
          <label class="tgl">
            <input type="checkbox" id="thTgl" onchange="tglTheme(this.checked)">
            <span class="ts"></span>
          </label>
        </div>
        <div class="srow">
          <div>
            <div class="slbl">Sesi Aktif</div>
            <div class="sdsc">Login sebagai <strong><?= htmlspecialchars($userNama) ?></strong></div>
          </div>
          <button class="btn-ghost" onclick="showLogoutModal()"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </div>
        <div class="srow" style="border-bottom:none;padding-bottom:0">
          <div>
            <div class="slbl" style="color:#dc2626">Zona Berbahaya</div>
            <div class="sdsc">Tindakan permanen yang tidak bisa dibatalkan</div>
          </div>
        </div>
        <div class="dzone">
          <div>
            <div class="dzlbl"><i class="bi bi-exclamation-triangle"></i> Hapus Akun</div>
            <div class="dzdsc">Seluruh data akan dihapus secara permanen.</div>
          </div>
          <button class="btn-danger" onclick="if(confirm('Yakin hapus akun?')){alert('Hubungi admin untuk menghapus akun.');}">
            <i class="bi bi-trash3"></i> Hapus
          </button>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- MODAL LOGOUT -->
<div class="modal-overlay" id="logoutModal" onclick="handleOverlayClick(event)">
  <div class="modal-box">
    <div class="modal-icon-wrap"><i class="bi bi-box-arrow-right"></i></div>
    <h3>Yakin ingin keluar?</h3>
    <p>Sesi aktifmu akan segera diakhiri.<br>Kamu perlu login ulang untuk melanjutkan.</p>
    <div class="modal-actions">
      <button type="button" class="btn-cancel" onclick="hideLogoutModal()"><i class="bi bi-x-lg"></i>&nbsp; Batal</button>
      <a href="public/logout.php" class="btn-logout-confirm"><i class="bi bi-box-arrow-right"></i>&nbsp; Ya, Keluar</a>
    </div>
  </div>
</div>

<script>
/* ── THEME ── */
const html = document.documentElement;
const thBtn = document.getElementById('themeBtn');
const thTgl = document.getElementById('thTgl');
function applyTheme(t) {
  html.setAttribute('data-theme', t);
  localStorage.setItem('ln_theme', t);
  thBtn.innerHTML = t === 'dark' ? '<i class="bi bi-sun-fill"></i>' : '<i class="bi bi-moon-fill"></i>';
  if (thTgl) thTgl.checked = (t === 'dark');
}
applyTheme(html.getAttribute('data-theme') || 'light');
thBtn.addEventListener('click', () => applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));
function tglTheme(d) { applyTheme(d ? 'dark' : 'light'); }

/* ── SIDEBAR ── */
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
function openSidebar() { sidebar.classList.add('open'); backdrop.classList.add('show'); document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); backdrop.classList.remove('show'); document.body.style.overflow = ''; }

/* ── TABS ── */
function switchTab(id, el) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  if (el) el.classList.add('active');
}

/* ── AUTO-SWITCH AFTER POST ── */
<?php if ($msg): ?>
<?php if (($_POST['aksi'] ?? '') === 'ganti_password'): ?>
switchTab('password', document.querySelectorAll('.tab-btn')[2]);
<?php elseif (($_POST['aksi'] ?? '') === 'update_profil'): ?>
switchTab('info', document.querySelectorAll('.tab-btn')[0]);
<?php endif; ?>
<?php endif; ?>

/* ── PASSWORD TOGGLE ── */
function tpw(id, btn) {
  const i = document.getElementById(id);
  if (!i) return;
  i.type = i.type === 'text' ? 'password' : 'text';
  btn.innerHTML = i.type === 'text' ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}

/* ── PASSWORD STRENGTH ── */
function cstr(pw) {
  const f = document.getElementById('sFill'), t = document.getElementById('sText');
  if (!f || !t) return;
  let s = 0;
  if (pw.length >= 6) s++; if (pw.length >= 10) s++; if (pw.length >= 14) s++;
  if (/[A-Z]/.test(pw)) s++; if (/[0-9]/.test(pw)) s++; if (/[^a-zA-Z0-9]/.test(pw)) s++;
  s = Math.min(s, 5);
  const C = ['#e5e7eb','#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
  const L = ['Sangat lemah','Sangat lemah','Lemah','Cukup','Kuat','Sangat kuat'];
  f.style.width = ((s/5)*100)+'%'; f.style.background = C[s];
  t.textContent = pw ? L[s] : 'Minimal 6 karakter.'; t.style.color = C[s];
}

/* ── MODAL LOGOUT ── */
function showLogoutModal() { document.getElementById('logoutModal').classList.add('show'); document.body.style.overflow = 'hidden'; }
function hideLogoutModal() { document.getElementById('logoutModal').classList.remove('show'); document.body.style.overflow = ''; }
function handleOverlayClick(e) { if (e.target === document.getElementById('logoutModal')) hideLogoutModal(); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') { hideLogoutModal(); closeSidebar(); } });
</script>
</body>
</html>