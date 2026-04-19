package org.example.web.repository;

import org.example.web.model.GoldAccount;
import org.example.web.model.GoldOperation;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface GoldOperationRepository extends JpaRepository<GoldOperation, Integer> {
    List<GoldOperation> findByGoldAccountOrderByDateDesc(GoldAccount goldAccount);
}
