<?php

namespace App\Controller\Admin;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sponsor')]
#[IsGranted('ROLE_ADMIN')]
class SponsorCrudController extends AbstractController
{
    #[Route('/', name: 'app_admin_sponsor_index', methods: ['GET'])]
    public function index(SponsorRepository $sponsorRepository): Response
    {
        return $this->render('admin/sponsor/index.html.twig', [
            'sponsors' => $sponsorRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_sponsor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sponsor = new Sponsor();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Sponsor créé avec succès.');
            return $this->redirectToRoute('app_admin_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sponsor/new.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_sponsor_show', methods: ['GET'])]
    public function show(Sponsor $sponsor): Response
    {
        return $this->render('admin/sponsor/show.html.twig', [
            'sponsor' => $sponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_sponsor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Sponsor mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/sponsor/edit.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_sponsor_delete', methods: ['POST'])]
    public function delete(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sponsor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sponsor);
            $entityManager->flush();
            $this->addFlash('success', 'Sponsor supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_sponsor_index', [], Response::HTTP_SEE_OTHER);
    }
}
