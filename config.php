<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host   = 'mysql-8.0';
$dbname = 'vodrf';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}