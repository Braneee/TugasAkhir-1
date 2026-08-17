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
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Nunito Sans', 'sans-serif'],
                        display: ['Varela Round', 'sans-serif'],
                    },
                    colors: {
                        primary: '#6366f1', 
                        primaryHover: '#4f46e5',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito Sans', sans-serif; background-color: #f8fafc; color: #334155; }
        .brand-font { font-family: 'Varela Round', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-50 p-4">

<div class="max-w-md w-full">
    <!-- Back Button -->
    <a href="index.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-primary transition-colors duration-200 mb-8 cursor-pointer font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Kembali ke Beranda
    </a>

    <!-- Login Card -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-8 pt-10 pb-8 text-center">
            <div class="inline-flex items-center justify-center p-3 bg-indigo-50 rounded-2xl text-primary mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <h2 class="brand-font text-2xl font-bold text-slate-900 mb-2">Selamat Datang!</h2>
            <p class="text-slate-500 text-sm">Silakan login untuk mengakses fitur lengkap.</p>
        </div>

        <div class="px-8 pb-10">
            <?php if (isset($error)): ?>
                <div class="bg-rose-50 text-rose-600 text-sm p-4 rounded-xl mb-6 flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">NIM / Username</label>
                    <input type="text" name="username" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all duration-200 text-slate-700" placeholder="Masukkan NIM atau username" required autofocus>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all duration-200 text-slate-700" placeholder="••••••••" required>
                </div>
                <button type="submit" class="w-full py-3 mt-4 bg-primary hover:bg-primaryHover text-white rounded-xl font-bold shadow-sm transition-colors duration-200 cursor-pointer">
                    Masuk Sekarang
                </button>
            </form>
        </div>
        
        <div class="bg-slate-50 px-8 py-5 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-500 leading-relaxed">
                <span class="font-semibold text-slate-700">Demo Akun:</span><br>
                Mahasiswa: <code class="bg-slate-200 px-1 py-0.5 rounded text-slate-700">2024001</code> (Pass: password123)<br>
                Admin: <code class="bg-slate-200 px-1 py-0.5 rounded text-slate-700">admin</code> (Pass: admin123)
            </p>
        </div>
    </div>
</div>

</body>
</html>
