<?php
require_once 'api/config.php';
require_once 'api/session.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVP Search - Mesin Pencari Cerdas</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700&family=Varela+Round&display=swap" rel="stylesheet">
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
        
        /* Claymorphism hover animations */
        .clay-btn {
            transition: all 0.15s ease-out;
        }
        .clay-btn:active {
            transform: translateY(4px);
            box-shadow: none !important;
        }
        .clay-card {
            transition: all 0.2s ease-out;
        }
        .clay-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 0 0 #FBCFE8;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center relative overflow-hidden">

    <!-- Decorative background elements -->
    <div class="absolute top-10 left-10 w-32 h-32 bg-c_secondary rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse"></div>
    <div class="absolute bottom-10 right-10 w-40 h-40 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse" style="animation-delay: 2s;"></div>

    <!-- Top Right Nav -->
    <div class="absolute top-6 right-8 z-10 flex gap-4">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin/dashboard.php" class="clay-btn bg-white text-c_text border-2 border-c_secondary shadow-clay-card px-5 py-2.5 rounded-2xl font-bold flex items-center gap-2 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Dashboard
                </a>
            <?php else: ?>
                <div class="bg-white text-c_text border-2 border-c_secondary shadow-clay-card px-5 py-2.5 rounded-2xl font-bold flex items-center gap-2 cursor-default">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-c_primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Halo, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>
                </div>
            <?php endif; ?>
            <a href="logout.php" class="clay-btn bg-rose-400 text-white border-2 border-rose-500 shadow-[0_4px_0_0_#BE123C] px-5 py-2.5 rounded-2xl font-bold flex items-center gap-2 cursor-pointer">
                Logout
            </a>
        <?php else: ?>
            <a href="login.php" class="clay-btn bg-c_primary text-white border-2 border-c_primary_dark shadow-clay-btn px-6 py-2.5 rounded-2xl font-bold text-lg flex items-center gap-2 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                Masuk
            </a>
        <?php endif; ?>
    </div>

    <!-- Main Search Container -->
    <div class="w-full max-w-2xl px-6 z-10 text-center">
        <!-- Logo/Title -->
        <div class="mb-10 relative inline-block">
            <h1 class="text-6xl md:text-7xl font-bold brand-font text-c_primary tracking-tight" style="text-shadow: 0 4px 0 #FBCFE8, 0 8px 0 #FCE7F3;">
                CUAN
            </h1>
            <div class="mt-4 bg-white border-4 border-c_secondary rounded-2xl py-2 px-6 shadow-clay-card inline-block rotate-2">
                <p class="text-c_text font-bold text-lg">Search Engine</p>
            </div>
        </div>

        <!-- Search Form -->
        <form action="results.php" method="GET" class="w-full relative">
            <div class="relative clay-card bg-white border-4 border-c_secondary rounded-3xl p-2 shadow-clay-card flex items-center">
                <div class="pl-4 pr-2 text-c_primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    name="q" 
                    placeholder="Tanya apa saja seputar materi kuliah..." 
                    class="w-full py-4 px-2 bg-transparent text-xl font-semibold text-c_text focus:outline-none placeholder-pink-300"
                    required
                    autocomplete="off"
                >
                <button type="submit" class="clay-btn bg-c_cta text-white border-2 border-c_cta_dark shadow-clay-cta p-4 rounded-2xl cursor-pointer mr-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
            
            <div class="mt-8 flex justify-center gap-3 flex-wrap">
                <span class="text-pink-400 font-bold mt-2">Coba ini:</span>
                <a href="results.php?q=Apa+itu+Data+Mining" class="clay-btn bg-white border-2 border-c_secondary text-c_primary px-4 py-2 rounded-xl font-bold shadow-[0_4px_0_0_#FBCFE8] cursor-pointer">
                    ✨ Apa itu Data Mining?
                </a>
                <a href="results.php?q=Jadwal+UAS" class="clay-btn bg-white border-2 border-c_secondary text-c_primary px-4 py-2 rounded-xl font-bold shadow-[0_4px_0_0_#FBCFE8] cursor-pointer">
                    📅 Jadwal UAS
                </a>
            </div>
        </form>
    </div>

</body>
</html>
