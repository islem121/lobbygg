package org.example.web.repository;

import org.example.web.model.Sponsor;
import org.example.web.model.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface SponsorRepository extends JpaRepository<Sponsor, Integer> {
    List<Sponsor> findByOwner(User owner);
}
