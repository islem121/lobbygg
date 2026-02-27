<?php

$dsn = "mysql:host=127.0.0.1;dbname=first_project;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $res = $pdo->query("DESCRIBE sponsor");
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
