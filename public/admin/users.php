<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../api/config.php';

$message = '';
$error = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $nim = ($role === 'student') ? $_POST['nim'] : null;

    if ($username && $password && $name) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, nim) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role, $name, $nim]);
            $message = "User berhasil ditambahkan!";
        } catch (PDOException $e) {
            $error = "Gagal menambahkan user. Pastikan Username/NIM belum digunakan.";
        }
    } else {
        $error = "Mohon isi semua field yang wajib.";
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    if ($id_to_delete != $_SESSION['user_id']) { // Cegah admin hapus dirinya sendiri
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $message = "User berhasil dihapus!";
        } catch (PDOException $e) {
            $error = "Gagal menghapus user.";
        }
    } else {
        $error = "Anda tidak dapat menghapus akun Anda sendiri.";
    }
}

// Fetch Users
$stmt = $pdo->query("SELECT id, username, role, name, nim, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - MVP Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar { background-color: #2c3e50; min-height: 100vh; color: white; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #34495e; }
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
            <a href="users.php" class="active"><i class="bi bi-people me-2"></i> Manajemen User</a>
            <a href="sync.php"><i class="bi bi-file-earmark-text me-2"></i> Knowledgebase</a>
            <a href="web_sources.php"><i class="bi bi-globe me-2"></i> Web Sources</a>
            <hr class="mx-3">
            <a href="../logout.php" class="text-danger"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-10 bg-light p-4">
            <h2 class="mb-4">Manajemen User</h2>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- FORM ADD USER -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white p-3">
                            <h5 class="mb-0">Tambah User Baru</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="add">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username (Login)</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" id="roleSelect" class="form-select" onchange="toggleNimField()" required>
                                        <option value="student">Mahasiswa</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="mb-4" id="nimGroup">
                                    <label class="form-label">NIM (Untuk Mahasiswa)</label>
                                    <input type="text" name="nim" id="nimInput" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-person-plus me-2"></i> Tambah User</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TABLE USERS -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white p-3">
                            <h5 class="mb-0">Daftar User</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>NIM</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr><td colspan="5" class="text-center p-4">Tidak ada user ditemukan</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['name']) ?></td>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td>
                                                    <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                                        <?= strtoupper(htmlspecialchars($user['role'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($user['nim'] ?? '-') ?></td>
                                                <td class="text-center">
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="users.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa menghapus akun sendiri"><i class="bi bi-trash"></i></button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleNimField() {
        var role = document.getElementById('roleSelect').value;
        var nimGroup = document.getElementById('nimGroup');
        var nimInput = document.getElementById('nimInput');
        if (role === 'admin') {
            nimGroup.style.display = 'none';
            nimInput.removeAttribute('required');
            nimInput.value = '';
        } else {
            nimGroup.style.display = 'block';
            nimInput.setAttribute('required', 'required');
        }
    }
</script>
</body>
</html>
