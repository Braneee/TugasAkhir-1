<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password

try {
    // 1. Connect to MySQL without database selected
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Memulai Setup Database ---<br>";

    // 2. Read and Execute Schema
    $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
    $pdo->exec($schema);
    echo "[OK] Schema berhasil dibuat.<br>";

    // 3. Read and Execute Seeder
    $seeder = file_get_contents(__DIR__ . '/../sql/seeder.sql');
    $pdo->exec($seeder);
    echo "[OK] Data seeder berhasil dimasukkan.<br>";

    echo "--- Setup Selesai! GAS BRO! ---";

} catch (PDOException $e) {
    echo "[ERROR] Gagal setup database: " . $e->getMessage();
}
