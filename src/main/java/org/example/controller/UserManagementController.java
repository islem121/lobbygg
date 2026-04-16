package org.example.controller;

import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.HBox;
import org.example.model.User;
import org.example.service.UserService;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.Period;
import java.util.List;
import java.util.Optional;
import java.util.regex.Pattern;

public class UserManagementController {

    private static final Pattern EMAIL_PATTERN = Pattern.compile("^[A-Za-z0-9+_.-]+@(.+)$");
    private static final Pattern PASSWORD_PATTERN = Pattern.compile("^(?=.*[A-Z])(?=.*\\d).{8,}$");

    @FXML private TableView<User> userTable;
    @FXML private TableColumn<User, Integer> idCol;
    @FXML private TableColumn<User, String> nomCol, usernameCol, emailCol, roleCol, telephoneCol;
    @FXML private TableColumn<User, LocalDate> dateNaisCol;
    @FXML private TableColumn<User, LocalDateTime> createdCol;
    @FXML private TableColumn<User, Void> actionsCol;

    private final UserService userService = new UserService();

    @FXML
    public void initialize() {
        idCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        nomCol.setCellValueFactory(new PropertyValueFactory<>("nom"));
        usernameCol.setCellValueFactory(new PropertyValueFactory<>("username"));
        emailCol.setCellValueFactory(new PropertyValueFactory<>("email"));
        roleCol.setCellValueFactory(new PropertyValueFactory<>("role"));
        telephoneCol.setCellValueFactory(new PropertyValueFactory<>("telephone"));
        dateNaisCol.setCellValueFactory(new PropertyValueFactory<>("dateNaissance"));
        createdCol.setCellValueFactory(new PropertyValueFactory<>("createdAt"));

        setupActionsColumn();
        loadUsers();
    }

    @FXML
    private void loadUsers() {
        List<User> users = userService.getAllUsers();
        userTable.setItems(FXCollections.observableArrayList(users));
    }

    @FXML
    private void handleAddUser() {
        showUserDialog(null);
    }

    private void showUserDialog(User existingUser) {
        boolean isEdit = existingUser != null;
        Dialog<User> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Utilisateur" : "Ajouter un nouvel utilisateur");
        dialog.setHeaderText(isEdit ? "Modifiez les informations de l'utilisateur" : "Saisissez les informations de l'utilisateur");

        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        grid.setPadding(new Insets(20, 150, 10, 10));

        TextField username = new TextField();
        username.setPromptText("Username");
        TextField email = new TextField();
        email.setPromptText("Email");
        PasswordField password = new PasswordField();
        password.setPromptText("Mot de passe (si changement)");
        TextField nom = new TextField();
        nom.setPromptText("Nom complet");
        TextField telephone = new TextField();
        telephone.setPromptText("Téléphone");
        DatePicker dateNais = new DatePicker();
        dateNais.setPromptText("Date de naissance");
        TextArea bio = new TextArea();
        bio.setPromptText("Bio");
        bio.setPrefRowCount(3);
        ComboBox<String> role = new ComboBox<>(FXCollections.observableArrayList("client", "admin", "sponsor"));
        role.setValue("client");

        if (isEdit) {
            username.setText(existingUser.getUsername());
            email.setText(existingUser.getEmail());
            nom.setText(existingUser.getNom());
            telephone.setText(existingUser.getTelephone());
            dateNais.setValue(existingUser.getDateNaissance());
            bio.setText(existingUser.getBio());
            role.setValue(existingUser.getRole());
            password.setPromptText("Laissez vide pour garder le même");
        }

        grid.add(new Label("Username:"), 0, 0);
        grid.add(username, 1, 0);
        grid.add(new Label("Email:"), 0, 1);
        grid.add(email, 1, 1);
        grid.add(new Label("Mot de passe:"), 0, 2);
        grid.add(password, 1, 2);
        grid.add(new Label("Nom complet:"), 0, 3);
        grid.add(nom, 1, 3);
        grid.add(new Label("Téléphone:"), 0, 4);
        grid.add(telephone, 1, 4);
        grid.add(new Label("Naissance:"), 0, 5);
        grid.add(dateNais, 1, 5);
        grid.add(new Label("Bio:"), 0, 6);
        grid.add(bio, 1, 6);
        grid.add(new Label("Rôle:"), 0, 7);
        grid.add(role, 1, 7);

        dialog.getDialogPane().setContent(grid);

        // Validation lors du clic sur Enregistrer
        final Button saveButton = (Button) dialog.getDialogPane().lookupButton(saveButtonType);
        saveButton.addEventFilter(javafx.event.ActionEvent.ACTION, event -> {
            String emailText = email.getText().trim();
            String telText = telephone.getText().trim();
            LocalDate birthDate = dateNais.getValue();
            String passText = password.getText();

            StringBuilder errors = new StringBuilder();

            if (username.getText().isBlank() || emailText.isBlank() || nom.getText().isBlank()) {
                errors.append("- Veuillez remplir tous les champs obligatoires (Username, Email, Nom).\n");
            }

            if (!EMAIL_PATTERN.matcher(emailText).matches()) {
                errors.append("- Format d'email invalide.\n");
            }

            if (!telText.matches("\\d{8}")) {
                errors.append("- Le téléphone doit contenir exactement 8 chiffres.\n");
            }

            if (birthDate == null || Period.between(birthDate, LocalDate.now()).getYears() < 18) {
                errors.append("- L'utilisateur doit avoir au moins 18 ans.\n");
            }

            if (!isEdit || !passText.isEmpty()) {
                if (passText.isEmpty()) {
                    errors.append("- Le mot de passe est obligatoire pour un nouvel utilisateur.\n");
                } else if (!PASSWORD_PATTERN.matcher(passText).matches()) {
                    errors.append("- Le mot de passe doit comporter au moins 8 caractères, une majuscule et un chiffre.\n");
                }
            }

            if (errors.length() > 0) {
                new Alert(Alert.AlertType.WARNING, "Erreurs de saisie :\n" + errors.toString()).show();
                event.consume(); // Empêche la fermeture du dialogue
            }
        });

        dialog.setResultConverter(dialogButton -> {
            if (dialogButton == saveButtonType) {
                User user = isEdit ? existingUser : new User();
                user.setUsername(username.getText());
                user.setEmail(email.getText());
                if (!password.getText().isEmpty()) {
                    user.setPasswordHash(org.example.service.PasswordUtils.hashPassword(password.getText()));
                }
                user.setNom(nom.getText());
                user.setTelephone(telephone.getText());
                user.setDateNaissance(dateNais.getValue());
                user.setBio(bio.getText());
                user.setRole(role.getValue());
                return user;
            }
            return null;
        });

        dialog.showAndWait().ifPresent(user -> {
            boolean success = isEdit ? userService.updateUser(user) : userService.addUser(user);
            if (success) {
                loadUsers();
            } else {
                new Alert(Alert.AlertType.ERROR, "Erreur lors de l'enregistrement de l'utilisateur.").show();
            }
        });
    }

    private void setupActionsColumn() {
        actionsCol.setCellFactory(param -> new TableCell<>() {
            private final Button deleteBtn = new Button("Supprimer");
            private final Button editBtn = new Button("Modifier");
            private final HBox container = new HBox(10, editBtn, deleteBtn);

            {
                deleteBtn.setStyle("-fx-background-color: #e74c3c; -fx-text-fill: white;");
                editBtn.setStyle("-fx-background-color: #f39c12; -fx-text-fill: white;");
                
                deleteBtn.setOnAction(event -> {
                    User user = getTableView().getItems().get(getIndex());
                    handleDelete(user);
                });
                
                editBtn.setOnAction(event -> {
                    User user = getTableView().getItems().get(getIndex());
                    handleEdit(user);
                });
            }

            @Override
            protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                setGraphic(empty ? null : container);
            }
        });
    }

    private void handleDelete(User user) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION, "Supprimer l'utilisateur " + user.getNom() + " ?", ButtonType.YES, ButtonType.NO);
        alert.showAndWait().ifPresent(response -> {
            if (response == ButtonType.YES) {
                if (userService.deleteUser(user.getId())) {
                    loadUsers();
                }
            }
        });
    }

    private void handleEdit(User user) {
        showUserDialog(user);
    }
}
