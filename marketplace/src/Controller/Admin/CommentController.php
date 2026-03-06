<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Service\PaginatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/comment')]
#[IsGranted('ROLE_ADMIN')]
class CommentController extends AbstractController
{
    #[Route('/', name: 'app_admin_comment_index', methods: ['GET'])]
    public function index(Request $request, CommentRepository $commentRepository, PaginatorService $paginatorService): Response
    {
        $page = $request->query->getInt('page', 1);
        $pagination = $paginatorService->paginate($commentRepository->findAllQuery(), $page, 15);

        return $this->render('admin/comment/index.html.twig', [
            'comments' => $pagination['items'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'current_page' => $pagination['current_page'],
        ]);
    }

    #[Route('/{id}', name: 'app_admin_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Le commentaire a été supprimé.');
        }

        return $this->redirectToRoute('app_admin_comment_index', [], Response::HTTP_SEE_OTHER);
    }
}
