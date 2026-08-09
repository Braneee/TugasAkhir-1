<?php
require_once 'api/session.php';
require_once 'api/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nim'] = $user['nim'];
            $_SESSION['name'] = $user['name'];
            
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } catch (Exception $e) {
        $error = "Terjadi kesalahan sistem.";
    }
}

// Handle Guest Login
if (isset($_GET['action']) && $_GET['action'] === 'guest') {
    $_SESSION['user_id'] = 0;
    $_SESSION['username'] = 'guest';
    $_SESSION['role'] = 'guest';
    $_SESSION['nim'] = null;
    $_SESSION['name'] = 'Tamu Umum';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MVP Search Engine Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .card { border-radius: 15px; }
        .btn-primary { border-radius: 10px; padding: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4 font-weight-bold">MVP Search Engine</h3>
                    <p class="text-center text-muted mb-4">Silakan login untuk mulai mencari</p>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">NIM / Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukkan NIM atau Admin" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm mb-3">Masuk Sekarang</button>
                    </form>
                    
                    <div class="mt-2 text-center">
                        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left"></i> Kembali ke Pencarian Umum</a>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <hr>
                        <small class="text-muted">
                            Student Login: Pakai NIM (2024001)<br>
                            Admin Login: username 'admin'<br>
                            Password default: <b>password123</b> / <b>admin123</b>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
