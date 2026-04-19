package org.example.web.repository;

import org.example.web.model.GoldAccount;
import org.example.web.model.User;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface GoldAccountRepository extends JpaRepository<GoldAccount, Integer> {
    Optional<GoldAccount> findByUser(User user);
}
