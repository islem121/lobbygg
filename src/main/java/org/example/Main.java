package org.example;

import org.example.web.LobbyApplication;
import org.springframework.boot.SpringApplication;
import org.springframework.context.ConfigurableApplicationContext;

public class Main {
    private static ConfigurableApplicationContext springContext;

    public static void main(String[] args) {
        System.out.println("Lobby.GG - Démarrage de l'écosystème...");
        
        // 1. Démarrer le serveur Spring Boot (FrontOffice) en arrière-plan
        Thread springThread = new Thread(() -> {
            try {
                System.out.println("Lancement du serveur Web (Port 8081)...");
                springContext = SpringApplication.run(LobbyApplication.class, args);
                System.out.println("Serveur Web PRÊT !");
            } catch (Exception e) {
                System.err.println("Erreur fatale au démarrage du serveur Web : " + e.getMessage());
                e.printStackTrace();
            }
        });
        springThread.setDaemon(true);
        springThread.start();

        // 2. Démarrer l'interface JavaFX (BackOffice & Portal)
        System.out.println("Lancement de l'interface Desktop...");
        try {
            AuthApplication.main(args);
        } catch (Exception e) {
            System.err.println("Erreur fatale au lancement de l'interface Desktop : " + e.getMessage());
            e.printStackTrace();
        } finally {
            if (springContext != null) {
                springContext.close();
            }
        }
    }
}
