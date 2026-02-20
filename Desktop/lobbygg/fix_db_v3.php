<?php

$dsn = "mysql:host=127.0.0.1;dbname=first_project;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sqls = [
        "ALTER TABLE sponsor CHANGE company_name nom_societe VARCHAR(255) NOT NULL",
        "ALTER TABLE sponsor ADD description LONGTEXT NOT NULL",
        "ALTER TABLE sponsor ADD amount DOUBLE PRECISION NOT NULL",
        "ALTER TABLE sponsor ADD target_type VARCHAR(50) NOT NULL",
        "ALTER TABLE sponsor ADD dossier VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE sponsor ADD created_at DATETIME NOT NULL",
        "ALTER TABLE sponsor ADD sponsor_id INT NOT NULL",
        "ALTER TABLE sponsor DROP COLUMN logo",
        "ALTER TABLE sponsor ADD CONSTRAINT FK_818CC9D412F7FB51 FOREIGN KEY (sponsor_id) REFERENCES user (id)",
        "CREATE INDEX IDX_818CC9D412F7FB51 ON sponsor (sponsor_id)"
    ];

    foreach ($sqls as $sql) {
        try {
            echo "Executing: $sql\n";
            $pdo->exec($sql);
            echo "Success\n";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
