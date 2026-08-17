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
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Nunito Sans', 'sans-serif'],
                        display: ['Varela Round', 'sans-serif'],
                    },
                    colors: {
                        c_bg: '#FDF2F8', /* Pink 50 */
                        c_primary: '#F472B6',
                        c_primary_dark: '#DB2777',
                        c_text: '#831843', /* Pink 900 */
                    },
                    boxShadow: {
                        'bento': '0 4px 20px -2px rgba(244, 114, 182, 0.1)',
                        'bento-hover': '0 10px 25px -2px rgba(244, 114, 182, 0.2)',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito Sans', sans-serif; background-color: #FDF2F8; color: #831843; }
        .brand-font { font-family: 'Varela Round', sans-serif; }
        
        .bento-card {
            background-color: white;
            border: 1px solid #FBCFE8;
            border-radius: 1.25rem; /* rounded-xl to rounded-2xl */
            box-shadow: 0 4px 20px -2px rgba(244, 114, 182, 0.1);
            transition: all 0.2s ease-in-out;
        }
        .bento-card:hover {
            box-shadow: 0 10px 25px -2px rgba(244, 114, 182, 0.2);
            border-color: #F9A8D4;
        }
        .btn-modern {
            transition: all 0.2s;
        }
        .btn-modern:hover {
            transform: translateY(-1px);
        }
        .btn-modern:active {
            transform: translateY(1px);
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-c_bg">

<!-- SIDEBAR -->
<aside class="w-64 bg-white border-r border-pink-100 flex flex-col z-20">
    <div class="p-6 flex items-center gap-3">
        <div class="p-2 bg-pink-100 rounded-lg text-c_primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <span class="brand-font font-bold text-xl text-c_primary">CUAN Admin</span>
    </div>
    
    <div class="flex-grow py-4 px-4 space-y-1">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-pink-50 text-c_primary rounded-xl font-semibold transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            Dashboard
        </a>
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-pink-600 hover:text-c_primary hover:bg-pink-50/50 rounded-xl font-medium transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            Manajemen User
        </a>
        <a href="sync.php" class="flex items-center gap-3 px-4 py-3 text-pink-600 hover:text-c_primary hover:bg-pink-50/50 rounded-xl font-medium transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            Knowledgebase
        </a>
        <a href="web_sources.php" class="flex items-center gap-3 px-4 py-3 text-pink-600 hover:text-c_primary hover:bg-pink-50/50 rounded-xl font-medium transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
            Web Sources
        </a>
    </div>
    
    <div class="p-6 border-t border-pink-100">
        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:text-rose-500 hover:bg-rose-50 rounded-xl font-medium transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="flex-grow flex flex-col h-screen overflow-y-auto">
    <div class="p-8 max-w-7xl mx-auto w-full">
        
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-c_text mb-1 brand-font">Ringkasan Sistem</h1>
            <p class="text-pink-600 font-medium text-sm">Overview aktivitas dan statistik CUAN Search Engine.</p>
        </header>
        
        <!-- BENTO GRID STATS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <!-- Stat 1 -->
            <div class="bento-card p-6 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-pink-600 font-semibold text-sm uppercase tracking-wider">Total Mahasiswa</h5>
                    <div class="p-2 bg-indigo-50 text-indigo-500 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                </div>
                <h2 class="text-4xl font-bold text-c_text brand-font"><?php echo $student_count; ?></h2>
            </div>
            
            <!-- Stat 2 -->
            <div class="bento-card p-6 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-pink-600 font-semibold text-sm uppercase tracking-wider">Total Pencarian</h5>
                    <div class="p-2 bg-sky-50 text-sky-500 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
                <h2 class="text-4xl font-bold text-c_text brand-font"><?php echo $search_count; ?></h2>
            </div>

            <!-- Stat 3 -->
            <div class="bento-card p-6 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-pink-600 font-semibold text-sm uppercase tracking-wider">Dokumen KB</h5>
                    <div class="p-2 bg-emerald-50 text-emerald-500 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                </div>
                <h2 class="text-4xl font-bold text-c_text brand-font"><?php echo count($files); ?></h2>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- RECENT SEARCH LOGS -->
            <div class="lg:col-span-2">
                <div class="bento-card flex flex-col h-full">
                    <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between">
                        <h3 class="font-bold text-c_text text-lg">Pencarian Terakhir</h3>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <table class="w-full text-left text-sm text-c_text">
                            <thead class="text-xs uppercase text-pink-400 font-semibold">
                                <tr>
                                    <th class="px-4 py-3">Pengguna</th>
                                    <th class="px-4 py-3">Kata Kunci</th>
                                    <th class="px-4 py-3">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-pink-50">
                                <?php if (empty($logs)): ?>
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-pink-300">Belum ada aktivitas.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="hover:bg-pink-50/50 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-c_text"><?php echo htmlspecialchars($log['name']); ?></div>
                                                <div class="text-xs text-pink-500 font-mono"><?php echo htmlspecialchars($log['nim']); ?></div>
                                            </td>
                                            <td class="px-4 py-3 font-medium text-pink-700">"<?php echo htmlspecialchars($log['query_text']); ?>"</td>
                                            <td class="px-4 py-3 text-slate-500 text-xs"><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS BENTO -->
            <div class="space-y-5">
                <div class="bento-card p-6">
                    <h3 class="font-bold text-c_text text-lg mb-4">Aksi Cepat</h3>
                    <p class="text-sm text-pink-600 mb-5 leading-relaxed">
                        Perbarui index pencarian setelah menambah dokumen baru.
                    </p>
                    <a href="sync.php" class="btn-modern flex items-center justify-center gap-2 w-full py-3 bg-c_primary text-white rounded-xl font-semibold shadow-sm hover:bg-c_primary_dark">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sinkronisasi Index
                    </a>
                </div>
                
                <div class="bento-card p-5 border-amber-200 bg-amber-50 shadow-none flex gap-3 text-amber-800 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong class="block font-bold mb-1">Service Elasticsearch</strong>
                        <span class="text-amber-700">Pastikan Elasticsearch lokal aktif sebelum Sync berjalan.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
