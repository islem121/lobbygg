<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => '',
            'error' => null,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré par Symfony Security
        throw new \LogicException('This method is handled by the firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(): Response
    {
        return $this->render('security/register.html.twig');
    }

   #[Route('/forgot-password', name: 'app_forgot_password')]
public function forgotPassword(): Response
{
    return $this->render('security/forgot_password.html.twig');
}

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return new Response('
            <html>
            <body style="background: black; color: lime; padding: 50px;">
                <h1>🎮 DASHBOARD</h1>
                <p>Welcome to the gaming dashboard!</p>
                <a href="/logout" style="color: red;">Logout</a>
            </body>
            </html>
        ');
    }

    #[Route('/', name: 'app_homepage')]
    public function homepage(): Response
    {
        return $this->redirectToRoute('app_login');
    }
}
