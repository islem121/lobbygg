package org.example.web.model;

import jakarta.persistence.*;
import org.hibernate.annotations.CreationTimestamp;

import java.time.LocalDateTime;

@Entity
@Table(name = "gold_operation")
public class GoldOperation {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    @ManyToOne
    @JoinColumn(name = "id_gold", nullable = false)
    private GoldAccount goldAccount;

    @Column(nullable = false, length = 50)
    private String type; // RECHARGE, ACHAT, etc.

    @Column(nullable = false)
    private Double montant;

    @CreationTimestamp
    private LocalDateTime date;

    @Column(nullable = false, length = 30)
    private String status = "VALIDE"; // VALIDE, ANNULE

    public GoldOperation() {}

    public GoldOperation(Integer id, GoldAccount goldAccount, String type, Double montant, LocalDateTime date, String status) {
        this.id = id;
        this.goldAccount = goldAccount;
        this.type = type;
        this.montant = montant;
        this.date = date;
        this.status = status;
    }

    public Integer getId() { return id; }
    public void setId(Integer id) { this.id = id; }

    public GoldAccount getGoldAccount() { return goldAccount; }
    public void setGoldAccount(GoldAccount goldAccount) { this.goldAccount = goldAccount; }

    public String getType() { return type; }
    public void setType(String type) { this.type = type; }

    public Double getMontant() { return montant; }
    public void setMontant(Double montant) { this.montant = montant; }

    public LocalDateTime getDate() { return date; }
    public void setDate(LocalDateTime date) { this.date = date; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }
}
