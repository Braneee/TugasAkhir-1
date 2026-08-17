<?php
require_once 'api/session.php';
// Jika belum login, otomatis jadi guest
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest';
    $_SESSION['name'] = 'Tamu Umum';
    $_SESSION['nim'] = null;
}
require_once 'api/config.php';

// Fetch Search History (Only for Students)
$history = [];
if ($_SESSION['role'] === 'student') {
    try {
        $stmt = $pdo->prepare("SELECT query_text FROM search_logs WHERE nim = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['nim']]);
        $history = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $history = [];
    }
}

// Suggested Keywords
$suggestions = ["UKT Semester 2", "Nilai Kalkulus", "Prosedur Wisuda", "Cek IPK", "Cara Bayar Biaya Kuliah"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Engine Kampus</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                        primary: '#6366f1', // Soft Indigo
                        primaryHover: '#4f46e5',
                        background: '#f8fafc', // Slate 50
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Nunito Sans', sans-serif; background-color: #f8fafc; color: #334155; }
        h1, h2, h3, h4, h5, h6, .brand-font { font-family: 'Varela Round', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex flex-col relative">

<!-- Floating Navbar -->
<nav class="absolute top-4 left-4 right-4 bg-white/80 backdrop-blur-md shadow-sm border border-slate-200 rounded-2xl px-6 py-4 flex items-center justify-between z-10 max-w-5xl mx-auto w-full">
    <a href="index.php" class="flex items-center gap-2 text-primary font-bold text-xl brand-font transition-opacity hover:opacity-80">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span>MVP Search</span>
    </a>
    
    <div>
        <?php if ($_SESSION['role'] !== 'guest'): ?>
            <div class="flex items-center gap-4 text-sm font-medium">
                <span class="text-slate-600">Halo, <span class="font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION['name']); ?></span> <?php echo $_SESSION['nim'] ? "(".htmlspecialchars($_SESSION['nim']).")" : ""; ?></span>
                <a href="logout.php" class="px-4 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl transition-colors duration-200 cursor-pointer">Keluar</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="px-6 py-2 bg-primary text-white hover:bg-primaryHover rounded-xl shadow-sm transition-colors duration-200 font-medium cursor-pointer">Masuk</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Main Content -->
<main class="flex-grow flex flex-col items-center justify-center pt-32 pb-16 px-4">
    
    <!-- Hero Image (SVG instead of raster) -->
    <div class="mb-8 p-4 bg-indigo-50 rounded-full text-indigo-500 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    
    <h1 class="text-3xl md:text-5xl brand-font text-slate-900 mb-8 text-center leading-tight">Apa yang ingin Anda <br> <span class="text-primary">cari</span> hari ini?</h1>
    
    <form action="results.php" method="GET" class="w-full max-w-2xl relative group">
        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        <input type="text" name="q" class="w-full pl-16 pr-32 py-5 rounded-2xl bg-white border-2 border-slate-100 shadow-sm text-lg focus:outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all duration-300 text-slate-700 placeholder-slate-400" placeholder="Ketik pertanyaan Anda di sini..." required autofocus>
        <button type="submit" class="absolute right-3 top-3 bottom-3 px-8 bg-primary hover:bg-primaryHover text-white font-medium rounded-xl shadow-sm transition-colors duration-200 cursor-pointer">Cari</button>
    </form>

    <div class="mt-8 max-w-2xl w-full text-center">
        <p class="text-sm font-medium text-slate-500 mb-4 uppercase tracking-wider">Pencarian Populer</p>
        <div class="flex flex-wrap justify-center gap-3">
            <?php foreach ($suggestions as $sug): ?>
                <a href="results.php?q=<?php echo urlencode($sug); ?>" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-full text-sm font-medium hover:border-primary hover:text-primary hover:shadow-sm transition-all duration-200 cursor-pointer">
                    <?php echo $sug; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($history)): ?>
    <div class="mt-16 w-full max-w-xl">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 overflow-hidden">
            <h3 class="brand-font text-lg text-slate-800 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Riwayat Pencarian
            </h3>
            <div class="space-y-2">
                <?php foreach ($history as $h): ?>
                    <a href="results.php?q=<?php echo urlencode($h); ?>" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 text-slate-600 transition-colors duration-200 cursor-pointer group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="font-medium"><?php echo htmlspecialchars($h); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
</main>

<footer class="py-8 text-center text-slate-400 text-sm">
    <p>&copy; 2026 MVP Search Engine Kampus with NLP</p>
</footer>

</body>
</html>
