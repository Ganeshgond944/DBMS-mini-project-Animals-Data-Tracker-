<?php
$host = 'localhost';
$db   = 'animal_tracker';   // name of your database in phpMyAdmin
$user = 'root';             // default XAMPP username
$pass = '';                 // default XAMPP password is empty
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
    die(json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]));
}
?>
