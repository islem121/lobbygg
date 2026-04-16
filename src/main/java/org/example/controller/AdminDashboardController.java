package org.example.controller;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Node;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.ScrollPane;
import javafx.scene.layout.StackPane;
import org.example.AuthApplication;
import org.example.service.UserService;

import java.io.IOException;
import java.util.Map;

public class AdminDashboardController {

    @FXML private Label adminNameLabel;
    @FXML private Label usersCountLabel;
    @FXML private Label reservationsCountLabel;
    @FXML private Label articlesCountLabel;
    @FXML private StackPane contentStack;
    @FXML private ScrollPane statsView;
    @FXML private Button statsBtn, userBtn, goldBtn;

    private final UserService userService = new UserService();
    private AuthApplication mainApp;

    @FXML
    public void initialize() {
        try {
            loadStats();
        } catch (Exception e) {
            System.err.println("Erreur lors de l'initialisation du dashboard : " + e.getMessage());
            e.printStackTrace();
        }
    }

    private void loadStats() {
        Map<String, Integer> stats = userService.getGlobalStats();
        
        usersCountLabel.setText(String.valueOf(stats.getOrDefault("Utilisateurs", 0)));
        reservationsCountLabel.setText(String.valueOf(stats.getOrDefault("Reservations", 0)));
        articlesCountLabel.setText(String.valueOf(stats.getOrDefault("Articles", 0)));
    }

    @FXML
    private void showStats() {
        setActiveView(statsView, statsBtn);
        loadStats();
    }

    @FXML
    private void showUserManagement() {
        loadView("/fxml/UserManagement.fxml", userBtn);
    }

    @FXML
    private void showGoldManagement() {
        loadView("/fxml/GoldManagement.fxml", goldBtn);
    }

    private void loadView(String fxmlPath, Button activeBtn) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource(fxmlPath));
            Node view = loader.load();
            setActiveView(view, activeBtn);
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private void setActiveView(Node view, Button activeBtn) {
        contentStack.getChildren().setAll(view);
        
        // Update button styles
        statsBtn.getStyleClass().remove("active");
        userBtn.getStyleClass().remove("active");
        goldBtn.getStyleClass().remove("active");
        activeBtn.getStyleClass().add("active");
    }

    public void setAdminName(String name) {
        adminNameLabel.setText(name);
    }

    public void setMainApp(AuthApplication mainApp) {
        this.mainApp = mainApp;
    }

    @FXML
    private void handleLogout() {
        if (mainApp != null) {
            mainApp.logout();
        } else {
            System.out.println("MainApp instance not set.");
        }
    }
}
