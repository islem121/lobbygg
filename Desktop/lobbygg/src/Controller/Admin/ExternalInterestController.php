<?php

namespace App\Controller\Admin;

use App\Repository\ExternalTournamentInterestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ExternalInterestController extends AbstractController
{
    #[Route('/admin/external-interests', name: 'app_admin_external_interest_index', methods: ['GET'])]
    public function index(ExternalTournamentInterestRepository $interestRepository): Response
    {
        return $this->render('admin/external_interest/index.html.twig', [
            'interests' => $interestRepository->findRecentWithUser(500),
        ]);
    }
}
