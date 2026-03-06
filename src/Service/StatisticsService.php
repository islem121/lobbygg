<?php

namespace App\Service;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;

use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private UserRepository $userRepository,
        private \App\Repository\SponsorRepository $sponsorRepository,
        private \App\Repository\PostRepository $postRepository,
        private \App\Repository\CommentRepository $commentRepository
    ) {}

    public function getDashboardSummary(): array
    {
        $ordersCount = $this->orderRepository->count([]);
        $productsCount = $this->productRepository->count([]);
        $usersCount = $this->userRepository->count([]);
        $sellersCount = $this->userRepository->count(['role' => 'seller']);
        $sponsorsCount = $this->sponsorRepository->count([]);
        $postsCount = $this->postRepository->count([]);
        $commentsCount = $this->commentRepository->count([]);
        
        // Total Revenue
        $revenue = $this->orderRepository->createQueryBuilder('o')
            ->select('SUM(o.quantity * p.price)')
            ->join('o.product', 'p')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'total_orders' => $ordersCount,
            'total_products' => $productsCount,
            'total_users' => $usersCount,
            'total_sellers' => $sellersCount,
            'total_revenue' => $revenue,
            'total_sponsors' => $sponsorsCount,
            'total_posts' => $postsCount,
            'total_comments' => $commentsCount,
        ];
    }

    public function getTopSellers(int $limit = 5): array
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->select('s.username as sellerName, s.email as sellerEmail, SUM(o.quantity) as totalSales, SUM(o.quantity * p.price) as revenue')
            ->join('o.product', 'p')
            ->join('p.seller', 's')
            ->groupBy('s.id')
            ->orderBy('revenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTopCategories(int $limit = 5): array
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->select('c.name as categoryName, SUM(o.quantity) as totalSales, SUM(o.quantity * p.price) as revenue')
            ->join('o.product', 'p')
            ->join('p.category', 'c')
            ->groupBy('c.id')
            ->orderBy('totalSales', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getBestSellingProducts(int $limit = 5): array
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->select('p.name as productName, SUM(o.quantity) as totalSales, SUM(o.quantity * p.price) as revenue')
            ->join('o.product', 'p')
            ->groupBy('p.id')
            ->orderBy('totalSales', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTopBuyers(int $limit = 5): array
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->select('u.username, u.email, SUM(o.quantity) as totalItems, SUM(o.quantity * p.price) as totalSpent')
            ->join('o.user', 'u')
            ->join('o.product', 'p')
            ->groupBy('u.id')
            ->orderBy('totalSpent', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getMonthlyRevenue(): array
    {
        // On récupère les 12 derniers mois de revenus
        // Note: DATE_FORMAT est spécifique à MySQL. 
        // Si besoin de compatibilité multi-DB, il faudrait une autre approche.
        $conn = $this->entityManager->getConnection();
        
        $sql = "
            SELECT 
                DATE_FORMAT(o.order_date, '%Y-%m') as month, 
                SUM(o.quantity * p.price) as revenue 
            FROM `order` o
            JOIN product p ON o.product_id = p.id
            GROUP BY month
            ORDER BY month ASC
            LIMIT 12
        ";
        
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getMonthlyOrders(): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = "
            SELECT 
                DATE_FORMAT(order_date, '%Y-%m') as month, 
                COUNT(id) as orders
            FROM `order`
            GROUP BY month
            ORDER BY month ASC
            LIMIT 12
        ";
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getMonthlyUsers(): array
    {
        $conn = $this->entityManager->getConnection();
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month, 
                COUNT(id) as users
            FROM user
            GROUP BY month
            ORDER BY month ASC
            LIMIT 12
        ";
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
