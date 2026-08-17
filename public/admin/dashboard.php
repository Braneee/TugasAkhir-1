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
    <title>Admin Dashboard - CUAN Search</title>
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
        .clay-card { transition: all 0.2s ease-out; }
        .clay-card:hover { transform: translateY(-4px); box-shadow: 0 12px 0 0 #FBCFE8; }
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
        <a href="dashboard.php" class="clay-btn flex items-center gap-3 px-4 py-3 bg-c_primary text-white border-2 border-c_primary_dark shadow-[0_4px_0_0_#DB2777] rounded-2xl font-bold cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            Dashboard
        </a>
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-pink-700 hover:text-c_primary hover:bg-pink-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-c_secondary">
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
        <a href="analytics.php" class="flex items-center gap-3 px-4 py-3 text-pink-700 hover:text-c_primary hover:bg-pink-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-c_secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
            Analytics
        </a>
        <a href="relevance.php" class="flex items-center gap-3 px-4 py-3 text-pink-700 hover:text-c_primary hover:bg-pink-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-c_secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
            Relevance Tuning
        </a>
        <a href="crawler_logs.php" class="flex items-center gap-3 px-4 py-3 text-pink-700 hover:text-c_primary hover:bg-pink-50 rounded-2xl font-bold transition-colors cursor-pointer border-2 border-transparent hover:border-c_secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Crawler Logs
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
        <h2 class="text-4xl font-bold text-c_primary mb-2 brand-font">Selamat Datang, Admin!</h2>
        <p class="text-pink-600 font-semibold mb-8">Berikut ringkasan aktivitas pencarian dan data sistem.</p>
        
        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Stat 1 -->
            <div class="clay-card bg-indigo-400 rounded-3xl p-6 text-white border-4 border-indigo-300 shadow-[0_8px_0_0_#818CF8] relative overflow-hidden group cursor-default">
                <div class="absolute -right-4 -top-4 opacity-20 group-hover:scale-110 transition-transform duration-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <div class="relative z-10">
                    <h5 class="text-indigo-100 font-bold mb-1 text-lg">Total Mahasiswa</h5>
                    <h2 class="text-5xl font-extrabold brand-font"><?php echo $student_count; ?></h2>
                </div>
            </div>
            
            <!-- Stat 2 -->
            <div class="clay-card bg-sky-400 rounded-3xl p-6 text-white border-4 border-sky-300 shadow-[0_8px_0_0_#7DD3FC] relative overflow-hidden group cursor-default">
                <div class="absolute -right-4 -top-4 opacity-20 group-hover:scale-110 transition-transform duration-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <div class="relative z-10">
                    <h5 class="text-sky-100 font-bold mb-1 text-lg">Total Pencarian</h5>
                    <h2 class="text-5xl font-extrabold brand-font"><?php echo $search_count; ?></h2>
                </div>
            </div>

            <!-- Stat 3 -->
            <div class="clay-card bg-emerald-400 rounded-3xl p-6 text-white border-4 border-emerald-300 shadow-[0_8px_0_0_#6EE7B7] relative overflow-hidden group cursor-default">
                <div class="absolute -right-4 -top-4 opacity-20 group-hover:scale-110 transition-transform duration-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-32 w-32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div class="relative z-10">
                    <h5 class="text-emerald-100 font-bold mb-1 text-lg">Dokumen KB</h5>
                    <h2 class="text-5xl font-extrabold brand-font"><?php echo count($files); ?></h2>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- RECENT SEARCH LOGS -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden h-full">
                    <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                        <h3 class="brand-font font-bold text-c_primary text-xl">Pencarian Terakhir Mahasiswa</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-c_text font-semibold">
                            <thead class="bg-white border-b-2 border-pink-100 uppercase text-xs tracking-wider text-pink-400">
                                <tr>
                                    <th class="px-6 py-4">Mahasiswa</th>
                                    <th class="px-6 py-4">Query</th>
                                    <th class="px-6 py-4">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-pink-100">
                                <?php if (empty($logs)): ?>
                                    <tr><td colspan="3" class="px-6 py-8 text-center text-pink-300">Belum ada riwayat pencarian</td></tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="hover:bg-pink-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-extrabold text-c_text text-base"><?php echo htmlspecialchars($log['name']); ?></div>
                                                <div class="text-xs text-pink-500 font-bold"><?php echo htmlspecialchars($log['nim']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 font-bold text-pink-600">"<?php echo htmlspecialchars($log['query_text']); ?>"</td>
                                            <td class="px-6 py-4 text-pink-400 font-bold text-xs"><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div>
                <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                        <h3 class="brand-font font-bold text-c_primary text-xl">Aksi Cepat</h3>
                    </div>
                    <div class="p-6">
                        <a href="sync.php" class="clay-btn flex items-center justify-center gap-2 w-full py-4 bg-c_cta text-white border-2 border-c_cta_dark shadow-clay-cta rounded-2xl font-extrabold transition-colors cursor-pointer mb-3 text-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Sync Knowledgebase
                        </a>
                        <p class="text-sm font-semibold text-pink-500 text-center leading-relaxed mt-4">
                            Update index Elasticsearch dengan dokumen terbaru dari folder /documents/
                        </p>
                    </div>
                </div>
                
                <div class="bg-amber-100 rounded-3xl border-4 border-amber-200 p-5 flex gap-3 text-amber-800 text-sm shadow-[0_4px_0_0_#FDE68A]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong class="block font-extrabold mb-1 text-base">Peringatan!</strong>
                        <span class="font-bold">Pastikan server Elasticsearch lokal kamu sudah aktif sebelum melakukan Sinkronisasi.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
