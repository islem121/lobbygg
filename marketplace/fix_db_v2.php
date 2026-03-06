<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$connection = $entityManager->getConnection();

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
        $connection->executeStatement($sql);
        echo "Success\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

$kernel->shutdown();
