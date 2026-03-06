<?php

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$connection = $entityManager->getConnection();

$log = "";
try {
    $columns = $connection->fetchAllAssociative("DESCRIBE sponsor");
    foreach ($columns as $column) {
        $log .= print_r($column, true) . "\n";
    }
} catch (\Exception $e) {
    $log .= "Error: " . $e->getMessage() . "\n";
}

file_put_contents('db_log.txt', $log);
echo "Log written to db_log.txt\n";

$kernel->shutdown();
