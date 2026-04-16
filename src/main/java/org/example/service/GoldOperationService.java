package org.example.service;

import org.example.database.MySqlConnection;
import org.example.model.GoldOperation;

import java.sql.*;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

public class GoldOperationService {

    private static final String INSERT_SQL = "INSERT INTO gold_operation (id_gold, type, montant, date, status) VALUES (?, ?, ?, ?, ?)";
    private static final String SELECT_BY_GOLD_SQL = "SELECT * FROM gold_operation WHERE id_gold = ? ORDER BY date DESC";
    private static final String UPDATE_STATUS_SQL = "UPDATE gold_operation SET status = ? WHERE id = ?";
    private static final String SELECT_BY_ID_SQL = "SELECT * FROM gold_operation WHERE id = ?";

    public boolean addOperation(int goldId, String type, double montant) {
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(INSERT_SQL)) {
            ps.setInt(1, goldId);
            ps.setString(2, type);
            ps.setDouble(3, montant);
            ps.setTimestamp(4, Timestamp.valueOf(LocalDateTime.now()));
            ps.setString(5, "VALIDE");
            return ps.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public List<GoldOperation> getOperationsByGoldId(int goldId) {
        List<GoldOperation> ops = new ArrayList<>();
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(SELECT_BY_GOLD_SQL)) {
            ps.setInt(1, goldId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    ops.add(mapOperation(rs));
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return ops;
    }

    public boolean cancelOperation(int opId) {
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(UPDATE_STATUS_SQL)) {
            ps.setString(1, "ANNULE");
            ps.setInt(2, opId);
            return ps.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public GoldOperation getById(int opId) {
        try (Connection conn = MySqlConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(SELECT_BY_ID_SQL)) {
            ps.setInt(1, opId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapOperation(rs);
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }

    private GoldOperation mapOperation(ResultSet rs) throws SQLException {
        GoldOperation op = new GoldOperation();
        op.setId(rs.getInt("id"));
        op.setIdGold(rs.getInt("id_gold"));
        op.setType(rs.getString("type"));
        op.setMontant(rs.getDouble("montant"));
        op.setDate(rs.getTimestamp("date").toLocalDateTime());
        op.setStatus(rs.getString("status"));
        return op;
    }
}
