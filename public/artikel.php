<?php
include '../config/koneksi.php';
$id = $_GET['id'];

// Tambah view
mysqli_query($koneksi, "UPDATE artikel SET jumlah_view = jumlah_view + 1 WHERE id_artikel=$id");

// Ambil artikel dengan join untuk mendapatkan nama kategori dan penulis
$query = mysqli_query($koneksi, "
    SELECT a.*, k.nama_kategori, u.username AS nama_penulis 
    FROM artikel a 
    LEFT JOIN kategori k ON a.kategori_id = k.id_kategori 
    LEFT JOIN user u ON a.penulis_id = u.id_user 
    WHERE a.id_artikel=$id
");
$data = mysqli_fetch_array($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['judul']); ?> - LiyNews</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --bg-light: #f8fafc;
            --bg-dark: #0f172a;
            --card-light: #ffffff;
            --card-dark: #1e293b;
            --text-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted-light: #64748b;
            --text-muted-dark: #cbd5e1;
            --border-light: #e2e8f0;
            --border-dark: #334155;
        }

        body {
            background: var(--bg-light);
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        body.dark-mode {
            background: var(--bg-dark);
            color: var(--text-light);
        }

        .article-container {
            background: var(--card-light);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        body.dark-mode .article-container {
            background: var(--card-dark);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .article-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 15px;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        body.dark-mode .article-title {
            color: var(--text-light);
        }

        .article-meta {
            color: var(--text-muted-light);
            font-size: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            transition: all 0.3s ease;
        }

        body.dark-mode .article-meta {
            color: var(--text-muted-dark);
        }

        .article-image {
            width: 100%;
            border-radius: 16px;
            margin-bottom: 30px;
            max-height: 500px;
            object-fit: cover;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .article-content {
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        body.dark-mode .article-content {
            color: var(--text-light);
        }

        .comment-card {
            background: var(--card-light);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-top: 40px;
            transition: all 0.3s ease;
        }

        body.dark-mode .comment-card {
            background: var(--card-dark);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .comment-box {
            background: var(--bg-light);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        body.dark-mode .comment-box {
            background: #1e293b;
            border-left: 4px solid var(--primary);
        }

        .comment-author {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        body.dark-mode .comment-author {
            color: var(--text-light);
        }

        .comment-date {
            font-size: 12px;
            color: var(--text-muted-light);
            margin-top: 8px;
            transition: all 0.3s ease;
        }

        body.dark-mode .comment-date {
            color: var(--text-muted-dark);
        }

        .category-badge {
            background: var(--primary);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .view-count {
            background: var(--bg-light);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            color: var(--text-muted-light);
            transition: all 0.3s ease;
        }

        body.dark-mode .view-count {
            background: #334155;
            color: var(--text-muted-dark);
        }

        .navbar-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            color: white;
        }

        .back-btn {
            background: var(--bg-light);
            border: none;
            color: var(--text-muted-light);
            padding: 8px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        body.dark-mode .back-btn {
            background: #334155;
            color: var(--text-muted-dark);
        }

        .back-btn:hover {
            background: var(--border-light);
            color: var(--text-dark);
            transform: translateX(-3px);
        }

        body.dark-mode .back-btn:hover {
            background: #475569;
            color: var(--text-light);
        }

        .form-control {
            background: var(--card-light);
            border: 2px solid var(--border-light);
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        body.dark-mode .form-control {
            background: var(--card-dark);
            border: 2px solid var(--border-dark);
            color: var(--text-light);
        }

        .form-control:focus {
            background: var(--card-light);
            border-color: var(--primary);
            color: var(--text-dark);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        body.dark-mode .form-control:focus {
            background: var(--card-dark);
            color: var(--text-light);
            border-color: var(--primary);
        }

        .form-control::placeholder {
            color: var(--text-muted-light);
        }

        body.dark-mode .form-control::placeholder {
            color: var(--text-muted-dark);
        }

        footer {
            background: #1e293b !important;
            transition: all 0.3s ease;
        }

        body.dark-mode footer {
            background: #0f172a !important;
        }

        .border-top {
            border-color: var(--border-light) !important;
        }

        body.dark-mode .border-top {
            border-color: var(--border-dark) !important;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">
            <i class="bi bi-newspaper me-2"></i>LiyNews
        </a>
        
        <div class="d-flex align-items-center">
            <!-- Dark Mode Toggle -->
            <button id="modeToggle" class="btn btn-sm btn-outline-light me-3">
                <i id="modeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            
            <a href="../index.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</nav>

<div class="container">

    <!-- Tombol Kembali -->
    <div class="mt-4">
        <a href="../index.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>

    <!-- Artikel -->
    <div class="article-container">
        
        <!-- Kategori -->
        <div class="mb-3">
            <span class="category-badge">
                <?= htmlspecialchars($data['nama_kategori'] ?? 'Umum'); ?>
            </span>
        </div>

        <!-- Judul -->
        <h1 class="article-title"><?= htmlspecialchars($data['judul']); ?></h1>

        <!-- Meta Info -->
        <div class="article-meta">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-event"></i>
                <span><?= date('d M Y', strtotime($data['tgl_posting'])); ?></span>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person"></i>
                <span><?= htmlspecialchars($data['nama_penulis'] ?? 'Admin'); ?></span>
            </div>
            
            <div class="view-count">
                <i class="bi bi-eye"></i> <?= number_format($data['jumlah_view']); ?>x dibaca
            </div>
        </div>

        <!-- Gambar -->
        <img src="../uploads/<?= htmlspecialchars($data['gambar']); ?>" 
             class="article-image"
             alt="<?= htmlspecialchars($data['judul']); ?>"
             onerror="this.src='https://placehold.co/800x400/3B82F6/FFFFFF?text=Gambar+Artikel'">

        <!-- Isi Artikel -->
        <div class="article-content">
            <?= nl2br(htmlspecialchars($data['isi'])); ?>
        </div>

    </div>

    <!-- Komentar -->
    <div class="comment-card">
        <h4 class="fw-bold mb-4">
            <i class="bi bi-chat-dots me-2"></i>Komentar Pembaca
        </h4>

        <?php
        $komen = mysqli_query($koneksi, "
            SELECT k.*, u.username 
            FROM komentar k 
            LEFT JOIN user u ON k.id_user = u.id_user 
            WHERE k.id_artikel=$id AND k.status='tampil'
            ORDER BY k.tgl_komentar DESC
        ");

        if (mysqli_num_rows($komen) == 0) {
            echo "
            <div class='text-center py-4'>
                <i class='bi bi-chat-square-text display-4 text-muted'></i>
                <p class='text-muted mt-3'>Belum ada komentar. Jadilah yang pertama berkomentar!</p>
            </div>";
        } else {
            while ($k = mysqli_fetch_array($komen)) {
                echo "
                <div class='comment-box'>
                    <div class='comment-author'>
                        <i class='bi bi-person-circle me-2'></i>
                        " . htmlspecialchars($k['username'] ?? 'User') . "
                    </div>
                    <div class='mt-2'>" . htmlspecialchars($k['isi_komentar']) . "</div>
                    <div class='comment-date'>
                        <i class='bi bi-clock me-1'></i>
                        " . date('d M Y H:i', strtotime($k['tgl_komentar'])) . "
                    </div>
                </div>";
            }
        }
        ?>

        <!-- Form Komentar -->
        <div class="mt-5 pt-4 border-top">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-pencil-square me-2"></i>Tulis Komentar
            </h5>

            <form method="post" action="komentar.php">
                <input type="hidden" name="id_artikel" value="<?= $id; ?>">

                <div class="mb-3">
                    <textarea name="isi_komentar" class="form-control" 
                              rows="4" 
                              placeholder="Bagikan pendapat Anda tentang artikel ini..."
                              style="border-radius: 12px; resize: none;"
                              required></textarea>
                </div>

                <button type="submit" class="btn btn-primary-custom">
                    <i class="bi bi-send me-2"></i>Kirim Komentar
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Footer -->
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5 class="fw-bold">
                    <i class="bi bi-newspaper me-2"></i>LiyNews
                </h5>
                <p class="mb-0 text-light">Sumber informasi terpercaya dan terupdate untuk semua kalangan.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">&copy; 2026 LiyNews. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script>
// Dark Mode System
const modeToggle = document.getElementById("modeToggle");
const modeIcon = document.getElementById("modeIcon");
const body = document.body;

// Load saved theme from localStorage (sinkron dengan halaman lain)
function loadTheme() {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") {
        body.classList.add("dark-mode");
        modeIcon.classList.replace("bi-moon-stars-fill", "bi-sun-fill");
    } else {
        body.classList.remove("dark-mode");
        modeIcon.classList.replace("bi-sun-fill", "bi-moon-stars-fill");
    }
}

// Apply theme on page load
loadTheme();

// Toggle theme
modeToggle.addEventListener("click", () => {
    body.classList.toggle("dark-mode");
    const isDark = body.classList.contains("dark-mode");

    // Update icon
    if (isDark) {
        modeIcon.classList.replace("bi-moon-stars-fill", "bi-sun-fill");
    } else {
        modeIcon.classList.replace("bi-sun-fill", "bi-moon-stars-fill");
    }

    // Save to localStorage (sinkron dengan halaman lain)
    localStorage.setItem("theme", isDark ? "dark" : "light");
});

// Smooth scroll dan animasi
document.addEventListener('DOMContentLoaded', function() {
    // Animasi fade in untuk konten
    const content = document.querySelector('.article-container');
    content.style.opacity = '0';
    content.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        content.style.transition = 'all 0.6s ease';
        content.style.opacity = '1';
        content.style.transform = 'translateY(0)';
    }, 100);

    // Listen for storage events (sinkronisasi real-time antar tab)
    window.addEventListener('storage', function(e) {
        if (e.key === 'theme') {
            loadTheme();
        }
    });
});

// Sinkronisasi theme antar halaman dalam tab yang sama
function syncTheme() {
    const isDark = body.classList.contains("dark-mode");
    localStorage.setItem("theme", isDark ? "dark" : "light");
}

// Panggil syncTheme ketika theme berubah
modeToggle.addEventListener("click", syncTheme);
</script>

</body>
</html>