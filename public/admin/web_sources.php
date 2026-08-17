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
        $message = "<div class='mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-3'><svg class='h-5 w-5 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Situs <b>$site_name</b> berhasil ditambahkan!</span></div>";
    } catch (PDOException $e) {
        $message = "<div class='mb-6 p-4 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl flex items-center gap-3'><svg class='h-5 w-5 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Gagal menambahkan situs: " . htmlspecialchars($e->getMessage()) . "</span></div>";
    }
}

// 2. Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM web_sources WHERE id = ?");
        $stmt->execute([$id]);
        $message = "<div class='mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-3'><svg class='h-5 w-5 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Situs berhasil dihapus!</span></div>";
    } catch (PDOException $e) {
        $message = "<div class='mb-6 p-4 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl flex items-center gap-3'><svg class='h-5 w-5 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Gagal menghapus situs.</span></div>";
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
        $message = "<div class='mb-6 p-4 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl flex items-center gap-3'><svg class='h-5 w-5 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Gagal mengubah status.</span></div>";
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
<body class="flex h-screen overflow-hidden bg-slate-50">

<!-- SIDEBAR -->
<aside class="w-64 bg-slate-900 text-slate-300 hidden md:flex flex-col shadow-xl z-20">
    <div class="p-6 flex items-center gap-3 text-white border-b border-slate-800">
        <div class="p-2 bg-primary rounded-xl text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <span class="brand-font font-bold text-xl tracking-wide">Admin Panel</span>
    </div>
    
    <div class="flex-grow py-6 px-4 space-y-2">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl font-medium transition-colors cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            Dashboard
        </a>
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl font-medium transition-colors cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            Manajemen User
        </a>
        <a href="sync.php" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl font-medium transition-colors cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Knowledgebase
        </a>
        <a href="web_sources.php" class="flex items-center gap-3 px-4 py-3 bg-primary text-white rounded-xl font-medium shadow-sm cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            Web Sources
        </a>
    </div>
    
    <div class="p-6 border-t border-slate-800">
        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-rose-400 hover:text-rose-300 hover:bg-slate-800 rounded-xl font-medium transition-colors cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="flex-grow flex flex-col h-screen overflow-y-auto">
    <!-- Topbar Mobile -->
    <div class="md:hidden bg-white shadow-sm border-b border-slate-200 p-4 flex justify-between items-center sticky top-0 z-10">
        <span class="brand-font font-bold text-xl text-slate-800">Admin Panel</span>
        <a href="../logout.php" class="text-rose-500 font-medium">Logout</a>
    </div>

    <div class="p-8 max-w-7xl mx-auto w-full">
        <h2 class="text-3xl font-bold text-slate-800 mb-8 brand-font">Manajemen Web Sources</h2>
        
        <?php echo $message; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <!-- LIST WEB SOURCES -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="brand-font font-bold text-slate-800 text-lg flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                            Daftar Situs Target
                        </h3>
                        <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            Total: <?php echo count($sources); ?>
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-xs tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Nama Situs</th>
                                    <th class="px-6 py-4">URL</th>
                                    <th class="px-6 py-4">Kategori</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($sources)): ?>
                                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada situs target yang ditambahkan.</td></tr>
                                <?php else: 
                                    foreach ($sources as $s): 
                                ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-slate-800"><?php echo htmlspecialchars($s['site_name']); ?></td>
                                        <td class="px-6 py-4">
                                            <a href="<?php echo htmlspecialchars($s['url']); ?>" target="_blank" class="text-sky-600 hover:text-sky-700 hover:underline inline-flex items-center gap-1">
                                                <?php echo htmlspecialchars($s['url']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700"><?php echo htmlspecialchars($s['category']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($s['status'] === 'active'): ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Nonaktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="?toggle=<?php echo $s['id']; ?>&status=<?php echo $s['status']; ?>" class="p-2 rounded-lg transition-colors <?php echo ($s['status'] === 'active' ? 'text-amber-500 hover:bg-amber-50' : 'text-emerald-500 hover:bg-emerald-50'); ?>" title="Ganti Status">
                                                    <?php if ($s['status'] === 'active'): ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    <?php else: ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    <?php endif; ?>
                                                </a>
                                                <a href="?delete=<?php echo $s['id']; ?>" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" onclick="return confirm('Hapus situs ini?')" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
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

            <div class="lg:col-span-1 space-y-6">
                <!-- ADD FORM -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="brand-font font-bold text-slate-800 text-lg flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Tambah Situs Baru
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Situs</label>
                                <input type="text" name="site_name" placeholder="Misal: UDINUS News" class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-700 transition-all" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">URL Utama</label>
                                <input type="url" name="url" placeholder="https://..." class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-700 transition-all" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Kategori</label>
                                <select name="category" class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-slate-700 transition-all">
                                    <option value="Berita">Berita</option>
                                    <option value="Pengumuman">Pengumuman</option>
                                    <option value="Informasi Umum" selected>Informasi Umum</option>
                                    <option value="Akademik">Akademik</option>
                                </select>
                            </div>
                            <button type="submit" name="add_source" class="w-full py-3 mt-4 bg-primary hover:bg-primaryHover text-white rounded-xl font-bold shadow-sm transition-colors duration-200 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Tambahkan Situs
                            </button>
                        </form>
                    </div>
                </div>

                <!-- SYNC BUTTON -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden text-center">
                    <div class="p-6">
                        <div class="inline-flex p-3 bg-slate-50 rounded-full text-slate-400 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        </div>
                        <h3 class="brand-font font-bold text-slate-800 text-lg mb-2">Sinkronisasi Web</h3>
                        <p class="text-xs text-slate-500 mb-6 px-4">Update index berdasarkan daftar situs aktif di atas untuk pencarian.</p>
                        
                        <form method="POST">
                            <button type="submit" name="sync_web" class="w-full py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold shadow-sm transition-colors duration-200 flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Sync Web Content
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SYNC LOGS -->
        <?php if ($sync_output): ?>
        <div class="mt-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="brand-font font-bold text-slate-800 text-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Sync Log
                    </h3>
                </div>
                <div class="p-6">
                    <div class="bg-slate-900 rounded-xl p-4 overflow-x-auto" style="max-height: 250px;">
                        <pre class="text-emerald-400 font-mono text-sm leading-relaxed"><?php echo htmlspecialchars($sync_output); ?></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

</body>
</html>