package org.example.controller;

import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.ButtonBar;
import javafx.scene.control.ButtonType;
import javafx.scene.control.Dialog;
import javafx.scene.control.Label;
import javafx.scene.control.TableCell;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.HBox;
import org.example.model.Sponsor;
import org.example.service.BackofficeService;

import java.time.LocalDateTime;
import java.util.List;

public class SponsorManagementController {
    @FXML private TableView<Sponsor> sponsorTable;
    @FXML private TableColumn<Sponsor, Integer> idCol;
    @FXML private TableColumn<Sponsor, String> companyCol;
    @FXML private TableColumn<Sponsor, Double> amountCol;
    @FXML private TableColumn<Sponsor, String> targetCol;
    @FXML private TableColumn<Sponsor, Integer> userIdCol;
    @FXML private TableColumn<Sponsor, LocalDateTime> createdCol;
    @FXML private TableColumn<Sponsor, Void> actionsCol;

    private final BackofficeService backofficeService = new BackofficeService();

    @FXML
    public void initialize() {
        idCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        companyCol.setCellValueFactory(new PropertyValueFactory<>("companyName"));
        amountCol.setCellValueFactory(new PropertyValueFactory<>("amount"));
        targetCol.setCellValueFactory(new PropertyValueFactory<>("targetType"));
        userIdCol.setCellValueFactory(new PropertyValueFactory<>("sponsorUserId"));
        createdCol.setCellValueFactory(new PropertyValueFactory<>("createdAt"));
        setupActionsColumn();
        loadSponsors();
    }

    @FXML
    private void loadSponsors() {
        List<Sponsor> sponsors = backofficeService.getAllSponsors();
        sponsorTable.setItems(FXCollections.observableArrayList(sponsors));
    }

    @FXML
    private void handleAddSponsor() {
        showSponsorDialog(null);
    }

    private void showSponsorDialog(Sponsor existingSponsor) {
        boolean isEdit = existingSponsor != null;
        Dialog<Sponsor> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Sponsor" : "Ajouter Sponsor");
        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);

        TextField companyField = new TextField();
        TextArea descriptionArea = new TextArea();
        TextField amountField = new TextField();
        TextField targetField = new TextField();
        TextField userIdField = new TextField();
        TextField logoField = new TextField();

        if (isEdit) {
            companyField.setText(existingSponsor.getCompanyName());
            descriptionArea.setText(existingSponsor.getDescription());
            amountField.setText(String.valueOf(existingSponsor.getAmount()));
            targetField.setText(existingSponsor.getTargetType());
            userIdField.setText(String.valueOf(existingSponsor.getSponsorUserId()));
            logoField.setText(existingSponsor.getLogoPath());
        }

        grid.addRow(0, new Label("Société"), companyField);
        grid.addRow(1, new Label("Description"), descriptionArea);
        grid.addRow(2, new Label("Montant"), amountField);
        grid.addRow(3, new Label("Cible"), targetField);
        grid.addRow(4, new Label("ID Sponsor"), userIdField);
        grid.addRow(5, new Label("Logo"), logoField);
        dialog.getDialogPane().setContent(grid);

        dialog.setResultConverter(button -> {
            if (button != saveButtonType) {
                return null;
            }
            Sponsor sponsor = isEdit ? existingSponsor : new Sponsor();
            sponsor.setCompanyName(companyField.getText().trim());
            sponsor.setDescription(descriptionArea.getText().trim());
            sponsor.setAmount(parseDouble(amountField.getText()));
            sponsor.setTargetType(targetField.getText().trim());
            sponsor.setSponsorUserId(parseInt(userIdField.getText()));
            sponsor.setLogoPath(logoField.getText().trim());
            if (!isEdit) {
                sponsor.setCreatedAt(LocalDateTime.now());
            }
            return sponsor;
        });

        dialog.showAndWait().ifPresent(sponsor -> {
            if (sponsor.getCompanyName().isBlank() || sponsor.getTargetType().isBlank() || sponsor.getSponsorUserId() <= 0) {
                showError("Veuillez remplir correctement les champs sponsor.");
                return;
            }
            boolean success = isEdit ? backofficeService.updateSponsor(sponsor) : backofficeService.addSponsor(sponsor);
            if (success) {
                loadSponsors();
            } else {
                showError("Erreur lors de l'enregistrement du sponsor.");
            }
        });
    }

    private void setupActionsColumn() {
        actionsCol.setCellFactory(param -> new TableCell<>() {
            private final Button editBtn = new Button("Modifier");
            private final Button deleteBtn = new Button("Supprimer");
            private final HBox box = new HBox(8, editBtn, deleteBtn);

            {
                editBtn.setOnAction(event -> showSponsorDialog(getTableView().getItems().get(getIndex())));
                deleteBtn.setOnAction(event -> {
                    Sponsor sponsor = getTableView().getItems().get(getIndex());
                    if (backofficeService.deleteSponsor(sponsor.getId())) {
                        loadSponsors();
                    }
                });
            }

            @Override
            protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                setGraphic(empty ? null : box);
            }
        });
    }

    private int parseInt(String value) {
        try {
            return Integer.parseInt(value.trim());
        } catch (Exception e) {
            return 0;
        }
    }

    private double parseDouble(String value) {
        try {
            return Double.parseDouble(value.trim());
        } catch (Exception e) {
            return 0.0;
        }
    }

    private void showError(String message) {
        new Alert(Alert.AlertType.ERROR, message).show();
    }
}
