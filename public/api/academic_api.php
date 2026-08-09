<?php
header('Content-Type: application/json');
require_once 'config.php';

// Simple API Key security
$apiKey = "rahasia123";
$headers = getallheaders();

if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== $apiKey) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$nim = $_GET['nim'] ?? '';
$type = $_GET['type'] ?? ''; // 'academic' or 'finance'

if (empty($nim) || empty($type)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters: nim or type']);
    exit;
}

try {
    if ($type === 'academic') {
        $stmt = $pdo->prepare("SELECT mata_kuliah, nilai, semester FROM academic_data WHERE nim = ?");
        $stmt->execute([$nim]);
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } elseif ($type === 'finance') {
        $stmt = $pdo->prepare("SELECT semester, bill, status FROM finance_data WHERE nim = ?");
        $stmt->execute([$nim]);
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid type. Use academic or finance']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()]);
}
