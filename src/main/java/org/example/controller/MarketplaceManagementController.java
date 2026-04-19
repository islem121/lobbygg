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
import org.example.model.Category;
import org.example.model.OrderRecord;
import org.example.model.Product;
import org.example.service.BackofficeService;

import java.time.LocalDateTime;
import java.util.List;

public class MarketplaceManagementController {
    @FXML private TableView<Category> categoryTable;
    @FXML private TableColumn<Category, Integer> categoryIdCol;
    @FXML private TableColumn<Category, String> categoryNameCol;
    @FXML private TableColumn<Category, String> categoryDescriptionCol;
    @FXML private TableColumn<Category, Void> categoryActionsCol;

    @FXML private TableView<Product> productTable;
    @FXML private TableColumn<Product, Integer> productIdCol;
    @FXML private TableColumn<Product, String> productNameCol;
    @FXML private TableColumn<Product, Double> productPriceCol;
    @FXML private TableColumn<Product, Integer> productStockCol;
    @FXML private TableColumn<Product, Integer> productCategoryCol;
    @FXML private TableColumn<Product, Integer> productSellerCol;
    @FXML private TableColumn<Product, Void> productActionsCol;

    @FXML private TableView<OrderRecord> orderTable;
    @FXML private TableColumn<OrderRecord, Integer> orderIdCol;
    @FXML private TableColumn<OrderRecord, Integer> orderProductCol;
    @FXML private TableColumn<OrderRecord, Integer> orderUserCol;
    @FXML private TableColumn<OrderRecord, Integer> orderQuantityCol;
    @FXML private TableColumn<OrderRecord, String> orderStatusCol;
    @FXML private TableColumn<OrderRecord, LocalDateTime> orderDateCol;
    @FXML private TableColumn<OrderRecord, Void> orderActionsCol;

    private final BackofficeService backofficeService = new BackofficeService();

    @FXML
    public void initialize() {
        categoryIdCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        categoryNameCol.setCellValueFactory(new PropertyValueFactory<>("name"));
        categoryDescriptionCol.setCellValueFactory(new PropertyValueFactory<>("description"));
        productIdCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        productNameCol.setCellValueFactory(new PropertyValueFactory<>("name"));
        productPriceCol.setCellValueFactory(new PropertyValueFactory<>("price"));
        productStockCol.setCellValueFactory(new PropertyValueFactory<>("stock"));
        productCategoryCol.setCellValueFactory(new PropertyValueFactory<>("categoryId"));
        productSellerCol.setCellValueFactory(new PropertyValueFactory<>("sellerId"));
        orderIdCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        orderProductCol.setCellValueFactory(new PropertyValueFactory<>("productId"));
        orderUserCol.setCellValueFactory(new PropertyValueFactory<>("userId"));
        orderQuantityCol.setCellValueFactory(new PropertyValueFactory<>("quantity"));
        orderStatusCol.setCellValueFactory(new PropertyValueFactory<>("status"));
        orderDateCol.setCellValueFactory(new PropertyValueFactory<>("orderDate"));
        setupCategoryActions();
        setupProductActions();
        setupOrderActions();
        loadAll();
    }

    @FXML
    private void loadAll() {
        loadCategories();
        loadProducts();
        loadOrders();
    }

    @FXML
    private void handleAddCategory() {
        showCategoryDialog(null);
    }

    @FXML
    private void handleAddProduct() {
        showProductDialog(null);
    }

    @FXML
    private void handleAddOrder() {
        showOrderDialog(null);
    }

    private void loadCategories() {
        List<Category> categories = backofficeService.getAllCategories();
        categoryTable.setItems(FXCollections.observableArrayList(categories));
    }

    private void loadProducts() {
        List<Product> products = backofficeService.getAllProducts();
        productTable.setItems(FXCollections.observableArrayList(products));
    }

    private void loadOrders() {
        List<OrderRecord> orders = backofficeService.getAllOrders();
        orderTable.setItems(FXCollections.observableArrayList(orders));
    }

    private void showCategoryDialog(Category existingCategory) {
        boolean isEdit = existingCategory != null;
        Dialog<Category> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Catégorie" : "Ajouter Catégorie");
        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        TextField nameField = new TextField();
        TextArea descriptionArea = new TextArea();
        if (isEdit) {
            nameField.setText(existingCategory.getName());
            descriptionArea.setText(existingCategory.getDescription());
        }
        grid.addRow(0, new Label("Nom"), nameField);
        grid.addRow(1, new Label("Description"), descriptionArea);
        dialog.getDialogPane().setContent(grid);
        dialog.setResultConverter(button -> {
            if (button != saveButtonType) {
                return null;
            }
            Category category = isEdit ? existingCategory : new Category();
            category.setName(nameField.getText().trim());
            category.setDescription(descriptionArea.getText().trim());
            return category;
        });
        dialog.showAndWait().ifPresent(category -> {
            if (category.getName().isBlank()) {
                showError("Le nom de la catégorie est obligatoire.");
                return;
            }
            boolean success = isEdit ? backofficeService.updateCategory(category) : backofficeService.addCategory(category);
            if (success) {
                loadCategories();
            }
        });
    }

    private void showProductDialog(Product existingProduct) {
        boolean isEdit = existingProduct != null;
        Dialog<Product> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Produit" : "Ajouter Produit");
        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        TextField nameField = new TextField();
        TextField priceField = new TextField();
        TextArea descriptionArea = new TextArea();
        TextField imageField = new TextField();
        TextField stockField = new TextField();
        TextField categoryIdField = new TextField();
        TextField sellerIdField = new TextField();
        if (isEdit) {
            nameField.setText(existingProduct.getName());
            priceField.setText(String.valueOf(existingProduct.getPrice()));
            descriptionArea.setText(existingProduct.getDescription());
            imageField.setText(existingProduct.getImage());
            stockField.setText(String.valueOf(existingProduct.getStock()));
            categoryIdField.setText(String.valueOf(existingProduct.getCategoryId()));
            sellerIdField.setText(String.valueOf(existingProduct.getSellerId()));
        }
        grid.addRow(0, new Label("Nom"), nameField);
        grid.addRow(1, new Label("Prix"), priceField);
        grid.addRow(2, new Label("Description"), descriptionArea);
        grid.addRow(3, new Label("Image"), imageField);
        grid.addRow(4, new Label("Stock"), stockField);
        grid.addRow(5, new Label("ID Catégorie"), categoryIdField);
        grid.addRow(6, new Label("ID Vendeur"), sellerIdField);
        dialog.getDialogPane().setContent(grid);
        dialog.setResultConverter(button -> {
            if (button != saveButtonType) {
                return null;
            }
            Product product = isEdit ? existingProduct : new Product();
            product.setName(nameField.getText().trim());
            product.setPrice(parseDouble(priceField.getText()));
            product.setDescription(descriptionArea.getText().trim());
            product.setImage(imageField.getText().trim());
            product.setStock(parseInt(stockField.getText()));
            product.setCategoryId(parseInt(categoryIdField.getText()));
            product.setSellerId(parseInt(sellerIdField.getText()));
            if (!isEdit) {
                product.setCreatedAt(LocalDateTime.now());
            }
            return product;
        });
        dialog.showAndWait().ifPresent(product -> {
            if (product.getName().isBlank() || product.getCategoryId() <= 0 || product.getSellerId() <= 0) {
                showError("Veuillez remplir correctement les champs produit.");
                return;
            }
            boolean success = isEdit ? backofficeService.updateProduct(product) : backofficeService.addProduct(product);
            if (success) {
                loadProducts();
            }
        });
    }

    private void showOrderDialog(OrderRecord existingOrder) {
        boolean isEdit = existingOrder != null;
        Dialog<OrderRecord> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Commande" : "Ajouter Commande");
        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        TextField productIdField = new TextField();
        TextField userIdField = new TextField();
        TextField quantityField = new TextField();
        TextField statusField = new TextField();
        if (isEdit) {
            productIdField.setText(String.valueOf(existingOrder.getProductId()));
            userIdField.setText(String.valueOf(existingOrder.getUserId()));
            quantityField.setText(String.valueOf(existingOrder.getQuantity()));
            statusField.setText(existingOrder.getStatus());
        }
        grid.addRow(0, new Label("ID Produit"), productIdField);
        grid.addRow(1, new Label("ID Utilisateur"), userIdField);
        grid.addRow(2, new Label("Quantité"), quantityField);
        grid.addRow(3, new Label("Statut"), statusField);
        dialog.getDialogPane().setContent(grid);
        dialog.setResultConverter(button -> {
            if (button != saveButtonType) {
                return null;
            }
            OrderRecord order = isEdit ? existingOrder : new OrderRecord();
            order.setProductId(parseInt(productIdField.getText()));
            order.setUserId(parseInt(userIdField.getText()));
            order.setQuantity(parseInt(quantityField.getText()));
            order.setStatus(statusField.getText().trim());
            if (!isEdit) {
                order.setOrderDate(LocalDateTime.now());
            }
            return order;
        });
        dialog.showAndWait().ifPresent(order -> {
            if (order.getProductId() == null || order.getProductId() <= 0 || order.getUserId() == null || order.getUserId() <= 0) {
                showError("Veuillez remplir correctement les champs commande.");
                return;
            }
            boolean success = isEdit ? backofficeService.updateOrder(order) : backofficeService.addOrder(order);
            if (success) {
                loadOrders();
            }
        });
    }

    private void setupCategoryActions() {
        categoryActionsCol.setCellFactory(param -> new ActionCell<>(
                item -> showCategoryDialog(item),
                item -> backofficeService.deleteCategory(item.getId()),
                this::loadCategories
        ));
    }

    private void setupProductActions() {
        productActionsCol.setCellFactory(param -> new ActionCell<>(
                item -> showProductDialog(item),
                item -> backofficeService.deleteProduct(item.getId()),
                this::loadProducts
        ));
    }

    private void setupOrderActions() {
        orderActionsCol.setCellFactory(param -> new ActionCell<>(
                item -> showOrderDialog(item),
                item -> backofficeService.deleteOrder(item.getId()),
                this::loadOrders
        ));
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

    private static class ActionCell<T> extends TableCell<T, Void> {
        private final Button editBtn = new Button("Modifier");
        private final Button deleteBtn = new Button("Supprimer");
        private final HBox box = new HBox(8, editBtn, deleteBtn);

        private final java.util.function.Consumer<T> editAction;
        private final java.util.function.Predicate<T> deleteAction;
        private final Runnable reloadAction;

        private ActionCell(java.util.function.Consumer<T> editAction,
                           java.util.function.Predicate<T> deleteAction,
                           Runnable reloadAction) {
            this.editAction = editAction;
            this.deleteAction = deleteAction;
            this.reloadAction = reloadAction;
            editBtn.setOnAction(event -> editAction.accept(getTableView().getItems().get(getIndex())));
            deleteBtn.setOnAction(event -> {
                T item = getTableView().getItems().get(getIndex());
                if (deleteAction.test(item)) {
                    reloadAction.run();
                }
            });
        }

        @Override
        protected void updateItem(Void item, boolean empty) {
            super.updateItem(item, empty);
            setGraphic(empty ? null : box);
        }
    }
}
