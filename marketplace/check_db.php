<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$connection = $entityManager->getConnection();

try {
    $columns = $connection->fetchAllAssociative("DESCRIBE sponsor");
    foreach ($columns as $column) {
        print_r($column);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$kernel->shutdown();
