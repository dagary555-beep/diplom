<?php
// config.php

define('DB_HOST', 'MySql-8.0');
define('DB_NAME', 'aequitas_academy');
define('DB_USER', 'root');
define('DB_PASS', '');

session_start();

function getDBConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die(json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]));
        }
    }
    
    return $connection;
}

function jsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
?>