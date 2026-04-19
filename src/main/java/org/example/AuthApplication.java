package org.example;

import javafx.application.Application;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Node;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.ButtonType;
import javafx.scene.control.ComboBox;
import javafx.scene.control.DatePicker;
import javafx.scene.control.Hyperlink;
import javafx.scene.control.Label;
import javafx.scene.control.PasswordField;
import javafx.scene.control.ScrollPane;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.scene.layout.BorderPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.beans.binding.Bindings;
import javafx.beans.property.BooleanProperty;
import javafx.beans.property.SimpleBooleanProperty;
import org.example.controller.AdminDashboardController;
import org.example.database.MySqlConnection;
import org.example.model.Gold;
import org.example.model.GoldOperation;
import org.example.model.User;
import org.example.service.GoldOperationService;
import org.example.service.GoldService;
import org.example.service.PasswordUtils;
import org.example.service.UserService;

import java.io.File;
import java.io.IOException;
import java.time.LocalDate;
import java.time.Period;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Optional;
import java.util.regex.Pattern;
import javafx.stage.FileChooser;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.web.WebView;
import javafx.scene.web.WebEngine;

public class AuthApplication extends Application {
    private static final String LOGIN = "LOGIN";
    private static final String SIGNUP = "SIGNUP";

    private final UserService userService = new UserService();
    private final GoldService goldService = new GoldService();
    private final GoldOperationService goldOpService = new GoldOperationService();
    private final Map<String, Button> navigationButtons = new LinkedHashMap<>();

    private Scene scene;
    private Stage primaryStage;
    private User currentUser;

    private Node loginForm;
    private Node signupForm;
    private Button loginTabButton;
    private Button signupTabButton;

    private TextField loginEmailField;
    private PasswordField loginPasswordField;
    private ComboBox<String> loginRoleComboBox;
    private Label loginMessageLabel;

    private TextField signupNameField;
    private TextField signupUsernameField;
    private TextField signupEmailField;
    private TextField signupTelephoneField;
    private DatePicker signupDateNaissancePicker;
    private ComboBox<String> signupRoleComboBox;
    private TextArea signupBioArea;
    private PasswordField signupPasswordField;
    private PasswordField signupConfirmPasswordField;
    private Label signupMessageLabel;
    private Button loginButton;
    private Button signupButton;

    private static final Pattern EMAIL_PATTERN = Pattern.compile("^[A-Za-z0-9+_.-]+@(.+)$");
    private static final Pattern PASSWORD_PATTERN = Pattern.compile("^(?=.*[A-Z])(?=.*\\d).{8,}$");

    private BorderPane dashboardRoot;
    private Label pageBadgeLabel;
    private Label pageTitleLabel;
    private Label pageDescriptionLabel;
    private VBox sectionCards;
    private Button activeNavButton;

    public static void main(String[] args) {
        launch(args);
    }

    @Override
    public void start(Stage stage) {
        System.out.println("Initialisation du Stage JavaFX...");
        primaryStage = stage;

        // Window Icons (Multiple sizes for OS compatibility)
        System.out.println("Chargement des icones...");
        try {
            loadIcons(stage);
        } catch (Exception e) {
            System.err.println("Erreur lors du chargement des icones : " + e.getMessage());
        }

        System.out.println("Creation de la scene...");
         try {
             scene = new Scene(createEntrySelector(), 1240, 760);
             var cssResource = getClass().getResource("/styles/auth.css");
             if (cssResource != null) {
                 scene.getStylesheets().add(cssResource.toExternalForm());
             } else {
                 System.err.println("CSS introuvable : /styles/auth.css");
             }
 
             stage.setTitle("Lobby.GG Desktop");
            stage.setMinWidth(1080);
            stage.setMinHeight(700);
            stage.setScene(scene);
            
            System.out.println("Affichage de la fenetre...");
            stage.show();

            if (!MySqlConnection.testConnection()) {
                updateMessage(loginMessageLabel, "Connexion MySQL indisponible. Verifie phpMyAdmin et les parametres JDBC.", false);
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la creation de l'UI : " + e.getMessage());
            e.printStackTrace();
        }
    }

    private StackPane createEntrySelector() {
        VBox container = new VBox(40);
        container.setAlignment(Pos.CENTER);
        container.getStyleClass().add("auth-panel");

        Label mainTitle = new Label("LOBBY.GG");
        mainTitle.setStyle("-fx-font-family: 'Poppins'; -fx-font-size: 64px; -fx-font-weight: 900; -fx-text-fill: white; -fx-letter-spacing: -0.05em;");
        
        Label subtitle = new Label("Choose your entry point into the esports ecosystem");
        subtitle.setStyle("-fx-font-size: 18px; -fx-text-fill: #9CA3AF; -fx-font-weight: 500;");

        HBox cards = new HBox(30);
        cards.setAlignment(Pos.CENTER);

        VBox platformCard = createSelectorCard(
                "EXPLORE PLATFORM",
                "Join tournaments, visit the marketplace, and manage your pro gaming career.",
                "client",
                event -> showWebFrontOffice("client")
        );

        VBox adminCard = createSelectorCard(
                "ADMIN DASHBOARD",
                "Manage sponsors, tournaments, and oversee the entire Lobby.GG ecosystem.",
                "admin",
                event -> {
                    scene.setRoot(createAuthRoot());
                    showMode(LOGIN);
                    loginRoleComboBox.setValue("admin");
                }
        );

        cards.getChildren().addAll(platformCard, adminCard);

        container.getChildren().addAll(mainTitle, subtitle, cards);

        StackPane root = new StackPane(container);
        root.getStyleClass().add("root");
        return root;
    }

    private VBox createSelectorCard(String title, String description, String iconType, javafx.event.EventHandler<javafx.event.ActionEvent> onAction) {
        VBox card = new VBox(20);
        card.setAlignment(Pos.CENTER);
        card.getStyleClass().add("auth-card");
        card.setPrefWidth(400);
        card.setPadding(new Insets(40));
        card.setStyle("-fx-cursor: hand;");

        // Simple icon representation
        Label icon = new Label(iconType.equals("admin") ? "⚙" : "🎮");
        icon.setStyle("-fx-font-size: 48px; -fx-text-fill: #7C3AED;");

        Label titleLabel = new Label(title);
        titleLabel.setStyle("-fx-font-size: 24px; -fx-font-weight: 800; -fx-text-fill: white; -fx-font-family: 'Poppins';");

        Label descLabel = new Label(description);
        descLabel.setStyle("-fx-font-size: 14px; -fx-text-fill: #9CA3AF; -fx-text-alignment: center;");
        descLabel.setWrapText(true);
        descLabel.setPrefHeight(60);

        Button actionBtn = new Button(iconType.equals("admin") ? "Access Dashboard" : "Access Platform");
        actionBtn.getStyleClass().add("primary-button");
        actionBtn.setMaxWidth(Double.MAX_VALUE);
        actionBtn.setOnAction(onAction);

        card.getChildren().addAll(icon, titleLabel, descLabel, actionBtn);

        // Hover effect
        card.setOnMouseEntered(e -> card.setStyle("-fx-border-color: #7C3AED; -fx-border-width: 2px; -fx-border-radius: 30px; -fx-background-radius: 30px; -fx-cursor: hand;"));
        card.setOnMouseExited(e -> card.setStyle("-fx-border-color: transparent; -fx-cursor: hand;"));

        return card;
    }

    private void loadIcons(Stage stage) {
        String[] iconPaths = {
            "/icons/logo-16.png", "/icons/logo-32.png", "/icons/logo-64.png", 
            "/icons/logo-128.png", "/icons/logo-256.png"
        };
        for (String path : iconPaths) {
            var stream = getClass().getResourceAsStream(path);
            if (stream != null) {
                stage.getIcons().add(new Image(stream));
            } else {
                System.err.println("Icone introuvable : " + path);
            }
        }
    }

    private BorderPane createAuthRoot() {
        BorderPane root = new BorderPane();
        root.getStyleClass().add("root");
        root.setLeft(createBrandPanel());
        root.setCenter(createAuthPanel());
        return root;
    }

    private VBox createBrandPanel() {
        ImageView logoView = new ImageView();
        var stream = getClass().getResourceAsStream("/images/logo.png");
        if (stream != null) {
            logoView.setImage(new Image(stream));
        } else {
            System.err.println("Logo introuvable : /images/logo.png");
        }
        logoView.setFitWidth(160);
        logoView.setPreserveRatio(true);

        Label badge = new Label("Desktop App");
        badge.getStyleClass().add("badge");

        Label title = new Label("Bienvenue dans votre espace securise");
        title.getStyleClass().add("brand-title");
        title.setWrapText(true);

        Label subtitle = new Label(
                "Connecte un compte existant de la base lobbyjava et arrive directement sur un espace desktop violet et blanc."
        );
        subtitle.getStyleClass().add("brand-subtitle");
        subtitle.setWrapText(true);

        VBox featureCard = new VBox(14,
                createFeature("Connexion base reelle", "Login et signup branches sur la table user de MySQL."),
                createFeature("Navigation fluide", "Sidebar avec Home, Profile, Sponsoring, Tournoit, Marketplace et Bloging."),
                createFeature("Interface evolutive", "Base JavaFX prete pour les futures pages metier.")
        );
        featureCard.getStyleClass().add("feature-card");

        VBox brandPanel = new VBox(26, logoView, badge, title, subtitle, featureCard);
        brandPanel.getStyleClass().add("brand-panel");
        brandPanel.setAlignment(Pos.CENTER_LEFT);
        return brandPanel;
    }

    private VBox createFeature(String title, String description) {
        Label dot = new Label("•");
        dot.getStyleClass().add("feature-dot");

        Label titleLabel = new Label(title);
        titleLabel.getStyleClass().add("feature-title");

        Label descriptionLabel = new Label(description);
        descriptionLabel.getStyleClass().add("feature-description");
        descriptionLabel.setWrapText(true);

        VBox textBox = new VBox(4, titleLabel, descriptionLabel);
        HBox row = new HBox(12, dot, textBox);
        row.setAlignment(Pos.TOP_LEFT);
        return new VBox(row);
    }

    private StackPane createAuthPanel() {
        Label title = new Label("Authentification");
        title.getStyleClass().add("panel-title");

        Label subtitle = new Label("Connectez-vous avec un compte existant ou creez un nouveau compte.");
        subtitle.getStyleClass().add("panel-subtitle");

        loginTabButton = new Button("Login");
        signupTabButton = new Button("Sign Up");
        loginTabButton.getStyleClass().add("toggle-button");
        signupTabButton.getStyleClass().add("toggle-button");
        loginTabButton.setOnAction(event -> showMode(LOGIN));
        signupTabButton.setOnAction(event -> showMode(SIGNUP));

        HBox switcher = new HBox(12, loginTabButton, signupTabButton);
        switcher.getStyleClass().add("switcher");

        loginForm = createLoginForm();
        signupForm = createSignupForm();

        StackPane formsContainer = new StackPane(loginForm, signupForm);
        formsContainer.getStyleClass().add("forms-container");

        VBox card = new VBox(22, title, subtitle, switcher, formsContainer);
        card.getStyleClass().add("auth-card");
        card.setAlignment(Pos.TOP_LEFT);
        card.setMaxWidth(490);

        VBox wrapper = new VBox(card);
        wrapper.getStyleClass().add("auth-panel");
        wrapper.setAlignment(Pos.CENTER);
        VBox.setVgrow(card, Priority.NEVER);
        return new StackPane(wrapper);
    }

    private VBox createLoginForm() {
        loginEmailField = createTextField("Adresse email");
        loginPasswordField = createPasswordField("Mot de passe");

        loginRoleComboBox = new ComboBox<>();
        loginRoleComboBox.getItems().addAll("client", "admin", "sponsor");
        loginRoleComboBox.setValue("client");
        loginRoleComboBox.getStyleClass().add("input-field");
        loginRoleComboBox.setMaxWidth(Double.MAX_VALUE);

        Label hint = new Label("Utilise un compte deja existant dans la table user.");
        hint.getStyleClass().add("helper-text");
        hint.setWrapText(true);

        loginButton = new Button("Se connecter");
        loginButton.getStyleClass().add("primary-button");
        loginButton.setMaxWidth(Double.MAX_VALUE);
        loginButton.setOnAction(event -> handleLogin());

        // Binding pour desactiver le bouton si vide
        loginButton.disableProperty().bind(
                Bindings.createBooleanBinding(
                        () -> loginEmailField.getText().trim().isEmpty() || loginPasswordField.getText().isEmpty(),
                        loginEmailField.textProperty(),
                        loginPasswordField.textProperty()
                )
        );

        loginMessageLabel = createStatusLabel();

        Label footerText = new Label("Pas encore de compte ?");
        footerText.getStyleClass().add("footer-text");

        Hyperlink goSignup = new Hyperlink("Creer un compte");
        goSignup.getStyleClass().add("inline-link");
        goSignup.setOnAction(event -> showMode(SIGNUP));

        HBox footer = new HBox(6, footerText, goSignup);
        footer.setAlignment(Pos.CENTER_LEFT);

        Hyperlink backToEntry = new Hyperlink("← Back to Selection");
        backToEntry.getStyleClass().add("inline-link");
        backToEntry.setStyle("-fx-text-fill: #9CA3AF;");
        backToEntry.setOnAction(event -> scene.setRoot(createEntrySelector()));

        VBox form = new VBox(14,
                createSectionLabel("Login"),
                loginEmailField,
                loginPasswordField,
                new Label("Accéder en tant que :"),
                loginRoleComboBox,
                hint,
                loginButton,
                loginMessageLabel,
                footer,
                backToEntry
        );
        form.getStyleClass().add("form-box");
        return form;
    }

    private Node createSignupForm() {
        signupNameField = createTextField("Nom complet");
        signupUsernameField = createTextField("Username");
        signupEmailField = createTextField("Adresse email");
        signupTelephoneField = createTextField("Telephone");
        signupDateNaissancePicker = new DatePicker();
        signupDateNaissancePicker.setPromptText("Date de naissance");
        signupDateNaissancePicker.getStyleClass().add("input-field");
        signupRoleComboBox = new ComboBox<>();
        signupRoleComboBox.getItems().addAll("client", "admin", "sponsor");
        signupRoleComboBox.setValue("client");
        signupRoleComboBox.setPromptText("Role");
        signupRoleComboBox.getStyleClass().add("input-field");
        signupBioArea = new TextArea();
        signupBioArea.setPromptText("Bio");
        signupBioArea.getStyleClass().addAll("input-field", "input-area");
        signupBioArea.setWrapText(true);
        signupBioArea.setPrefRowCount(3);
        signupPasswordField = createPasswordField("Mot de passe");
        signupConfirmPasswordField = createPasswordField("Confirmer le mot de passe");

        signupButton = new Button("Creer un compte");
        signupButton.getStyleClass().add("primary-button");
        signupButton.setMaxWidth(Double.MAX_VALUE);
        signupButton.setOnAction(event -> handleSignup());

        // Binding complexe pour desactiver le bouton d'inscription
        signupButton.disableProperty().bind(
                Bindings.createBooleanBinding(
                        () -> signupNameField.getText().trim().isEmpty() ||
                                signupUsernameField.getText().trim().isEmpty() ||
                                signupEmailField.getText().trim().isEmpty() ||
                                signupPasswordField.getText().isEmpty() ||
                                signupConfirmPasswordField.getText().isEmpty() ||
                                signupDateNaissancePicker.getValue() == null ||
                                signupRoleComboBox.getValue() == null,
                        signupNameField.textProperty(),
                        signupUsernameField.textProperty(),
                        signupEmailField.textProperty(),
                        signupPasswordField.textProperty(),
                        signupConfirmPasswordField.textProperty(),
                        signupDateNaissancePicker.valueProperty(),
                        signupRoleComboBox.valueProperty()
                )
        );

        signupMessageLabel = createStatusLabel();

        Label autoFields = new Label("ID et date de creation sont generes automatiquement par l'application.");
        autoFields.getStyleClass().add("helper-text");
        autoFields.setWrapText(true);

        Label terms = new Label("Tous les autres champs persistants de la table user sont presents dans ce formulaire.");
        terms.getStyleClass().add("muted-text");
        terms.setWrapText(true);

        Label footerText = new Label("Vous avez deja un compte ?");
        footerText.getStyleClass().add("footer-text");

        Hyperlink goLogin = new Hyperlink("Se connecter");
        goLogin.getStyleClass().add("inline-link");
        goLogin.setOnAction(event -> showMode(LOGIN));

        HBox footer = new HBox(6, footerText, goLogin);
        footer.setAlignment(Pos.CENTER_LEFT);

        Hyperlink backToEntry = new Hyperlink("← Back to Selection");
        backToEntry.getStyleClass().add("inline-link");
        backToEntry.setStyle("-fx-text-fill: #9CA3AF;");
        backToEntry.setOnAction(event -> scene.setRoot(createEntrySelector()));

        VBox form = new VBox(14,
                createSectionLabel("Sign Up"),
                signupNameField,
                signupUsernameField,
                signupEmailField,
                signupTelephoneField,
                signupDateNaissancePicker,
                signupRoleComboBox,
                signupBioArea,
                signupPasswordField,
                signupConfirmPasswordField,
                autoFields,
                signupButton,
                signupMessageLabel,
                terms,
                footer,
                backToEntry
        );
        form.getStyleClass().add("form-box");

        ScrollPane scrollPane = new ScrollPane(form);
        scrollPane.setFitToWidth(true);
        scrollPane.getStyleClass().add("form-scroll");
        scrollPane.setHbarPolicy(ScrollPane.ScrollBarPolicy.NEVER);
        return scrollPane;
    }

    private Label createSectionLabel(String text) {
        Label label = new Label(text);
        label.getStyleClass().add("section-label");
        return label;
    }

    private TextField createTextField(String prompt) {
        TextField field = new TextField();
        field.setPromptText(prompt);
        field.getStyleClass().add("input-field");
        return field;
    }

    private PasswordField createPasswordField(String prompt) {
        PasswordField field = new PasswordField();
        field.setPromptText(prompt);
        field.getStyleClass().add("input-field");
        return field;
    }

    private Label createStatusLabel() {
        Label label = new Label();
        label.getStyleClass().add("status-label");
        label.setWrapText(true);
        label.setManaged(false);
        label.setVisible(false);
        return label;
    }

    private void handleLogin() {
        clearErrors(loginEmailField, loginPasswordField);
        updateMessage(loginMessageLabel, "", false);

        String email = loginEmailField.getText().trim();
        String password = loginPasswordField.getText();

        if (email.isBlank() || password.isBlank()) {
            markError(loginEmailField, loginPasswordField);
            updateMessage(loginMessageLabel, "Saisis ton email et ton mot de passe.", false);
            return;
        }

        if (!MySqlConnection.testConnection()) {
            updateMessage(loginMessageLabel, "Connexion impossible a MySQL. Verifie le serveur phpMyAdmin.", false);
            return;
        }

        System.out.println("Tentative d'authentification pour : " + email);
        Optional<User> authenticatedUser = userService.authenticate(email, password);
        if (authenticatedUser.isPresent()) {
            currentUser = authenticatedUser.get();
            String chosenRole = loginRoleComboBox.getValue();
            System.out.println("Utilisateur authentifié : " + currentUser.getUsername() + " (Role DB: " + currentUser.getRole() + ", Role choisi: " + chosenRole + ")");
            
            // On vérifie si l'utilisateur a le droit d'utiliser ce rôle (Case insensitive)
            if (!currentUser.getRole().equalsIgnoreCase(chosenRole) && !"admin".equalsIgnoreCase(currentUser.getRole())) {
                System.out.println("Accès refusé : rôle incorrect.");
                updateMessage(loginMessageLabel, "Vous n'avez pas les droits pour accéder en tant que " + chosenRole, false);
                return;
            }

            if ("admin".equalsIgnoreCase(chosenRole)) {
                showAdminDashboard();
            } else {
                showWebFrontOffice(chosenRole);
            }
        } else {
            System.out.println("Authentification échouée : email ou mot de passe incorrect.");
            markError(loginEmailField, loginPasswordField);
            updateMessage(loginMessageLabel, "Email ou mot de passe incorrect.", false);
        }
    }

    private void showAdminDashboard() {
        System.out.println("Lancement du BackOffice Admin (JavaFX)...");
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/fxml/AdminDashboard.fxml"));
            Parent root = loader.load();

            AdminDashboardController controller = loader.getController();
            controller.setAdminName(currentUser.getNom() != null ? currentUser.getNom() : currentUser.getUsername());
            controller.setMainApp(this);

            scene.setRoot(root);
            primaryStage.setTitle("Lobby.GG - Admin Dashboard");
        } catch (Exception e) {
            System.err.println("Erreur de chargement du dashboard : " + e.getMessage());
            e.printStackTrace();
            updateMessage(loginMessageLabel, "Erreur lors du chargement du dashboard admin : " + e.getMessage(), false);
        }
    }

    private void showWebFrontOffice(String role) {
        System.out.println("Lancement du FrontOffice (Web View) pour : " + role);
        try {
            WebView webView = new WebView();
            webView.getEngine().setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
            WebEngine engine = webView.getEngine();
            
            // On pointe vers l'URL du serveur Spring Boot démarré dans Main.java
            String url = "http://localhost:8081/login";
            System.out.println("Tentative de chargement : " + url);
            
            engine.getLoadWorker().stateProperty().addListener((obs, oldState, newState) -> {
                System.out.println("WebView State: " + newState);
                if (newState == javafx.concurrent.Worker.State.SUCCEEDED) {
                    System.out.println("Page chargée avec succès : " + engine.getLocation());
                }
                if (newState == javafx.concurrent.Worker.State.FAILED) {
                    System.err.println("Erreur de chargement WebView : " + engine.getLoadWorker().getException());
                    engine.loadContent("<html><body style='background:#0B0E14; color:white; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; flex-direction:column;'>" +
                            "<h1 style='color:#7C3AED'>Erreur de Connexion</h1>" +
                            "<p>Le serveur Lobby.GG (Port 8081) ne répond pas.</p>" +
                            "<p style='font-size:12px; color:gray;'>Détails : " + engine.getLoadWorker().getException() + "</p>" +
                            "<button onclick='window.location.reload()' style='background:#7C3AED; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;'>Réessayer</button>" +
                            "</body></html>");
                }
            });

            engine.loadContent("<html><body style='background:#0B0E14; color:white; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; flex-direction:column;'>" +
                    "<div style='width:50px; height:50px; border:5px solid #161B22; border-top:5px solid #7C3AED; border-radius:50%; animation:spin 1s linear infinite;'></div>" +
                    "<h2 style='margin-top:20px; font-weight:900; letter-spacing:-0.05em;'>LOBBY.GG</h2>" +
                    "<p style='color:gray; font-size:14px;'>Chargement de votre portail sécurisé...</p>" +
                    "<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>" +
                    "</body></html>");
            
            // On attend un court instant pour laisser Spring Boot finir de s'initialiser si besoin
            javafx.animation.PauseTransition delay = new javafx.animation.PauseTransition(javafx.util.Duration.seconds(1));
            delay.setOnFinished(e -> engine.load(url));
            delay.play();

            VBox webContainer = new VBox(webView);
            VBox.setVgrow(webView, Priority.ALWAYS);
            
            scene.setRoot(webContainer);
            primaryStage.setTitle("Lobby.GG - Portal " + role.toUpperCase());
            primaryStage.setMaximized(true);
        } catch (Exception e) {
            System.err.println("Erreur lors de l'ouverture de la Web View : " + e.getMessage());
            e.printStackTrace();
        }
    }

    private void handleSignup() {
        clearErrors(signupNameField, signupUsernameField, signupEmailField, signupPasswordField, signupConfirmPasswordField);
        updateMessage(signupMessageLabel, "", false);

        String fullName = signupNameField.getText().trim();
        String username = signupUsernameField.getText().trim();
        String email = signupEmailField.getText().trim();
        String telephone = signupTelephoneField.getText().trim();
        LocalDate dateNaissance = signupDateNaissancePicker.getValue();
        String role = signupRoleComboBox.getValue();
        String bio = signupBioArea.getText().trim();
        String password = signupPasswordField.getText();
        String confirmPassword = signupConfirmPasswordField.getText();

        if (fullName.isBlank() || username.isBlank() || email.isBlank() || role == null || role.isBlank()
                || dateNaissance == null || password.isBlank() || confirmPassword.isBlank()) {
            updateMessage(signupMessageLabel, "Remplis tous les champs obligatoires.", false);
            return;
        }

        if (!EMAIL_PATTERN.matcher(email).matches()) {
            markError(signupEmailField);
            updateMessage(signupMessageLabel, "Adresse email invalide.", false);
            return;
        }

        if (!PASSWORD_PATTERN.matcher(password).matches()) {
            markError(signupPasswordField);
            updateMessage(signupMessageLabel, "Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.", false);
            return;
        }

        if (!password.equals(confirmPassword)) {
            markError(signupPasswordField, signupConfirmPasswordField);
            updateMessage(signupMessageLabel, "Les mots de passe ne correspondent pas.", false);
            return;
        }

        if (!MySqlConnection.testConnection()) {
            updateMessage(signupMessageLabel, "Connexion impossible a MySQL. Verifie le serveur phpMyAdmin.", false);
            return;
        }

        if (userService.emailExists(email)) {
            markError(signupEmailField);
            updateMessage(signupMessageLabel, "Cet email existe deja dans la base.", false);
            return;
        }

        if (userService.usernameExists(username)) {
            markError(signupUsernameField);
            updateMessage(signupMessageLabel, "Ce username existe deja dans la base.", false);
            return;
        }

        User newUser = userService.buildNewUser(
                fullName,
                username,
                email,
                password,
                role,
                telephone.isBlank() ? null : telephone,
                dateNaissance,
                bio.isBlank() ? null : bio
        );
        if (userService.addUser(newUser)) {
            currentUser = userService.findByEmail(email).orElse(newUser);
            String chosenRole = currentUser.getRole();
            
            if ("admin".equalsIgnoreCase(chosenRole)) {
                showAdminDashboard();
            } else {
                showWebFrontOffice(chosenRole);
            }
        } else {
            updateMessage(signupMessageLabel, "Creation du compte impossible. Verifie la structure SQL de la table user.", false);
        }
    }

    private void markError(Node... nodes) {
        for (Node node : nodes) {
            node.getStyleClass().add("input-error");
        }
    }

    private void clearErrors(Node... nodes) {
        for (Node node : nodes) {
            node.getStyleClass().remove("input-error");
        }
    }

    private void updateMessage(Label label, String message, boolean success) {
        label.setText(message);
        label.setVisible(!message.isBlank());
        label.setManaged(!message.isBlank());
        label.getStyleClass().removeAll("error-label", "success-label");
        label.getStyleClass().add(success ? "success-label" : "error-label");
    }

    private void showDashboard(String initialSection) {
        dashboardRoot = new BorderPane();
        dashboardRoot.getStyleClass().add("dashboard-root");
        dashboardRoot.setLeft(createSidebar());
        dashboardRoot.setCenter(createDashboardContent());

        scene.setRoot(dashboardRoot);
        primaryStage.setTitle("Violet Home - " + currentUser.getDisplayName());
        showSection(initialSection);
    }

    private VBox createSidebar() {
        navigationButtons.clear();

        ImageView logoView = new ImageView();
        var stream = getClass().getResourceAsStream("/images/logo.png");
        if (stream != null) {
            logoView.setImage(new Image(stream));
        } else {
            System.err.println("Logo introuvable : /images/logo.png");
        }
        logoView.setFitWidth(120);
        logoView.setPreserveRatio(true);

        Label appLabel = new Label("Lobby.GG");
        appLabel.getStyleClass().add("sidebar-app-title");

        Label appSubtitle = new Label("Desktop navigation");
        appSubtitle.getStyleClass().add("sidebar-app-subtitle");

        VBox header = new VBox(10, logoView, appLabel, appSubtitle);
        header.getStyleClass().add("sidebar-header");

        Label nameLabel = new Label(currentUser.getDisplayName());
        nameLabel.getStyleClass().add("user-card-title");

        Label emailLabel = new Label(currentUser.getEmail());
        emailLabel.getStyleClass().add("user-card-subtitle");

        Label roleLabel = new Label("Role : " + currentUser.getRole());
        roleLabel.getStyleClass().add("user-card-role");

        VBox userCard = new VBox(6, nameLabel, emailLabel, roleLabel);
        userCard.getStyleClass().add("user-card");

        VBox navigation = new VBox(10,
                createNavButton("Home"),
                createNavButton("Profile"),
                createNavButton("Sponsoring"),
                createNavButton("Tournoit"),
                createNavButton("Marketplace"),
                createNavButton("Bloging")
        );
        navigation.getStyleClass().add("sidebar-nav");

        Button logoutButton = new Button("Logout");
        logoutButton.getStyleClass().add("logout-button");
        logoutButton.setMaxWidth(Double.MAX_VALUE);
        logoutButton.setOnAction(event -> logout());

        VBox sidebar = new VBox(22, header, userCard, navigation, logoutButton);
        sidebar.getStyleClass().add("sidebar");
        VBox.setVgrow(navigation, Priority.ALWAYS);
        return sidebar;
    }

    private Button createNavButton(String section) {
        Button button = new Button(section);
        button.getStyleClass().add("nav-button");
        button.setMaxWidth(Double.MAX_VALUE);
        button.setOnAction(event -> showSection(section));
        navigationButtons.put(section, button);
        return button;
    }

    private ScrollPane createDashboardContent() {
        pageBadgeLabel = new Label();
        pageBadgeLabel.getStyleClass().add("page-badge");

        pageTitleLabel = new Label();
        pageTitleLabel.getStyleClass().add("page-title");
        pageTitleLabel.setWrapText(true);

        pageDescriptionLabel = new Label();
        pageDescriptionLabel.getStyleClass().add("page-description");
        pageDescriptionLabel.setWrapText(true);

        sectionCards = new VBox(18);

        VBox content = new VBox(18, pageBadgeLabel, pageTitleLabel, pageDescriptionLabel, sectionCards);
        content.getStyleClass().add("content-panel");
        content.setPadding(new Insets(38));

        ScrollPane scrollPane = new ScrollPane(content);
        scrollPane.setFitToWidth(true);
        scrollPane.getStyleClass().add("content-scroll");
        return scrollPane;
    }

    private void showSection(String section) {
        selectNavigation(section);
        pageBadgeLabel.setText(section);
        pageTitleLabel.setText(getSectionTitle(section));
        pageDescriptionLabel.setText(getSectionDescription(section));
        sectionCards.getChildren().setAll(buildSectionContent(section));
    }

    private void selectNavigation(String section) {
        if (activeNavButton != null) {
            activeNavButton.getStyleClass().remove("active-nav");
        }

        Button button = navigationButtons.get(section);
        if (button != null) {
            button.getStyleClass().add("active-nav");
            activeNavButton = button;
        }
    }

    private Node[] buildSectionContent(String section) {
        return switch (section) {
            case "Profile" -> new Node[]{
                    createHeroCard("Gestion du profil", "Modifiez vos informations personnelles ici."),
                    createProfileForm()
            };
            case "Marketplace" -> new Node[]{
                    createHeroCard("Achat de Gold", "Rechargez votre compte Gold en toute sécurité."),
                    createGoldRechargeView()
            };
            case "GoldSpace" -> new Node[]{
                    createHeroCard("Mon Espace Gold", "Gérez votre solde et vos transactions récentes."),
                    createGoldSpaceView()
            };
            case "Sponsoring" -> new Node[]{
                    createHeroCard("Espace Sponsoring",
                            "Cette section est prete pour afficher les sponsors, contrats et actions marketing lies a ton projet."),
                    createMetricsRow(
                            createMetricCard("Etat", "Pret", "Navigation JavaFX operationnelle"),
                            createMetricCard("Base", "lobbyjava", "Connexion JDBC reutilisable"),
                            createMetricCard("Suite", "CRUD sponsoring", "A brancher ensuite")
                    )
            };
            case "Tournoit" -> new Node[]{
                    createHeroCard("Gestion Tournoit",
                            "Tu arrives ici depuis la sidebar. La page pourra accueillir tes tournois, inscriptions et calendriers."),
                    createMetricsRow(
                            createMetricCard("Navigation", "Active", "Bouton Tournoit connecte"),
                            createMetricCard("Module", "A completer", "Listing des tournois"),
                            createMetricCard("Base", "Compatible", "Table tournament disponible")
                    )
            };
            case "Bloging" -> new Node[]{
                    createHeroCard("Bloging",
                            "Ici tu pourras brancher les posts, commentaires et reactions du projet web dans une interface desktop."),
                    createMetricsRow(
                            createMetricCard("Posts", "Disponibles", "Table post detectee"),
                            createMetricCard("Commentaires", "Disponibles", "Tables comment et comment_reaction"),
                            createMetricCard("Action", "Prochaine etape", "Afficher une vraie liste")
                    )
            };
            default -> new Node[]{
                    createHeroCard("Bienvenue " + currentUser.getDisplayName(),
                            "Connexion reussie. Tu arrives directement sur Home, puis tu peux naviguer depuis la sidebar."),
                    createMetricsRow(
                            createMetricCard("Home", "Actif", "Page affichee apres login"),
                            createMetricCard("Profil", safeValue(currentUser.getUsername()), "Compte charge depuis MySQL"),
                            createMetricCard("Base", "lobbyjava", "Connexion JDBC validee dans l'application")
                    ),
                    createMetricsRow(
                            createMetricCard("Sponsoring", "Pret", "Bouton et espace disponibles"),
                            createMetricCard("Tournoit", "Pret", "Navigation vers la page disponible"),
                            createMetricCard("Marketplace", "Pret", "Navigation vers la page disponible")
                    ),
                    createMetricsRow(
                            createMetricCard("Bloging", "Pret", "Section accessible"),
                            createMetricCard("Role", safeValue(currentUser.getRole()), "Role courant en base"),
                            createMetricCard("Email", safeValue(currentUser.getEmail()), "Compte connecte")
                    )
            };
        };
    }

    private VBox createProfileForm() {
        Optional<Gold> goldOpt = goldService.getGoldByUserId(currentUser.getId());
        double solde = goldOpt.isPresent() ? goldOpt.get().getSolde() : 0.0;

        // Photo de profil
        ImageView profileImageView = new ImageView();
        profileImageView.setFitWidth(100);
        profileImageView.setFitHeight(100);
        profileImageView.setPreserveRatio(true);
        if (currentUser.getPhotoUrl() != null && !currentUser.getPhotoUrl().isEmpty()) {
            try {
                profileImageView.setImage(new Image(new File(currentUser.getPhotoUrl()).toURI().toString()));
            } catch (Exception e) {
                // Image par défaut si erreur
            }
        }

        Button changePhotoBtn = new Button("Changer la photo");
        changePhotoBtn.getStyleClass().add("secondary-button");
        changePhotoBtn.setOnAction(e -> {
            FileChooser fileChooser = new FileChooser();
            fileChooser.getExtensionFilters().addAll(
                new FileChooser.ExtensionFilter("Images", "*.png", "*.jpg", "*.jpeg")
            );
            File selectedFile = fileChooser.showOpenDialog(primaryStage);
            if (selectedFile != null) {
                currentUser.setPhotoUrl(selectedFile.getAbsolutePath());
                profileImageView.setImage(new Image(selectedFile.toURI().toString()));
            }
        });

        VBox photoBox = new VBox(10, profileImageView, changePhotoBtn);
        photoBox.setAlignment(Pos.CENTER);

        // Bouton Gold (Orange)
        Button goldSpaceBtn = new Button("Mon Espace Gold (" + solde + " G)");
        goldSpaceBtn.setStyle("-fx-background-color: #f39c12; -fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 14px; -fx-padding: 10 20; -fx-background-radius: 10;");
        goldSpaceBtn.setMaxWidth(Double.MAX_VALUE);
        goldSpaceBtn.setOnAction(e -> showSection("GoldSpace"));

        TextField nomField = createTextField("Nom complet");
        nomField.setText(currentUser.getNom());
        TextField usernameField = createTextField("Username");
        usernameField.setText(currentUser.getUsername());
        TextField emailField = createTextField("Email");
        emailField.setText(currentUser.getEmail());

        TextField telephoneField = createTextField("Téléphone (8 chiffres)");
        telephoneField.setText(currentUser.getTelephone());

        DatePicker dateNaisPicker = new DatePicker();
        dateNaisPicker.setPromptText("Date de naissance");
        dateNaisPicker.getStyleClass().add("input-field");
        dateNaisPicker.setMaxWidth(Double.MAX_VALUE);
        dateNaisPicker.setValue(currentUser.getDateNaissance());

        PasswordField passField = createPasswordField("Nouveau mot de passe (optionnel)");
        PasswordField confirmPassField = createPasswordField("Confirmer le mot de passe");

        Button updateBtn = new Button("Mettre à jour le profil");
        updateBtn.getStyleClass().add("primary-button");
        updateBtn.setMaxWidth(Double.MAX_VALUE);
        updateBtn.setOnAction(e -> {
            String tel = telephoneField.getText().trim();
            LocalDate birth = dateNaisPicker.getValue();
            String newPass = passField.getText();
            String confirmPass = confirmPassField.getText();

            // Validation Téléphone (8 chiffres)
            if (!tel.matches("\\d{8}")) {
                new Alert(Alert.AlertType.ERROR, "Le téléphone doit contenir exactement 8 chiffres.").show();
                return;
            }

            // Validation Date de naissance (> 18 ans)
            if (birth == null || Period.between(birth, LocalDate.now()).getYears() < 18) {
                new Alert(Alert.AlertType.ERROR, "Vous devez avoir plus de 18 ans.").show();
                return;
            }

            // Validation Mot de passe
            if (!newPass.isEmpty()) {
                if (!PASSWORD_PATTERN.matcher(newPass).matches()) {
                    new Alert(Alert.AlertType.ERROR, "Le mot de passe doit comporter au moins 8 caractères, une majuscule et un chiffre.").show();
                    return;
                }
                if (!newPass.equals(confirmPass)) {
                    new Alert(Alert.AlertType.ERROR, "Les mots de passe ne correspondent pas.").show();
                    return;
                }
                currentUser.setPasswordHash(PasswordUtils.hashPassword(newPass));
            }

            currentUser.setNom(nomField.getText());
            currentUser.setUsername(usernameField.getText());
            currentUser.setEmail(emailField.getText());
            currentUser.setTelephone(tel);
            currentUser.setDateNaissance(birth);

            if (userService.updateProfil(currentUser)) {
                new Alert(Alert.AlertType.INFORMATION, "Profil mis à jour avec succès !").show();
            }
        });

        Button deleteBtn = new Button("Supprimer mon compte");
        deleteBtn.setStyle("-fx-background-color: #e74c3c; -fx-text-fill: white; -fx-font-weight: bold;");
        deleteBtn.getStyleClass().add("primary-button");
        deleteBtn.setMaxWidth(Double.MAX_VALUE);
        deleteBtn.setOnAction(e -> {
            Alert alert = new Alert(Alert.AlertType.CONFIRMATION, "Supprimer votre compte définitivement ?", ButtonType.YES, ButtonType.NO);
            alert.showAndWait().ifPresent(res -> {
                if (res == ButtonType.YES) {
                    if (userService.deleteUser(currentUser.getId())) {
                        logout();
                    }
                }
            });
        });

        VBox form = new VBox(15, photoBox, goldSpaceBtn, nomField, usernameField, emailField, telephoneField, dateNaisPicker, passField, confirmPassField, updateBtn, deleteBtn);
        form.setPadding(new Insets(20));
        form.setStyle("-fx-background-color: white; -fx-background-radius: 15;");
        
        ScrollPane scrollPane = new ScrollPane(form);
        scrollPane.setFitToWidth(true);
        scrollPane.setStyle("-fx-background-color: transparent; -fx-background: transparent;");
        
        VBox container = new VBox(scrollPane);
        container.setMaxHeight(500);
        return container;
    }

    private VBox createGoldRechargeView() {
        TextField amountField = createTextField("Montant à recharger");
        TextField cardField = createTextField("Numéro de carte (Factice)");

        Button rechargeBtn = new Button("Recharger maintenant");
        rechargeBtn.getStyleClass().add("primary-button");
        rechargeBtn.setOnAction(e -> {
            try {
                double amount = Double.parseDouble(amountField.getText());
                if (goldService.addGold(currentUser.getId(), amount)) {
                    new Alert(Alert.AlertType.INFORMATION, "Recharge réussie !").show();
                    showSection("Profile"); // Redirect to profile to see the new balance
                }
            } catch (NumberFormatException ex) {
                new Alert(Alert.AlertType.ERROR, "Montant invalide").show();
            }
        });

        VBox box = new VBox(20, amountField, cardField, rechargeBtn);
        box.setPadding(new Insets(20));
        box.setStyle("-fx-background-color: white; -fx-background-radius: 15;");
        return box;
    }

    private VBox createGoldSpaceView() {
        Optional<Gold> goldOpt = goldService.getGoldByUserId(currentUser.getId());
        if (goldOpt.isEmpty()) {
            boolean created = goldService.createGoldAccount(currentUser.getId());
            if (created) {
                goldOpt = goldService.getGoldByUserId(currentUser.getId());
            }
        }

        if (goldOpt.isEmpty()) {
            Label errorLabel = new Label("Impossible d'accéder à votre compte Gold.\n" +
                    "Assurez-vous que la table 'gold' existe dans la base de données.");
            errorLabel.setStyle("-fx-text-fill: red; -fx-font-weight: bold; -fx-text-alignment: center;");
            VBox errorBox = new VBox(20, errorLabel);
            errorBox.setAlignment(Pos.CENTER);
            errorBox.setPadding(new Insets(50));
            return errorBox;
        }

        final Gold gold = goldOpt.get();
        Label soldeLabel = new Label("Solde actuel : " + gold.getSolde() + " Gold");
        soldeLabel.setStyle("-fx-font-size: 24px; -fx-font-weight: bold; -fx-text-fill: #f39c12;");

        // Formulaire de recharge
        TextField amountField = createTextField("Montant (ex: 50)");
        TextField cardField = createTextField("Numéro de carte (16 chiffres)");
        TextField expiryField = createTextField("MM/AA");
        TextField cvcField = createTextField("CVC");

        Button rechargeBtn = new Button("Valider la recharge");
        rechargeBtn.getStyleClass().add("primary-button");
        rechargeBtn.setMaxWidth(Double.MAX_VALUE);
        rechargeBtn.setOnAction(e -> {
            try {
                double amount = Double.parseDouble(amountField.getText());
                if (amount <= 0) throw new NumberFormatException();
                
                if (goldService.addGold(currentUser.getId(), amount)) {
                    new Alert(Alert.AlertType.INFORMATION, "Recharge de " + amount + " Gold réussie !").show();
                    showSection("GoldSpace"); // Refresh
                }
            } catch (NumberFormatException ex) {
                new Alert(Alert.AlertType.ERROR, "Veuillez entrer un montant valide.").show();
            }
        });

        VBox rechargeBox = new VBox(10, new Label("Recharger mon compte"), amountField, cardField, new HBox(10, expiryField, cvcField), rechargeBtn);
        rechargeBox.setPadding(new Insets(15));
        rechargeBox.setStyle("-fx-background-color: #f8f9fa; -fx-background-radius: 10; -fx-border-color: #dee2e6; -fx-border-radius: 10;");

        // Historique des transactions
        Label histLabel = new Label("Historique récent");
        histLabel.setStyle("-fx-font-size: 18px; -fx-font-weight: bold;");

        VBox historyContainer = new VBox(10);
        java.util.List<GoldOperation> ops = goldOpService.getOperationsByGoldId(gold.getId());
        for (GoldOperation op : ops) {
            HBox opRow = new HBox(15);
            opRow.setAlignment(Pos.CENTER_LEFT);
            opRow.setPadding(new Insets(10));
            opRow.setStyle("-fx-background-color: white; -fx-border-color: #eee; -fx-border-radius: 5;");

            Label typeLabel = new Label(op.getType());
            typeLabel.setPrefWidth(100);
            Label amountLabel = new Label(op.getMontant() + " G");
            amountLabel.setPrefWidth(80);
            Label statusLabel = new Label(op.getStatus());
            statusLabel.setStyle(op.getStatus().equals("ANNULE") ? "-fx-text-fill: red;" : "-fx-text-fill: green;");
            
            Region spacer = new Region();
            HBox.setHgrow(spacer, Priority.ALWAYS);

            Button cancelBtn = new Button("Annuler");
            cancelBtn.getStyleClass().add("secondary-button");
            cancelBtn.setDisable(op.getStatus().equals("ANNULE"));
            cancelBtn.setOnAction(e -> {
                if (goldOpService.cancelOperation(op.getId())) {
                    // Si on annule, on doit retirer le solde (simulation simple)
                    goldService.updateSolde(currentUser.getId(), gold.getSolde() - op.getMontant());
                    showSection("GoldSpace");
                }
            });

            opRow.getChildren().addAll(typeLabel, amountLabel, statusLabel, spacer, cancelBtn);
            historyContainer.getChildren().add(opRow);
        }

        ScrollPane scrollHistory = new ScrollPane(historyContainer);
        scrollHistory.setFitToWidth(true);
        scrollHistory.setPrefHeight(200);
        scrollHistory.setStyle("-fx-background-color: transparent; -fx-background: transparent;");

        VBox mainBox = new VBox(20, soldeLabel, rechargeBox, histLabel, scrollHistory);
        mainBox.setPadding(new Insets(20));
        return mainBox;
    }

    private VBox createHeroCard(String title, String description) {
        Label titleLabel = new Label(title);
        titleLabel.getStyleClass().add("hero-title");
        titleLabel.setWrapText(true);

        Label descriptionLabel = new Label(description);
        descriptionLabel.getStyleClass().add("hero-description");
        descriptionLabel.setWrapText(true);

        VBox hero = new VBox(10, titleLabel, descriptionLabel);
        hero.getStyleClass().add("hero-card");
        return hero;
    }

    private HBox createMetricsRow(VBox... cards) {
        HBox row = new HBox(16, cards);
        row.getStyleClass().add("metrics-row");
        for (VBox card : cards) {
            HBox.setHgrow(card, Priority.ALWAYS);
            card.setMaxWidth(Double.MAX_VALUE);
        }
        return row;
    }

    private VBox createMetricCard(String title, String value, String hint) {
        Label titleLabel = new Label(title);
        titleLabel.getStyleClass().add("metric-title");

        Label valueLabel = new Label(value);
        valueLabel.getStyleClass().add("metric-value");
        valueLabel.setWrapText(true);

        Label hintLabel = new Label(hint);
        hintLabel.getStyleClass().add("metric-hint");
        hintLabel.setWrapText(true);

        VBox card = new VBox(10, titleLabel, valueLabel, hintLabel);
        card.getStyleClass().add("metric-card");
        return card;
    }

    private String getSectionTitle(String section) {
        return switch (section) {
            case "Profile" -> "Gestion du profil";
            case "GoldSpace" -> "Mon Espace Gold";
            case "Sponsoring" -> "Zone sponsoring";
            case "Tournoit" -> "Espace tournoit";
            case "Marketplace" -> "Marketplace desktop";
            case "Bloging" -> "Bloging et contenu";
            default -> "Page Home";
        };
    }

    private String getSectionDescription(String section) {
        return switch (section) {
            case "Profile" -> "Visualise le compte connecte et les donnees chargees depuis MySQL.";
            case "GoldSpace" -> "Gérez votre solde, vos recharges et l'historique de vos transactions.";
            case "Sponsoring" -> "Point d'entree pour les futurs modules sponsors et contrats.";
            case "Tournoit" -> "Espace reserve a la gestion des tournois et participations.";
            case "Marketplace" -> "Base graphique pour connecter produits, stock et commandes.";
            case "Bloging" -> "Section dediee aux publications, commentaires et reactions.";
            default -> "Accueil principal apres authentification reussie.";
        };
    }

    private String safeValue(String value) {
        return value == null || value.isBlank() ? "Non renseigne" : value;
    }

    public void logout() {
        currentUser = null;
        navigationButtons.clear();
        activeNavButton = null;
        scene.setRoot(createAuthRoot());
        primaryStage.setTitle("Violet Auth Desktop");
        showMode(LOGIN);
        loginEmailField.clear();
        loginPasswordField.clear();
        signupNameField.clear();
        signupUsernameField.clear();
        signupEmailField.clear();
        signupTelephoneField.clear();
        signupDateNaissancePicker.setValue(null);
        signupRoleComboBox.setValue("client");
        signupBioArea.clear();
        signupPasswordField.clear();
        signupConfirmPasswordField.clear();
    }

    private void showMode(String mode) {
        boolean isLogin = LOGIN.equals(mode);

        loginForm.setVisible(isLogin);
        loginForm.setManaged(isLogin);
        signupForm.setVisible(!isLogin);
        signupForm.setManaged(!isLogin);

        loginTabButton.getStyleClass().remove("active");
        signupTabButton.getStyleClass().remove("active");

        if (isLogin) {
            loginTabButton.getStyleClass().add("active");
        } else {
            signupTabButton.getStyleClass().add("active");
        }
    }
}
