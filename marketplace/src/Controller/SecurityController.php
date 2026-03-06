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

            if (!$email || !$password) {
                $this->addFlash('danger', 'Email et mot de passe requis.');
                return $this->render('security/register.html.twig', ['errors' => $errors]);
            }

            try {
                $user = new User();
                $user->setEmail($email);
                
                // Génération du username
                $baseUsername = explode('@', $email)[0];
                if (strlen($baseUsername) < 3) $baseUsername .= rand(100, 999);
                
                $username = $baseUsername;
                $counter = 1;
                while ($entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
                    $username = $baseUsername . $counter++;
                }
                $user->setUsername($username);
                $user->setRole($role);
                $user->setCreatedAt(new \DateTimeImmutable()); // Forcer la date de création

                // Hachage immédiat (pas de validation intermédiaire pour tester l'insertion)
                $user->setPassword($passwordHasher->hashPassword($user, $password));

                // Données optionnelles
                if ($nom) $user->setNom($nom);
                if ($prenom) $user->setPrenom($prenom);
                if ($telephone) {
                    // Nettoyage du téléphone (on garde les chiffres et le + pour la BDD)
                    $cleanPhone = preg_replace('/[^0-9+]/', '', $telephone);
                    $user->setTelephone($cleanPhone);
                }
                if ($dateNaissance) {
                    try { $user->setDateNaissance(new \DateTime($dateNaissance)); } catch (\Exception $e) {}
                }

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'COMPTE CRÉÉ AVEC SUCCÈS !');
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->addFlash('danger', 'ERREUR CRITIQUE : ' . $e->getMessage());
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
