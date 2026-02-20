<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(\Symfony\Component\Security\Http\Authentication\AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré par Symfony Security
        throw new \LogicException('This method is handled by the firewall.');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $errors = [];
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');
            $confirmPassword = (string) $request->request->get('confirm_password');
            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $dateNaissance = $request->request->get('date_naissance');
            $telephoneInput = (string) $request->request->get('telephone');
            $telephoneDigits = preg_replace('/\D+/', '', $telephoneInput);
            $telephone = $telephoneDigits !== '' ? $telephoneDigits : null;
            $role = trim((string) $request->request->get('role')) ?: User::ROLE_CLIENT;

            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
            }

            $user = new User();
            $user->setEmail($email);
            // Keep registration robust even if first/last name fields are empty on the UI.
            if ($prenom === '') {
                $prenom = 'User';
            }
            if ($nom === '') {
                $nom = 'Lobby';
            }
            $username = trim($prenom . ' ' . $nom);
            if ($username === '') {
                $username = strstr($email, '@', true) ?: $email;
            }
            $user->setUsername($username);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            if ($dateNaissance) {
                try {
                    $user->setDateNaissance(new \DateTime($dateNaissance));
                } catch (\Exception $e) {}
            }
            $user->setTelephone($telephone);
            $user->setRole($role);
            $user->setPassword($password); // Temporairement pour la validation

            // Validation Symfony
            $violations = $validator->validate($user, null, ['Default', 'registration']);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
            }

            if (empty($errors)) {
                // Hachage du mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Inscription réussie ! Veuillez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'errors' => $errors
        ]);
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

    // Supprimé car en conflit avec PageController::home
    /*
    #[Route('/', name: 'app_homepage')]
    public function homepage(): Response
    {
        return $this->render('security/login.html.twig');
    }
    */
}
