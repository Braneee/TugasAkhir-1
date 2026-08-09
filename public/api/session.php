<?php
// Set session save path to a local directory within the project
// This avoids errors when PHP default session path is not accessible (e.g., C:\xampp\tmp)
$session_path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'tmp';

if (!is_dir($session_path)) {
    mkdir($session_path, 0777, true);
}

session_save_path($session_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
