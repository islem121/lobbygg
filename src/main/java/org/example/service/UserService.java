package org.example.service;

import org.example.database.MySqlConnection;
import org.example.model.User;

import java.sql.Connection;
import java.sql.Date;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;

public class UserService {
    private static final String SELECT_ALL_SQL = """
            SELECT id, username, email, password, bio, role, nom, telephone, datenaissance, created_at, photo_url
            FROM user
            ORDER BY id DESC
            """;
    private static final String SELECT_BY_EMAIL_SQL = """
            SELECT id, username, email, password, bio, role, nom, telephone, datenaissance, created_at, photo_url
            FROM user
            WHERE email = ?
            LIMIT 1
            """;
    private static final String SELECT_BY_USERNAME_SQL = """
            SELECT id, username, email, password, bio, role, nom, telephone, datenaissance, created_at, photo_url
            FROM user
            WHERE username = ?
            LIMIT 1
            """;
    private static final String INSERT_SQL = """
            INSERT INTO user (username, email, password, bio, role, nom, telephone, datenaissance, created_at, photo_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """;
    private static final String UPDATE_USER_SQL = """
            UPDATE user SET username = ?, email = ?, password = ?, bio = ?, role = ?, nom = ?, telephone = ?, datenaissance = ?, photo_url = ?
            WHERE id = ?
            """;
    private static final String DELETE_USER_SQL = "DELETE FROM user WHERE id = ?";

    public Map<String, Integer> getGlobalStats() {
        Map<String, Integer> stats = new LinkedHashMap<>();
        
        try (Connection connection = MySqlConnection.getConnection()) {
            // Nombre d'utilisateurs
            stats.put("Utilisateurs", getCount(connection, "user"));
            
            // On tente de recuperer les reservations et articles si les tables existent
            stats.put("Reservations", getCount(connection, "reservation"));
            stats.put("Articles", getCount(connection, "article"));
            
        } catch (SQLException e) {
            System.err.println("Erreur lors de la recuperation des statistiques : " + e.getMessage());
        }
        
        return stats;
    }

    private int getCount(Connection connection, String tableName) {
        String sql = "SELECT COUNT(*) FROM " + tableName;
        try (PreparedStatement preparedStatement = connection.prepareStatement(sql);
             ResultSet resultSet = preparedStatement.executeQuery()) {
            if (resultSet.next()) {
                return resultSet.getInt(1);
            }
        } catch (SQLException e) {
            // Si la table n'existe pas, on retourne 0 sans faire planter le dashboard
            System.err.println("Note: Table " + tableName + " non trouvee ou erreur: " + e.getMessage());
        }
        return 0;
    }

    public List<User> getAllUsers() {
        List<User> users = new ArrayList<>();

        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement preparedStatement = connection.prepareStatement(SELECT_ALL_SQL);
             ResultSet resultSet = preparedStatement.executeQuery()) {

            while (resultSet.next()) {
                users.add(mapUser(resultSet));
            }
        } catch (SQLException e) {
            System.err.println("Erreur lors de la recuperation des utilisateurs : " + e.getMessage());
        }

        return users;
    }

    public Optional<User> findByEmail(String email) {
        return findOneByValue(SELECT_BY_EMAIL_SQL, email);
    }

    public Optional<User> findByUsername(String username) {
        return findOneByValue(SELECT_BY_USERNAME_SQL, username);
    }

    private Optional<User> findOneByValue(String sql, String value) {
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement preparedStatement = connection.prepareStatement(sql)) {

            preparedStatement.setString(1, value);

            try (ResultSet resultSet = preparedStatement.executeQuery()) {
                if (resultSet.next()) {
                    return Optional.of(mapUser(resultSet));
                }
            }
        } catch (SQLException e) {
            System.err.println("Erreur lors de la recherche de l'utilisateur : " + e.getMessage());
        }

        return Optional.empty();
    }

    public boolean addUser(User user) {
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement preparedStatement = connection.prepareStatement(INSERT_SQL)) {

            preparedStatement.setString(1, user.getUsername());
            preparedStatement.setString(2, user.getEmail());
            preparedStatement.setString(3, user.getPasswordHash());
            preparedStatement.setString(4, user.getBio());
            preparedStatement.setString(5, user.getRole());
            preparedStatement.setString(6, user.getNom());
            preparedStatement.setString(7, user.getTelephone());

            if (user.getDateNaissance() != null) {
                preparedStatement.setDate(8, Date.valueOf(user.getDateNaissance()));
            } else {
                preparedStatement.setDate(8, null);
            }

            LocalDateTime createdAt = user.getCreatedAt() != null ? user.getCreatedAt() : LocalDateTime.now();
            preparedStatement.setTimestamp(9, Timestamp.valueOf(createdAt));
            preparedStatement.setString(10, user.getPhotoUrl());

            return preparedStatement.executeUpdate() > 0;
        } catch (SQLException e) {
            System.err.println("Erreur lors de l'insertion d'un utilisateur : " + e.getMessage());
            return false;
        }
    }

    public boolean updateUser(User user) {
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement preparedStatement = connection.prepareStatement(UPDATE_USER_SQL)) {
            preparedStatement.setString(1, user.getUsername());
            preparedStatement.setString(2, user.getEmail());
            preparedStatement.setString(3, user.getPasswordHash());
            preparedStatement.setString(4, user.getBio());
            preparedStatement.setString(5, user.getRole());
            preparedStatement.setString(6, user.getNom());
            preparedStatement.setString(7, user.getTelephone());
            preparedStatement.setDate(8, user.getDateNaissance() != null ? Date.valueOf(user.getDateNaissance()) : null);
            preparedStatement.setString(9, user.getPhotoUrl());
            preparedStatement.setInt(10, user.getId());
            return preparedStatement.executeUpdate() > 0;
        } catch (SQLException e) {
            System.err.println("Erreur lors de la mise a jour : " + e.getMessage());
            return false;
        }
    }

    public boolean updateProfil(User user) {
        // Cette méthode est identique à updateUser mais sémantiquement dédiée au profil client
        return updateUser(user);
    }

    public boolean deleteUser(int userId) {
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement preparedStatement = connection.prepareStatement(DELETE_USER_SQL)) {
            preparedStatement.setInt(1, userId);
            return preparedStatement.executeUpdate() > 0;
        } catch (SQLException e) {
            System.err.println("Erreur lors de la suppression : " + e.getMessage());
            return false;
        }
    }

    public Optional<User> authenticate(String email, String plainPassword) {
        Optional<User> userOptional = findByEmail(email);
        if (userOptional.isEmpty()) {
            return Optional.empty();
        }

        User user = userOptional.get();
        return PasswordUtils.verifyPassword(plainPassword, user.getPasswordHash())
                ? Optional.of(user)
                : Optional.empty();
    }

    public boolean emailExists(String email) {
        return findByEmail(email).isPresent();
    }

    public boolean usernameExists(String username) {
        return findByUsername(username).isPresent();
    }

    public User buildNewUser(
            String nom,
            String username,
            String email,
            String plainPassword,
            String role,
            String telephone,
            LocalDate dateNaissance,
            String bio
    ) {
        User user = new User();
        user.setNom(nom);
        user.setUsername(username);
        user.setEmail(email);
        user.setPasswordHash(PasswordUtils.hashPassword(plainPassword));
        user.setRole(role);
        user.setTelephone(telephone);
        user.setDateNaissance(dateNaissance);
        user.setBio(bio);
        user.setCreatedAt(LocalDateTime.now());
        return user;
    }

    private User mapUser(ResultSet resultSet) throws SQLException {
        User user = new User();
        user.setId(resultSet.getInt("id"));
        user.setUsername(resultSet.getString("username"));
        user.setEmail(resultSet.getString("email"));
        user.setPasswordHash(resultSet.getString("password"));
        user.setBio(resultSet.getString("bio"));
        user.setRole(resultSet.getString("role"));
        user.setNom(resultSet.getString("nom"));
        user.setTelephone(resultSet.getString("telephone"));

        Date birthDate = resultSet.getDate("datenaissance");
        if (birthDate != null) {
            user.setDateNaissance(birthDate.toLocalDate());
        }

        Timestamp createdAt = resultSet.getTimestamp("created_at");
        if (createdAt != null) {
            user.setCreatedAt(createdAt.toLocalDateTime());
        }

        user.setPhotoUrl(resultSet.getString("photo_url"));

        return user;
    }
}
