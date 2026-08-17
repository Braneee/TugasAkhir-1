<?php
require_once 'api/session.php';
// Jika belum login, otomatis jadi guest
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest';
    $_SESSION['name'] = 'Tamu Umum';
    $_SESSION['nim'] = null;
}
require_once 'api/config.php';

$query = $_GET['q'] ?? '';
if (empty($query)) {
    header('Location: index.php');
    exit;
}

// 1. Simpan ke riwayat pencarian (Hanya Mahasiswa)
if ($_SESSION['role'] === 'student') {
    try {
        $stmt = $pdo->prepare("INSERT INTO search_logs (nim, query_text) VALUES (?, ?)");
        $stmt->execute([$_SESSION['nim'], $query]);
    } catch (Exception $e) {
        // Abaikan error riwayat
    }
}

// 2. Panggil Python NLP API (The Brain)
$nlp_data = ['intent' => 'GENERAL', 'entities' => []];
try {
    $nlp_url = "http://localhost:8000/analyze";
    $ch = curl_init($nlp_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query, 'nim' => $_SESSION['nim']]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $nlp_response = curl_exec($ch);
    curl_close($ch);
    if ($nlp_response) {
        $nlp_data = json_decode($nlp_response, true);
    }
} catch (Exception $e) {}

// 3. Ambil data Akademik/Keuangan
$academic_results = null;
if ($_SESSION['role'] === 'student' && isset($nlp_data['intent']) && ($nlp_data['intent'] === 'ACADEMIC' || $nlp_data['intent'] === 'FINANCE')) {
    try {
        $type = ($nlp_data['intent'] === 'ACADEMIC') ? 'academic' : 'finance';
        if ($type === 'academic') {
            $sql = "SELECT mata_kuliah, nilai, semester FROM academic_data WHERE nim = ?";
            $params = [$_SESSION['nim']];
            if (isset($nlp_data['entities']['mata_kuliah'])) {
                $sql .= " AND mata_kuliah LIKE ?";
                $params[] = "%" . $nlp_data['entities']['mata_kuliah'] . "%";
            }
            if (isset($nlp_data['entities']['semester'])) {
                $sql .= " AND semester = ?";
                $params[] = $nlp_data['entities']['semester'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            $academic_results = ['status' => 'success', 'data' => $data];
        } elseif ($type === 'finance') {
            $sql = "SELECT semester, bill, status FROM finance_data WHERE nim = ?";
            $params = [$_SESSION['nim']];
            if (isset($nlp_data['entities']['semester'])) {
                $sql .= " AND semester = ?";
                $params[] = $nlp_data['entities']['semester'];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            $academic_results = ['status' => 'success', 'data' => $data];
        }
    } catch (Exception $e) {}
}

// 4. Cari di Elasticsearch (The Knowledgebase)
$es_results = [];
$has_suggestion = false;
$suggested_query = "";

try {
    $es_url = "http://localhost:9200/campus_kb/_search";
    $ch = curl_init($es_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $es_query = [];
    if (isset($nlp_data['vector']) && !empty($nlp_data['vector'])) {
        $es_query = [
            'knn' => [
                'field' => 'content_vector',
                'query_vector' => $nlp_data['vector'],
                'k' => 5,
                'num_candidates' => 50,
                'boost' => 0.8
            ],
            'query' => [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['filename', 'content'],
                    'fuzziness' => 'AUTO',
                    'boost' => 0.2
                ]
            ],
            'highlight' => ['fields' => ['content' => new stdClass()]]
        ];
    } else {
        $es_query = [
            'query' => [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['filename', 'content'],
                    'fuzziness' => 'AUTO'
                ]
            ],
            'highlight' => ['fields' => ['content' => new stdClass()]]
        ];
    }
    
    $es_query['suggest'] = [
        'text' => $query,
        'did_you_mean' => ['term' => ['field' => 'content']]
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($es_query));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $es_response = curl_exec($ch);
    curl_close($ch);

    if ($es_response) {
        $es_data = json_decode($es_response, true);
        if (isset($es_data['hits']['hits'])) {
            $es_results = $es_data['hits']['hits'];
        }
        if (isset($es_data['suggest']['did_you_mean'])) {
            $words = [];
            foreach ($es_data['suggest']['did_you_mean'] as $token) {
                if (!empty($token['options'])) {
                    $words[] = $token['options'][0]['text'];
                    $has_suggestion = true;
                } else {
                    $words[] = $token['text'];
                }
            }
            if ($has_suggestion) $suggested_query = implode(" ", $words);
        }
    }
} catch (Exception $e) {}

if (isset($nlp_data['suggested_query']) && !empty($nlp_data['suggested_query'])) {
    $suggested_query = $nlp_data['suggested_query'];
    $has_suggestion = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - <?php echo htmlspecialchars($query); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
                        primary: '#6366f1', 
                        primaryHover: '#4f46e5',
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
<body class="min-h-screen flex flex-col">

<!-- Top Navigation -->
<header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
        <a href="index.php" class="flex items-center gap-2 text-primary font-bold text-xl brand-font flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span class="hidden sm:inline">MVP Search</span>
        </a>
        
        <form class="flex-grow max-w-2xl relative" action="results.php" method="GET">
            <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" class="w-full pl-4 pr-12 py-2 rounded-full bg-slate-50 border border-slate-200 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-slate-700">
            <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 px-3 bg-primary text-white rounded-full hover:bg-primaryHover transition-colors cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </form>
        
        <div class="flex-shrink-0">
            <?php if ($_SESSION['role'] !== 'guest'): ?>
                <a href="logout.php" class="text-sm font-medium text-rose-500 hover:text-rose-600 transition-colors">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-sm font-bold text-primary hover:text-primaryHover transition-colors">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="flex-grow max-w-7xl mx-auto w-full px-4 py-8 flex flex-col lg:flex-row gap-8">
    
    <!-- Main Results Column -->
    <div class="flex-grow min-w-0">
        
        <!-- Auto Correction -->
        <?php if ($has_suggestion && strtolower($suggested_query) !== strtolower($query)): ?>
            <div class="mb-6 p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <div>
                    <span class="text-slate-600">Mungkin yang Anda maksud adalah: </span>
                    <a href="results.php?q=<?php echo urlencode($suggested_query); ?>" class="font-bold text-amber-700 hover:text-amber-800 underline decoration-amber-300 underline-offset-4 transition-colors">
                        <?php echo htmlspecialchars($suggested_query); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Smart Answer -->
        <?php 
        $has_smart_answer = false;
        $smart_answer_text = "";
        if ($academic_results && !empty($academic_results['data'])) {
            $has_smart_answer = true;
            $row = $academic_results['data'][0];
            if ($nlp_data['intent'] === 'ACADEMIC') {
                $smart_answer_text = "Berdasarkan data akademik Anda, nilai untuk mata kuliah <strong class='text-slate-800'>" . $row['mata_kuliah'] . "</strong> di semester " . $row['semester'] . " adalah <strong class='text-slate-800'>" . $row['nilai'] . "</strong>.";
            } else {
                $status = ($row['status'] === 'lunas') ? "sudah lunas" : "belum dibayar";
                $smart_answer_text = "Tagihan UKT Anda untuk <strong class='text-slate-800'>Semester " . $row['semester'] . "</strong> adalah sebesar <strong class='text-slate-800'>Rp " . number_format($row['bill'], 0, ',', '.') . "</strong> dan statusnya <strong class='text-slate-800'>" . $status . "</strong>.";
            }
        } elseif (!empty($es_results)) {
            $has_smart_answer = true;
            $top_hit = $es_results[0];
            $content = isset($top_hit['highlight']['content']) ? implode(" ", $top_hit['highlight']['content']) : substr($top_hit['_source']['content'], 0, 300);
            $smart_answer_text = "Menemukan informasi di dokumen <strong class='text-slate-800'>" . $top_hit['_source']['filename'] . "</strong>: <br><br>\"..." . $content . "...\"";
        }
        ?>

        <?php if ($has_smart_answer): ?>
            <div class="mb-8 bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative">
                <div class="absolute top-0 left-0 w-1 h-full bg-primary"></div>
                <div class="p-6">
                    <div class="flex items-center gap-2 mb-3 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h2 class="brand-font font-bold text-lg">Jawaban Pintar</h2>
                    </div>
                    <p class="text-slate-600 leading-relaxed text-[1.05rem]">
                        <?php echo $smart_answer_text; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Academic/Finance Detailed Table -->
        <?php if ($academic_results && $academic_results['status'] === 'success' && !empty($academic_results['data'])): ?>
            <div class="mb-8 bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="brand-font font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                        Detail Data Anda
                    </h3>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">Personal</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <?php if ($nlp_data['intent'] === 'ACADEMIC'): ?>
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-xs tracking-wider">
                                <tr><th class="px-6 py-3">Mata Kuliah</th><th class="px-6 py-3">Semester</th><th class="px-6 py-3">Nilai</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($academic_results['data'] as $row): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-slate-800"><?php echo $row['mata_kuliah']; ?></td>
                                        <td class="px-6 py-4"><?php echo $row['semester']; ?></td>
                                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"><?php echo $row['nilai']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php else: ?>
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-xs tracking-wider">
                                <tr><th class="px-6 py-3">Semester</th><th class="px-6 py-3">Tagihan</th><th class="px-6 py-3">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($academic_results['data'] as $row): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-slate-800">Semester <?php echo $row['semester']; ?></td>
                                        <td class="px-6 py-4">Rp <?php echo number_format($row['bill'], 0, ',', '.'); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($row['status'] === 'lunas'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">LUNAS</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">BELUM LUNAS</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Knowledge Base Results -->
        <?php if (!empty($es_results)): ?>
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 mt-8">Hasil dari Dokumen & Web</h3>
            <div class="space-y-4">
                <?php foreach ($es_results as $hit): ?>
                    <?php 
                        $is_web = isset($hit['_source']['source_type']) && $hit['_source']['source_type'] === 'web';
                        $icon = $is_web ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />' : 
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />';
                        $icon_color = $is_web ? 'text-sky-500 bg-sky-50' : 'text-rose-500 bg-rose-50';
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md hover:border-slate-200 transition-all duration-200 group">
                        <div class="flex items-start gap-4">
                            <div class="p-2 rounded-xl <?php echo $icon_color; ?> flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <?php echo $icon; ?>
                                </svg>
                            </div>
                            <div class="flex-grow min-w-0">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h4 class="font-bold text-slate-800 truncate group-hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($hit['_source']['filename']); ?>
                                    </h4>
                                    <?php if (isset($hit['_score'])): ?>
                                        <span class="text-[0.65rem] font-bold uppercase tracking-wider bg-slate-100 text-slate-500 px-2 py-0.5 rounded flex-shrink-0 flex items-center gap-1">
                                            Score <?php echo round($hit['_score'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-slate-600 mb-3 line-clamp-2">
                                    <?php 
                                    if (isset($hit['highlight']['content'])) {
                                        echo "..." . implode("...", $hit['highlight']['content']) . "...";
                                    } else {
                                        echo substr(htmlspecialchars($hit['_source']['content']), 0, 200) . "...";
                                    }
                                    ?>
                                </p>
                                <div>
                                    <?php if ($is_web && isset($hit['_source']['url'])): ?>
                                        <a href="<?php echo $hit['_source']['url']; ?>" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-sky-600 hover:text-sky-700 uppercase tracking-wider transition-colors cursor-pointer">
                                            Kunjungi Situs
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-700 uppercase tracking-wider transition-colors cursor-pointer">
                                            Buka Dokumen
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php if (!$academic_results): ?>
                <div class="text-center py-20 bg-white rounded-3xl border border-slate-100 shadow-sm border-dashed">
                    <div class="inline-flex p-4 bg-slate-50 rounded-full text-slate-300 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="brand-font text-xl text-slate-800 font-bold mb-2">Pencarian Tidak Ditemukan</h3>
                    <p class="text-slate-500 mb-6 max-w-md mx-auto">Kami tidak dapat menemukan data atau dokumen yang cocok dengan kata kunci Anda.</p>
                    <a href="index.php" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-xl hover:bg-primaryHover font-medium transition-colors cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- Sidebar: NLP Analysis -->
    <div class="lg:w-80 flex-shrink-0">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 sticky top-24">
            <h3 class="brand-font font-bold text-slate-800 flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-fuchsia-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                NLP Analysis
            </h3>
            
            <div class="space-y-4">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Detected Intent</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-slate-800 text-white">
                        <?php echo $nlp_data['intent']; ?>
                    </span>
                </div>
                
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Search Method</span>
                    <?php if (isset($nlp_data['vector']) && !empty($nlp_data['vector'])): ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-100 text-indigo-700">
                            Hybrid (Semantic)
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-600">
                            Keyword Matching
                        </span>
                    <?php endif; ?>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-2">Entities Found</span>
                    <?php if (empty($nlp_data['entities'])): ?>
                        <p class="text-sm text-slate-500 italic">Tidak ada entitas spesifik</p>
                    <?php else: ?>
                        <ul class="space-y-1">
                            <?php foreach ($nlp_data['entities'] as $key => $val): ?>
                                <li class="text-sm text-slate-700 bg-slate-50 px-2 py-1 rounded border border-slate-100 flex justify-between">
                                    <span class="font-medium text-slate-500 capitalize"><?php echo str_replace('_', ' ', $key); ?>:</span> 
                                    <span class="font-bold"><?php echo $val; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-slate-100">
                <div class="bg-sky-50 text-sky-700 p-4 rounded-xl text-sm leading-relaxed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-1 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <strong>Tips:</strong> Coba cari "Berapa ukt saya" atau "nilai kalkulus" untuk memicu Smart Answer.
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
