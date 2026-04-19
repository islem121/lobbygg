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
import org.example.model.CommentRecord;
import org.example.model.PostRecord;
import org.example.service.BackofficeService;

import java.time.LocalDateTime;
import java.util.List;

public class BlogManagementController {
    @FXML private TableView<PostRecord> postTable;
    @FXML private TableColumn<PostRecord, Integer> postIdCol;
    @FXML private TableColumn<PostRecord, String> postTitleCol;
    @FXML private TableColumn<PostRecord, String> postTypeCol;
    @FXML private TableColumn<PostRecord, Integer> postUserCol;
    @FXML private TableColumn<PostRecord, Integer> postUpVotesCol;
    @FXML private TableColumn<PostRecord, Integer> postDownVotesCol;
    @FXML private TableColumn<PostRecord, Void> postActionsCol;

    @FXML private TableView<CommentRecord> commentTable;
    @FXML private TableColumn<CommentRecord, Integer> commentIdCol;
    @FXML private TableColumn<CommentRecord, Integer> commentPostCol;
    @FXML private TableColumn<CommentRecord, Integer> commentUserCol;
    @FXML private TableColumn<CommentRecord, Integer> commentUpVotesCol;
    @FXML private TableColumn<CommentRecord, Integer> commentDownVotesCol;
    @FXML private TableColumn<CommentRecord, String> commentContentCol;
    @FXML private TableColumn<CommentRecord, Void> commentActionsCol;

    private final BackofficeService backofficeService = new BackofficeService();

    @FXML
    public void initialize() {
        postIdCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        postTitleCol.setCellValueFactory(new PropertyValueFactory<>("title"));
        postTypeCol.setCellValueFactory(new PropertyValueFactory<>("type"));
        postUserCol.setCellValueFactory(new PropertyValueFactory<>("userId"));
        postUpVotesCol.setCellValueFactory(new PropertyValueFactory<>("upVotes"));
        postDownVotesCol.setCellValueFactory(new PropertyValueFactory<>("downVotes"));
        commentIdCol.setCellValueFactory(new PropertyValueFactory<>("id"));
        commentPostCol.setCellValueFactory(new PropertyValueFactory<>("postId"));
        commentUserCol.setCellValueFactory(new PropertyValueFactory<>("userId"));
        commentUpVotesCol.setCellValueFactory(new PropertyValueFactory<>("upVotes"));
        commentDownVotesCol.setCellValueFactory(new PropertyValueFactory<>("downVotes"));
        commentContentCol.setCellValueFactory(new PropertyValueFactory<>("content"));
        setupPostActions();
        setupCommentActions();
        loadAll();
    }

    @FXML
    private void loadAll() {
        loadPosts();
        loadComments();
    }

    @FXML
    private void handleAddPost() {
        showPostDialog(null);
    }

    @FXML
    private void handleAddComment() {
        showCommentDialog(null);
    }

    private void loadPosts() {
        List<PostRecord> posts = backofficeService.getAllPosts();
        postTable.setItems(FXCollections.observableArrayList(posts));
    }

    private void loadComments() {
        List<CommentRecord> comments = backofficeService.getAllComments();
        commentTable.setItems(FXCollections.observableArrayList(comments));
    }

    private void showPostDialog(PostRecord existingPost) {
        boolean isEdit = existingPost != null;
        Dialog<PostRecord> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Post" : "Ajouter Post");
        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);
        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        TextField titleField = new TextField();
        TextArea contentArea = new TextArea();
        TextField typeField = new TextField();
        TextField userIdField = new TextField();
        TextField imageField = new TextField();
        TextField upVotesField = new TextField();
        TextField downVotesField = new TextField();
        if (isEdit) {
            titleField.setText(existingPost.getTitle());
            contentArea.setText(existingPost.getContent());
            typeField.setText(existingPost.getType());
            userIdField.setText(String.valueOf(existingPost.getUserId()));
            imageField.setText(existingPost.getImage());
            upVotesField.setText(String.valueOf(existingPost.getUpVotes()));
            downVotesField.setText(String.valueOf(existingPost.getDownVotes()));
        }
        grid.addRow(0, new Label("Titre"), titleField);
        grid.addRow(1, new Label("Contenu"), contentArea);
        grid.addRow(2, new Label("Catégorie"), typeField);
        grid.addRow(3, new Label("ID Auteur"), userIdField);
        grid.addRow(4, new Label("Image"), imageField);
        grid.addRow(5, new Label("Upvotes"), upVotesField);
        grid.addRow(6, new Label("Downvotes"), downVotesField);
        dialog.getDialogPane().setContent(grid);
        dialog.setResultConverter(button -> {
            if (button != saveButtonType) {
                return null;
            }
            PostRecord post = isEdit ? existingPost : new PostRecord();
            post.setTitle(titleField.getText().trim());
            post.setContent(contentArea.getText().trim());
            post.setType(typeField.getText().trim());
            post.setUserId(parseInt(userIdField.getText()));
            post.setImage(imageField.getText().trim());
            post.setUpVotes(parseInt(upVotesField.getText()));
            post.setDownVotes(parseInt(downVotesField.getText()));
            if (!isEdit) {
                post.setCreatedAt(LocalDateTime.now());
            }
            return post;
        });
        dialog.showAndWait().ifPresent(post -> {
            if (post.getTitle().isBlank() || post.getType().isBlank() || post.getUserId() <= 0) {
                showError("Veuillez remplir correctement les champs post.");
                return;
            }
            boolean success = isEdit ? backofficeService.updatePost(post) : backofficeService.addPost(post);
            if (success) {
                loadPosts();
            }
        });
    }

    private void showCommentDialog(CommentRecord existingComment) {
        boolean isEdit = existingComment != null;
        Dialog<CommentRecord> dialog = new Dialog<>();
        dialog.setTitle(isEdit ? "Modifier Commentaire" : "Ajouter Commentaire");
        ButtonType saveButtonType = new ButtonType("Enregistrer", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(saveButtonType, ButtonType.CANCEL);
        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        TextArea contentArea = new TextArea();
        TextField postIdField = new TextField();
        TextField userIdField = new TextField();
        TextField imageField = new TextField();
        TextField upVotesField = new TextField();
        TextField downVotesField = new TextField();
        if (isEdit) {
            contentArea.setText(existingComment.getContent());
            postIdField.setText(String.valueOf(existingComment.getPostId()));
            userIdField.setText(String.valueOf(existingComment.getUserId()));
            imageField.setText(existingComment.getImage());
            upVotesField.setText(String.valueOf(existingComment.getUpVotes()));
            downVotesField.setText(String.valueOf(existingComment.getDownVotes()));
        }
        grid.addRow(0, new Label("Contenu"), contentArea);
        grid.addRow(1, new Label("ID Post"), postIdField);
        grid.addRow(2, new Label("ID Auteur"), userIdField);
        grid.addRow(3, new Label("Image"), imageField);
        grid.addRow(4, new Label("Upvotes"), upVotesField);
        grid.addRow(5, new Label("Downvotes"), downVotesField);
        dialog.getDialogPane().setContent(grid);
        dialog.setResultConverter(button -> {
            if (button != saveButtonType) {
                return null;
            }
            CommentRecord comment = isEdit ? existingComment : new CommentRecord();
            comment.setContent(contentArea.getText().trim());
            comment.setPostId(parseInt(postIdField.getText()));
            comment.setUserId(parseInt(userIdField.getText()));
            comment.setImage(imageField.getText().trim());
            comment.setUpVotes(parseInt(upVotesField.getText()));
            comment.setDownVotes(parseInt(downVotesField.getText()));
            if (!isEdit) {
                comment.setCreatedAt(LocalDateTime.now());
            }
            return comment;
        });
        dialog.showAndWait().ifPresent(comment -> {
            if (comment.getContent().isBlank() || comment.getPostId() <= 0 || comment.getUserId() <= 0) {
                showError("Veuillez remplir correctement les champs commentaire.");
                return;
            }
            boolean success = isEdit ? backofficeService.updateComment(comment) : backofficeService.addComment(comment);
            if (success) {
                loadComments();
            }
        });
    }

    private void setupPostActions() {
        postActionsCol.setCellFactory(param -> new TableCell<>() {
            private final Button editBtn = new Button("Modifier");
            private final Button deleteBtn = new Button("Supprimer");
            private final HBox box = new HBox(8, editBtn, deleteBtn);

            {
                editBtn.setOnAction(event -> showPostDialog(getTableView().getItems().get(getIndex())));
                deleteBtn.setOnAction(event -> {
                    PostRecord post = getTableView().getItems().get(getIndex());
                    if (backofficeService.deletePost(post.getId())) {
                        loadPosts();
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

    private void setupCommentActions() {
        commentActionsCol.setCellFactory(param -> new TableCell<>() {
            private final Button editBtn = new Button("Modifier");
            private final Button deleteBtn = new Button("Supprimer");
            private final HBox box = new HBox(8, editBtn, deleteBtn);

            {
                editBtn.setOnAction(event -> showCommentDialog(getTableView().getItems().get(getIndex())));
                deleteBtn.setOnAction(event -> {
                    CommentRecord comment = getTableView().getItems().get(getIndex());
                    if (backofficeService.deleteComment(comment.getId())) {
                        loadComments();
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

    private void showError(String message) {
        new Alert(Alert.AlertType.ERROR, message).show();
    }
}
