<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Service\StatisticsService;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(StatisticsService $statisticsService, \App\Repository\OrderRepository $orderRepository, \App\Repository\UserRepository $userRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'summary' => $statisticsService->getDashboardSummary(),
            'recentOrders' => $orderRepository->findBy([], ['orderDate' => 'DESC'], 5),
            'recentUsers' => $userRepository->findBy([], ['createdAt' => 'DESC'], 6),
        ]);
    }

    #[Route('/admin/statistics', name: 'app_admin_statistics')]
    public function statistics(StatisticsService $statisticsService): Response
    {
        return $this->render('admin/statistics.html.twig', [
            'summary' => $statisticsService->getDashboardSummary(),
            'bestProducts' => $statisticsService->getBestSellingProducts(),
            'topBuyers' => $statisticsService->getTopBuyers(),
            'monthlyRevenue' => $statisticsService->getMonthlyRevenue(),
            'monthlyOrders' => $statisticsService->getMonthlyOrders(),
            'monthlyUsers' => $statisticsService->getMonthlyUsers(),
            'topCategories' => $statisticsService->getTopCategories(),
            'topSellers' => $statisticsService->getTopSellers(),
        ]);
    }

    #[Route('/admin/sponsoring', name: 'app_admin_sponsoring')]
    public function sponsoringManagement(): Response
    {
        return $this->render('admin/sponsoring/index.html.twig');
    }

    #[Route('/admin/search/ajax', name: 'app_admin_search_ajax', methods: ['GET'])]
    public function searchAjax(Request $request, \App\Repository\ProductRepository $productRepository, \App\Repository\CategoryRepository $categoryRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $products = $productRepository->searchByName($query);
        $categories = $categoryRepository->searchByName($query);

        $results = [];

        foreach ($products as $product) {
            $results[] = [
                'type' => 'Product',
                'label' => $product->getName(),
                'detail' => $product->getPrice() . ' TND',
                'url' => $this->generateUrl('app_admin_product_show', ['id' => $product->getId()])
            ];
        }

        foreach ($categories as $category) {
            $results[] = [
                'type' => 'Category',
                'label' => $category->getName(),
                'detail' => substr($category->getDescription() ?? '', 0, 50) . '...',
                'url' => $this->generateUrl('app_admin_category_show', ['id' => $category->getId()])
            ];
        }

        return $this->json($results);
    }
}
