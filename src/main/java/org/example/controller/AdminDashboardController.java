package org.example.controller;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Node;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.ScrollPane;
import javafx.scene.layout.StackPane;
import org.example.AuthApplication;
import org.example.service.BackofficeService;
import org.example.service.UserService;

import java.io.IOException;
import java.util.Map;

public class AdminDashboardController {
    @FXML private Label adminNameLabel;
    @FXML private Label usersCountLabel;
    @FXML private Label sponsorsCountLabel;
    @FXML private Label tournamentsCountLabel;
    @FXML private Label categoriesCountLabel;
    @FXML private Label productsCountLabel;
    @FXML private Label ordersCountLabel;
    @FXML private Label postsCountLabel;
    @FXML private Label commentsCountLabel;
    @FXML private StackPane contentStack;
    @FXML private ScrollPane statsView;
    @FXML private Button statsBtn;
    @FXML private Button userBtn;
    @FXML private Button goldBtn;
    @FXML private Button sponsorBtn;
    @FXML private Button tournamentBtn;
    @FXML private Button marketplaceBtn;
    @FXML private Button blogBtn;

    private final UserService userService = new UserService();
    private final BackofficeService backofficeService = new BackofficeService();
    private AuthApplication mainApp;

    @FXML
    public void initialize() {
        loadStats();
    }

    private void loadStats() {
        Map<String, Integer> stats = userService.getGlobalStats();
        usersCountLabel.setText(String.valueOf(stats.getOrDefault("Utilisateurs", 0)));
        sponsorsCountLabel.setText(String.valueOf(backofficeService.getCount("sponsor")));
        tournamentsCountLabel.setText(String.valueOf(backofficeService.getCount("tournament")));
        categoriesCountLabel.setText(String.valueOf(backofficeService.getCount("category")));
        productsCountLabel.setText(String.valueOf(backofficeService.getCount("product")));
        ordersCountLabel.setText(String.valueOf(backofficeService.getCount("`order`")));
        postsCountLabel.setText(String.valueOf(backofficeService.getCount("post")));
        commentsCountLabel.setText(String.valueOf(backofficeService.getCount("comment")));
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

    @FXML
    private void showSponsorManagement() {
        loadView("/fxml/SponsorManagement.fxml", sponsorBtn);
    }

    @FXML
    private void showTournamentManagement() {
        loadView("/fxml/TournamentManagement.fxml", tournamentBtn);
    }

    @FXML
    private void showMarketplaceManagement() {
        loadView("/fxml/MarketplaceManagement.fxml", marketplaceBtn);
    }

    @FXML
    private void showBlogManagement() {
        loadView("/fxml/BlogManagement.fxml", blogBtn);
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
        statsBtn.getStyleClass().remove("active");
        userBtn.getStyleClass().remove("active");
        goldBtn.getStyleClass().remove("active");
        sponsorBtn.getStyleClass().remove("active");
        tournamentBtn.getStyleClass().remove("active");
        marketplaceBtn.getStyleClass().remove("active");
        blogBtn.getStyleClass().remove("active");
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
        }
    }
}
