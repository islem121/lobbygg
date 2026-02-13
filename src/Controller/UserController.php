<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
final class UserController extends AbstractController
{
    #[Route('/update-image', name: 'app_user_update_image', methods: ['POST'])]
    public function updateImage(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $imageFile = $request->files->get('profile_image');

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/profiles',
                    $newFilename
                );
                
                // Supprimer l'ancienne image si elle existe
                if ($user->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/profiles/'.$user->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $user->setImage($newFilename);
                $entityManager->flush();
                
                $this->addFlash('success', 'Profile image updated!');
            } catch (FileException $e) {
                $this->addFlash('error', 'Could not upload image.');
            }
        }

        return $this->redirectToRoute('app_profile_view');
    }

    #[Route('/settings/update', name: 'app_settings_update', methods: ['POST'])]
    public function updateSettings(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $username = $request->request->get('username');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $passwordConfirm = $request->request->get('password_confirm');
        $imageFile = $request->files->get('profile_image');

        if ($username) $user->setPrenom($username);
        if ($email) $user->setEmail($email);
        
        // Validation Symfony
        $violations = $validator->validate($user);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->render('user/settings.html.twig', [
                'user' => $user,
                'errors' => $errors
            ]);
        }

        if ($password) {
            if ($password === $passwordConfirm) {
                if (strlen($password) < 6) {
                    $this->addFlash('error', 'Le mot de passe doit faire au moins 6 caractères.');
                    return $this->redirectToRoute('app_settings');
                }
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            } else {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_settings');
            }
        }

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/profiles',
                    $newFilename
                );
                
                if ($user->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/profiles/'.$user->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $user->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
            }
        }

        $entityManager->flush();
        $this->addFlash('success', 'Settings updated!');

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/account/delete', name: 'app_account_delete', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        if ($this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            // Delete profile image if exists
            if ($user->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/profiles/'.$user->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Invalidate session and logout
            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your account has been deleted successfully.');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('error', 'Invalid CSRF token.');
        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings', name: 'app_settings', priority: 10)]
    public function settings(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        return $this->render('user/settings.html.twig', [
            'user' => $user,
            'errors' => []
        ]);
    }

    #[Route('/profile/view', name: 'app_profile_view', priority: 10)]
    public function viewProfile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        return $this->render('user/view_profile.html.twig', [
            'user' => $user,
            'marketplace_history' => [], // Mock data
            'games_played' => [], // Mock data
            'sponsoring_history' => [], // Mock data for now
        ]);
    }
}
