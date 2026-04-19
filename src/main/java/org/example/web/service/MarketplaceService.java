package org.example.web.service;

import lombok.RequiredArgsConstructor;
import org.example.web.model.Category;
import org.example.web.model.Order;
import org.example.web.model.Product;
import org.example.web.model.User;
import org.example.web.repository.CategoryRepository;
import org.example.web.repository.OrderRepository;
import org.example.web.repository.ProductRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Service
@RequiredArgsConstructor
public class MarketplaceService {
    private final ProductRepository productRepository;
    private final CategoryRepository categoryRepository;
    private final OrderRepository orderRepository;
    private final WalletService walletService;

    public List<Product> getAllProducts() {
        return productRepository.findAll();
    }

    public List<Category> getAllCategories() {
        return categoryRepository.findAll();
    }

    public Optional<Product> getProductById(Integer id) {
        return productRepository.findById(id);
    }

    @Transactional
    public boolean purchase(User user, Integer productId, Integer quantity) {
        Product product = productRepository.findById(productId).orElse(null);
        if (product == null || product.getStock() < quantity) {
            return false;
        }

        Double totalCost = product.getPrice() * quantity;
        if (walletService.deduct(user, totalCost, "ACHAT_PRODUIT")) {
            product.setStock(product.getStock() - quantity);
            productRepository.save(product);

            Order order = new Order();
            order.setUser(user);
            order.setProduct(product);
            order.setQuantity(quantity);
            order.setStatus("COMPLETED");
            orderRepository.save(order);
            return true;
        }
        return false;
    }

    public List<Order> getOrdersByUser(User user) {
        return orderRepository.findByUserOrderByOrderDateDesc(user);
    }
}
