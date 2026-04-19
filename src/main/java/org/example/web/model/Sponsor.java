package org.example.web.model;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import org.hibernate.annotations.CreationTimestamp;

import java.time.LocalDateTime;

@Entity
@Table(name = "sponsor")
@Data
@NoArgsConstructor
@AllArgsConstructor
public class Sponsor {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @Column(name = "sponsor_id")
    private Integer id;

    @Column(name = "company_name", nullable = false)
    private String companyName;

    @Column(columnDefinition = "TEXT")
    private String description;

    private Double amount = 0.0;

    @Column(name = "target_type")
    private String targetType;

    private String logo;

    @ManyToOne
    @JoinColumn(name = "user_id")
    private User owner;

    @CreationTimestamp
    @Column(name = "created_at")
    private LocalDateTime createdAt;
}
