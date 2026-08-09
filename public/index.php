<?php
require_once 'api/session.php';
// Jika belum login, otomatis jadi guest
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest';
    $_SESSION['name'] = 'Tamu Umum';
    $_SESSION['nim'] = null;
}
require_once 'api/config.php';

// Fetch Search History (Only for Students)
$history = [];
if ($_SESSION['role'] === 'student') {
    try {
        $stmt = $pdo->prepare("SELECT query_text FROM search_logs WHERE nim = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['nim']]);
        $history = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $history = [];
    }
}

// Suggested Keywords (Static as discussed)
$suggestions = ["UKT Semester 2", "Nilai Kalkulus", "Prosedur Wisuda", "Cek IPK", "Cara Bayar Biaya Kuliah"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Engine Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .search-container { max-width: 800px; margin-top: 100px; }
        .search-box { border-radius: 30px; padding: 15px 25px; font-size: 1.2rem; border: 2px solid #dee2e6; }
        .search-box:focus { border-color: #0d6efd; box-shadow: none; }
        .suggestion-badge { border-radius: 20px; padding: 8px 15px; margin: 5px; cursor: pointer; transition: 0.3s; }
        .suggestion-badge:hover { background-color: #0d6efd !important; color: white !important; }
        .card { border-radius: 15px; border: none; }
        .navbar { background-color: #2c3e50; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-search"></i> MVP Kampus Search</a>
        <div class="navbar-text ms-auto text-white">
            <?php if ($_SESSION['role'] !== 'guest'): ?>
                <span class="me-3">Halo, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong> <?php echo $_SESSION['nim'] ? "(".htmlspecialchars($_SESSION['nim']).")" : ""; ?></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Keluar</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm rounded-pill px-4">Login Mahasiswa / Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container search-container text-center">
    <img src="https://img.icons8.com/clouds/200/search--v1.png" alt="Search Icon" class="mb-3">
    <h1 class="mb-4 font-weight-bold">Apa yang ingin Anda cari hari ini?</h1>
    
    <form action="results.php" method="GET">
        <div class="input-group mb-4 shadow-sm rounded-pill overflow-hidden bg-white p-1">
            <input type="text" name="q" class="form-control search-box border-0" placeholder="Ketik pertanyaan Anda di sini..." required autofocus>
            <button class="btn btn-primary px-5 rounded-pill" type="submit">Cari</button>
        </div>
    </form>

    <div class="mt-4">
        <p class="text-muted mb-2">Pencarian populer:</p>
        <?php foreach ($suggestions as $sug): ?>
            <a href="results.php?q=<?php echo urlencode($sug); ?>" class="badge bg-white text-dark border suggestion-badge text-decoration-none shadow-sm">
                <?php echo $sug; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($history)): ?>
    <div class="mt-5 text-start">
        <div class="card shadow-sm p-4">
            <h5 class="mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Pencarian Terakhir</h5>
            <div class="list-group list-group-flush">
                <?php foreach ($history as $h): ?>
                    <a href="results.php?q=<?php echo urlencode($h); ?>" class="list-group-item list-group-item-action border-0 px-0 py-2 text-muted">
                        <i class="bi bi-chevron-right small me-2"></i> <?php echo htmlspecialchars($h); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer class="mt-5 text-center text-muted pb-4">
    <small>&copy; 2026 MVP Search Engine Kampus with NLP</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
