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
        // Gabungkan dengan suggestion dari NLP Service (karena NLP lebih tau context akademik)
        if (isset($nlp_data['suggested_query']) && !empty($nlp_data['suggested_query'])) {
            $suggested_query = $nlp_data['suggested_query'];
            $has_suggestion = true;
        }
        
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout 3 detik
    $nlp_response = curl_exec($ch);
    curl_close($ch);
    if ($nlp_response) {
        $nlp_data = json_decode($nlp_response, true);
    }
} catch (Exception $e) {
    // Fallback ke general
}

// 3. Ambil data Akademik/Keuangan (HANYA UNTUK MAHASISWA)
$academic_results = null;
if ($_SESSION['role'] === 'student' && isset($nlp_data['intent']) && ($nlp_data['intent'] === 'ACADEMIC' || $nlp_data['intent'] === 'FINANCE')) {
    try {
        $type = ($nlp_data['intent'] === 'ACADEMIC') ? 'academic' : 'finance';
        
        // Kita simulasiin API Call dengan query dinamis berdasarkan hasil NLP
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
        // Gabungkan dengan suggestion dari NLP Service (karena NLP lebih tau context akademik)
        if (isset($nlp_data['suggested_query']) && !empty($nlp_data['suggested_query'])) {
            $suggested_query = $nlp_data['suggested_query'];
            $has_suggestion = true;
        }
    }
    
} catch (Exception $e) {
        // Abaikan error
    }
}

// 4. Cari di Elasticsearch (The Knowledgebase) - Hybrid Search (Fuzzy + Semantic)
$es_results = [];
try {
    $es_url = "http://localhost:9200/campus_kb/_search";
    $ch = curl_init($es_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $es_query = [];
    
    // Jika NLP API berhasil mengirimkan vektor (Semantic Search kNN)
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
                    'fuzziness' => 'AUTO', // Auto Correction!
                    'boost' => 0.2
                ]
            ],
            'highlight' => [
                'fields' => [
                    'content' => new stdClass()
                ]
            ]
        ];
    } else {
        // Fallback jika tidak ada vektor (hanya Lexical + Fuzzy)
        $es_query = [
            'query' => [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['filename', 'content'],
                    'fuzziness' => 'AUTO' // Auto Correction!
                ]
            ],
            'highlight' => [
                'fields' => [
                    'content' => new stdClass()
                ]
            ]
        ];
    }
    
    // Tambahkan Auto-Correction Suggester
    $es_query['suggest'] = [
        'text' => $query,
        'did_you_mean' => [
            'term' => [
                'field' => 'content'
            ]
        ]
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($es_query));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $es_response = curl_exec($ch);
    curl_close($ch);
    $has_suggestion = false;
    $suggested_query = "";

    if ($es_response) {
        $es_data = json_decode($es_response, true);
        if (isset($es_data['hits']['hits'])) {
            $es_results = $es_data['hits']['hits'];
        }
        
        // Proses Suggestion "Did you mean"
        if (isset($es_data['suggest']['did_you_mean'])) {
            $words = [];
            foreach ($es_data['suggest']['did_you_mean'] as $token) {
                if (!empty($token['options'])) {
                    $words[] = $token['options'][0]['text']; // Ambil opsi skor tertinggi
                    $has_suggestion = true;
                } else {
                    $words[] = $token['text']; // Kalau nggak ada saran, pake kata asli
                }
            }
            if ($has_suggestion) {
                $suggested_query = implode(" ", $words);
            }
        }
        
        // Prioritaskan suggestion dari NLP Service (karena NLP lebih tau context akademik dan database)
        if (isset($nlp_data['suggested_query']) && !empty($nlp_data['suggested_query'])) {
            $suggested_query = $nlp_data['suggested_query'];
            $has_suggestion = true;
        }
    }

} catch (Exception $e) {
    // Abaikan error ES
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - <?php echo htmlspecialchars($query); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .result-card { border-radius: 12px; margin-bottom: 20px; transition: 0.2s; }
        .result-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important; }
        .highlight { background-color: #fff3cd; padding: 2px 4px; border-radius: 4px; }
        .intent-badge { font-size: 0.8rem; vertical-align: middle; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">MVP Search</a>
        <form class="d-flex flex-grow-1 mx-4" action="results.php" method="GET">
            <input class="form-control rounded-pill me-2" type="search" name="q" value="<?php echo htmlspecialchars($query); ?>">
            <button class="btn btn-primary rounded-pill" type="submit">Cari</button>
        </form>
        <div class="navbar-text text-white d-none d-lg-block">
            <?php if ($_SESSION['role'] !== 'guest'): ?>
                <a href="logout.php" class="text-danger">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-primary text-decoration-none">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <h4 class="mb-4 text-muted">Hasil pencarian untuk "<?php echo htmlspecialchars($query); ?>"</h4>

            <!-- AUTO CORRECTION (DID YOU MEAN) -->
            <?php if ($has_suggestion && strtolower($suggested_query) !== strtolower($query)): ?>
                <div class="alert alert-warning mb-4 shadow-sm" style="border-radius: 15px; border-left: 5px solid #ffc107;">
                    <i class="bi bi-lightbulb-fill me-2 text-warning fs-5"></i>
                    <span style="font-size: 1.1rem;">Mungkin yang Anda maksud adalah: </span>
                    <a href="results.php?q=<?php echo urlencode($suggested_query); ?>" class="fw-bold text-decoration-none ms-1 fs-5 text-dark" style="border-bottom: 2px dashed #ffc107;">
                        <?php echo htmlspecialchars($suggested_query); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- SECTION 0: JAWABAN LANGSUNG (SMART ANSWER) -->
            <?php 
            $has_smart_answer = false;
            $smart_answer_text = "";
            
            // Jika ada data akademik/keuangan, buat jawaban kalimat
            if ($academic_results && !empty($academic_results['data'])) {
                $has_smart_answer = true;
                $row = $academic_results['data'][0];
                if ($nlp_data['intent'] === 'ACADEMIC') {
                    $smart_answer_text = "Berdasarkan data akademik Anda, nilai untuk mata kuliah <strong>" . $row['mata_kuliah'] . "</strong> di semester " . $row['semester'] . " adalah <strong>" . $row['nilai'] . "</strong>.";
                } else {
                    $status = ($row['status'] === 'lunas') ? "sudah lunas" : "belum dibayar";
                    $smart_answer_text = "Tagihan UKT Anda untuk <strong>Semester " . $row['semester'] . "</strong> adalah sebesar <strong>Rp " . number_format($row['bill'], 0, ',', '.') . "</strong> dan statusnya <strong>" . $status . "</strong>.";
                }
            } 
            // Jika tidak ada data privat tapi ada hasil KB, ambil snippet terbaik
            elseif (!empty($es_results)) {
                $has_smart_answer = true;
                $top_hit = $es_results[0];
                $content = isset($top_hit['highlight']['content']) ? implode(" ", $top_hit['highlight']['content']) : substr($top_hit['_source']['content'], 0, 300);
                $smart_answer_text = "Menemukan informasi di dokumen <strong>" . $top_hit['_source']['filename'] . "</strong>: <br><br>\"..." . $content . "...\"";
            }

            if ($has_smart_answer): ?>
                <div class="card shadow border-0 mb-5 overflow-hidden" style="border-radius: 20px;">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-stars me-2"></i> Jawaban Untuk Anda</h5>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <p class="lead mb-0" style="font-size: 1.1rem; line-height: 1.6;">
                            <?php echo $smart_answer_text; ?>
                        </p>
                    </div>
                    <?php if (!empty($es_results) && $nlp_data['intent'] === 'GENERAL'): ?>
                    <div class="card-footer bg-light border-0 py-2 text-center">
                        <small class="text-muted">Jawaban ini diekstrak otomatis dari dokumen resmi kampus</small>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- SECTION 1: DATA AKADEMIK / KEUANGAN (DETAIL TABLE) -->
            <?php if ($academic_results && $academic_results['status'] === 'success' && !empty($academic_results['data'])): ?>
                <h5 class="mb-3 text-muted">Detail Data Akademik</h5>
                <div class="card shadow-sm result-card border-0 mb-5">
                    <div class="card-body p-4">
                        <h5 class="card-title text-primary mb-3">
                            <i class="bi bi-person-badge me-2"></i> Data Akademik Anda
                            <span class="badge bg-info intent-badge ms-2">Personal Info</span>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <?php if ($nlp_data['intent'] === 'ACADEMIC'): ?>
                                    <thead><tr><th>Mata Kuliah</th><th>Semester</th><th>Nilai</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($academic_results['data'] as $row): ?>
                                            <tr>
                                                <td><?php echo $row['mata_kuliah']; ?></td>
                                                <td><?php echo $row['semester']; ?></td>
                                                <td><span class="badge bg-success"><?php echo $row['nilai']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                <?php else: ?>
                                    <thead><tr><th>Semester</th><th>Tagihan</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($academic_results['data'] as $row): ?>
                                            <tr>
                                                <td>Semester <?php echo $row['semester']; ?></td>
                                                <td>Rp <?php echo number_format($row['bill'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo ($row['status'] === 'lunas' ? 'bg-success' : 'bg-danger'); ?>">
                                                        <?php echo strtoupper(str_replace('_', ' ', $row['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SECTION 2: DOKUMEN KNOWLEDGEBASE (DARI ELASTICSEARCH) -->
            <?php if (!empty($es_results)): ?>
                <h5 class="mb-3 mt-5 text-muted">Hasil dari Knowledgebase & Web</h5>
                <?php foreach ($es_results as $hit): ?>
                    <?php 
                        $is_web = isset($hit['_source']['source_type']) && $hit['_source']['source_type'] === 'web';
                        $icon = $is_web ? 'bi-globe text-info' : (strpos($hit['_source']['filename'], '.pdf') !== false ? 'bi-file-pdf text-danger' : 'bi-file-word text-primary');
                    ?>
                    <div class="card shadow-sm result-card">
                        <div class="card-body">
                            <h6 class="card-title text-primary d-flex justify-content-between align-items-start">
                                <div>
                                    <i class="bi <?php echo $icon; ?> me-2"></i>
                                    <?php echo htmlspecialchars($hit['_source']['filename']); ?>
                                </div>
                                <?php if (isset($hit['_score'])): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success ms-2" title="Hybrid Search Score" style="font-size: 0.7rem;">
                                        <i class="bi bi-bullseye"></i> Similarity: <?php echo round($hit['_score'], 3); ?>
                                    </span>
                                <?php endif; ?>
                            </h6>
                            <p class="card-text text-muted small">
                                <?php 
                                if (isset($hit['highlight']['content'])) {
                                    echo "..." . implode("...", $hit['highlight']['content']) . "...";
                                } else {
                                    echo substr(htmlspecialchars($hit['_source']['content']), 0, 200) . "...";
                                }
                                ?>
                            </p>
                            <?php if ($is_web && isset($hit['_source']['url'])): ?>
                                <a href="<?php echo $hit['_source']['url']; ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill">Kunjungi Situs</a>
                            <?php else: ?>
                                <a href="#" class="btn btn-outline-secondary btn-sm rounded-pill">Buka Dokumen</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (!$academic_results): ?>
                    <div class="alert alert-light border text-center p-5">
                        <i class="bi bi-search display-1 text-muted"></i>
                        <p class="mt-3">Maaf, kami tidak menemukan data yang cocok dengan pencarian Anda.</p>
                        <a href="index.php" class="btn btn-primary rounded-pill">Coba Cari Lagi</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR -->
        <div class="col-lg-4">
            <div class="card shadow-sm p-4 mb-4">
                <h6><i class="bi bi-robot me-2"></i> NLP Analysis</h6>
                <hr>
                <p class="small mb-1"><strong>Intent:</strong> <span class="badge bg-dark"><?php echo $nlp_data['intent']; ?></span></p>
                <p class="small mb-1"><strong>Metode Pencarian:</strong> 
                    <?php if (isset($nlp_data['vector']) && !empty($nlp_data['vector'])): ?>
                        <span class="badge bg-primary">Hybrid (Semantic + Keyword)</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Keyword Only</span>
                    <?php endif; ?>
                </p>
                <p class="small mb-0 mt-2"><strong>Entities:</strong></p>
                <ul class="small ps-3 text-muted">
                    <?php if (empty($nlp_data['entities'])): ?>
                        <li>Tidak ada entitas terdeteksi</li>
                    <?php else: ?>
                        <?php foreach ($nlp_data['entities'] as $key => $val): ?>
                            <li><?php echo $key . ": " . $val; ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="alert alert-info small">
                <i class="bi bi-info-circle me-2"></i> <strong>Tips:</strong> Coba cari "Berapa ukt saya" atau "nilai semester 1" untuk hasil yang lebih spesifik.
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
