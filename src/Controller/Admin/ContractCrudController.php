<?php

namespace App\Controller\Admin;

use App\Entity\Contract;
use App\Form\ContractType;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/contract')]
#[IsGranted('ROLE_ADMIN')]
class ContractCrudController extends AbstractController
{
    #[Route('/', name: 'app_admin_contract_index', methods: ['GET'])]
    public function index(ContractRepository $contractRepository): Response
    {
        return $this->render('admin/contract/index.html.twig', [
            'contracts' => $contractRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_contract_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contract = new Contract();
        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contract);
            $entityManager->flush();

            $this->addFlash('success', 'Contrat créé avec succès.');
            return $this->redirectToRoute('app_admin_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/contract/new.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_contract_show', methods: ['GET'])]
    public function show(Contract $contract): Response
    {
        return $this->render('admin/contract/show.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_contract_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContractType::class, $contract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Contrat mis à jour avec succès.');
            return $this->redirectToRoute('app_admin_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/contract/edit.html.twig', [
            'contract' => $contract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_contract_delete', methods: ['POST'])]
    public function delete(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contract->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contract);
            $entityManager->flush();
            $this->addFlash('success', 'Contrat supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_contract_index', [], Response::HTTP_SEE_OTHER);
    }
}
