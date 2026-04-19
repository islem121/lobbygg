package org.example.service;

import org.example.database.MySqlConnection;
import org.example.model.Category;
import org.example.model.CommentRecord;
import org.example.model.OrderRecord;
import org.example.model.PostRecord;
import org.example.model.Product;
import org.example.model.Sponsor;
import org.example.model.Tournament;

import java.sql.Connection;
import java.sql.Date;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Timestamp;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

public class BackofficeService {
    public List<Sponsor> getAllSponsors() {
        String sql = """
                SELECT sponsor_id, company_name, description, amount, target_type, user_id, logo, created_at
                FROM sponsor
                ORDER BY sponsor_id DESC
                """;
        List<Sponsor> sponsors = new ArrayList<>();
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet resultSet = statement.executeQuery()) {
            while (resultSet.next()) {
                sponsors.add(mapSponsor(resultSet));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return sponsors;
    }

    public boolean addSponsor(Sponsor sponsor) {
        String sql = """
                INSERT INTO sponsor (company_name, description, amount, target_type, user_id, logo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillSponsorStatement(statement, sponsor);
            statement.setTimestamp(7, Timestamp.valueOf(defaultNow(sponsor.getCreatedAt())));
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean updateSponsor(Sponsor sponsor) {
        String sql = """
                UPDATE sponsor
                SET company_name = ?, description = ?, amount = ?, target_type = ?, user_id = ?, logo = ?
                WHERE sponsor_id = ?
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillSponsorStatement(statement, sponsor);
            statement.setInt(7, sponsor.getId());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean deleteSponsor(int sponsorId) {
        return deleteById("DELETE FROM sponsor WHERE sponsor_id = ?", sponsorId);
    }

    public List<Tournament> getAllTournaments() {
        String sql = """
                SELECT id, title, description, start_date, end_date, max_players, status, created_at
                FROM tournament
                ORDER BY id DESC
                """;
        List<Tournament> tournaments = new ArrayList<>();
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet resultSet = statement.executeQuery()) {
            while (resultSet.next()) {
                tournaments.add(mapTournament(resultSet));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return tournaments;
    }

    public boolean addTournament(Tournament tournament) {
        String sql = """
                INSERT INTO tournament (title, description, start_date, end_date, max_players, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillTournamentStatement(statement, tournament);
            statement.setTimestamp(7, Timestamp.valueOf(defaultNow(tournament.getCreatedAt())));
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean updateTournament(Tournament tournament) {
        String sql = """
                UPDATE tournament
                SET title = ?, description = ?, start_date = ?, end_date = ?, max_players = ?, status = ?
                WHERE id = ?
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillTournamentStatement(statement, tournament);
            statement.setInt(7, tournament.getId());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean deleteTournament(int tournamentId) {
        return deleteById("DELETE FROM tournament WHERE id = ?", tournamentId);
    }

    public List<Category> getAllCategories() {
        String sql = """
                SELECT category_id, name, description
                FROM category
                ORDER BY category_id DESC
                """;
        List<Category> categories = new ArrayList<>();
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet resultSet = statement.executeQuery()) {
            while (resultSet.next()) {
                categories.add(mapCategory(resultSet));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return categories;
    }

    public boolean addCategory(Category category) {
        String sql = "INSERT INTO category (name, description) VALUES (?, ?)";
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            statement.setString(1, category.getName());
            statement.setString(2, category.getDescription());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean updateCategory(Category category) {
        String sql = "UPDATE category SET name = ?, description = ? WHERE category_id = ?";
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            statement.setString(1, category.getName());
            statement.setString(2, category.getDescription());
            statement.setInt(3, category.getId());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean deleteCategory(int categoryId) {
        return deleteById("DELETE FROM category WHERE category_id = ?", categoryId);
    }

    public List<Product> getAllProducts() {
        String sql = """
                SELECT product_id, name, price, description, image, stock, category_id, seller_id, created_at
                FROM product
                ORDER BY product_id DESC
                """;
        List<Product> products = new ArrayList<>();
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet resultSet = statement.executeQuery()) {
            while (resultSet.next()) {
                products.add(mapProduct(resultSet));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return products;
    }

    public boolean addProduct(Product product) {
        String sql = """
                INSERT INTO product (name, price, description, image, stock, category_id, seller_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillProductStatement(statement, product);
            statement.setTimestamp(8, Timestamp.valueOf(defaultNow(product.getCreatedAt())));
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean updateProduct(Product product) {
        String sql = """
                UPDATE product
                SET name = ?, price = ?, description = ?, image = ?, stock = ?, category_id = ?, seller_id = ?
                WHERE product_id = ?
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillProductStatement(statement, product);
            statement.setInt(8, product.getId());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean deleteProduct(int productId) {
        return deleteById("DELETE FROM product WHERE product_id = ?", productId);
    }

    public List<OrderRecord> getAllOrders() {
        String sql = """
                SELECT order_id, quantity, order_date, status, product_id, user_id
                FROM `order`
                ORDER BY order_id DESC
                """;
        List<OrderRecord> orders = new ArrayList<>();
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet resultSet = statement.executeQuery()) {
            while (resultSet.next()) {
                orders.add(mapOrder(resultSet));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return orders;
    }

    public boolean addOrder(OrderRecord order) {
        String sql = """
                INSERT INTO `order` (quantity, order_date, status, product_id, user_id)
                VALUES (?, ?, ?, ?, ?)
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillOrderStatement(statement, order);
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean updateOrder(OrderRecord order) {
        String sql = """
                UPDATE `order`
                SET quantity = ?, order_date = ?, status = ?, product_id = ?, user_id = ?
                WHERE order_id = ?
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillOrderStatement(statement, order);
            statement.setInt(6, order.getId());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean deleteOrder(int orderId) {
        return deleteById("DELETE FROM `order` WHERE order_id = ?", orderId);
    }

    public List<PostRecord> getAllPosts() {
        String sql = """
                SELECT post_id, title, content, image, category, up_votes, down_votes, user_id, created_at
                FROM post
                ORDER BY post_id DESC
                """;
        List<PostRecord> posts = new ArrayList<>();
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet resultSet = statement.executeQuery()) {
            while (resultSet.next()) {
                posts.add(mapPost(resultSet));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return posts;
    }

    public boolean addPost(PostRecord post) {
        String sql = """
                INSERT INTO post (title, content, image, category, up_votes, down_votes, user_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillPostStatement(statement, post);
            statement.setTimestamp(8, Timestamp.valueOf(defaultNow(post.getCreatedAt())));
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean updatePost(PostRecord post) {
        String sql = """
                UPDATE post
                SET title = ?, content = ?, image = ?, category = ?, up_votes = ?, down_votes = ?, user_id = ?
                WHERE post_id = ?
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillPostStatement(statement, post);
            statement.setInt(8, post.getId());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean deletePost(int postId) {
        return deleteById("DELETE FROM post WHERE post_id = ?", postId);
    }

    public List<CommentRecord> getAllComments() {
        String sql = """
                SELECT comment_id, content, image, up_votes, down_votes, user_id, post_id, created_at
                FROM comment
                ORDER BY comment_id DESC
                """;
        List<CommentRecord> comments = new ArrayList<>();
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql);
             ResultSet resultSet = statement.executeQuery()) {
            while (resultSet.next()) {
                comments.add(mapComment(resultSet));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return comments;
    }

    public boolean addComment(CommentRecord comment) {
        String sql = """
                INSERT INTO comment (content, image, up_votes, down_votes, user_id, post_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillCommentStatement(statement, comment);
            statement.setTimestamp(7, Timestamp.valueOf(defaultNow(comment.getCreatedAt())));
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean updateComment(CommentRecord comment) {
        String sql = """
                UPDATE comment
                SET content = ?, image = ?, up_votes = ?, down_votes = ?, user_id = ?, post_id = ?
                WHERE comment_id = ?
                """;
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            fillCommentStatement(statement, comment);
            statement.setInt(7, comment.getId());
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    public boolean deleteComment(int commentId) {
        return deleteById("DELETE FROM comment WHERE comment_id = ?", commentId);
    }

    public int getCount(String tableName) {
        String sql = "SELECT COUNT(*) FROM " + tableName;
        try (Connection connection = MySqlConnection.getConnection();
             Statement statement = connection.createStatement();
             ResultSet resultSet = statement.executeQuery(sql)) {
            return resultSet.next() ? resultSet.getInt(1) : 0;
        } catch (SQLException e) {
            return 0;
        }
    }

    private boolean deleteById(String sql, int id) {
        try (Connection connection = MySqlConnection.getConnection();
             PreparedStatement statement = connection.prepareStatement(sql)) {
            statement.setInt(1, id);
            return statement.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    private void fillSponsorStatement(PreparedStatement statement, Sponsor sponsor) throws SQLException {
        statement.setString(1, sponsor.getCompanyName());
        statement.setString(2, sponsor.getDescription());
        statement.setDouble(3, sponsor.getAmount());
        statement.setString(4, sponsor.getTargetType());
        statement.setInt(5, sponsor.getSponsorUserId());
        statement.setString(6, sponsor.getLogoPath());
    }

    private void fillTournamentStatement(PreparedStatement statement, Tournament tournament) throws SQLException {
        statement.setString(1, tournament.getTitle());
        statement.setString(2, tournament.getDescription());
        statement.setTimestamp(3, Timestamp.valueOf(tournament.getStartDate()));
        statement.setDate(4, Date.valueOf(tournament.getEndDate()));
        statement.setInt(5, tournament.getMaxPlayers());
        statement.setString(6, tournament.getStatus());
    }

    private void fillProductStatement(PreparedStatement statement, Product product) throws SQLException {
        statement.setString(1, product.getName());
        statement.setDouble(2, product.getPrice());
        statement.setString(3, product.getDescription());
        statement.setString(4, product.getImage());
        statement.setInt(5, product.getStock());
        statement.setInt(6, product.getCategoryId());
        statement.setInt(7, product.getSellerId());
    }

    private void fillOrderStatement(PreparedStatement statement, OrderRecord order) throws SQLException {
        statement.setInt(1, order.getQuantity());
        statement.setTimestamp(2, Timestamp.valueOf(defaultNow(order.getOrderDate())));
        statement.setString(3, order.getStatus());
        statement.setInt(4, order.getProductId());
        statement.setInt(5, order.getUserId());
    }

    private void fillPostStatement(PreparedStatement statement, PostRecord post) throws SQLException {
        statement.setString(1, post.getTitle());
        statement.setString(2, post.getContent());
        statement.setString(3, post.getImage());
        statement.setString(4, post.getType());
        statement.setInt(5, post.getUpVotes());
        statement.setInt(6, post.getDownVotes());
        statement.setInt(7, post.getUserId());
    }

    private void fillCommentStatement(PreparedStatement statement, CommentRecord comment) throws SQLException {
        statement.setString(1, comment.getContent());
        statement.setString(2, comment.getImage());
        statement.setInt(3, comment.getUpVotes());
        statement.setInt(4, comment.getDownVotes());
        statement.setInt(5, comment.getUserId());
        statement.setInt(6, comment.getPostId());
    }

    private Sponsor mapSponsor(ResultSet resultSet) throws SQLException {
        Sponsor sponsor = new Sponsor();
        sponsor.setId(resultSet.getInt("sponsor_id"));
        sponsor.setCompanyName(resultSet.getString("company_name"));
        sponsor.setDescription(resultSet.getString("description"));
        sponsor.setAmount(resultSet.getDouble("amount"));
        sponsor.setTargetType(resultSet.getString("target_type"));
        sponsor.setSponsorUserId(resultSet.getInt("user_id"));
        sponsor.setLogoPath(resultSet.getString("logo"));
        Timestamp createdAt = resultSet.getTimestamp("created_at");
        if (createdAt != null) {
            sponsor.setCreatedAt(createdAt.toLocalDateTime());
        }
        return sponsor;
    }

    private Tournament mapTournament(ResultSet resultSet) throws SQLException {
        Tournament tournament = new Tournament();
        tournament.setId(resultSet.getInt("id"));
        tournament.setTitle(resultSet.getString("title"));
        tournament.setDescription(resultSet.getString("description"));
        Timestamp startDate = resultSet.getTimestamp("start_date");
        if (startDate != null) {
            tournament.setStartDate(startDate.toLocalDateTime());
        }
        Date endDate = resultSet.getDate("end_date");
        if (endDate != null) {
            tournament.setEndDate(endDate.toLocalDate());
        }
        tournament.setMaxPlayers(resultSet.getInt("max_players"));
        tournament.setStatus(resultSet.getString("status"));
        Timestamp createdAt = resultSet.getTimestamp("created_at");
        if (createdAt != null) {
            tournament.setCreatedAt(createdAt.toLocalDateTime());
        }
        return tournament;
    }

    private Category mapCategory(ResultSet resultSet) throws SQLException {
        Category category = new Category();
        category.setId(resultSet.getInt("category_id"));
        category.setName(resultSet.getString("name"));
        category.setDescription(resultSet.getString("description"));
        return category;
    }

    private Product mapProduct(ResultSet resultSet) throws SQLException {
        Product product = new Product();
        product.setId(resultSet.getInt("product_id"));
        product.setName(resultSet.getString("name"));
        product.setPrice(resultSet.getDouble("price"));
        product.setDescription(resultSet.getString("description"));
        product.setImage(resultSet.getString("image"));
        product.setStock(resultSet.getInt("stock"));
        product.setCategoryId(resultSet.getInt("category_id"));
        product.setSellerId(resultSet.getInt("seller_id"));
        Timestamp createdAt = resultSet.getTimestamp("created_at");
        if (createdAt != null) {
            product.setCreatedAt(createdAt.toLocalDateTime());
        }
        return product;
    }

    private OrderRecord mapOrder(ResultSet resultSet) throws SQLException {
        OrderRecord order = new OrderRecord();
        order.setId(resultSet.getInt("order_id"));
        order.setQuantity(resultSet.getInt("quantity"));
        Timestamp orderDate = resultSet.getTimestamp("order_date");
        if (orderDate != null) {
            order.setOrderDate(orderDate.toLocalDateTime());
        }
        order.setStatus(resultSet.getString("status"));
        order.setProductId(resultSet.getInt("product_id"));
        order.setUserId(resultSet.getInt("user_id"));
        return order;
    }

    private PostRecord mapPost(ResultSet resultSet) throws SQLException {
        PostRecord post = new PostRecord();
        post.setId(resultSet.getInt("post_id"));
        post.setTitle(resultSet.getString("title"));
        post.setContent(resultSet.getString("content"));
        post.setImage(resultSet.getString("image"));
        post.setType(resultSet.getString("category"));
        post.setUpVotes(resultSet.getInt("up_votes"));
        post.setDownVotes(resultSet.getInt("down_votes"));
        post.setUserId(resultSet.getInt("user_id"));
        Timestamp createdAt = resultSet.getTimestamp("created_at");
        if (createdAt != null) {
            post.setCreatedAt(createdAt.toLocalDateTime());
        }
        return post;
    }

    private CommentRecord mapComment(ResultSet resultSet) throws SQLException {
        CommentRecord comment = new CommentRecord();
        comment.setId(resultSet.getInt("comment_id"));
        comment.setContent(resultSet.getString("content"));
        comment.setImage(resultSet.getString("image"));
        comment.setUpVotes(resultSet.getInt("up_votes"));
        comment.setDownVotes(resultSet.getInt("down_votes"));
        comment.setUserId(resultSet.getInt("user_id"));
        comment.setPostId(resultSet.getInt("post_id"));
        Timestamp createdAt = resultSet.getTimestamp("created_at");
        if (createdAt != null) {
            comment.setCreatedAt(createdAt.toLocalDateTime());
        }
        return comment;
    }

    private LocalDateTime defaultNow(LocalDateTime value) {
        return value != null ? value : LocalDateTime.now();
    }
}
