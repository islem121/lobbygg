<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Sponsor;
use App\Entity\Document;
use App\Form\SponsorType;
use App\Form\DocumentType;
use App\Entity\Contract;
use App\Repository\ContractRepository;
use App\Repository\SponsorRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class PageController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function home(): Response
    {
        // Tout le monde sauf admin va vers app_home
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/sponsoring/create', name: 'front_sponsoring_create')]
    public function createSponsor(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        $sponsor = new Sponsor();
        $user = $this->getUser();
        
        // On pré-remplit le nom de la société avec le nom de l'utilisateur
        $sponsor->setNomSociete($user->getNom() . ' ' . $user->getPrenom());
        $sponsor->setSponsor($user);

        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Votre offre de sponsoring a été créée avec succès !');

            return $this->redirectToRoute('front_sponsoring');
        }

        return $this->render('front/modules/create_offre.html.twig', [
            'form' => $form->createView(),
            'page' => 'create_offre'
        ]);
    }

    #[Route('/sponsoring/requests', name: 'front_sponsor_requests')]
    public function sponsorRequests(DocumentRepository $documentRepository, SponsorRepository $sponsorRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');
        
        $user = $this->getUser();
        $requests = $documentRepository->findBySponsor($user);
        $offersCount = count($sponsorRepository->findBy(['sponsor' => $user]));

        return $this->render('front/modules/sponsor_requests.html.twig', [
            'page' => 'sponsor_requests',
            'requests' => $requests,
            'offersCount' => $offersCount
        ]);
    }

    #[Route('/my-sponsoring-requests', name: 'front_client_requests')]
    public function clientRequests(DocumentRepository $documentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        
        $user = $this->getUser();
        $requests = $documentRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);
        
        // Calculer les statistiques simples pour le client
        $stats = [
            'total' => count($requests),
            'accepted' => count(array_filter($requests, fn($r) => $r->getStatus() === 'accepté')),
            'pending' => count(array_filter($requests, fn($r) => $r->getStatus() === 'en attente')),
        ];

        return $this->render('front/modules/client_requests.html.twig', [
            'page' => 'client_requests',
            'requests' => $requests,
            'stats' => $stats
        ]);
    }

    #[Route('/users', name: 'front_users')]
    public function users(): Response
    {
        // Revert: /users is back to its original module.
        return $this->render('front/modules/users.html.twig', [
            'page' => 'users',
        ]);
    }

    #[Route('/marketplace', name: 'front_marketplace')]
    public function marketplace(): Response
    {
        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace',
        ]);
    }

    #[Route('/tournaments', name: 'front_tournaments')]
    public function tournaments(): Response
    {
        return $this->render('front/modules/tournaments.html.twig', [
            'page' => 'tournaments',
        ]);
    }

    #[Route('/sponsoring', name: 'front_sponsoring')]
    public function sponsoring(SponsorRepository $sponsorRepository): Response
    {
        $offers = $sponsorRepository->findAll();
        
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);

        return $this->render('front/modules/sponsoring.html.twig', [
            'page' => 'sponsoring',
            'offers' => $offers,
            'form' => $form->createView()
        ]);
    }

    #[Route('/sponsoring/request/{id}', name: 'front_sponsoring_request_submit', methods: ['POST'])]
    public function submitSponsoringRequest(
        int $id, 
        Request $httpRequest, 
        EntityManagerInterface $entityManager,
        SponsorRepository $sponsorRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');

        $sponsor = $sponsorRepository->find($id);
        if (!$sponsor) {
            throw $this->createNotFoundException('Offre non trouvée');
        }

        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($httpRequest);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $document->setClient($user);
            $document->setOffer($sponsor);
            
            $entityManager->persist($document);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de sponsoring a été envoyée avec succès !');
        } else {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre demande.');
        }

        return $this->redirectToRoute('front_sponsoring');
    }

    #[Route('/sponsoring/document/{id}/status/{status}', name: 'front_document_update_status')]
    public function updateDocumentStatus(
        int $id, 
        string $status, 
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
        ContractRepository $contractRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        $document = $documentRepository->find($id);
        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        // Vérifier que le sponsor est bien le propriétaire de l'offre
        if ($document->getOffer()->getSponsor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce document');
        }

        $validStatuses = ['accepté', 'refusé', 'en attente'];
        if (in_array($status, $validStatuses)) {
            $document->setStatus($status);
            
            // Si accepté, créer un contrat s'il n'existe pas déjà
            if ($status === 'accepté') {
                $existingContract = $contractRepository->findOneBy(['request' => $document]);
                if (!$existingContract) {
                    $contract = new Contract();
                    $contract->setRequest($document);
                    $contract->setSponsor($this->getUser());
                    $contract->setClient($document->getClient());
                    $contract->setContent("Contrat de sponsoring entre " . $document->getOffer()->getNomSociete() . " et " . $document->getClient()->getNom() . " " . $document->getClient()->getPrenom() . ".");
                    $entityManager->persist($contract);
                }
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Le statut du contrat a été mis à jour : ' . $status);
        }

        return $this->redirectToRoute('front_sponsor_requests');
    }

    #[Route('/contracts', name: 'front_contracts')]
    public function listContracts(
        ContractRepository $contractRepository, 
        DocumentRepository $documentRepository, 
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // --- SYNCHRONISATION AUTOMATIQUE DES ANCIENS CONTRATS ---
        // On récupère toutes les demandes acceptées liées à l'utilisateur qui n'ont pas encore de contrat
        $criteria = in_array('ROLE_SPONSOR', $user->getRoles()) ? [] : ['client' => $user];
        $acceptedDocs = $documentRepository->findBy(array_merge($criteria, ['status' => 'accepté']));
        
        $syncCount = 0;
        foreach ($acceptedDocs as $doc) {
            // Pour le sponsor, on vérifie si l'offre lui appartient
            if (in_array('ROLE_SPONSOR', $user->getRoles()) && $doc->getOffer()->getSponsor() !== $user) {
                continue;
            }

            $existing = $contractRepository->findOneBy(['request' => $doc]);
            if (!$existing) {
                $contract = new Contract();
                $contract->setRequest($doc);
                $contract->setSponsor($doc->getOffer()->getSponsor());
                $contract->setClient($doc->getClient());
                $contract->setContent("Contrat de sponsoring récupéré automatiquement pour " . $doc->getOffer()->getNomSociete() . ".");
                $contract->setCreatedAt($doc->getCreatedAt());
                $entityManager->persist($contract);
                $syncCount++;
            }
        }
        
        if ($syncCount > 0) {
            $entityManager->flush();
        }
        // --------------------------------------------------------

        if (in_array('ROLE_SPONSOR', $user->getRoles())) {
            $contracts = $contractRepository->findBy(['sponsor' => $user], ['createdAt' => 'DESC']);
        } else {
            $contracts = $contractRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);
        }

        return $this->render('front/modules/contracts.html.twig', [
            'page' => 'contracts',
            'contracts' => $contracts,
            'isSponsor' => in_array('ROLE_SPONSOR', $user->getRoles())
        ]);
    }

    #[Route('/contracts/migrate', name: 'front_contracts_migrate')]
    public function migrateContracts(
        DocumentRepository $documentRepository, 
        ContractRepository $contractRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        $acceptedDocuments = $documentRepository->findBy(['status' => 'accepté']);
        $count = 0;

        foreach ($acceptedDocuments as $document) {
            $existingContract = $contractRepository->findOneBy(['request' => $document]);
            if (!$existingContract) {
                $contract = new Contract();
                $contract->setRequest($document);
                $contract->setSponsor($document->getOffer()->getSponsor());
                $contract->setClient($document->getClient());
                $contract->setContent("Contrat de sponsoring historique récupéré pour " . $document->getOffer()->getNomSociete() . ".");
                $contract->setCreatedAt($document->getCreatedAt()); // Garder la date originale
                
                $entityManager->persist($contract);
                $count++;
            }
        }

        $entityManager->flush();
        $this->addFlash('success', "$count contrats historiques ont été récupérés et stockés.");

        return $this->redirectToRoute('front_contracts');
    }

    #[Route('/blog', name: 'front_blog')]
    public function blog(): Response
    {
        // Social frontoffice: render the news feed
        return $this->render('front/feed.html.twig', ['page' => 'blog']);
    }
}
