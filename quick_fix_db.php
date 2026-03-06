<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');
if (file_exists(__DIR__.'/.env.local')) {
    $dotenv->load(__DIR__.'/.env.local');
}

$connectionParams = [
    'url' => $_ENV['DATABASE_URL'],
];

$conn = DriverManager::getConnection($connectionParams);

try {
    echo "Adding seller_id to product table...\n";
    $conn->executeStatement("ALTER TABLE product ADD seller_id INT DEFAULT NULL");
    echo "Done.\n";
    
    echo "Adding foreign key constraint...\n";
    $conn->executeStatement("ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id)");
    echo "Done.\n";
    
    echo "Creating index...\n";
    $conn->executeStatement("CREATE INDEX IDX_D34A04AD8DE820D9 ON product (seller_id)");
    echo "Done.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
