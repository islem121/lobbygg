package org.example.model;

import java.time.LocalDateTime;

public class Gold {
    private int id;
    private int idUtilisateur;
    private double solde;
    private LocalDateTime derniereMaj;

    public Gold() {}

    public Gold(int id, int idUtilisateur, double solde, LocalDateTime derniereMaj) {
        this.id = id;
        this.idUtilisateur = idUtilisateur;
        this.solde = solde;
        this.derniereMaj = derniereMaj;
    }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getIdUtilisateur() { return idUtilisateur; }
    public void setIdUtilisateur(int idUtilisateur) { this.idUtilisateur = idUtilisateur; }

    public double getSolde() { return solde; }
    public void setSolde(double solde) { this.solde = solde; }

    public LocalDateTime getDerniereMaj() { return derniereMaj; }
    public void setDerniereMaj(LocalDateTime derniereMaj) { this.derniereMaj = derniereMaj; }
}
