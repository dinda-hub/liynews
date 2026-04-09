<?php
session_start();
if (!isset($_SESSION['admin_login'])) {
    header("Location: login.php");
    exit;
}
include '../config/koneksi.php';

// Ambil nama dari session
$nama_admin = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - LiyNews/title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --accent: #3b82f6;
    --bg-light: #f8fafc;
    --bg-dark: #0f172a;
    --card-light: #ffffff;
    --card-dark: #1e293b;
    --text-light: #f8fafc;
    --text-dark: #1e1e1e;
    --muted-light: #6b7280;
    --muted-dark: #cbd5e1;
    --border: rgba(0,0,0,0.08);
    --shadow: 0 2px 10px rgba(0,0,0,0.08);
}

body {
    font-family: "Inter", "Poppins", sans-serif;
    background-color: var(--bg-light);
    color: var(--text-dark);
    transition: background-color 0.4s ease, color 0.4s ease;
}
body.dark-mode {
    background-color: var(--bg-dark);
    color: var(--text-light);
}

/* Sidebar */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 240px;
    background-color: var(--primary);
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: var(--shadow);
    transition: background-color 0.3s, color 0.3s;
}
.sidebar h3 {
    text-align: center;
    font-weight: 600;
    margin: 30px 0;
}
.sidebar a {
    display: block;
    padding: 12px 25px;
    color: white;
    text-decoration: none;
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: all 0.2s;
}
.sidebar a:hover {
    background-color: rgba(255,255,255,0.15);
    border-left: 3px solid #fff;
}
body.dark-mode .sidebar {
    background-color: #1e293b;
}

/* Topbar */
.topbar {
    margin-left: 240px;
    height: 60px;
    background-color: var(--card-light);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 25px;
    border-bottom: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: background-color 0.3s;
}
body.dark-mode .topbar {
    background-color: var(--card-dark);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
body.dark-mode .topbar span {
    color: #f8fafc;
}

/* Toggle Mode */
.toggle-mode {
    border: none;
    background: none;
    font-size: 1.4rem;
    color: var(--muted-light);
    margin-right: 20px;
    cursor: pointer;
    transition: 0.2s;
}
.toggle-mode:hover {
    color: var(--accent);
}
body.dark-mode .toggle-mode {
    color: var(--muted-dark);
}

/* Content */
.content {
    margin-left: 240px;
    padding: 30px;
}
.content h4 {
    color: var(--text-dark);
}
body.dark-mode .content h4 {
    color: #f8fafc;
}

/* Cards */
.stat-card {
    border-radius: 12px;
    padding: 25px;
    background-color: var(--card-light);
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
body.dark-mode .stat-card {
    background-color: var(--card-dark);
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
body.dark-mode .stat-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.5);
}
.stat-card .text-muted {
    color: var(--muted-light) !important;
}
body.dark-mode .stat-card .text-muted {
    color: #cbd5e1 !important;
}

/* General Card */
.card {
    background-color: var(--card-light);
    border: none;
    border-radius: 12px;
    box-shadow: var(--shadow);
    transition: background-color 0.3s, color 0.3s;
}
body.dark-mode .card {
    background-color: var(--card-dark);
    color: #f8fafc;
}
body.dark-mode .card h1,
body.dark-mode .card h2,
body.dark-mode .card h3,
body.dark-mode .card h4,
body.dark-mode .card h5,
body.dark-mode .card h6 {
    color: #f8fafc;
}

/* Table */
.table {
    background-color: transparent;
    --bs-table-bg: transparent;
}
.table thead {
    background-color: var(--accent);
    color: white;
}
.table thead th {
    background-color: var(--accent);
    color: white;
    border-bottom: 2px solid rgba(255,255,255,0.2);
}
.table tbody {
    background-color: transparent;
}
.table tbody td {
    background-color: transparent;
    border-bottom: 1px solid var(--border);
}
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,0.04);
}
body.dark-mode .table thead {
    background-color: #1e40af;
}
body.dark-mode .table thead th {
    background-color: #1e40af;
    color: white;
    border-bottom: 2px solid rgba(255,255,255,0.2);
}
body.dark-mode .table tbody {
    background-color: transparent;
}
body.dark-mode .table tbody td {
    background-color: transparent;
    color: #f8fafc;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
body.dark-mode .table-hover tbody tr:hover {
    background-color: rgba(255,255,255,0.08);
}
body.dark-mode .table {
    color: #f8fafc;
    --bs-table-bg: transparent;
    --bs-table-striped-bg: transparent;
    --bs-table-active-bg: transparent;
    --bs-table-color: #f8fafc;
}
body.dark-mode .table tbody tr {
    border-bottom-color: rgba(255,255,255,0.1);
}
body.dark-mode .table tbody tr td {
    color: #f8fafc !important;
}
.card-header {
    background-color: transparent;
    border-bottom: 1px solid var(--border);
    padding: 1rem 1.25rem;
}
body.dark-mode .card-header {
    border-bottom-color: rgba(255,255,255,0.1);
}

/* Dropdown */
.dropdown-menu {
    border-radius: 10px;
    overflow: hidden;
}
body.dark-mode .dropdown-menu {
    background-color: #2d2d2d;
    color: #f1f1f1;
}
body.dark-mode .dropdown-item {
    color: #f1f1f1;
}
body.dark-mode .dropdown-item:hover {
    background-color: #3b3b3b;
}
body.dark-mode .dropdown-header {
    color: #f8fafc;
    font-weight: 600;
}
.topbar .dropdown-toggle {
    color: var(--text-dark);
}
body.dark-mode .topbar .dropdown-toggle {
    color: #f8fafc;
}
body.dark-mode .topbar .dropdown-toggle:hover {
    color: #60a5fa;
}

/* Button style fix */
.btn-outline-secondary,
.btn-outline-danger {
    transition: background-color 0.3s, color 0.3s;
}
body.dark-mode .btn-outline-secondary {
    color: #e5e5e5;
    border-color: #555;
}
body.dark-mode .btn-outline-secondary:hover {
    background-color: #555;
    border-color: #555;
    color: white;
}
body.dark-mode .btn-outline-danger {
    color: #fca5a5;
    border-color: #ef4444;
}
body.dark-mode .btn-outline-danger:hover {
    background-color: #ef4444;
    border-color: #ef4444;
    color: white;
}

/* Icon colors in dark mode */
body.dark-mode .text-primary {
    color: #60a5fa !important;
}
body.dark-mode .text-success {
    color: #4ade80 !important;
}
body.dark-mode .text-warning {
    color: #fbbf24 !important;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div>
        <h3>Admin Panel</h3>
        <a href="dashboardadmin.php"><i class="bi bi-house-door me-2"></i>Dashboard</a>
        <a href="kelola_berita.php"><i class="bi bi-newspaper me-2"></i>Berita</a>
        <a href="kelola_kategori.php"><i class="bi bi-folder me-2"></i>Kategori</a>
        <a href="kelola_user.php"><i class="bi bi-people me-2"></i>Pengguna</a>
    </div>
    <div>
        <a href="logout.php" class="text-warning fw-bold"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </div>
</div>

<!-- Topbar -->
<div class="topbar">
    <button id="modeToggle" class="toggle-mode"><i class="bi bi-moon-stars"></i></button>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <img src="../assets/profile/default.png" class="rounded-circle me-2" width="38" height="38">
            <span><?= htmlspecialchars($nama_admin) ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header"><?= htmlspecialchars($nama_admin) ?></h6></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Profil</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>
</div>

<!-- Content -->
<div class="content">
    <h4 class="mb-4 fw-semibold">Selamat datang, <?= htmlspecialchars($nama_admin) ?> 👋</h4>

    <div class="row g-4 mb-4">
        <?php
        $jumlahBerita = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM artikel"))['total'] ?? 0;
        $jumlahKategori = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM kategori"))['total'] ?? 0;
        $jumlahUser = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM user"))['total'] ?? 0;
        ?>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <i class="bi bi-newspaper text-primary" style="font-size:2rem;"></i>
                <h4 class="mt-2"><?= $jumlahBerita ?></h4>
                <p class="text-muted mb-0">Total Berita</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <i class="bi bi-folder text-success" style="font-size:2rem;"></i>
                <h4 class="mt-2"><?= $jumlahKategori ?></h4>
                <p class="text-muted mb-0">Kategori</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <i class="bi bi-people text-warning" style="font-size:2rem;"></i>
                <h4 class="mt-2"><?= $jumlahUser ?></h4>
                <p class="text-muted mb-0">Pengguna</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold"><i class="bi bi-clock-history me-2"></i>Berita Terbaru</div>
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Penulis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $query = mysqli_query($koneksi, "
                        SELECT a.*, 
                               k.nama_kategori, 
                               u.username AS nama_penulis
                        FROM artikel a
                        LEFT JOIN kategori k ON a.kategori_id = k.id_kategori
                        LEFT JOIN user u ON a.penulis_id = u.id_user
                        ORDER BY a.tgl_posting DESC 
                        LIMIT 5
                    ");
                    if (mysqli_num_rows($query) == 0) {
                        echo "<tr><td colspan='6' class='text-center text-muted'>Belum ada berita.</td></tr>";
                    } else {
                        while ($row = mysqli_fetch_assoc($query)) {
                            echo "<tr>
                                <td>{$no}</td>
                                <td>" . htmlspecialchars($row['judul'] ?? '-') . "</td>
                                <td>" . htmlspecialchars($row['nama_kategori'] ?? '-') . "</td>
                                <td>" . date('d M Y', strtotime($row['tgl_posting'])) . "</td>
                                <td>" . htmlspecialchars($row['nama_penulis'] ?? '-') . "</td>
                                <td>
                                    <a href='edit_berita.php?id={$row['id_artikel']}' class='btn btn-sm btn-outline-secondary'><i class='bi bi-pencil'></i></a>
                                    <a href='hapus_berita.php?id={$row['id_artikel']}' class='btn btn-sm btn-outline-danger' onclick='return confirm(\"Hapus berita ini?\")'><i class='bi bi-trash'></i></a>
                                </td>
                            </tr>";
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// === AUTO MODE SESUAI SISTEM ===
const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
if (prefersDark) {
    document.body.classList.add("dark-mode");
    document.querySelector("#modeToggle i").classList.replace("bi-moon-stars", "bi-sun");
}

// === TOGGLE MANUAL ===
const modeToggle = document.getElementById("modeToggle");
modeToggle.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");
    const icon = modeToggle.querySelector("i");
    icon.classList.toggle("bi-sun");
    icon.classList.toggle("bi-moon-stars");
});
</script>
</body>
</html>