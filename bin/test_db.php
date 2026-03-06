<?php

require dirname(__DIR__).'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

echo "Kernel booted successfully.\n";

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');
$connection = $entityManager->getConnection();

try {
    // Just running a simple query to verify connection
    $connection->executeQuery('SELECT 1');
    echo "Database connection successful.\n";
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
