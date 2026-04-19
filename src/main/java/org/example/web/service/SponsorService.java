package org.example.web.service;

import lombok.RequiredArgsConstructor;
import org.example.web.model.Sponsor;
import org.example.web.model.User;
import org.example.web.repository.SponsorRepository;
import org.springframework.stereotype.Service;

import java.util.List;

@Service
@RequiredArgsConstructor
public class SponsorService {
    private final SponsorRepository sponsorRepository;

    public List<Sponsor> getSponsorsByUser(User user) {
        return sponsorRepository.findByOwner(user);
    }

    public List<Sponsor> getAllSponsors() {
        return sponsorRepository.findAll();
    }

    public Sponsor saveSponsor(Sponsor sponsor) {
        return sponsorRepository.save(sponsor);
    }

    public void deleteSponsor(Integer id) {
        sponsorRepository.deleteById(id);
    }
}
