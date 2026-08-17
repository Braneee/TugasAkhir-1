<?php
require_once 'config.php';
require_once 'session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = $_POST['query_text'] ?? '';
    $title = $_POST['document_title'] ?? '';
    $url = $_POST['document_url'] ?? null;
    $type = $_POST['feedback_type'] ?? '';
    $nim = $_SESSION['nim'];

    if ($query && $title && in_array($type, ['up', 'down'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO search_feedback (query_text, document_title, document_url, feedback_type, nim) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$query, $title, $url, $type, $nim]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
}
