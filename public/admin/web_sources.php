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
        $message = "<div class='mb-6 p-4 bg-emerald-100 text-emerald-800 border-4 border-emerald-300 rounded-2xl flex items-center gap-3 font-bold shadow-[0_4px_0_0_#6EE7B7]'><svg class='h-6 w-6 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='3'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Situs <b>$site_name</b> berhasil ditambahkan!</span></div>";
    } catch (PDOException $e) {
        $message = "<div class='mb-6 p-4 bg-rose-100 text-rose-800 border-4 border-rose-300 rounded-2xl flex items-center gap-3 font-bold shadow-[0_4px_0_0_#FDA4AF]'><svg class='h-6 w-6 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='3'><path stroke-linecap='round' stroke-linejoin='round' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Gagal menambahkan situs: " . htmlspecialchars($e->getMessage()) . "</span></div>";
    }
}

// 2. Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM web_sources WHERE id = ?");
        $stmt->execute([$id]);
        $message = "<div class='mb-6 p-4 bg-emerald-100 text-emerald-800 border-4 border-emerald-300 rounded-2xl flex items-center gap-3 font-bold shadow-[0_4px_0_0_#6EE7B7]'><svg class='h-6 w-6 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='3'><path stroke-linecap='round' stroke-linejoin='round' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Situs berhasil dihapus!</span></div>";
    } catch (PDOException $e) {
        $message = "<div class='mb-6 p-4 bg-rose-100 text-rose-800 border-4 border-rose-300 rounded-2xl flex items-center gap-3 font-bold shadow-[0_4px_0_0_#FDA4AF]'><svg class='h-6 w-6 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='3'><path stroke-linecap='round' stroke-linejoin='round' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Gagal menghapus situs.</span></div>";
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
        $message = "<div class='mb-6 p-4 bg-rose-100 text-rose-800 border-4 border-rose-300 rounded-2xl flex items-center gap-3 font-bold shadow-[0_4px_0_0_#FDA4AF]'><svg class='h-6 w-6 flex-shrink-0' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='3'><path stroke-linecap='round' stroke-linejoin='round' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' /></svg><span>Gagal mengubah status.</span></div>";
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
    <title>Manajemen Web Sources - CUAN Search</title>
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
                        'clay-cta': '0 4px 0 0 #16A34A',
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

<?php include 'sidebar.php'; ?>

<!-- MAIN CONTENT -->
<main class="flex-grow flex flex-col h-screen overflow-y-auto relative z-10">
    <!-- Topbar Mobile -->
    <div class="md:hidden bg-white border-b-4 border-c_secondary p-4 flex justify-between items-center sticky top-0 z-30">
        <span class="brand-font font-bold text-xl text-c_primary">Admin Panel</span>
        <a href="../logout.php" class="text-rose-500 font-bold">Logout</a>
    </div>

    <div class="p-8 max-w-7xl mx-auto w-full">
        <h2 class="text-4xl font-bold text-c_primary mb-8 brand-font">Manajemen Web Sources</h2>
        
        <?php echo $message; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <!-- LIST WEB SOURCES -->
                <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden">
                    <div class="px-6 py-5 border-b-4 border-c_secondary flex justify-between items-center bg-pink-50">
                        <h3 class="brand-font font-bold text-c_primary text-xl flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-c_primary_dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                            Daftar Situs Target
                        </h3>
                        <span class="bg-c_primary text-white px-4 py-1.5 rounded-full text-xs font-extrabold uppercase tracking-wider shadow-sm">
                            Total: <?php echo count($sources); ?>
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-c_text font-semibold">
                            <thead class="bg-white border-b-2 border-pink-100 uppercase text-xs tracking-wider text-pink-400">
                                <tr>
                                    <th class="px-6 py-4">Nama Situs</th>
                                    <th class="px-6 py-4">URL</th>
                                    <th class="px-6 py-4">Kategori</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-pink-100">
                                <?php if (empty($sources)): ?>
                                    <tr><td colspan="5" class="px-6 py-8 text-center text-pink-300">Belum ada situs target yang ditambahkan.</td></tr>
                                <?php else: 
                                    foreach ($sources as $s): 
                                ?>
                                    <tr class="hover:bg-pink-50 transition-colors">
                                        <td class="px-6 py-4 font-extrabold text-c_text text-base"><?php echo htmlspecialchars($s['site_name']); ?></td>
                                        <td class="px-6 py-4">
                                            <a href="<?php echo htmlspecialchars($s['url']); ?>" target="_blank" class="text-sky-500 hover:text-sky-600 font-bold hover:underline inline-flex items-center gap-1">
                                                <?php echo htmlspecialchars($s['url']); ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-extrabold bg-pink-100 text-pink-700 border-2 border-pink-200"><?php echo htmlspecialchars($s['category']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($s['status'] === 'active'): ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-emerald-100 text-emerald-700 border-2 border-emerald-200">
                                                    <span class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_8px_0_#34d399]"></span> Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold bg-rose-100 text-rose-700 border-2 border-rose-200">
                                                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> Nonaktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="?toggle=<?php echo $s['id']; ?>&status=<?php echo $s['status']; ?>" class="clay-btn p-2 rounded-xl transition-colors <?php echo ($s['status'] === 'active' ? 'bg-white text-amber-500 border-2 border-amber-200 shadow-[0_2px_0_0_#FDE68A]' : 'bg-white text-emerald-500 border-2 border-emerald-200 shadow-[0_2px_0_0_#A7F3D0]'); ?>" title="Ganti Status">
                                                    <?php if ($s['status'] === 'active'): ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    <?php else: ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    <?php endif; ?>
                                                </a>
                                                <a href="?delete=<?php echo $s['id']; ?>" class="clay-btn p-2 bg-white text-rose-500 border-2 border-rose-200 shadow-[0_2px_0_0_#FECDD3] rounded-xl transition-colors" onclick="return confirm('Hapus situs ini?')" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
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
                <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden">
                    <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                        <h3 class="brand-font font-bold text-c_primary text-xl flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-c_primary_dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Tambah Situs Baru
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">Nama Situs</label>
                                <input type="text" name="site_name" placeholder="Misal: UDINUS News" class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold" required>
                            </div>
                            <div>
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">URL Utama</label>
                                <input type="url" name="url" placeholder="https://..." class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold" required>
                            </div>
                            <div>
                                <label class="block text-sm font-extrabold text-c_text mb-1 pl-1">Kategori</label>
                                <select name="category" class="clay-input w-full px-4 py-3 rounded-2xl bg-pink-50/50 border-2 border-pink-200 focus:outline-none text-c_text font-bold cursor-pointer">
                                    <option value="Berita">Berita</option>
                                    <option value="Pengumuman">Pengumuman</option>
                                    <option value="Informasi Umum" selected>Informasi Umum</option>
                                    <option value="Akademik">Akademik</option>
                                </select>
                            </div>
                            <button type="submit" name="add_source" class="clay-btn w-full py-4 mt-6 bg-c_primary text-white border-2 border-c_primary_dark shadow-[0_4px_0_0_#DB2777] rounded-2xl font-extrabold flex items-center justify-center gap-2 cursor-pointer text-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Tambahkan Situs
                            </button>
                        </form>
                    </div>
                </div>

                <!-- SYNC BUTTON -->
                <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden text-center">
                    <div class="p-6">
                        <div class="inline-flex p-4 bg-pink-50 border-4 border-pink-100 rounded-full text-pink-400 mb-4 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        </div>
                        <h3 class="brand-font font-bold text-c_primary text-xl mb-2">Sinkronisasi Web</h3>
                        <p class="text-sm font-semibold text-pink-500 mb-6 px-4">Update index berdasarkan daftar situs aktif untuk pencarian.</p>
                        
                        <form method="POST">
                            <button type="submit" name="sync_web" class="clay-btn w-full py-4 bg-c_cta text-white border-2 border-c_cta_dark shadow-clay-cta rounded-2xl font-extrabold flex items-center justify-center gap-2 cursor-pointer text-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
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
            <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden">
                <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                    <h3 class="brand-font font-bold text-c_primary text-xl flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-c_primary_dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        Sync Log
                    </h3>
                </div>
                <div class="p-6">
                    <div class="bg-slate-900 rounded-2xl p-5 border-4 border-slate-700 shadow-inner overflow-x-auto" style="max-height: 250px;">
                        <pre class="text-emerald-400 font-mono text-sm font-bold leading-relaxed"><?php echo htmlspecialchars($sync_output); ?></pre>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

</body>
</html>