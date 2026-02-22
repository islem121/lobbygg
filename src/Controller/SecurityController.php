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
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $dateNaissance = $request->request->get('date_naissance');
            $telephone = $request->request->get('telephone');
            $role = $request->request->get('role') ?: User::ROLE_CLIENT;

            $user = new User();
            $user->setEmail($email);
            // Utiliser le prénom et le nom pour le username s'ils existent
            $user->setUsername(($prenom && $nom) ? ($prenom . ' ' . $nom) : $email);
            $user->setNom($nom);
            $user->setPrenom($prenom);
            if ($dateNaissance) {
                try {
                    $user->setDateNaissance(new \DateTime($dateNaissance));
                } catch (\Exception $e) {}
            }
            $user->setTelephone($telephone);
            $user->setRole($role);
            $user->setPassword($password);

            // Validation Symfony simplifiée
            $violations = $validator->validate($user);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
            } else {
                try {
                    // Hachage du mot de passe
                    $user->setPassword(
                        $passwordHasher->hashPassword($user, $password)
                    );

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_login');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur base de données : ' . $e->getMessage());
                }
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
