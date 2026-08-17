<?php
require_once '../api/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once '../api/config.php';

// Fetch Logs
try {
    $stmt = $pdo->query("SELECT * FROM crawler_logs ORDER BY created_at DESC LIMIT 50");
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crawler Logs - CUAN Search</title>
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
        <!-- Header -->
        <div class="flex items-center gap-4 mb-10">
            <div>
                <h1 class="brand-font text-4xl font-extrabold text-c_primary">Crawler Logs</h1>
                <p class="font-bold text-pink-400 mt-1">Status Pengambilan Data Web</p>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-clay-card border-4 border-c_secondary overflow-hidden">
            <div class="px-6 py-5 border-b-4 border-c_secondary bg-pink-50">
                <h3 class="brand-font font-bold text-c_primary text-xl">Riwayat Sinkronisasi Web</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-c_text font-semibold">
                    <thead class="bg-white border-b-2 border-pink-100 uppercase text-xs tracking-wider text-pink-400">
                        <tr>
                            <th class="px-6 py-4">Waktu</th>
                            <th class="px-6 py-4">Source URL</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Dokumen</th>
                            <th class="px-6 py-4">Error Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-pink-100">
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center text-pink-300">Belum ada riwayat web crawler</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-pink-50 transition-colors">
                                    <td class="px-6 py-4 text-xs font-bold text-pink-400">
                                        <?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 font-bold">
                                        <a href="<?php echo htmlspecialchars($log['source_url']); ?>" target="_blank" class="text-blue-500 hover:underline">
                                            <?php echo htmlspecialchars(strlen($log['source_url']) > 50 ? substr($log['source_url'],0,50).'...' : $log['source_url']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($log['status'] === 'success'): ?>
                                            <span class="px-3 py-1 bg-green-100 text-green-700 border-2 border-green-200 rounded-lg text-xs font-extrabold uppercase">Success</span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-red-100 text-red-700 border-2 border-red-200 rounded-lg text-xs font-extrabold uppercase">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 font-extrabold text-c_primary text-lg text-center">
                                        <?php echo $log['documents_indexed']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-red-400">
                                        <?php echo htmlspecialchars($log['error_message'] ?: '-'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
</html>
