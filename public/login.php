<?php
require_once 'api/config.php';
require_once 'api/session.php';

// Jika sudah login, langsung ke index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['nim'] = $user['nim'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem.';
        }
    } else {
        $error = 'Mohon isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CUAN Search Engine</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&family=Varela+Round&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Nunito Sans', 'sans-serif'],
                        display: ['Varela Round', 'sans-serif'],
                    },
                    colors: {
                        c_bg: '#FDF2F8',
                        c_primary: '#F472B6',
                        c_primary_dark: '#DB2777',
                        c_secondary: '#FBCFE8',
                        c_text: '#9D174D',
                        c_cta: '#22C55E',
                        c_cta_dark: '#16A34A',
                    },
                    boxShadow: {
                        'clay-card': '0 8px 0 0 #FBCFE8',
                        'clay-btn': '0 4px 0 0 #DB2777',
                        'clay-input': 'inset 0 4px 0 0 rgba(0,0,0,0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito Sans', sans-serif; background-color: #FDF2F8; color: #9D174D; }
        .brand-font { font-family: 'Varela Round', sans-serif; }
        
        .clay-btn { transition: all 0.15s ease-out; }
        .clay-btn:active { transform: translateY(4px); box-shadow: none !important; }
        .clay-input {
            transition: all 0.2s ease-out;
        }
        .clay-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 0 0 #F472B6 !important;
            border-color: #F472B6;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative p-4 overflow-hidden">

    <!-- Decorative blobs -->
    <div class="absolute top-1/4 left-1/4 w-48 h-48 bg-c_secondary rounded-full mix-blend-multiply filter blur-2xl opacity-70 animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-64 h-64 bg-pink-300 rounded-full mix-blend-multiply filter blur-2xl opacity-70 animate-pulse" style="animation-delay: 2s;"></div>

    <div class="w-full max-w-md z-10">
        <!-- Back to Home -->
        <a href="index.php" class="inline-flex items-center gap-2 text-c_primary font-bold hover:text-c_primary_dark mb-6 transition-colors bg-white px-4 py-2 rounded-xl border-2 border-pink-200 shadow-[0_2px_0_0_#FBCFE8]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali ke Beranda
        </a>

        <!-- Login Card -->
        <div class="bg-white border-4 border-c_secondary rounded-3xl p-8 shadow-clay-card relative">
            <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-c_primary border-4 border-c_primary_dark rounded-2xl w-20 h-20 flex items-center justify-center shadow-clay-btn">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <div class="text-center mt-8 mb-8">
                <h2 class="text-3xl font-bold brand-font text-c_text">Selamat Datang!</h2>
                <p class="text-pink-400 font-semibold mt-2">Login ke CUAN Search Engine</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-rose-50 border-2 border-rose-200 rounded-2xl flex items-center gap-3 text-rose-600 font-bold shadow-[0_2px_0_0_#FECDD3]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-c_text mb-2 pl-1">Username</label>
                    <input type="text" name="username" class="clay-input w-full px-5 py-4 rounded-2xl bg-slate-50 border-2 border-slate-200 text-c_text font-bold focus:outline-none placeholder-slate-400 shadow-[inset_0_4px_0_0_rgba(0,0,0,0.05)]" placeholder="Masukkan username..." required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-c_text mb-2 pl-1">Password</label>
                    <input type="password" name="password" class="clay-input w-full px-5 py-4 rounded-2xl bg-slate-50 border-2 border-slate-200 text-c_text font-bold focus:outline-none placeholder-slate-400 shadow-[inset_0_4px_0_0_rgba(0,0,0,0.05)]" placeholder="Masukkan password..." required>
                </div>
                
                <button type="submit" class="clay-btn w-full bg-c_primary text-white border-2 border-c_primary_dark shadow-clay-btn py-4 rounded-2xl font-extrabold text-lg mt-4 cursor-pointer flex justify-center items-center gap-2">
                    Masuk Sekarang
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>
            </form>
        </div>
        
        <div class="text-center mt-8">
            <p class="text-pink-400 font-bold text-sm">
                Hubungi Admin jika belum memiliki akun.
            </p>
        </div>
    </div>

</body>
</html>
