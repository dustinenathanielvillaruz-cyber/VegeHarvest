<?php
// Auto-switch: localhost (XAMPP) vs InfinityFree
$is_production = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'infinityfreeapp.com') !== false;

if ($is_production) {
    // InfinityFree credentials from dash.infinityfree.com
    // From your screenshot:
    $host = 'sql304.infinityfree.com';
    $user = 'if0_43163209';
    $pass = 'Dustine123321h'; // InfinityFree hosting/MySQL password
    $db   = 'if0_43163209_vege_harvest_db';
} else {
    // Local XAMPP
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'vege_harvest_db';
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>