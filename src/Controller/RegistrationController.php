<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $user = new User();

        // 1️⃣ Création du formulaire
        $form = $this->createForm(RegistrationFormType::class, $user);

        // 2️⃣ Liaison requête → formulaire
        $form->handleRequest($request);

        // 3️⃣ Validation Symfony
        if ($form->isSubmitted() && $form->isValid()) {

            // 4️⃣ Hash du mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );
            $user->setPassword($hashedPassword);

            // 5️⃣ Insertion DB
            $em->persist($user);
            $em->flush();

            // 6️⃣ Confirmation
            $this->addFlash('success', 'Compte créé avec succès');

            return $this->redirectToRoute('app_login');
        }
         
        // 7️⃣ Affichage (avec erreurs si existantes)
        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}