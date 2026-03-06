<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class TestUserController extends AbstractController
{
    #[Route('/test/user', name: 'test_user')]
    public function index(Request $request, Connection $connection): Response
    {
        $message = '';
        $users = [];
        
        // Si formulaire soumis
        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $bio = $request->request->get('bio');
            
            // Validation simple
            if (!empty($username) && !empty($email) && !empty($password)) {
                try {
                    // Hacher le mot de passe (en production, utilisez UserPasswordHasherInterface)
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertion dans la base
                    $connection->executeStatement(
                        'INSERT INTO user (username, email, password, bio, created_at) VALUES (?, ?, ?, ?, NOW())',
                        [$username, $email, $hashedPassword, $bio]
                    );
                    
                    $message = '✅ Utilisateur "' . htmlspecialchars($username) . '" ajouté avec succès !';
                    
                } catch (\Exception $e) {
                    $message = '❌ Erreur : ' . $e->getMessage();
                }
            } else {
                $message = '⚠️ Veuillez remplir tous les champs obligatoires';
            }
        }
        
        // Récupérer tous les utilisateurs
        try {
            $users = $connection->executeQuery(
                'SELECT id, username, email, bio, created_at FROM user ORDER BY created_at DESC'
            )->fetchAllAssociative();
        } catch (\Exception $e) {
            $users = [];
            $message .= '<br>⚠️ Erreur lecture : ' . $e->getMessage();
        }
        
        return $this->render('test_user/index.html.twig', [
            'message' => $message,
            'users' => $users,
        ]);
    }
    
    #[Route('/test/user/delete/{id}', name: 'test_user_delete')]
    public function delete($id, Connection $connection): Response
    {
        try {
            $connection->executeStatement('DELETE FROM user WHERE id = ?', [$id]);
            $this->addFlash('success', 'Utilisateur supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('test_user');
    }
}