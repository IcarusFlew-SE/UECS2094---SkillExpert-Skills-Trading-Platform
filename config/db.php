<?php
$host     = 'localhost';
$dbname   = 'swapexpert';
$username = 'root';
$password = '';
// pdo = PHP data objects - connect to MySQL Database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION); // show errors
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // fetch data as associative arrays
} catch (PDOException $e) {
    die("Failed to connect to database: " . $e->getMessage());
}
