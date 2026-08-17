<?php
require_once 'api/config.php';
require_once 'api/session.php';

$query = $_GET['q'] ?? '';
$results = [];
$time_taken = 0;

if ($query) {
    // Log pencarian jika user login
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO search_logs (nim, query_text) VALUES (?, ?)");
            $stmt->execute([$_SESSION['nim'], $query]);
        } catch (Exception $e) {
            // Abaikan error logging
        }
    }

    $start_time = microtime(true);
    
    // Call FastAPI backend
    $data = array(
        "query" => $query,
        "top_k" => 10,
        "use_semantic" => true
    );
    
    $ch = curl_init('http://localhost:8000/search');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    if (!curl_errno($ch)) {
        $json_response = json_decode($response, true);
        if (isset($json_response['results'])) {
            $results = $json_response['results'];
        }
    }
    curl_close($ch);
    
    $end_time = microtime(true);
    $time_taken = round($end_time - $start_time, 3);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($query) ?> - CUAN Search</title>
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
                        'clay-card-hover': '0 12px 0 0 #FBCFE8',
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
        .clay-card { transition: all 0.2s ease-out; }
        .clay-card:hover { transform: translateY(-4px); box-shadow: 0 12px 0 0 #FBCFE8; }
        
        .highlight { background-color: #FBCFE8; padding: 2px 4px; border-radius: 4px; font-weight: bold; color: #DB2777; }
    </style>
</head>
<body class="min-h-screen bg-c_bg">

    <!-- Top Navigation & Search Bar -->
    <div class="bg-white border-b-4 border-c_secondary sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-6 py-4 flex flex-col md:flex-row items-center gap-6">
            <!-- Logo -->
            <a href="index.php" class="flex-shrink-0">
                <h1 class="text-3xl font-bold brand-font text-c_primary tracking-tight hover:text-c_primary_dark transition-colors" style="text-shadow: 0 2px 0 #FBCFE8;">
                    CUAN
                </h1>
            </a>
            
            <!-- Search Form -->
            <form action="results.php" method="GET" class="flex-grow w-full md:w-auto relative">
                <div class="relative bg-white border-4 border-c_secondary rounded-2xl flex items-center shadow-clay-card">
                    <input 
                        type="text" 
                        name="q" 
                        value="<?= htmlspecialchars($query) ?>" 
                        class="w-full py-3 px-6 bg-transparent text-lg font-bold text-c_text focus:outline-none placeholder-pink-300"
                        autocomplete="off"
                    >
                    <button type="submit" class="clay-btn bg-c_cta text-white border-2 border-c_cta_dark shadow-clay-cta p-2 mr-1 rounded-xl cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </form>
            
            <!-- User Nav -->
            <div class="flex-shrink-0 flex gap-3 hidden md:flex">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="clay-btn bg-white border-2 border-c_secondary shadow-[0_4px_0_0_#FBCFE8] px-4 py-2.5 rounded-xl font-bold text-c_primary cursor-pointer flex items-center gap-2">
                            Dashboard
                        </a>
                    <?php else: ?>
                        <div class="bg-white border-2 border-c_secondary shadow-[0_4px_0_0_#FBCFE8] px-4 py-2.5 rounded-xl font-bold text-c_text cursor-default flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-c_primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                        </div>
                    <?php endif; ?>
                    <a href="logout.php" class="clay-btn bg-rose-400 text-white border-2 border-rose-500 shadow-[0_4px_0_0_#BE123C] p-2.5 rounded-xl cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="clay-btn bg-c_primary text-white border-2 border-c_primary_dark shadow-clay-btn px-6 py-2.5 rounded-xl font-bold cursor-pointer flex items-center gap-2">
                        Masuk
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-6 py-8">
        
        <?php if ($query): ?>
            <div class="mb-8 p-4 bg-white border-4 border-c_secondary rounded-2xl shadow-[0_4px_0_0_#FBCFE8] flex items-center gap-3 inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-c_primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <p class="text-c_text font-bold">
                    Ditemukan <span class="text-c_primary text-xl"><?= count($results) ?></span> hasil dalam <span class="text-c_primary"><?= $time_taken ?></span> detik
                </p>
            </div>

            <?php if (empty($results)): ?>
                <div class="text-center py-20">
                    <div class="bg-white border-4 border-c_secondary shadow-clay-card rounded-3xl p-10 inline-block max-w-lg">
                        <div class="bg-pink-100 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-c_primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold brand-font text-c_text mb-2">Waduh, tidak ketemu!</h2>
                        <p class="text-pink-500 font-medium">Maaf, kami tidak menemukan hasil untuk "<strong><?= htmlspecialchars($query) ?></strong>". Coba gunakan kata kunci lain ya.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($results as $res): ?>
                        <div class="clay-card bg-white border-4 border-c_secondary rounded-3xl p-6 shadow-clay-card relative overflow-hidden group">
                            
                            <!-- Source Badge -->
                            <div class="absolute top-0 right-0 bg-c_secondary text-c_primary_dark font-bold text-xs px-4 py-2 rounded-bl-2xl">
                                <?php if ($res['type'] === 'kb'): ?>
                                    <span class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                        Dokumen Internal
                                    </span>
                                <?php else: ?>
                                    <span class="flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                                        Web Internet
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="pr-32">
                                <h3 class="text-2xl font-bold brand-font text-c_primary mb-2 group-hover:text-c_primary_dark transition-colors">
                                    <?php if ($res['type'] === 'web'): ?>
                                        <a href="<?= htmlspecialchars($res['url']) ?>" target="_blank" class="hover:underline flex items-center gap-2">
                                            <?= htmlspecialchars($res['title']) ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($res['title']) ?>
                                    <?php endif; ?>
                                </h3>
                                
                                <p class="text-pink-900 leading-relaxed font-medium">
                                    <!-- Simplified highlighting fallback in PHP -->
                                    <?php 
                                        $content = htmlspecialchars($res['content'] ?? '');
                                        // Highlight query terms roughly
                                        $terms = explode(' ', $query);
                                        foreach($terms as $term) {
                                            if (strlen($term) > 2) {
                                                $content = preg_replace('/('.preg_quote($term, '/').')/i', '<span class="highlight">$1</span>', $content);
                                            }
                                        }
                                        echo $content;
                                    ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20">
                <div class="bg-white border-4 border-c_secondary shadow-clay-card rounded-3xl p-10 inline-block">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-pink-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-2xl font-bold brand-font text-c_text">Ketik sesuatu di atas untuk mulai mencari!</h2>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
