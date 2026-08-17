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
    if ($id_to_delete != $_SESSION['user_id']) { 
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
    <title>Manajemen User - CUAN Search</title>
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
        .clay-input { transition: all 0.2s ease-out; }
        .clay-input:focus { transform: translateY(-2px); box-shadow: 0 4px 0 0 #F472B6 !important; border-color: #F472B6; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-c_bg relative">

<!-- SIDEBAR -->
<aside class="w-64 bg-white border-r-4 border-c_secondary text-c_text hidden md:flex flex-col z-20">
    <div class="p-6 flex items-center gap-3 border-b-4 border-c_secondary bg-pink-50/50">
        <div class="p-2 bg-c_primary rounded-xl text-white shadow-clay-btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <span class="brand-font font-bold text-xl tracking-wide text-c_primary">Admin Panel</span>
    </div>
    
    <div class="flex-grow py-6 px-4 space-y-3">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-pink-700 hover:text-c_primary hover:bg-pink-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-c_secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            Dashboard
        </a>
        <a href="users.php" class="clay-btn flex items-center gap-3 px-4 py-3 bg-c_primary text-white border-2 border-c_primary_dark shadow-[0_4px_0_0_#DB2777] rounded-2xl font-bold cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            Manajemen User
        </a>
        <a href="sync.php" class="flex items-center gap-3 px-4 py-3 text-pink-700 hover:text-c_primary hover:bg-pink-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-c_secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Knowledgebase
        </a>
        <a href="web_sources.php" class="flex items-center gap-3 px-4 py-3 text-pink-700 hover:text-c_primary hover:bg-pink-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-c_secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            Web Sources
        </a>
    </div>
    
    <div class="p-6 border-t-4 border-c_secondary">
        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-rose-500 hover:text-rose-600 hover:bg-rose-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-rose-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="flex-grow flex flex-col h-screen overflow-y-auto relative z-10">
    <!-- Topbar Mobile -->
    <div class="md:hidden bg-white border-b-4 border-c_secondary p-4 flex justify-between items-center sticky top-0 z-30">
        <span class="brand-font font-bold text-xl text-c_primary">Admin Panel</span>
        <a href="../logout.php" class="text-rose-500 font-bold">Logout</a>
    </div>

    <div class="p-8 max-w-7xl mx-auto w-full">
        <h2 class="text-4xl font-bold text-c_primary mb-8 brand-font">Manajemen User</h2>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-emerald-100 text-emerald-800 border-4 border-emerald-300 rounded-2xl flex items-center gap-3 font-bold shadow-[0_4px_0_0_#6EE7B7]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-rose-100 text-rose-800 border-4 border-rose-300 rounded-2xl flex items-center gap-3 font-bold shadow-[0_4px_0_0_#FDA4AF]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- FORM ADD USER -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden sticky top-8">
                    <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                        <h3 class="brand-font font-bold text-c_primary text-xl flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-c_primary_dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            Tambah User Baru
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">Nama Lengkap</label>
                                <input type="text" name="name" class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold" required>
                            </div>
                            <div>
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">Username (Login)</label>
                                <input type="text" name="username" class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold" required>
                            </div>
                            <div>
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">Password</label>
                                <input type="password" name="password" class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold" required>
                            </div>
                            <div>
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">Role</label>
                                <select name="role" id="roleSelect" class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold cursor-pointer" onchange="toggleNimField()" required>
                                    <option value="student">Mahasiswa</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div id="nimGroup" class="transition-all">
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">NIM (Untuk Mahasiswa)</label>
                                <input type="text" name="nim" id="nimInput" class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold" required>
                            </div>
                            <button type="submit" class="clay-btn w-full py-4 mt-6 bg-c_primary text-white border-2 border-c_primary_dark shadow-[0_4px_0_0_#DB2777] rounded-2xl font-extrabold cursor-pointer flex items-center justify-center gap-2 text-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Tambah User
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABLE USERS -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden">
                    <div class="px-6 py-5 border-b-4 border-c_secondary flex items-center justify-between bg-pink-50">
                        <h3 class="brand-font font-bold text-c_primary text-xl">Daftar User</h3>
                        <span class="bg-c_primary text-white px-4 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-wider shadow-sm">Total: <?php echo count($users); ?></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-c_text font-semibold">
                            <thead class="bg-white border-b-2 border-pink-100 uppercase text-xs tracking-wider text-pink-400">
                                <tr>
                                    <th class="px-6 py-4">Nama</th>
                                    <th class="px-6 py-4">Username</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">NIM</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-pink-100">
                                <?php if (empty($users)): ?>
                                    <tr><td colspan="5" class="px-6 py-8 text-center text-pink-300">Tidak ada user ditemukan</td></tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-pink-50 transition-colors">
                                            <td class="px-6 py-4 font-extrabold text-c_text text-base"><?= htmlspecialchars($user['name']) ?></td>
                                            <td class="px-6 py-4 font-bold text-pink-600"><?= htmlspecialchars($user['username']) ?></td>
                                            <td class="px-6 py-4">
                                                <?php if ($user['role'] === 'admin'): ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-extrabold bg-indigo-100 text-indigo-700 border-2 border-indigo-200">ADMIN</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-extrabold bg-emerald-100 text-emerald-700 border-2 border-emerald-200">STUDENT</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-pink-500 font-bold"><?= htmlspecialchars($user['nim'] ?? '-') ?></td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?delete=<?= $user['id'] ?>" class="clay-btn inline-flex items-center justify-center p-2.5 bg-white text-rose-500 border-2 border-rose-200 rounded-xl shadow-[0_2px_0_0_#FECDD3] cursor-pointer" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?');" title="Hapus User">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center justify-center p-2.5 bg-slate-100 text-slate-400 border-2 border-slate-200 rounded-xl opacity-50" title="Tidak bisa menghapus akun sendiri">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </span>
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
</main>

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
