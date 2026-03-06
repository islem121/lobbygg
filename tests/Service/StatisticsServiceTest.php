<?php

namespace App\Tests\Service;

use App\Service\StatisticsService;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\SponsorRepository;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use PHPUnit\Framework\TestCase;

class StatisticsServiceTest extends TestCase
{
    private $entityManager;
    private $orderRepo;
    private $productRepo;
    private $userRepo;
    private $sponsorRepo;
    private $postRepo;
    private $commentRepo;

    protected function setUp(): void
    {
        // On crée des mocks pour les dépôts nécessaires au constructeur
        $this->entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->orderRepo = $this->createMock(OrderRepository::class);
        $this->productRepo = $this->createMock(ProductRepository::class);
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->sponsorRepo = $this->createMock(SponsorRepository::class);
        $this->postRepo = $this->createMock(PostRepository::class);
        $this->commentRepo = $this->createMock(CommentRepository::class);
    }

    public function testDashboardSummaryCount(): void
    {
        // On configure les mocks pour renvoyer des valeurs fictives
        $this->orderRepo->method('count')->willReturn(10);
        $this->productRepo->method('count')->willReturn(50);
        $this->userRepo->method('count')->willReturn(20);
        $this->sponsorRepo->method('count')->willReturn(5);
        $this->postRepo->method('count')->willReturn(15);
        $this->commentRepo->method('count')->willReturn(30);

        // Simulation du revenu (select queryBuilder)
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        
        $this->orderRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('select')->willReturn($queryBuilder);
        $queryBuilder->method('join')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getSingleScalarResult')->willReturn(5000.0);

        $service = new StatisticsService(
            $this->entityManager,
            $this->orderRepo,
            $this->productRepo,
            $this->userRepo,
            $this->sponsorRepo,
            $this->postRepo,
            $this->commentRepo
        );

        $summary = $service->getDashboardSummary();

        $this->assertEquals(10, $summary['total_orders']);
        $this->assertEquals(50, $summary['total_products']);
        $this->assertEquals(5000.0, $summary['total_revenue']);
    }
}
