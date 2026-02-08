<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function home(): Response
    {
        // Entry point (after login): dynamic news feed
        return $this->redirectToRoute('front_blog');
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

    #[Route('/sponsoring', name: 'front_sponsoring')]
    public function sponsoring(): Response
    {
        return $this->render('front/modules/sponsoring.html.twig', [
            'page' => 'sponsoring',
        ]);
    }

    #[Route('/blog', name: 'front_blog')]
    public function blog(): Response
    {
        // Social frontoffice: render the news feed
        return $this->render('front/feed.html.twig', ['page' => 'blog']);
    }
}
