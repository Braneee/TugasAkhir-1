<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../api/config.php';

$message = "";

// 1. Handle Create (Add Source)
if (isset($_POST['add_source'])) {
    $site_name = $_POST['site_name'];
    $url = $_POST['url'];
    $category = $_POST['category'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO web_sources (site_name, url, category) VALUES (?, ?, ?)");
        $stmt->execute([$site_name, $url, $category]);
        $message = "<div class='alert alert-success'>Situs <b>$site_name</b> berhasil ditambahkan!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Gagal menambahkan situs: " . $e->getMessage() . "</div>";
    }
}

// 2. Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM web_sources WHERE id = ?");
        $stmt->execute([$id]);
        $message = "<div class='alert alert-success'>Situs berhasil dihapus!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Gagal menghapus situs.</div>";
    }
}

// 3. Handle Toggle Status (Active/Inactive)
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $current = $_GET['status'];
    $new_status = ($current === 'active') ? 'inactive' : 'active';
    try {
        $stmt = $pdo->prepare("UPDATE web_sources SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        header('Location: web_sources.php');
        exit;
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Gagal mengubah status.</div>";
    }
}

// Fetch all sources
$sources = $pdo->query("SELECT * FROM web_sources ORDER BY created_at DESC")->fetchAll();

// 4. Handle Web Sync
$sync_output = "";
if (isset($_POST['sync_web'])) {
    $script_path = realpath(__DIR__ . "/../../scripts/web_indexer.py");
    if ($script_path) {
        $command = "python \"$script_path\" 2>&1";
        $sync_output = shell_exec($command);
    } else {
        $sync_output = "Error: Script web_indexer.py tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Web Sources - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar { background-color: #2c3e50; min-height: 100vh; color: white; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #34495e; }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
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
            <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="users.php"><i class="bi bi-people me-2"></i> Manajemen User</a>
            <a href="sync.php"><i class="bi bi-file-earmark-text me-2"></i> Knowledgebase</a>
            <a href="web_sources.php" class="active"><i class="bi bi-globe me-2"></i> Web Sources</a>
            <hr class="mx-3">
            <a href="../logout.php" class="text-danger"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 bg-light p-4">
            <h2 class="mb-4">Manajemen Web Sources</h2>
            
            <?php echo $message; ?>

            <div class="row">
                <div class="col-md-8">
                    <!-- LIST WEB SOURCES -->
                    <div class="card shadow-sm border-0 p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="bi bi-list-task me-2"></i> Daftar Situs Target</h5>
                            <span class="badge bg-light text-dark">Total: <?php echo count($sources); ?> Situs</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Situs</th>
                                        <th>URL</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sources)): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada situs target yang ditambahkan.</td></tr>
                                    <?php else: 
                                        foreach ($sources as $s): 
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($s['site_name']); ?></strong></td>
                                            <td><a href="<?php echo $s['url']; ?>" target="_blank" class="text-decoration-none small"><?php echo htmlspecialchars($s['url']); ?></a></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($s['category']); ?></span></td>
                                            <td>
                                                <?php if ($s['status'] === 'active'): ?>
                                                    <span class="badge bg-success"><span class="status-dot bg-white"></span> Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><span class="status-dot bg-white"></span> Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="?toggle=<?php echo $s['id']; ?>&status=<?php echo $s['status']; ?>" class="btn btn-sm <?php echo ($s['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success'); ?>" title="Ganti Status">
                                                        <i class="bi <?php echo ($s['status'] === 'active' ? 'bi-pause-fill' : 'bi-play-fill'); ?>"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus situs ini?')" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- ADD FORM -->
                    <div class="card shadow-sm border-0 p-4 mb-4">
                        <h5><i class="bi bi-plus-circle me-2"></i> Tambah Situs Baru</h5>
                        <form method="POST">
                            <div class="mb-3 mt-3">
                                <label class="form-label small">Nama Situs</label>
                                <input class="form-control" type="text" name="site_name" placeholder="Misal: UDINUS News" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">URL Utama</label>
                                <input class="form-control" type="url" name="url" placeholder="https://..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Kategori</label>
                                <select class="form-select" name="category">
                                    <option value="Berita">Berita</option>
                                    <option value="Pengumuman">Pengumuman</option>
                                    <option value="Informasi Umum" selected>Informasi Umum</option>
                                    <option value="Akademik">Akademik</option>
                                </select>
                            </div>
                            <button type="submit" name="add_source" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg me-1"></i> Tambahkan Situs
                            </button>
                        </form>
                    </div>

                    <!-- SYNC BUTTON -->
                    <div class="card shadow-sm border-0 p-4 mb-4 text-center">
                        <h5><i class="bi bi-cloud-arrow-down me-2"></i> Sinkronisasi Web</h5>
                        <p class="text-muted small">Update index berdasarkan daftar situs aktif.</p>
                        <form method="POST">
                            <button type="submit" name="sync_web" class="btn btn-dark w-100">
                                <i class="bi bi-play-fill me-1"></i> Sync Web Content
                            </button>
                        </form>
                    </div>

                    <!-- SYNC LOGS -->
                    <?php if ($sync_output): ?>
                    <div class="card shadow-sm border-0 p-4 mb-4">
                        <h5><i class="bi bi-terminal me-2"></i> Sync Log</h5>
                        <pre class="bg-dark text-success p-3 rounded mt-2 small" style="max-height: 200px; overflow-y: auto;"><?php echo htmlspecialchars($sync_output); ?></pre>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-info small shadow-sm border-0" style="border-radius: 12px;">
                        <i class="bi bi-info-circle-fill me-2"></i> <strong>Note:</strong> Situs yang ditambahkan di sini akan menjadi target crawling oleh engine pencari web kita nanti.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>