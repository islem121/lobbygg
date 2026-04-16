package org.example.database;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public final class MySqlConnection {
    private static final String URL = "jdbc:mysql://localhost:3306/first_project";
    private static final String USER = "root";
    private static final String PASSWORD = "";

    private MySqlConnection() {
    }

    public static Connection getConnection() throws SQLException {
        return DriverManager.getConnection(URL, USER, PASSWORD);
    }

    public static boolean testConnection() {
        try (Connection connection = getConnection()) {
            return connection != null && !connection.isClosed();
        } catch (SQLException e) {
            System.err.println("Erreur lors de la connexion a MySQL : " + e.getMessage());
            return false;
        }
    }
}
