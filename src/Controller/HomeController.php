<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(\App\Repository\ProductRepository $productRepository, \App\Repository\UserRepository $userRepository): Response
    {
        // On récupère les 3 derniers produits pour la home
        $latestProducts = $productRepository->findBy([], ['createdAt' => 'DESC'], 3);
        
        // On récupère quelques stats
        $totalProducts = $productRepository->count([]);
        $totalUsers = $userRepository->count([]);
        
        return $this->render('home/index.html.twig', [
            'user' => $this->getUser(),
            'latestProducts' => $latestProducts,
            'stats' => [
                'products' => $totalProducts,
                'users' => $totalUsers,
                'tournaments' => 12, // Valeur statique pour l'instant
            ]
        ]);
    }
}
