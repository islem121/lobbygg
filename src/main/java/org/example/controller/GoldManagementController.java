package org.example.controller;

import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import org.example.model.Gold;
import org.example.service.GoldService;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;
import java.util.stream.Collectors;

public class GoldManagementController {

    @FXML private TableView<Gold> goldTable;
    @FXML private TableColumn<Gold, Integer> idCol, userIdCol;
    @FXML private TableColumn<Gold, Double> soldeCol;
    @FXML private TableColumn<Gold, LocalDateTime> dateCol;
    @FXML private TableColumn<Gold, Void> actionsCol;
    @FXML private TextField searchField;

    private final GoldService goldService = new GoldService();
    private List<Gold> allGolds;

    @FXML
    public void initialize() {
        idCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        userIdCol.setCellValueFactory(new PropertyValueFactory<>("idUtilisateur"));
        soldeCol.setCellValueFactory(new PropertyValueFactory<>("solde"));
        dateCol.setCellValueFactory(new PropertyValueFactory<>("derniereMaj"));

        setupActionsColumn();
        loadGolds();

        searchField.textProperty().addListener((obs, oldVal, newVal) -> {
            filterGolds(newVal);
        });
    }

    @FXML
    private void loadGolds() {
        allGolds = goldService.getAllGolds();
        goldTable.setItems(FXCollections.observableArrayList(allGolds));
    }

    private void filterGolds(String userId) {
        if (userId == null || userId.isEmpty()) {
            goldTable.setItems(FXCollections.observableArrayList(allGolds));
            return;
        }
        List<Gold> filtered = allGolds.stream()
                .filter(g -> String.valueOf(g.getIdUtilisateur()).contains(userId))
                .collect(Collectors.toList());
        goldTable.setItems(FXCollections.observableArrayList(filtered));
    }

    @FXML
    private void handleAddGold() {
        TextInputDialog dialog = new TextInputDialog();
        dialog.setTitle("Ajouter un compte Gold");
        dialog.setHeaderText("Créer un solde initial pour un utilisateur");
        dialog.setContentText("ID de l'utilisateur:");

        Optional<String> result = dialog.showAndWait();
        result.ifPresent(userIdStr -> {
            try {
                int userId = Integer.parseInt(userIdStr);
                if (goldService.addGold(userId, 0.0)) { // This creates the account if it doesn't exist
                    loadGolds();
                } else {
                    new Alert(Alert.AlertType.ERROR, "Erreur lors de la création du compte Gold.").show();
                }
            } catch (NumberFormatException e) {
                new Alert(Alert.AlertType.ERROR, "ID invalide.").show();
            }
        });
    }

    private void setupActionsColumn() {
        actionsCol.setCellFactory(param -> new TableCell<>() {
            private final Button adjustBtn = new Button("Ajuster");

            {
                adjustBtn.setStyle("-fx-background-color: #f39c12; -fx-text-fill: white;");
                adjustBtn.setOnAction(event -> {
                    Gold gold = getTableView().getItems().get(getIndex());
                    handleAdjust(gold);
                });
            }

            @Override
            protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                setGraphic(empty ? null : adjustBtn);
            }
        });
    }

    private void handleAdjust(Gold gold) {
        TextInputDialog dialog = new TextInputDialog(String.valueOf(gold.getSolde()));
        dialog.setTitle("Ajuster Solde Gold");
        dialog.setHeaderText("Modifier le solde pour l'utilisateur ID: " + gold.getIdUtilisateur());
        dialog.setContentText("Nouveau solde:");

        Optional<String> result = dialog.showAndWait();
        result.ifPresent(soldeStr -> {
            try {
                double newSolde = Double.parseDouble(soldeStr);
                if (goldService.updateSolde(gold.getIdUtilisateur(), newSolde)) {
                    loadGolds();
                }
            } catch (NumberFormatException e) {
                new Alert(Alert.AlertType.ERROR, "Veuillez entrer un nombre valide.").show();
            }
        });
    }
}
