<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $q = $request->query->get('q');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'ASC');

        // Whitelist allowed sort fields to prevent SQL injection
        $allowedSorts = ['id', 'username', 'nom', 'prenom', 'email', 'dateNaissance', 'genre', 'origin', 'telephone', 'createdAt'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'id';
        }

        // Whitelist allowed directions
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        if ($q) {
            $users = $userRepository->searchNonAdmins($q, $sort, $direction);
        } else {
            $users = $userRepository->findAllNonAdmins($sort, $direction);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/user/_table_body.html.twig', [
                'users' => $users,
            ]);
        }

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'currentSort' => $sort,
            'currentDirection' => $direction,
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe
            $plainPassword = $user->getPassword();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher): Response
    {
        // On stocke l'ancien mot de passe pour ne pas l'écraser s'il n'est pas modifié
        $oldPassword = $user->getPassword();
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $user->getPassword();
            
            // Si le mot de passe a été changé (différent du hachage précédent)
            if ($newPassword && $newPassword !== $oldPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            } else {
                // On remet l'ancien mot de passe haché si le champ est resté identique ou vide
                $user->setPassword($oldPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Route temporaire pour réparer les mots de passe en clair
     */
    #[Route('/repair-passwords', name: 'app_admin_repair_passwords')]
    public function repairPasswords(EntityManagerInterface $entityManager, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();
        $count = 0;

        foreach ($users as $user) {
            $currentPassword = $user->getPassword();
            
            // Si le mot de passe ne ressemble pas à un hachage (ex: ne commence pas par $)
            if ($currentPassword && strpos($currentPassword, '$') !== 0) {
                $user->setPassword($passwordHasher->hashPassword($user, $currentPassword));
                $count++;
            }
        }

        if ($count > 0) {
            $entityManager->flush();
            $this->addFlash('success', "$count mot(s) de passe ont été réparés et hachés.");
        } else {
            $this->addFlash('info', "Tous les mots de passe sont déjà correctement hachés.");
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/block', name: 'app_admin_user_block', methods: ['POST'])]
    public function block(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('block'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsBlocked(!$user->isBlocked());
            $entityManager->flush();
            
            $status = $user->isBlocked() ? 'bloqué' : 'débloqué';
            $this->addFlash('success', "L'utilisateur a été $status avec succès.");
        }

        return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
