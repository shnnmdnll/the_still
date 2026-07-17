<?php

$db_host = 'localhost';
$db_name = 'the_still';
$db_user = 'root';
$db_pass = 'Shnnmdnll_12';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}
