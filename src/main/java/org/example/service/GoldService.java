package org.example.service;

import org.example.database.MySqlConnection;
import org.example.model.Gold;

import java.sql.*;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Optional;

public class GoldService {

    private static final String INSERT_SQL = "INSERT INTO gold (id_utilisateur, solde, derniere_maj) VALUES (?, ?, ?)";
    private static final String UPDATE_SQL = "UPDATE gold SET solde = ?, derniere_maj = ? WHERE id_utilisateur = ?";
    private static final String DELETE_SQL = "DELETE FROM gold WHERE id_utilisateur = ?";
    private static final String SELECT_BY_USER_SQL = "SELECT id, id_utilisateur, solde, derniere_maj FROM gold WHERE id_utilisateur = ?";
    private static final String SELECT_ALL_SQL = "SELECT id, id_utilisateur, solde, derniere_maj FROM gold";

    private final GoldOperationService operationService = new GoldOperationService();

    public boolean createGoldAccount(int userId) {
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(INSERT_SQL)) {
            ps.setInt(1, userId);
            ps.setDouble(2, 0.0);
            ps.setTimestamp(3, Timestamp.valueOf(LocalDateTime.now()));
            return ps.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public Optional<Gold> getGoldByUserId(int userId) {
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(SELECT_BY_USER_SQL)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return Optional.of(mapGold(rs));
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return Optional.empty();
    }

    public List<Gold> getAllGolds() {
        List<Gold> golds = new ArrayList<>();
        try (Connection conn = MySqlConnection.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(SELECT_ALL_SQL)) {
            while (rs.next()) {
                golds.add(mapGold(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return golds;
    }

    public boolean updateSolde(int userId, double newSolde) {
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(UPDATE_SQL)) {
            ps.setDouble(1, newSolde);
            ps.setTimestamp(2, Timestamp.valueOf(LocalDateTime.now()));
            ps.setInt(3, userId);
            return ps.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean addGold(int userId, double montant) {
        Optional<Gold> goldOpt = getGoldByUserId(userId);
        if (goldOpt.isPresent()) {
            double currentSolde = goldOpt.get().getSolde();
            if (updateSolde(userId, currentSolde + montant)) {
                operationService.addOperation(goldOpt.get().getId(), "RECHARGE", montant);
                return true;
            }
        } else {
            // Create account if not exists
            if (createGoldAccount(userId)) {
                return addGold(userId, montant);
            }
        }
        return false;
    }

    public boolean deleteGoldAccount(int userId) {
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(DELETE_SQL)) {
            ps.setInt(1, userId);
            return ps.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    private Gold mapGold(ResultSet rs) throws SQLException {
        Gold gold = new Gold();
        gold.setId(rs.getInt("id"));
        gold.setIdUtilisateur(rs.getInt("id_utilisateur"));
        gold.setSolde(rs.getDouble("solde"));
        Timestamp ts = rs.getTimestamp("derniere_maj");
        if (ts != null) {
            gold.setDerniereMaj(ts.toLocalDateTime());
        }
        return gold;
    }
}
