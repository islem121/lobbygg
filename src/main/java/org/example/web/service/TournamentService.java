package org.example.web.service;

import lombok.RequiredArgsConstructor;
import org.example.web.model.Tournament;
import org.example.web.repository.TournamentRepository;
import org.springframework.stereotype.Service;

import java.util.List;
import java.util.Optional;

@Service
@RequiredArgsConstructor
public class TournamentService {
    private final TournamentRepository tournamentRepository;

    public List<Tournament> getAllActiveTournaments() {
        return tournamentRepository.findAll();
    }

    public Optional<Tournament> getById(Integer id) {
        return tournamentRepository.findById(id);
    }
}
