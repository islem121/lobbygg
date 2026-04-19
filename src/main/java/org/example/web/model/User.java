package org.example.web.model;

import jakarta.persistence.*;
import org.hibernate.annotations.CreationTimestamp;

import java.time.LocalDate;
import java.time.LocalDateTime;

@Entity
@Table(name = "user")
public class User {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    @Column(unique = true, nullable = false, length = 100)
    private String username;

    @Column(unique = true, nullable = false, length = 180)
    private String email;

    @Column(nullable = false)
    private String password;

    @Column(columnDefinition = "TEXT")
    private String bio;

    @Column(nullable = false, length = 50)
    private String role = "client"; // client, admin, sponsor

    @Column(nullable = false, length = 150)
    private String nom;

    @Column(length = 20)
    private String telephone;

    private LocalDate datenaissance;

    @CreationTimestamp
    @Column(name = "created_at", nullable = false, updatable = false)
    private LocalDateTime createdAt;

    @Column(name = "photo_url")
    private String photoUrl;

    @OneToOne(mappedBy = "user", cascade = CascadeType.ALL)
    private GoldAccount goldAccount;

    public User() {}

    public User(Integer id, String username, String email, String password, String bio, String role, String nom, String telephone, LocalDate datenaissance, LocalDateTime createdAt, String photoUrl, GoldAccount goldAccount) {
        this.id = id;
        this.username = username;
        this.email = email;
        this.password = password;
        this.bio = bio;
        this.role = role;
        this.nom = nom;
        this.telephone = telephone;
        this.datenaissance = datenaissance;
        this.createdAt = createdAt;
        this.photoUrl = photoUrl;
        this.goldAccount = goldAccount;
    }

    public Integer getId() { return id; }
    public void setId(Integer id) { this.id = id; }

    public String getUsername() { return username; }
    public void setUsername(String username) { this.username = username; }

    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }

    public String getPassword() { return password; }
    public void setPassword(String password) { this.password = password; }

    public String getBio() { return bio; }
    public void setBio(String bio) { this.bio = bio; }

    public String getRole() { return role; }
    public void setRole(String role) { this.role = role; }

    public String getNom() { return nom; }
    public void setNom(String nom) { this.nom = nom; }

    public String getTelephone() { return telephone; }
    public void setTelephone(String telephone) { this.telephone = telephone; }

    public LocalDate getDatenaissance() { return datenaissance; }
    public void setDatenaissance(LocalDate datenaissance) { this.datenaissance = datenaissance; }

    public LocalDateTime getCreatedAt() { return createdAt; }
    public void setCreatedAt(LocalDateTime createdAt) { this.createdAt = createdAt; }

    public String getPhotoUrl() { return photoUrl; }
    public void setPhotoUrl(String photoUrl) { this.photoUrl = photoUrl; }

    public GoldAccount getGoldAccount() { return goldAccount; }
    public void setGoldAccount(GoldAccount goldAccount) { this.goldAccount = goldAccount; }

    public String getDisplayName() {
        return nom != null && !nom.isEmpty() ? nom : username;
    }

    public boolean isAdmin() {
        return "admin".equalsIgnoreCase(role);
    }

    public boolean isSponsor() {
        return "sponsor".equalsIgnoreCase(role);
    }

    public boolean isClient() {
        return "client".equalsIgnoreCase(role);
    }
}
