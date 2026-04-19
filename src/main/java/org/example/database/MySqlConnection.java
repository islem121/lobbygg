package org.example.database;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.util.LinkedHashMap;
import java.util.Map;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.io.IOException;

public final class MySqlConnection {
    private static final String DEFAULT_HOST = "localhost";
    private static final String DEFAULT_PORT = "3306";
    private static final String DEFAULT_DATABASE = "lobbyjava";
    private static final String DEFAULT_USER = "root";
    private static final String DEFAULT_PASSWORD = "";
    private static final String JDBC_URL_SUFFIX = "?createDatabaseIfNotExist=true&useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=UTC";
    private static final Map<String, String> FILE_CONFIG = loadEnvFile();
    private static volatile boolean schemaInitialized;

    private MySqlConnection() {
    }

    public static Connection getConnection() throws SQLException {
        Connection connection = DriverManager.getConnection(resolveUrl(), resolveUser(), resolvePassword());
        initializeSchema(connection);
        return connection;
    }

    public static boolean testConnection() {
        try (Connection connection = getConnection()) {
            return connection != null && !connection.isClosed();
        } catch (SQLException e) {
            System.err.println("Erreur lors de la connexion a MySQL : " + e.getMessage());
            return false;
        }
    }

    private static String resolveUrl() {
        String configuredUrl = readConfig("JDBC_URL", "jdbc.url");
        if (configuredUrl != null && !configuredUrl.isBlank()) {
            return configuredUrl;
        }

        String host = readConfig("DB_HOST", "db.host", DEFAULT_HOST);
        String port = readConfig("DB_PORT", "db.port", DEFAULT_PORT);
        String database = readConfig("DB_NAME", "db.name", DEFAULT_DATABASE);

        return "jdbc:mysql://" + host + ":" + port + "/" + database + JDBC_URL_SUFFIX;
    }

    private static String resolveUser() {
        return readConfig("DB_USER", "db.user", DEFAULT_USER);
    }

    private static String resolvePassword() {
        return readConfig("DB_PASSWORD", "db.password", DEFAULT_PASSWORD);
    }

    private static String readConfig(String envKey, String propertyKey) {
        String value = System.getProperty(propertyKey);
        if (value != null && !value.isBlank()) {
            return value;
        }

        value = System.getenv(envKey);
        if (value != null && !value.isBlank()) {
            return value;
        }

        return FILE_CONFIG.get(envKey);
    }

    private static String readConfig(String envKey, String propertyKey, String defaultValue) {
        String value = readConfig(envKey, propertyKey);
        return value == null || value.isBlank() ? defaultValue : value;
    }

    private static synchronized void initializeSchema(Connection connection) throws SQLException {
        if (schemaInitialized) {
            return;
        }

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS user (
                    id INT NOT NULL AUTO_INCREMENT,
                    username VARCHAR(100) NOT NULL,
                    email VARCHAR(180) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    bio TEXT NULL,
                    role VARCHAR(50) NOT NULL DEFAULT 'client',
                    nom VARCHAR(150) NOT NULL,
                    telephone VARCHAR(20) NULL,
                    datenaissance DATE NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    photo_url VARCHAR(255) NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_user_username (username),
                    UNIQUE KEY uq_user_email (email)
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS gold (
                    id INT NOT NULL AUTO_INCREMENT,
                    id_utilisateur INT NOT NULL,
                    solde DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                    derniere_maj TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_gold_user (id_utilisateur),
                    CONSTRAINT fk_gold_user
                        FOREIGN KEY (id_utilisateur) REFERENCES user (id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS gold_operation (
                    id INT NOT NULL AUTO_INCREMENT,
                    id_gold INT NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    montant DECIMAL(12, 2) NOT NULL,
                    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    status VARCHAR(30) NOT NULL DEFAULT 'VALIDE',
                    PRIMARY KEY (id),
                    KEY idx_gold_operation_gold (id_gold),
                    CONSTRAINT fk_gold_operation_gold
                        FOREIGN KEY (id_gold) REFERENCES gold (id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS reservation (
                    id INT NOT NULL AUTO_INCREMENT,
                    reference_code VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_reservation_reference (reference_code)
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS article (
                    id INT NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS sponsor (
                    sponsor_id INT NOT NULL AUTO_INCREMENT,
                    company_name VARCHAR(180) NOT NULL,
                    description TEXT NULL,
                    amount DECIMAL(12, 2) NULL DEFAULT 0.00,
                    target_type VARCHAR(50) NULL,
                    logo VARCHAR(255) NULL,
                    user_id INT NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (sponsor_id),
                    KEY idx_sponsor_user (user_id),
                    CONSTRAINT fk_sponsor_user
                        FOREIGN KEY (user_id) REFERENCES user (id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS tournament (
                    id INT NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    start_date TIMESTAMP NOT NULL,
                    end_date DATE NOT NULL,
                    max_players INT NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS category (
                    category_id INT NOT NULL AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    PRIMARY KEY (category_id)
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS product (
                    product_id INT NOT NULL AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    price DECIMAL(12, 2) NOT NULL,
                    description TEXT NULL,
                    image VARCHAR(255) NULL,
                    stock INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    category_id INT NOT NULL,
                    seller_id INT NOT NULL,
                    PRIMARY KEY (product_id),
                    KEY idx_product_category (category_id),
                    KEY idx_product_seller (seller_id),
                    CONSTRAINT fk_product_category
                        FOREIGN KEY (category_id) REFERENCES category (category_id)
                        ON DELETE RESTRICT
                        ON UPDATE CASCADE,
                    CONSTRAINT fk_product_seller
                        FOREIGN KEY (seller_id) REFERENCES user (id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS `order` (
                    order_id INT NOT NULL AUTO_INCREMENT,
                    quantity INT NOT NULL,
                    order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    status VARCHAR(50) NOT NULL,
                    product_id INT NOT NULL,
                    user_id INT NOT NULL,
                    PRIMARY KEY (order_id),
                    KEY idx_order_product (product_id),
                    KEY idx_order_user (user_id),
                    CONSTRAINT fk_order_product
                        FOREIGN KEY (product_id) REFERENCES product (product_id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE,
                    CONSTRAINT fk_order_user
                        FOREIGN KEY (user_id) REFERENCES user (id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS post (
                    post_id INT NOT NULL AUTO_INCREMENT,
                    title VARCHAR(255) NULL,
                    content TEXT NULL,
                    image VARCHAR(255) NULL,
                    category VARCHAR(20) NOT NULL,
                    up_votes INT NOT NULL DEFAULT 0,
                    down_votes INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    user_id INT NOT NULL,
                    PRIMARY KEY (post_id),
                    KEY idx_post_user (user_id),
                    CONSTRAINT fk_post_user
                        FOREIGN KEY (user_id) REFERENCES user (id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                CREATE TABLE IF NOT EXISTS comment (
                    comment_id INT NOT NULL AUTO_INCREMENT,
                    content TEXT NOT NULL,
                    image VARCHAR(255) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    up_votes INT NOT NULL DEFAULT 0,
                    down_votes INT NOT NULL DEFAULT 0,
                    user_id INT NOT NULL,
                    post_id INT NOT NULL,
                    PRIMARY KEY (comment_id),
                    KEY idx_comment_user (user_id),
                    KEY idx_comment_post (post_id),
                    CONSTRAINT fk_comment_user
                        FOREIGN KEY (user_id) REFERENCES user (id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE,
                    CONSTRAINT fk_comment_post
                        FOREIGN KEY (post_id) REFERENCES post (post_id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB
                """);

        executeStatement(connection, """
                INSERT INTO user (username, email, password, bio, role, nom, telephone, datenaissance, created_at, photo_url)
                SELECT 'admin_demo', 'admin@firstproject.local', 'Admin123', 'Compte admin de demonstration.', 'admin',
                       'Admin Demo', '12345678', '1990-01-01', CURRENT_TIMESTAMP, NULL
                WHERE NOT EXISTS (
                    SELECT 1 FROM user WHERE email = 'admin@firstproject.local'
                )
                """);

        executeStatement(connection, """
                INSERT INTO user (username, email, password, bio, role, nom, telephone, datenaissance, created_at, photo_url)
                SELECT 'client_demo', 'client@firstproject.local', 'Client123', 'Compte client de demonstration.', 'client',
                       'Client Demo', '23456789', '1995-05-10', CURRENT_TIMESTAMP, NULL
                WHERE NOT EXISTS (
                    SELECT 1 FROM user WHERE email = 'client@firstproject.local'
                )
                """);

        executeStatement(connection, """
                INSERT INTO user (username, email, password, bio, role, nom, telephone, datenaissance, created_at, photo_url)
                SELECT 'sponsor_demo', 'sponsor@firstproject.local', 'Sponsor123', 'Compte sponsor de demonstration.', 'sponsor',
                       'Sponsor Demo', '34567890', '1992-09-15', CURRENT_TIMESTAMP, NULL
                WHERE NOT EXISTS (
                    SELECT 1 FROM user WHERE email = 'sponsor@firstproject.local'
                )
                """);

        executeStatement(connection, """
                INSERT INTO sponsor (company_name, description, amount, target_type, user_id, logo, created_at)
                SELECT 'Neon Corp', 'Sponsor principal e-sport.', 5000.00, 'tournament', id, NULL, CURRENT_TIMESTAMP
                FROM user
                WHERE email = 'sponsor@firstproject.local'
                  AND NOT EXISTS (SELECT 1 FROM sponsor WHERE company_name = 'Neon Corp')
                LIMIT 1
                """);

        executeStatement(connection, """
                INSERT INTO tournament (title, description, start_date, end_date, max_players, status, created_at)
                SELECT 'Lobby Championship', 'Tournoi principal du backoffice Java.', CURRENT_TIMESTAMP, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 32, 'PLANIFIE', CURRENT_TIMESTAMP
                WHERE NOT EXISTS (SELECT 1 FROM tournament WHERE title = 'Lobby Championship')
                """);

        executeStatement(connection, """
                INSERT INTO category (name, description)
                SELECT 'Gaming Gear', 'Produits marketplace gaming.'
                WHERE NOT EXISTS (SELECT 1 FROM category WHERE name = 'Gaming Gear')
                """);

        executeStatement(connection, """
                INSERT INTO product (name, price, description, image, stock, created_at, category_id, seller_id)
                SELECT 'Pro Controller', 89.99, 'Manette premium pour tournoi.', NULL, 25, CURRENT_TIMESTAMP, c.category_id, u.id
                FROM category c
                JOIN user u ON u.email = 'admin@firstproject.local'
                WHERE c.name = 'Gaming Gear'
                  AND NOT EXISTS (SELECT 1 FROM product WHERE name = 'Pro Controller')
                LIMIT 1
                """);

        executeStatement(connection, """
                INSERT INTO `order` (quantity, order_date, status, product_id, user_id)
                SELECT 2, CURRENT_TIMESTAMP, 'EN_ATTENTE', p.product_id, u.id
                FROM product p
                JOIN user u ON u.email = 'client@firstproject.local'
                WHERE p.name = 'Pro Controller'
                  AND NOT EXISTS (SELECT 1 FROM `order` WHERE status = 'EN_ATTENTE')
                LIMIT 1
                """);

        executeStatement(connection, """
                INSERT INTO post (title, content, image, category, up_votes, down_votes, created_at, user_id)
                SELECT 'Bienvenue sur Lobby Java', 'Post de démonstration pour le module blog backoffice.', NULL, 'news', 12, 1, CURRENT_TIMESTAMP, u.id
                FROM user u
                WHERE u.email = 'admin@firstproject.local'
                  AND NOT EXISTS (SELECT 1 FROM post WHERE title = 'Bienvenue sur Lobby Java')
                LIMIT 1
                """);

        executeStatement(connection, """
                INSERT INTO comment (content, image, created_at, up_votes, down_votes, user_id, post_id)
                SELECT 'Premier commentaire de démonstration.', NULL, CURRENT_TIMESTAMP, 3, 0, u.id, p.post_id
                FROM user u
                JOIN post p ON p.title = 'Bienvenue sur Lobby Java'
                WHERE u.email = 'client@firstproject.local'
                  AND NOT EXISTS (SELECT 1 FROM comment WHERE content = 'Premier commentaire de démonstration.')
                LIMIT 1
                """);

        schemaInitialized = true;
    }

    private static void executeStatement(Connection connection, String sql) throws SQLException {
        try (PreparedStatement statement = connection.prepareStatement(sql)) {
            statement.executeUpdate();
        }
    }

    private static Map<String, String> loadEnvFile() {
        Map<String, String> values = new LinkedHashMap<>();
        Path envPath = Paths.get(".env");
        if (!Files.exists(envPath)) {
            return values;
        }

        try {
            for (String line : Files.readAllLines(envPath)) {
                String trimmed = line.trim();
                if (trimmed.isEmpty() || trimmed.startsWith("#")) {
                    continue;
                }

                int separatorIndex = trimmed.indexOf('=');
                if (separatorIndex <= 0) {
                    continue;
                }

                String key = trimmed.substring(0, separatorIndex).trim();
                String value = trimmed.substring(separatorIndex + 1).trim();
                if ((value.startsWith("\"") && value.endsWith("\"")) || (value.startsWith("'") && value.endsWith("'"))) {
                    value = value.substring(1, value.length() - 1);
                }

                if (!key.isEmpty() && !value.isEmpty()) {
                    values.put(key, value);
                }
            }
        } catch (IOException e) {
            System.err.println("Impossible de lire le fichier .env : " + e.getMessage());
        }

        return values;
    }
}
