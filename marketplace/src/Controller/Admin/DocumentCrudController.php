<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\DocumentRepository;
use App\Service\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/document')]
#[IsGranted('ROLE_ADMIN')]
class DocumentCrudController extends AbstractController
{
    #[Route('/', name: 'app_admin_document_index', methods: ['GET'])]
    public function index(Request $request, DocumentRepository $documentRepository, PaginatorService $paginatorService): Response
    {
        $page = $request->query->getInt('page', 1);
        $pagination = $paginatorService->paginate($documentRepository->findAllQuery(), $page, 10);

        return $this->render('admin/document/index.html.twig', [
            'documents' => $pagination['items'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'current_page' => $pagination['current_page'],
        ]);
    }

    #[Route('/search', name: 'app_admin_document_search', methods: ['GET'])]
    public function search(Request $request, DocumentRepository $documentRepository): Response
    {
        $query = $request->query->get('q', '');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');
        
        $documents = $documentRepository->searchDocuments($query, $sort, $direction);

        return $this->render('admin/document/_list.html.twig', [
            'documents' => $documents,
        ]);
    }

    #[Route('/stats/data', name: 'app_admin_document_stats_data', methods: ['GET'])]
    public function statsData(DocumentRepository $documentRepository): Response
    {
        $stats = $documentRepository->getCountByStatus();
        return $this->json($stats);
    }

    #[Route('/{id}', name: 'app_admin_document_show', methods: ['GET'])]
    public function show(Document $document): Response
    {
        return $this->render('admin/document/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_document_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Document mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_document_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_document_delete', methods: ['POST'])]
    public function delete(Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            $entityManager->remove($document);
            $entityManager->flush();
            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_document_index', [], Response::HTTP_SEE_OTHER);
    }
}
