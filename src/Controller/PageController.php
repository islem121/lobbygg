<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Sponsor;
use App\Entity\Document;
use App\Form\SponsorType;
use App\Form\DocumentType;
use App\Entity\Contract;
use App\Repository\ProductRepository;
use App\Repository\ContractRepository;
use App\Repository\SponsorRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PageController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function home(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Tout le monde sauf admin va vers app_home
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }

        return $this->redirectToRoute('app_home');
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
            'accepted' => count(array_filter($requests, fn($r) => $r->getStatus() === 'acceptÃ©')),
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
    public function marketplace(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace',
            'products' => $products,
        ]);
    }

    #[Route('/marketplace/add/{id}', name: 'front_marketplace_add', methods: ['GET'])]
    public function addToCart(int $id, Request $request, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $cart[$id] = ($cart[$id] ?? 0) + 1;
        $session->set('cart', $cart);
        $this->addFlash('success', sprintf('"%s" ajoutÃ© au panier', $product->getName()));
        return $this->redirectToRoute('front_marketplace');
    }

    #[Route('/marketplace/cart', name: 'front_marketplace_cart')]
    public function viewCart(Request $request, ProductRepository $productRepository): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $items = [];
        $total = 0.0;
        foreach ($cart as $productId => $qty) {
            $p = $productRepository->find($productId);
            if ($p) {
                $lineTotal = $p->getPrice() * $qty;
                $items[] = [
                    'product' => $p,
                    'qty' => $qty,
                    'lineTotal' => $lineTotal,
                ];
                $total += $lineTotal;
            }
        }
        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace',
            'products' => $productRepository->findBy([], ['createdAt' => 'DESC']),
            'cartItems' => $items,
            'cartTotal' => $total,
        ]);
    }

    #[Route('/marketplace/checkout', name: 'front_marketplace_checkout')]
    public function checkout(Request $request, EntityManagerInterface $em, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('front_marketplace');
        }
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        foreach ($cart as $productId => $qty) {
            $product = $productRepository->find($productId);
            if (!$product) {
                continue;
            }
            $order = new \App\Entity\Order();
            $order->setUser($user);
            $order->setProduct($product);
            $order->setQuantity($qty);
            $order->setOrderDate(new \DateTimeImmutable());
            $order->setStatus('pending');
            $em->persist($order);
        }
        $em->flush();
        $session->remove('cart');
        $this->addFlash('success', 'Commande crÃ©Ã©e avec succÃ¨s.');
        return $this->redirectToRoute('front_marketplace');
    }

    #[Route('/tournaments', name: 'front_tournaments')]
    public function tournaments(): Response
    {
        return $this->redirectToRoute('tournament_frontoffice');
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

    #[Route('/sponsoring/new', name: 'front_sponsoring_new', methods: ['GET', 'POST'])]
    public function createSponsor(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $sponsor = new Sponsor();
        $sponsor->setNomSociete($user->getNom() . ' ' . $user->getPrenom());
        $sponsor->setSponsor($user);

        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Votre offre de sponsoring a Ã©tÃ© crÃ©Ã©e avec succÃ¨s !');

            return $this->redirectToRoute('front_sponsoring_my_offers');
        }

        return $this->render('front/modules/create_offre.html.twig', [
            'form' => $form->createView(),
            'page' => 'create_offre'
        ]);
    }

    #[Route('/sponsoring/my-offers', name: 'front_sponsoring_my_offers')]
    public function myOffers(Request $request, SponsorRepository $sponsorRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Logique de crÃ©ation (pour la modale)
        $newSponsor = new Sponsor();
        $newSponsor->setNomSociete($user->getNom() . ' ' . $user->getPrenom());
        $newSponsor->setSponsor($user);

        $form = $this->createForm(SponsorType::class, $newSponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($newSponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Votre offre de sponsoring a Ã©tÃ© crÃ©Ã©e avec succÃ¨s !');

            return $this->redirectToRoute('front_sponsoring_my_offers');
        }

        $offers = $sponsorRepository->findBy(['sponsor' => $user]);

        return $this->render('front/modules/my_offers.html.twig', [
            'page' => 'my_offers',
            'offers' => $offers,
            'form' => $form->createView()
        ]);
    }

    #[Route('/sponsoring/edit/{id}', name: 'front_sponsoring_edit')]
    public function editSponsor(int $id, Request $request, SponsorRepository $sponsorRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        $sponsor = $sponsorRepository->find($id);
        if (!$sponsor) {
            throw $this->createNotFoundException('Offre non trouvÃ©e');
        }

        // VÃ©rifier que le sponsor est bien le propriÃ©taire de l'offre
        if ($sponsor->getSponsor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'Ãªtes pas autorisÃ© Ã  modifier cette offre');
        }

        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre offre de sponsoring a Ã©tÃ© mise Ã  jour avec succÃ¨s !');

            return $this->redirectToRoute('front_sponsoring_my_offers');
        }

        return $this->render('front/modules/create_offre.html.twig', [
            'form' => $form->createView(),
            'page' => 'edit_offre',
            'sponsor' => $sponsor
        ]);
    }

    #[Route('/sponsoring/delete/{id}', name: 'front_sponsoring_delete', methods: ['POST'])]
    public function deleteSponsor(int $id, Request $request, SponsorRepository $sponsorRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        $sponsor = $sponsorRepository->find($id);
        if (!$sponsor) {
            throw $this->createNotFoundException('Offre non trouvÃ©e');
        }

        // VÃ©rifier que le sponsor est bien le propriÃ©taire de l'offre
        if ($sponsor->getSponsor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'Ãªtes pas autorisÃ© Ã  supprimer cette offre');
        }

        if ($this->isCsrfTokenValid('delete'.$sponsor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sponsor);
            $entityManager->flush();
            $this->addFlash('success', 'Votre offre de sponsoring a Ã©tÃ© supprimÃ©e.');
        }

        return $this->redirectToRoute('front_sponsoring_my_offers');
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
            throw $this->createNotFoundException('Offre non trouvÃ©e');
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

            $this->addFlash('success', 'Votre demande de sponsoring a Ã©tÃ© envoyÃ©e avec succÃ¨s !');
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
            throw $this->createNotFoundException('Document non trouvÃ©');
        }

        // VÃ©rifier que le sponsor est bien le propriÃ©taire de l'offre
        if ($document->getOffer()->getSponsor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'Ãªtes pas autorisÃ© Ã  modifier ce document');
        }

        $validStatuses = ['acceptÃ©', 'refusÃ©', 'en attente'];
        if (in_array($status, $validStatuses)) {
            $document->setStatus($status);
            
            // Si acceptÃ©, crÃ©er un contrat s'il n'existe pas dÃ©jÃ 
            if ($status === 'acceptÃ©') {
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
            $this->addFlash('success', 'Le statut du contrat a Ã©tÃ© mis Ã  jour : ' . $status);
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
        // On rÃ©cupÃ¨re toutes les demandes acceptÃ©es liÃ©es Ã  l'utilisateur qui n'ont pas encore de contrat
        $criteria = in_array('ROLE_SPONSOR', $user->getRoles()) ? [] : ['client' => $user];
        $acceptedDocs = $documentRepository->findBy(array_merge($criteria, ['status' => 'acceptÃ©']));
        
        $syncCount = 0;
        foreach ($acceptedDocs as $doc) {
            // Pour le sponsor, on vÃ©rifie si l'offre lui appartient
            if (in_array('ROLE_SPONSOR', $user->getRoles()) && $doc->getOffer()->getSponsor() !== $user) {
                continue;
            }

            $existing = $contractRepository->findOneBy(['request' => $doc]);
            if (!$existing) {
                $contract = new Contract();
                $contract->setRequest($doc);
                $contract->setSponsor($doc->getOffer()->getSponsor());
                $contract->setClient($doc->getClient());
                $contract->setContent("Contrat de sponsoring rÃ©cupÃ©rÃ© automatiquement pour " . $doc->getOffer()->getNomSociete() . ".");
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

        $acceptedDocuments = $documentRepository->findBy(['status' => 'acceptÃ©']);
        $count = 0;

        foreach ($acceptedDocuments as $document) {
            $existingContract = $contractRepository->findOneBy(['request' => $document]);
            if (!$existingContract) {
                $contract = new Contract();
                $contract->setRequest($document);
                $contract->setSponsor($document->getOffer()->getSponsor());
                $contract->setClient($document->getClient());
                $contract->setContent("Contrat de sponsoring historique rÃ©cupÃ©rÃ© pour " . $document->getOffer()->getNomSociete() . ".");
                $contract->setCreatedAt($document->getCreatedAt()); // Garder la date originale
                
                $entityManager->persist($contract);
                $count++;
            }
        }

        $entityManager->flush();
        $this->addFlash('success', "$count contrats historiques ont Ã©tÃ© rÃ©cupÃ©rÃ©s et stockÃ©s.");

        return $this->redirectToRoute('front_contracts');
    }
}


