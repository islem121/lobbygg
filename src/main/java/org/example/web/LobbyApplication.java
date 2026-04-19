package org.example.web;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.autoconfigure.domain.EntityScan;
import org.springframework.data.jpa.repository.config.EnableJpaRepositories;

@SpringBootApplication
@EntityScan("org.example.web.model")
@EnableJpaRepositories("org.example.web.repository")
public class LobbyApplication {
    public static void main(String[] args) {
        System.out.println("Démarrage de LobbyApplication (Spring Boot)...");
        SpringApplication.run(LobbyApplication.class, args);
    }
}
