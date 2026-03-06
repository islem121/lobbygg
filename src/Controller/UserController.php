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

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
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
                
                // Optional: Delete old image if it exists
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
    public function updateSettings(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $username = $request->request->get('username');
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $imageFile = $request->files->get('profile_image');

        if ($username) $user->setPrenom($username);
        if ($email) $user->setEmail($email);
        
        if ($password && $password === $request->request->get('password_confirm')) {
            // In a real app, use UserPasswordHasherInterface
            $user->setPassword($password); 
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
            } catch (FileException $e) {}
        }

        $entityManager->flush();
        $this->addFlash('success', 'Settings updated!');

        return $this->redirectToRoute('app_settings');
    }

    #[Route('/settings', name: 'app_settings', priority: 10)]
    public function settings(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // Logic for settings update will go here
        return $this->render('user/settings.html.twig', [
            'user' => $user,
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
