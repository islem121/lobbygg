package org.example.web.controller;

import lombok.RequiredArgsConstructor;
import org.example.web.model.User;
import org.example.web.repository.UserRepository;
import org.example.web.service.MarketplaceService;
import org.example.web.service.SponsorService;
import org.example.web.service.TournamentService;
import org.example.web.service.WalletService;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.ModelAttribute;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

@Controller
@RequiredArgsConstructor
public class HomeController {

    private final TournamentService tournamentService;
    private final MarketplaceService marketplaceService;
    private final SponsorService sponsorService;
    private final WalletService walletService;
    private final UserRepository userRepository;

    @ModelAttribute("currentUser")
    public User populateCurrentUser() {
        String email = SecurityContextHolder.getContext().getAuthentication().getName();
        if (email == null || "anonymousUser".equals(email)) return null;
        return userRepository.findByEmail(email).orElse(null);
    }

    private User getCurrentUser() {
        return populateCurrentUser();
    }

    @GetMapping("/")
    public String index() {
        return "redirect:/login";
    }

    @GetMapping("/home")
    public String home(Model model) {
        User user = getCurrentUser();
        if (user != null) {
            model.addAttribute("account", walletService.getOrCreateAccount(user));
        }
        model.addAttribute("tournaments", tournamentService.getAllActiveTournaments());
        model.addAttribute("products", marketplaceService.getAllProducts());
        model.addAttribute("content", "pages/home");
        return "layout/layout";
    }

    @GetMapping("/tournaments")
    public String tournaments(Model model) {
        model.addAttribute("tournaments", tournamentService.getAllActiveTournaments());
        model.addAttribute("content", "pages/tournaments");
        return "layout/layout";
    }

    @GetMapping("/marketplace")
    public String marketplace(Model model) {
        model.addAttribute("products", marketplaceService.getAllProducts());
        model.addAttribute("categories", marketplaceService.getAllCategories());
        model.addAttribute("content", "pages/marketplace");
        return "layout/layout";
    }

    @GetMapping("/blog")
    public String blog(Model model) {
        model.addAttribute("content", "pages/blog");
        return "layout/layout";
    }

    @GetMapping("/wallet")
    public String wallet(Model model) {
        User user = getCurrentUser();
        if (user != null) {
            model.addAttribute("account", walletService.getOrCreateAccount(user));
            model.addAttribute("history", walletService.getHistory(user));
        }
        model.addAttribute("content", "pages/wallet");
        return "layout/layout";
    }

    @PostMapping("/wallet/recharge")
    public String recharge(@RequestParam Double amount, RedirectAttributes redirectAttributes) {
        User user = getCurrentUser();
        if (user != null && amount > 0) {
            walletService.recharge(user, amount);
            redirectAttributes.addFlashAttribute("success", "Recharge de " + amount + " Gold réussie !");
        }
        return "redirect:/wallet";
    }

    @GetMapping("/profile")
    public String profile(Model model) {
        model.addAttribute("user", getCurrentUser());
        model.addAttribute("content", "pages/profile");
        return "layout/layout";
    }

    @GetMapping("/messages")
    public String messages(Model model) {
        model.addAttribute("content", "pages/messages");
        return "layout/layout";
    }

    @GetMapping("/settings")
    public String settings(Model model) {
        model.addAttribute("user", getCurrentUser());
        model.addAttribute("content", "pages/settings");
        return "layout/layout";
    }

    @GetMapping("/sponsor/dashboard")
    public String sponsorDashboard(Model model) {
        User user = getCurrentUser();
        if (user != null) {
            model.addAttribute("sponsors", sponsorService.getSponsorsByUser(user));
        }
        model.addAttribute("content", "pages/sponsor_dashboard");
        return "layout/layout";
    }

    @GetMapping("/notifications")
    public String notifications(Model model) {
        model.addAttribute("content", "pages/notifications");
        return "layout/layout";
    }

    @GetMapping("/tournament/{id}")
    public String tournamentDetails(@PathVariable Integer id, Model model) {
        tournamentService.getById(id).ifPresent(t -> model.addAttribute("tournament", t));
        model.addAttribute("content", "pages/tournament_details");
        return "layout/layout";
    }

    @GetMapping("/product/{id}")
    public String productDetails(@PathVariable Integer id, Model model) {
        marketplaceService.getProductById(id).ifPresent(p -> model.addAttribute("product", p));
        model.addAttribute("content", "pages/product_details");
        return "layout/layout";
    }

    @PostMapping("/marketplace/buy")
    public String buyProduct(@RequestParam Integer productId, @RequestParam Integer quantity, RedirectAttributes redirectAttributes) {
        User user = getCurrentUser();
        if (user != null) {
            if (marketplaceService.purchase(user, productId, quantity)) {
                redirectAttributes.addFlashAttribute("success", "Achat réussi !");
            } else {
                redirectAttributes.addFlashAttribute("error", "Solde insuffisant ou stock épuisé.");
            }
        }
        return "redirect:/marketplace";
    }

    @GetMapping("/sponsor/{id}")
    public String sponsorProfile(Model model) {
        model.addAttribute("content", "pages/sponsor_profile");
        return "layout/layout";
    }

    @GetMapping("/admin/dashboard")
    public String adminDashboard() {
        return "pages/admin_redirect";
    }
}
