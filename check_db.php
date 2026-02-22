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
    echo "Exécutant les modifications de la table product...\n";
    
    // Ajout de la colonne seller_id
    try {
        $connection->executeStatement("ALTER TABLE product ADD seller_id INT DEFAULT NULL");
        echo "OK: Colonne seller_id ajoutée.\n";
    } catch (\Exception $e) {
        echo "INFO: La colonne seller_id existe peut-être déjà : " . $e->getMessage() . "\n";
    }

    // Ajout de la contrainte de clé étrangère
    try {
        $connection->executeStatement("ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id)");
        echo "OK: Contrainte FK ajoutée.\n";
    } catch (\Exception $e) {
        echo "INFO: La contrainte FK existe peut-être déjà : " . $e->getMessage() . "\n";
    }

    // Création de l'index
    try {
        $connection->executeStatement("CREATE INDEX IDX_D34A04AD8DE820D9 ON product (seller_id)");
        echo "OK: Index ajouté.\n";
    } catch (\Exception $e) {
        echo "INFO: L'index existe peut-être déjà : " . $e->getMessage() . "\n";
    }

    // Autres correctifs mineurs pour éviter les erreurs de schéma
    try {
        $connection->executeStatement("ALTER TABLE product CHANGE image image VARCHAR(255) DEFAULT NULL");
        $connection->executeStatement("ALTER TABLE user CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE datenaissance datenaissance DATE DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL");
        echo "OK: Correctifs de colonnes appliqués.\n";
    } catch (\Exception $e) {
        echo "INFO: Certains correctifs mineurs n'ont pas pu être appliqués (probablement déjà faits).\n";
    }

    // Ajout des colonnes Stripe à la table order
    try {
        $connection->executeStatement("ALTER TABLE `order` ADD stripe_session_id VARCHAR(255) DEFAULT NULL, ADD payment_intent_id VARCHAR(255) DEFAULT NULL");
        echo "OK: Colonnes Stripe ajoutées à la table order.\n";
    } catch (\Exception $e) {
        echo "INFO: Les colonnes Stripe existent peut-être déjà : " . $e->getMessage() . "\n";
    }

    // Création de la table return_request
    try {
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS return_request (
                id INT AUTO_INCREMENT NOT NULL, 
                order_item_id INT NOT NULL, 
                reason LONGTEXT NOT NULL, 
                status VARCHAR(20) NOT NULL, 
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', 
                refund_amount DECIMAL(10, 2) DEFAULT NULL, 
                admin_comment LONGTEXT DEFAULT NULL, 
                UNIQUE INDEX UNIQ_C2E06A26E415FB15 (order_item_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        $connection->executeStatement("ALTER TABLE return_request ADD CONSTRAINT FK_C2E06A26E415FB15 FOREIGN KEY (order_item_id) REFERENCES `order` (id)");
        echo "OK: Table return_request créée.\n";
    } catch (\Exception $e) {
        echo "INFO: La table return_request existe peut-être déjà ou erreur : " . $e->getMessage() . "\n";
    }

    // Création des tables de messagerie
    try {
        echo "Création des tables de messagerie...\n";
        
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS conversation (
                id INT AUTO_INCREMENT NOT NULL, 
                buyer_id INT NOT NULL, 
                seller_id INT NOT NULL, 
                product_id INT DEFAULT NULL, 
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', 
                INDEX IDX_8A8E26196C755722 (buyer_id), 
                INDEX IDX_8A8E26198DE820D9 (seller_id), 
                INDEX IDX_8A8E26194584665A (product_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");
        
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS message (
                id INT AUTO_INCREMENT NOT NULL, 
                conversation_id INT NOT NULL, 
                sender_id INT NOT NULL, 
                content LONGTEXT NOT NULL, 
                is_read TINYINT(1) NOT NULL, 
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', 
                INDEX IDX_B6BD307F9AC0396 (conversation_id), 
                INDEX IDX_B6BD307FF624B39D (sender_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");

        // Ajout des contraintes FK pour conversation
        try { $connection->executeStatement("ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26196C755722 FOREIGN KEY (buyer_id) REFERENCES user (id)"); } catch(\Exception $e) {}
        try { $connection->executeStatement("ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26198DE820D9 FOREIGN KEY (seller_id) REFERENCES user (id)"); } catch(\Exception $e) {}
        try { $connection->executeStatement("ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26194584665A FOREIGN KEY (product_id) REFERENCES product (id)"); } catch(\Exception $e) {}
        
        // Ajout des contraintes FK pour message
        try { $connection->executeStatement("ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)"); } catch(\Exception $e) {}
        try { $connection->executeStatement("ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES user (id)"); } catch(\Exception $e) {}

        // Ajout de la colonne audio_path pour la messagerie vocale
        try {
            $connection->executeStatement("ALTER TABLE message ADD audio_path VARCHAR(255) DEFAULT NULL");
            echo "OK: Colonne audio_path ajoutée à la table message.\n";
        } catch (\Exception $e) {
            echo "INFO: La colonne audio_path existe peut-être déjà : " . $e->getMessage() . "\n";
        }

        echo "OK: Tables de messagerie créées.\n";
    } catch (\Exception $e) {
        echo "ERROR: Erreur lors de la création des tables de messagerie : " . $e->getMessage() . "\n";
    }

    echo "Toutes les opérations sont terminées.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$kernel->shutdown();
