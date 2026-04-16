package org.example.model;

import java.time.LocalDateTime;

public class GoldOperation {
    private int id;
    private int idGold;
    private String type; // RECHARGE, ACHAT, etc.
    private double montant;
    private LocalDateTime date;
    private String status; // VALIDE, ANNULE

    public GoldOperation() {}

    public GoldOperation(int id, int idGold, String type, double montant, LocalDateTime date, String status) {
        this.id = id;
        this.idGold = idGold;
        this.type = type;
        this.montant = montant;
        this.date = date;
        this.status = status;
    }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getIdGold() { return idGold; }
    public void setIdGold(int idGold) { this.idGold = idGold; }

    public String getType() { return type; }
    public void setType(String type) { this.type = type; }

    public double getMontant() { return montant; }
    public void setMontant(double montant) { this.montant = montant; }

    public LocalDateTime getDate() { return date; }
    public void setDate(LocalDateTime date) { this.date = date; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }
}
