<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';
$source_file = '../../scripts/web_sources.json';

// Create file if not exists
if (!file_exists($source_file)) {
    file_put_contents($source_file, json_encode(['urls' => []]));
}

$sources = json_decode(file_get_contents($source_file), true);
if (!$sources) $sources = ['urls' => []];

// Add URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $new_url = filter_var($_POST['url'], FILTER_VALIDATE_URL);
    if ($new_url) {
        if (!in_array($new_url, $sources['urls'])) {
            $sources['urls'][] = $new_url;
            file_put_contents($source_file, json_encode($sources, JSON_PRETTY_PRINT));
            $message = "URL berhasil ditambahkan!";
        } else {
            $error = "URL sudah ada di dalam daftar.";
        }
    } else {
        $error = "Format URL tidak valid.";
    }
}

// Delete URL
if (isset($_GET['delete'])) {
    $url_to_delete = $_GET['delete'];
    $index = array_search($url_to_delete, $sources['urls']);
    if ($index !== false) {
        array_splice($sources['urls'], $index, 1);
        file_put_contents($source_file, json_encode($sources, JSON_PRETTY_PRINT));
        $message = "URL berhasil dihapus.";
    }
}

// Trigger Web Crawler Sync
if (isset($_GET['action']) && $_GET['action'] === 'crawl') {
    $script_path = realpath('../../scripts/web_indexer.py');
    $python_exe = 'python';
    
    // Background execution
    $cmd = "start /B $python_exe \"$script_path\"";
    pclose(popen($cmd, "r"));
    $message = "Web Crawler (Spider) sedang berjalan di latar belakang. Proses mungkin memakan waktu.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Sources - CUAN Search</title>
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
                        c_bg: '#FDF2F8',
                        c_primary: '#F472B6',
                        c_primary_dark: '#DB2777',
                        c_text: '#831843',
                    },
                    boxShadow: {
                        'bento': '0 4px 20px -2px rgba(244, 114, 182, 0.1)',
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
            border-radius: 1.25rem;
            box-shadow: 0 4px 20px -2px rgba(244, 114, 182, 0.1);
        }
        .bento-input {
            border: 1px solid #FBCFE8;
            transition: all 0.2s;
        }
        .bento-input:focus {
            border-color: #F472B6;
            box-shadow: 0 0 0 3px rgba(244, 114, 182, 0.2);
            outline: none;
        }
        .btn-modern { transition: all 0.2s; }
        .btn-modern:hover { transform: translateY(-1px); }
        .btn-modern:active { transform: translateY(1px); }
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
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-pink-600 hover:text-c_primary hover:bg-pink-50/50 rounded-xl font-medium transition-colors">
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
        <a href="web_sources.php" class="flex items-center gap-3 px-4 py-3 bg-pink-50 text-c_primary rounded-xl font-semibold transition-colors">
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

<main class="flex-grow flex flex-col h-screen overflow-y-auto">
    <div class="p-8 max-w-7xl mx-auto w-full">
        
        <header class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold text-c_text mb-1 brand-font">Web Sources (Crawler)</h1>
                <p class="text-pink-600 font-medium text-sm">Kelola daftar URL website eksternal untuk dicari dan di-index.</p>
            </div>
            <a href="?action=crawl" class="btn-modern px-5 py-2.5 bg-c_primary text-white rounded-xl font-semibold shadow-sm hover:bg-c_primary_dark flex items-center gap-2 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                </svg>
                Jalankan Web Crawler
            </a>
        </header>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl flex items-center gap-3 font-medium text-sm shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl flex items-center gap-3 font-medium text-sm shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- FORM ADD URL -->
            <div class="lg:col-span-1">
                <div class="bento-card sticky top-8">
                    <div class="px-6 py-5 border-b border-pink-100">
                        <h3 class="font-bold text-c_text text-lg">Tambah Web Source</h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="block text-xs font-bold text-c_text mb-1 uppercase tracking-wider">URL Website</label>
                                <input type="url" name="url" placeholder="https://example.com/artikel" class="bento-input w-full px-4 py-2.5 rounded-xl bg-slate-50 text-c_text text-sm" required>
                                <p class="text-[11px] text-pink-400 mt-2 leading-relaxed">
                                    Sistem akan melakukan ekstraksi teks dari URL ini saat sinkronisasi web berjalan.
                                </p>
                            </div>
                            <button type="submit" class="btn-modern w-full py-3 mt-4 bg-c_primary text-white rounded-xl font-semibold shadow-sm hover:bg-c_primary_dark text-sm">
                                Simpan URL
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TABLE URLS -->
            <div class="lg:col-span-2">
                <div class="bento-card overflow-hidden">
                    <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between">
                        <h3 class="font-bold text-c_text text-lg">Daftar Sumber Web</h3>
                        <span class="bg-pink-100 text-c_primary px-3 py-1 rounded-lg text-xs font-bold"><?= count($sources['urls']) ?> URL</span>
                    </div>
                    <div class="p-2">
                        <?php if (empty($sources['urls'])): ?>
                            <div class="p-8 text-center text-pink-300 font-medium">Belum ada URL yang ditambahkan.</div>
                        <?php else: ?>
                            <ul class="divide-y divide-pink-50">
                                <?php foreach ($sources['urls'] as $url): ?>
                                    <li class="flex items-center justify-between p-4 hover:bg-pink-50/50 transition-colors">
                                        <div class="flex items-center gap-4 overflow-hidden pr-4">
                                            <div class="p-2 bg-sky-50 text-sky-500 rounded-lg shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                            </div>
                                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="font-semibold text-c_primary hover:underline block text-sm truncate"><?= htmlspecialchars($url) ?></a>
                                        </div>
                                        <a href="?delete=<?= urlencode($url) ?>" class="p-2 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors shrink-0" onclick="return confirm('Hapus URL ini?');" title="Hapus URL">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>