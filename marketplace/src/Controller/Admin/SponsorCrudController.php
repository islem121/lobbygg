<?php

namespace App\Controller\Admin;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Repository\SponsorRepository;
use App\Service\PaginatorService;
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
    public function index(Request $request, SponsorRepository $sponsorRepository, PaginatorService $paginatorService): Response
    {
        $page = $request->query->getInt('page', 1);
        $pagination = $paginatorService->paginate($sponsorRepository->findAllQuery(), $page, 10);

        return $this->render('admin/sponsor/index.html.twig', [
            'sponsors' => $pagination['items'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'current_page' => $pagination['current_page'],
        ]);
    }

    #[Route('/search', name: 'app_admin_sponsor_search', methods: ['GET'])]
    public function search(Request $request, SponsorRepository $sponsorRepository): Response
    {
        $query = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');
        
        $sponsors = $sponsorRepository->searchSponsors($query, $sort, $direction);

        return $this->render('admin/sponsor/_list.html.twig', [
            'sponsors' => $sponsors,
        ]);
    }

    #[Route('/stats/data', name: 'app_admin_sponsor_stats_data', methods: ['GET'])]
    public function statsData(SponsorRepository $sponsorRepository): Response
    {
        $stats = $sponsorRepository->getRequestsCountByCompany();
        return $this->json($stats);
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
