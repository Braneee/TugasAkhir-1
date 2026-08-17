<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../api/config.php';

// Top Keywords
try {
    $stmt = $pdo->query("SELECT query_text, COUNT(*) as search_count FROM search_logs GROUP BY query_text ORDER BY search_count DESC LIMIT 10");
    $top_keywords = $stmt->fetchAll();
    
    // Zero Result Searches
    $stmt = $pdo->query("SELECT query_text, COUNT(*) as failed_count FROM search_logs WHERE result_count = 0 GROUP BY query_text ORDER BY failed_count DESC LIMIT 10");
    $zero_results = $stmt->fetchAll();

    // Feedback Stats
    $upvotes = $pdo->query("SELECT COUNT(*) FROM search_feedback WHERE feedback_type = 'up'")->fetchColumn();
    $downvotes = $pdo->query("SELECT COUNT(*) FROM search_feedback WHERE feedback_type = 'down'")->fetchColumn();
} catch (Exception $e) {
    $top_keywords = [];
    $zero_results = [];
    $upvotes = 0;
    $downvotes = 0;
}

$labels = [];
$data = [];
foreach ($top_keywords as $k) {
    $labels[] = $k['query_text'];
    $data[] = $k['search_count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - CUAN Search</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>
<body class="bg-c_bg min-h-screen p-8 text-c_text">

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-10">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="p-3 bg-white rounded-xl border-4 border-c_secondary shadow-clay-card hover:-translate-y-1 hover:shadow-[0_12px_0_0_#FBCFE8] transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-c_primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="brand-font text-4xl font-extrabold text-c_primary">Analytics Dashboard</h1>
                    <p class="font-bold text-pink-400 mt-1">Insight Pencarian & Performa AI</p>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-3xl p-6 border-4 border-c_secondary shadow-clay-card flex items-center justify-between">
                <div>
                    <p class="text-pink-400 font-bold uppercase tracking-wider text-sm mb-1">Total Upvotes 👍</p>
                    <p class="brand-font text-5xl font-extrabold text-c_cta"><?php echo $upvotes; ?></p>
                </div>
            </div>
            <div class="bg-white rounded-3xl p-6 border-4 border-c_secondary shadow-clay-card flex items-center justify-between">
                <div>
                    <p class="text-pink-400 font-bold uppercase tracking-wider text-sm mb-1">Total Downvotes 👎</p>
                    <p class="brand-font text-5xl font-extrabold text-red-500"><?php echo $downvotes; ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- CHART -->
            <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden">
                <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                    <h3 class="brand-font font-bold text-c_primary text-xl">Top 10 Kata Kunci</h3>
                </div>
                <div class="p-6">
                    <canvas id="keywordChart" height="250"></canvas>
                </div>
            </div>

            <!-- ZERO RESULT -->
            <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden">
                <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                    <h3 class="brand-font font-bold text-red-500 text-xl">Pencarian Gagal (Zero-Result)</h3>
                    <p class="text-xs text-pink-500 font-bold mt-1">Kata kunci yang butuh ditambahkan ke Knowledgebase</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-c_text font-semibold">
                        <thead class="bg-white border-b-2 border-pink-100 uppercase text-xs tracking-wider text-pink-400">
                            <tr>
                                <th class="px-6 py-4">Kata Kunci</th>
                                <th class="px-6 py-4 text-center">Jumlah Gagal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-pink-100">
                            <?php if (empty($zero_results)): ?>
                                <tr><td colspan="2" class="px-6 py-8 text-center text-pink-300">Belum ada data pencarian gagal</td></tr>
                            <?php else: ?>
                                <?php foreach ($zero_results as $zr): ?>
                                    <tr class="hover:bg-pink-50 transition-colors">
                                        <td class="px-6 py-4 font-extrabold text-red-500">"<?php echo htmlspecialchars($zr['query_text']); ?>"</td>
                                        <td class="px-6 py-4 text-center font-bold text-lg"><?php echo htmlspecialchars($zr['failed_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('keywordChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Jumlah Pencarian',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: '#F472B6',
                    borderColor: '#DB2777',
                    borderWidth: 3,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>
