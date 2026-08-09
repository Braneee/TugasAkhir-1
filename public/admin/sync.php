<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$sync_output = "";
$status = "";
$message = "";
$doc_dir = realpath(__DIR__ . "/../../documents/");

// 1. Handle File Upload (Create)
if (isset($_FILES['kb_file'])) {
    $file = $_FILES['kb_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'docx'];

    if (in_array($ext, $allowed)) {
        $target = $doc_dir . DIRECTORY_SEPARATOR . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $message = "<div class='alert alert-success'>File <b>" . htmlspecialchars($file['name']) . "</b> berhasil diupload!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal mengupload file.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Format file tidak didukung. Gunakan PDF atau DOCX.</div>";
    }
}

// 2. Handle File Delete (Delete)
if (isset($_GET['delete'])) {
    $file_to_delete = basename($_GET['delete']);
    $target = $doc_dir . DIRECTORY_SEPARATOR . $file_to_delete;
    if (file_exists($target)) {
        if (unlink($target)) {
            $message = "<div class='alert alert-success'>File <b>" . htmlspecialchars($file_to_delete) . "</b> berhasil dihapus!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menghapus file.</div>";
        }
    }
}

// 3. Handle Sync (Update Index)
if (isset($_POST['sync'])) {
    // Jalankan script indexing Python
    // Pastikan python terinstall dan ada di PATH
    // Path script harus absolut atau relatif dari file PHP ini
    $script_path = realpath(__DIR__ . "/../../scripts/indexer.py");
    
    if ($script_path) {
        $command = "python \"$script_path\" 2>&1";
        $sync_output = shell_exec($command);
        $status = "success";
    } else {
        $sync_output = "Error: Script indexer.py tidak ditemukan!";
        $status = "error";
    }
}

// Cek status Elasticsearch
$es_status = false;
try {
    $ch = curl_init("http://localhost:9200");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code === 200) $es_status = true;
} catch (Exception $e) {
    $es_status = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Knowledgebase - MVP Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar { background-color: #2c3e50; min-height: 100vh; color: white; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #34495e; }
        pre { background-color: #212529; color: #00ff00; padding: 15px; border-radius: 8px; font-size: 0.85rem; }
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
            <a href="sync.php" class="active"><i class="bi bi-file-earmark-text me-2"></i> Knowledgebase</a>
            <a href="web_sources.php"><i class="bi bi-globe me-2"></i> Web Sources</a>
            <hr class="mx-3">
            <a href="../logout.php" class="text-danger"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 bg-light p-4">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Knowledgebase Sync</li>
              </ol>
            </nav>

            <h2 class="mb-4">Manajemen Knowledgebase</h2>
            
            <?php echo $message; ?>

            <div class="row">
                <div class="col-md-8">
                    <!-- 1. FILE LIST (Read & Delete) -->
                    <div class="card shadow-sm border-0 p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i> Daftar Dokumen</h5>
                            <span class="badge bg-light text-dark">Total: <?php echo count(glob($doc_dir . "/*.{pdf,docx}", GLOB_BRACE)); ?> Dokumen</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $files = glob($doc_dir . "/*.{pdf,docx}", GLOB_BRACE);
                                    if (empty($files)): ?>
                                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada dokumen yang diupload.</td></tr>
                                    <?php else: 
                                        foreach ($files as $f): 
                                            $name = basename($f);
                                            $size = round(filesize($f) / 1024, 2);
                                    ?>
                                        <tr>
                                            <td>
                                                <i class="bi <?php echo (pathinfo($name, PATHINFO_EXTENSION) == 'pdf' ? 'bi-file-pdf text-danger' : 'bi-file-word text-primary'); ?> me-2"></i>
                                                <?php echo htmlspecialchars($name); ?>
                                            </td>
                                            <td><?php echo $size; ?> KB</td>
                                            <td class="text-center">
                                                <a href="?delete=<?php echo urlencode($name); ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus file ini?')">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- 2. UPLOAD FORM (Create) -->
                    <div class="card shadow-sm border-0 p-4 mb-4">
                        <h5><i class="bi bi-cloud-upload me-2"></i> Upload Dokumen</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3 mt-3">
                                <input class="form-control" type="file" name="kb_file" required>
                                <small class="text-muted">Hanya file .pdf dan .docx</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-upload me-2"></i> Upload Sekarang
                            </button>
                        </form>
                    </div>

                    <!-- 3. SYNC BUTTON (Update Index) -->
                    <div class="card shadow-sm border-0 p-4 mb-4">
                        <h5><i class="bi bi-arrow-repeat me-2"></i> Sinkronisasi Index</h5>
                        <div class="mt-2 mb-3">
                            <?php if ($es_status): ?>
                                <span class="badge bg-success p-2 w-100"><i class="bi bi-check-circle me-1"></i> Elasticsearch Online</span>
                            <?php else: ?>
                                <span class="badge bg-danger p-2 w-100"><i class="bi bi-x-circle me-1"></i> Elasticsearch Offline</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small">Jalankan ini setelah menambah/menghapus file agar pencarian terupdate.</p>
                        <form method="POST">
                            <button type="submit" name="sync" class="btn btn-dark btn-lg w-100" <?php echo !$es_status ? 'disabled' : ''; ?>>
                                <i class="bi bi-play-fill me-1"></i> Jalankan Sync
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 4. OUTPUT LOGS -->
            <div class="row mt-2">
                <div class="col-12">
                    <div class="card shadow-sm border-0 p-4">
                        <h5>Output Log Sinkronisasi</h5>
                        <?php if ($sync_output): ?>
                            <pre class="mt-3"><?php echo htmlspecialchars($sync_output); ?></pre>
                            <?php if ($status === 'success'): ?>
                                <div class="alert alert-success py-2 mt-2 small">
                                    <i class="bi bi-check-lg"></i> Proses selesai! Data di Elasticsearch telah diperbarui.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-muted p-5">
                                <i class="bi bi-terminal display-4"></i>
                                <p class="mt-2">Output sinkronisasi akan muncul di sini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
