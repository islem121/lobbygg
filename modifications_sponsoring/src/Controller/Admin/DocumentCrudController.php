<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\DocumentRepository;
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
    public function index(DocumentRepository $documentRepository): Response
    {
        return $this->render('admin/document/index.html.twig', [
            'documents' => $documentRepository->findAll(),
        ]);
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
