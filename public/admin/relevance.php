<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../api/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lexical = (float) ($_POST['lexical_weight'] ?? 0.5);
    $semantic = (float) ($_POST['semantic_weight'] ?? 0.5);
    
    try {
        $pdo->prepare("UPDATE system_config SET setting_value = ? WHERE setting_key = 'lexical_weight'")->execute([$lexical]);
        $pdo->prepare("UPDATE system_config SET setting_value = ? WHERE setting_key = 'semantic_weight'")->execute([$semantic]);
        $message = "Konfigurasi Bobot AI berhasil disimpan!";
    } catch (Exception $e) {
        $message = "Gagal menyimpan konfigurasi.";
    }
}

// Get current weights
try {
    $lexical_stmt = $pdo->query("SELECT setting_value FROM system_config WHERE setting_key = 'lexical_weight'");
    $lexical_weight = $lexical_stmt->fetchColumn() ?: 0.5;

    $semantic_stmt = $pdo->query("SELECT setting_value FROM system_config WHERE setting_key = 'semantic_weight'");
    $semantic_weight = $semantic_stmt->fetchColumn() ?: 0.5;
} catch (Exception $e) {
    $lexical_weight = 0.5;
    $semantic_weight = 0.5;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Relevance Tuning - CUAN Search</title>
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
        .brand-font { font-family: 'Varela Round', sans-serif; }
        
        input[type=range] {
            -webkit-appearance: none;
            width: 100%;
            background: transparent;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 28px;
            width: 28px;
            border-radius: 50%;
            background: #F472B6;
            cursor: pointer;
            margin-top: -10px;
            border: 4px solid #DB2777;
            box-shadow: 0 4px 0 0 #DB2777;
        }
        input[type=range]::-webkit-slider-runnable-track {
            width: 100%;
            height: 12px;
            cursor: pointer;
            background: #FBCFE8;
            border-radius: 999px;
            border: 2px solid #F472B6;
        }
    </style>
</head>
<body class="bg-c_bg min-h-screen p-8 text-c_text">

    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="flex items-center gap-4 mb-10">
            <a href="dashboard.php" class="p-3 bg-white rounded-xl border-4 border-c_secondary shadow-clay-card hover:-translate-y-1 hover:shadow-[0_12px_0_0_#FBCFE8] transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-c_primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="brand-font text-4xl font-extrabold text-c_primary">Relevance Tuning</h1>
                <p class="font-bold text-pink-400 mt-1">Konfigurasi Bobot Hybrid Search AI</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border-4 border-green-300 text-green-700 p-4 rounded-2xl mb-8 font-bold shadow-[0_4px_0_0_#86EFAC]">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary p-8 mb-8">
            <p class="text-sm font-semibold text-pink-500 leading-relaxed mb-8">
                Gunakan pengaturan ini untuk menyeimbangkan pencarian. <br/>
                <b>Lexical</b> (Pencocokan Kata Kunci Akurat - Elasticsearch BM25)<br/>
                <b>Semantic</b> (Pencocokan Makna dan Konteks Kalimat - Vector AI)
            </p>

            <form method="POST" action="" class="space-y-12">
                <!-- Lexical -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <label class="brand-font font-bold text-xl text-c_primary">Bobot Lexical (BM25)</label>
                        <span id="lexical_val" class="font-extrabold text-2xl text-c_cta bg-green-50 px-4 py-2 rounded-xl border-2 border-green-200"><?php echo floatval($lexical_weight); ?></span>
                    </div>
                    <input type="range" name="lexical_weight" id="lexical_weight" min="0" max="1" step="0.1" value="<?php echo $lexical_weight; ?>" oninput="updateVal('lexical')">
                </div>

                <!-- Semantic -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <label class="brand-font font-bold text-xl text-c_primary">Bobot Semantic (Vector)</label>
                        <span id="semantic_val" class="font-extrabold text-2xl text-c_cta bg-green-50 px-4 py-2 rounded-xl border-2 border-green-200"><?php echo floatval($semantic_weight); ?></span>
                    </div>
                    <input type="range" name="semantic_weight" id="semantic_weight" min="0" max="1" step="0.1" value="<?php echo $semantic_weight; ?>" oninput="updateVal('semantic')">
                </div>

                <div class="pt-4 border-t-4 border-pink-50">
                    <button type="submit" class="w-full bg-c_cta text-white font-extrabold text-xl py-4 px-6 rounded-2xl border-4 border-c_cta_dark shadow-clay-cta hover:-translate-y-1 hover:shadow-[0_8px_0_0_#16A34A] transition-all">
                        Simpan Konfigurasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateVal(type) {
            const val = document.getElementById(type + '_weight').value;
            document.getElementById(type + '_val').innerText = val;
        }
    </script>
</body>
</html>
