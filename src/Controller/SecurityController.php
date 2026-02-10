<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Récupérer les erreurs de connexion de Symfony
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier email saisi
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Initialiser les erreurs de validation
        $emailError = null;
        $passwordError = null;
        
        // VALIDATION SI FORMULAIRE SOUMIS
        if ($request->isMethod('POST')) {
            $email = $request->request->get('_username', '');
            $password = $request->request->get('_password', '');
            
            // Validation Symfony des données
            $validator = Validation::createValidator();
            
            // Validation email
            $emailViolations = $validator->validate($email, [
                new Assert\NotBlank(['message' => 'L\'email est obligatoire.']),
                new Assert\Email(['message' => 'Veuillez entrer un email valide.']),
                new Assert\Length([
                    'max' => 180,
                    'maxMessage' => 'L\'email ne peut pas dépasser {{ limit }} caractères.'
                ])
            ]);
            
            if (count($emailViolations) > 0) {
                $emailError = $emailViolations[0]->getMessage();
            }
            
            // Validation mot de passe
            $passwordViolations = $validator->validate($password, [
                new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                new Assert\Length([
                    'min' => 6,
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                ])
            ]);
            
            if (count($passwordViolations) > 0) {
                $passwordError = $passwordViolations[0]->getMessage();
            }
            
            // Si validation échoue, on ajoute des messages flash
            if ($emailError || $passwordError) {
                if ($emailError) {
                    $this->addFlash('error', $emailError);
                }
                if ($passwordError) {
                    $this->addFlash('error', $passwordError);
                }
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'email_error' => $emailError,
            'password_error' => $passwordError,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method is handled by the firewall.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/blog', name: 'app_blog')]
public function blog(): Response
{
    // Vérifier que l'utilisateur est connecté ET a le rôle client
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_login');
    }
    
    $user = $this->getUser();
    
    // Vérifier si l'utilisateur a le rôle client
    if ($user->getRole() !== 'client') {
        // Si ce n'est pas un client, rediriger vers le dashboard
        $this->addFlash('error', 'Accès réservé aux clients.');
        return $this->redirectToRoute('app_login');
    }

    // Afficher la page blog
    return $this->render('front/modules/blog.html.twig', [
        'user' => $user,
    ]);
}


    #[Route('/', name: 'app_homepage')]
    public function homepage(): Response
    {
        return $this->redirectToRoute('app_login');
    }
}