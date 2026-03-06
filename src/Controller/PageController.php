<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class PageController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function home(): Response
    {
        // Tout le monde sauf admin va vers app_home
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/home', name: 'app_home')]
    public function appHome(): Response
    {
        return $this->render('front/modules/home.html.twig', [
            'page' => 'home',
        ]);
    }

    #[Route('/admin-placeholder', name: 'app_admin_placeholder')]
    public function appAdminPlaceholder(): Response
    {
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/users', name: 'front_users')]
    public function users(): Response
    {
        // Revert: /users is back to its original module.
        return $this->render('front/modules/users.html.twig', [
            'page' => 'users',
        ]);
    }

    #[Route('/marketplace', name: 'front_marketplace')]
    public function marketplace(): Response
    {
        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace',
        ]);
    }

    #[Route('/tournaments', name: 'front_tournaments')]
    public function tournaments(): Response
    {
        return $this->render('front/modules/tournaments.html.twig', [
            'page' => 'tournaments',
        ]);
    }

    #[Route('/blog', name: 'front_blog')]
    public function blog(): Response
    {
        // Social frontoffice: render the news feed
        return $this->render('front/feed.html.twig', ['page' => 'blog']);
    }
}
