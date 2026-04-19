package org.example.web.model;

import jakarta.persistence.*;
import org.hibernate.annotations.CreationTimestamp;

import java.time.LocalDateTime;

@Entity
@Table(name = "gold")
public class GoldAccount {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    @OneToOne
    @JoinColumn(name = "id_utilisateur", referencedColumnName = "id")
    private User user;

    @Column(nullable = false)
    private Double solde = 0.0;

    @CreationTimestamp
    @Column(name = "derniere_maj")
    private LocalDateTime derniereMaj;

    public GoldAccount() {}

    public GoldAccount(Integer id, User user, Double solde, LocalDateTime derniereMaj) {
        this.id = id;
        this.user = user;
        this.solde = solde;
        this.derniereMaj = derniereMaj;
    }

    public Integer getId() { return id; }
    public void setId(Integer id) { this.id = id; }

    public User getUser() { return user; }
    public void setUser(User user) { this.user = user; }

    public Double getSolde() { return solde; }
    public void setSolde(Double solde) { this.solde = solde; }

    public LocalDateTime getDerniereMaj() { return derniereMaj; }
    public void setDerniereMaj(LocalDateTime derniereMaj) { this.derniereMaj = derniereMaj; }
}
