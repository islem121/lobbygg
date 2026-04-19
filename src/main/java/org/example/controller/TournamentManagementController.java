package org.example.controller;

import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.ButtonBar;
import javafx.scene.control.ButtonType;
import javafx.scene.control.ComboBox;
import javafx.scene.control.DatePicker;
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
import org.example.model.Tournament;
import org.example.service.BackofficeService;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;

public class TournamentManagementController {
    private static final List<String> TOURNAMENT_STATUSES = List.of(
            "PLANIFIE",
            "OUVERT",
            "EN_COURS",
            "TERMINE",
            "ANNULE"
    );

    @FXML private TableView<Tournament> tournamentTable;
    @FXML private TableColumn<Tournament, Integer> idCol;
    @FXML private TableColumn<Tournament, String> titleCol;
    @FXML private TableColumn<Tournament, String> statusCol;
    @FXML private TableColumn<Tournament, Integer> maxPlayersCol;
    @FXML private TableColumn<Tournament, LocalDateTime> startCol;
    @FXML private TableColumn<Tournament, LocalDate> endCol;
    @FXML private TableColumn<Tournament, Void> actionsCol;

    private final BackofficeService backofficeService = new BackofficeService();

    @FXML
    public void initialize() {
        idCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        titleCol.setCellValueFactory(new PropertyValueFactory<>("title"));
        statusCol.setCellValueFactory(new PropertyValueFactory<>("status"));
        maxPlayersCol.setCellValueFactory(new PropertyValueFactory<>("maxPlayers"));
        startCol.setCellValueFactory(new PropertyValueFactory<>("startDate"));
        endCol.setCellValueFactory(new PropertyValueFactory<>("endDate"));
        setupActionsColumn();
        loadTournaments();
    }

    @FXML
    private void loadTournaments() {
        List<Tournament> tournaments = backofficeService.getAllTournaments();
        tournamentTable.setItems(FXCollections.observableArrayList(tournaments));
    }

    @FXML
    private void handleAddTournament() {
        showTournamentDialog(null);
    }

    private void showTournamentDialog(Tournament existingTournament) {
        boolean isEdit = existingTournament != null;
        Dialog<Tournament> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Tournoi" : "Ajouter Tournoi");
        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);

        TextField titleField = new TextField();
        TextArea descriptionArea = new TextArea();
        ComboBox<String> statusField = new ComboBox<>(FXCollections.observableArrayList(TOURNAMENT_STATUSES));
        TextField maxPlayersField = new TextField();
        DatePicker startDatePicker = new DatePicker();
        DatePicker endDatePicker = new DatePicker();
        statusField.setValue("PLANIFIE");

        if (isEdit) {
            titleField.setText(existingTournament.getTitle());
            descriptionArea.setText(existingTournament.getDescription());
            statusField.setValue(existingTournament.getStatus());
            maxPlayersField.setText(String.valueOf(existingTournament.getMaxPlayers()));
            if (existingTournament.getStartDate() != null) {
                startDatePicker.setValue(existingTournament.getStartDate().toLocalDate());
            }
            endDatePicker.setValue(existingTournament.getEndDate());
        }

        grid.addRow(0, new Label("Titre"), titleField);
        grid.addRow(1, new Label("Description"), descriptionArea);
        grid.addRow(2, new Label("Statut"), statusField);
        grid.addRow(3, new Label("Max Joueurs"), maxPlayersField);
        grid.addRow(4, new Label("Début"), startDatePicker);
        grid.addRow(5, new Label("Fin"), endDatePicker);
        dialog.getDialogPane().setContent(grid);

        dialog.setResultConverter(button -> {
            if (button != saveButtonType) {
                return null;
            }
            Tournament tournament = isEdit ? existingTournament : new Tournament();
            tournament.setTitle(titleField.getText().trim());
            tournament.setDescription(descriptionArea.getText().trim());
            tournament.setStatus(statusField.getValue());
            tournament.setMaxPlayers(parseInt(maxPlayersField.getText()));
            tournament.setStartDate((startDatePicker.getValue() != null ? startDatePicker.getValue() : LocalDate.now()).atStartOfDay());
            tournament.setEndDate(endDatePicker.getValue() != null ? endDatePicker.getValue() : LocalDate.now().plusDays(1));
            if (!isEdit) {
                tournament.setCreatedAt(LocalDateTime.now());
            }
            return tournament;
        });

        dialog.showAndWait().ifPresent(tournament -> {
            if (tournament.getTitle().isBlank() || tournament.getStatus().isBlank() || tournament.getMaxPlayers() <= 0) {
                new Alert(Alert.AlertType.ERROR, "Veuillez remplir correctement les champs tournoi.").show();
                return;
            }
            boolean success = isEdit ? backofficeService.updateTournament(tournament) : backofficeService.addTournament(tournament);
            if (success) {
                loadTournaments();
            } else {
                new Alert(Alert.AlertType.ERROR, "Erreur lors de l'enregistrement du tournoi.").show();
            }
        });
    }

    private void setupActionsColumn() {
        actionsCol.setCellFactory(param -> new TableCell<>() {
            private final Button editBtn = new Button("Modifier");
            private final Button deleteBtn = new Button("Supprimer");
            private final HBox box = new HBox(8, editBtn, deleteBtn);

            {
                editBtn.setOnAction(event -> showTournamentDialog(getTableView().getItems().get(getIndex())));
                deleteBtn.setOnAction(event -> {
                    Tournament tournament = getTableView().getItems().get(getIndex());
                    if (backofficeService.deleteTournament(tournament.getId())) {
                        loadTournaments();
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
}
