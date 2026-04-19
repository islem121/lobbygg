package org.example.web.service;

import lombok.RequiredArgsConstructor;
import org.example.web.model.GoldAccount;
import org.example.web.model.GoldOperation;
import org.example.web.model.User;
import org.example.web.repository.GoldAccountRepository;
import org.example.web.repository.GoldOperationRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@RequiredArgsConstructor
public class WalletService {
    private final GoldAccountRepository goldAccountRepository;
    private final GoldOperationRepository goldOperationRepository;

    public GoldAccount getOrCreateAccount(User user) {
        return goldAccountRepository.findByUser(user)
                .orElseGet(() -> {
                    GoldAccount account = new GoldAccount();
                    account.setUser(user);
                    account.setSolde(0.0);
                    return goldAccountRepository.save(account);
                });
    }

    @Transactional
    public void recharge(User user, Double amount) {
        GoldAccount account = getOrCreateAccount(user);
        account.setSolde(account.getSolde() + amount);
        goldAccountRepository.save(account);

        GoldOperation operation = new GoldOperation();
        operation.setGoldAccount(account);
        operation.setType("RECHARGE");
        operation.setMontant(amount);
        goldOperationRepository.save(operation);
    }

    @Transactional
    public boolean deduct(User user, Double amount, String type) {
        GoldAccount account = getOrCreateAccount(user);
        if (account.getSolde() < amount) {
            return false;
        }
        account.setSolde(account.getSolde() - amount);
        goldAccountRepository.save(account);

        GoldOperation operation = new GoldOperation();
        operation.setGoldAccount(account);
        operation.setType(type);
        operation.setMontant(-amount);
        goldOperationRepository.save(operation);
        return true;
    }

    public List<GoldOperation> getHistory(User user) {
        GoldAccount account = getOrCreateAccount(user);
        return goldOperationRepository.findByGoldAccountOrderByDateDesc(account);
    }
}
