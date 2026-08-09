<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../api/config.php';

// Stats
try {
    $student_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $search_count = $pdo->query("SELECT COUNT(*) FROM search_logs")->fetchColumn();
    
    // Recent logs
    $stmt = $pdo->query("SELECT l.*, u.name FROM search_logs l JOIN users u ON l.nim = u.nim ORDER BY created_at DESC LIMIT 10");
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
    $student_count = $search_count = 0;
    $logs = [];
}

// Check Knowledgebase files
$doc_path = '../../documents/';
$files = is_dir($doc_path) ? array_diff(scandir($doc_path), ['.', '..']) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MVP Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar { background-color: #2c3e50; min-height: 100vh; color: white; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #34495e; }
        .card-stat { border-radius: 15px; border: none; transition: 0.3s; }
        .card-stat:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-2 sidebar p-0 d-none d-md-block">
            <div class="p-4 text-center">
                <h4><i class="bi bi-gear-fill"></i> Admin Panel</h4>
            </div>
            <hr class="mx-3">
            <a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="users.php"><i class="bi bi-people me-2"></i> Manajemen User</a>
            <a href="sync.php"><i class="bi bi-file-earmark-text me-2"></i> Knowledgebase</a>
            <a href="web_sources.php"><i class="bi bi-globe me-2"></i> Web Sources</a>
            <hr class="mx-3">
            <a href="../logout.php" class="text-danger"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 bg-light p-4">
            <h2 class="mb-4">Selamat Datang, Admin!</h2>
            
            <!-- STATS -->
            <div class="row mb-5">
                <div class="col-md-4">
                    <div class="card card-stat bg-primary text-white p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Mahasiswa</h5>
                                <h2><?php echo $student_count; ?></h2>
                            </div>
                            <i class="bi bi-people display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat bg-success text-white p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Pencarian</h5>
                                <h2><?php echo $search_count; ?></h2>
                            </div>
                            <i class="bi bi-search display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-stat bg-info text-white p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5>Dokumen KB</h5>
                                <h2><?php echo count($files); ?></h2>
                            </div>
                            <i class="bi bi-file-earmark-text display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- RECENT SEARCH LOGS -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white p-3">
                            <h5 class="mb-0">Pencarian Terakhir Mahasiswa</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mahasiswa</th>
                                        <th>Query</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr><td colspan="3" class="text-center p-4">Belum ada riwayat pencarian</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($log['name']); ?></strong><br><small class="text-muted"><?php echo $log['nim']; ?></small></td>
                                                <td>"<?php echo htmlspecialchars($log['query_text']); ?>"</td>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white p-3">
                            <h5 class="mb-0">Aksi Cepat</h5>
                        </div>
                        <div class="card-body">
                            <a href="sync.php" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-arrow-repeat me-2"></i> Sync Knowledgebase
                            </a>
                            <p class="small text-muted text-center">Update Elasticsearch dengan file terbaru dari folder /documents/</p>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning border-0 shadow-sm">
                        <h6><i class="bi bi-info-circle me-2"></i> Pengumuman</h6>
                        <small>Pastikan Elasticsearch sudah aktif sebelum melakukan Syncing Knowledgebase!</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
