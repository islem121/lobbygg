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
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\ContractRepository;
use App\Repository\SponsorRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/marketplace/compare', name: 'front_marketplace_compare', methods: ['POST'])]
    public function compareProducts(Request $request, ProductRepository $productRepository, \App\Service\AiComparisonService $aiComparisonService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $productIds = $data['productIds'] ?? [];

        if (count($productIds) < 2) {
            return $this->json(['error' => 'Veuillez sélectionner au moins deux produits.'], 400);
        }

        $products = $productRepository->findBy(['id' => $productIds]);

        if (count($products) < 2) {
            return $this->json(['error' => 'Produits introuvables.'], 404);
        }

        $comparisonHtml = $aiComparisonService->compareProducts($products);

        return $this->json(['html' => $comparisonHtml]);
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
    public function marketplace(Request $request, ProductRepository $productRepository, \App\Repository\OrderRepository $orderRepository): Response
    {
        $limit = 12; // Products per page
        $page = max(1, $request->query->getInt('page', 1));
        $offset = ($page - 1) * $limit;

        // Count total products
        $totalProducts = $productRepository->count([]);
        $totalPages = ceil($totalProducts / $limit);

        $products = $productRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        
        $user = $this->getUser();
        $orders = [];
        $myProducts = [];
        
        if ($user) {
            $orders = $orderRepository->findBy(['user' => $user], ['orderDate' => 'DESC']);
            $myProducts = $productRepository->findBy(['seller' => $user], ['createdAt' => 'DESC']);
        }

        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace',
            'products' => $products,
            'orders' => $orders,
            'myProducts' => $myProducts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/marketplace/sell', name: 'front_marketplace_sell', methods: ['GET', 'POST'])]
    public function sell(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $product = new Product();
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setSeller($user);
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Votre produit a été mis en vente avec succès !');
            return $this->redirectToRoute('front_marketplace');
        }

        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace_sell',
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    #[Route('/marketplace/my-products', name: 'front_marketplace_my_products')]
    public function myProducts(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $myProducts = $productRepository->findBy(['seller' => $this->getUser()], ['createdAt' => 'DESC']);

        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace_my_products',
            'myProducts' => $myProducts,
        ]);
    }

    #[Route('/marketplace/product/edit/{id}', name: 'front_marketplace_product_edit', methods: ['GET', 'POST'])]
    public function editProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if ($product->getSeller() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce produit.');
        }
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Produit mis à jour avec succès.');
            return $this->redirectToRoute('front_marketplace_my_products');
        }

        return $this->render('front/modules/marketplace.html.twig', [
            'page' => 'marketplace_sell',
            'form' => $form->createView(),
            'product' => $product,
            'is_edit' => true
        ]);
    }

    #[Route('/marketplace/product/delete/{id}', name: 'front_marketplace_product_delete', methods: ['POST'])]
    public function deleteProduct(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if ($product->getSeller() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce produit.');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé avec succès.');
        }

        return $this->redirectToRoute('front_marketplace_my_products');
    }

    #[Route('/marketplace/add/{id}', name: 'front_marketplace_add', methods: ['GET'])]
    public function addToCart(int $id, Request $request, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        if ($product->getStock() <= 0) {
            $this->addFlash('error', sprintf('Le produit "%s" est en rupture de stock.', $product->getName()));
            return $this->redirectToRoute('front_marketplace');
        }

        $session = $request->getSession();
        $cart = $session->get('cart', []);
        $currentQty = $cart[$id] ?? 0;

        if ($currentQty + 1 > $product->getStock()) {
            $this->addFlash('error', sprintf('Désolé, seulement %d unité(s) disponible(s) pour "%s".', $product->getStock(), $product->getName()));
            return $this->redirectToRoute('front_marketplace');
        }

        $cart[$id] = $currentQty + 1;
        $session->set('cart', $cart);
        $this->addFlash('success', sprintf('"%s" ajouté au panier', $product->getName()));
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
    public function checkout(Request $request, EntityManagerInterface $em, ProductRepository $productRepository, \App\Service\PaymentService $paymentService): Response
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

        $orders = [];
        // 1. Première vérification de tous les stocks avant de commencer
        foreach ($cart as $productId => $qty) {
            $product = $productRepository->find($productId);
            if (!$product) {
                $this->addFlash('error', 'Un produit dans votre panier n\'existe plus.');
                return $this->redirectToRoute('front_marketplace_cart');
            }
            if ($product->getStock() < $qty) {
                $this->addFlash('error', sprintf('Le stock pour "%s" est insuffisant (Disponible: %d).', $product->getName(), $product->getStock()));
                return $this->redirectToRoute('front_marketplace_cart');
            }
        }

        // 2. Création des commandes et mise à jour des stocks
        foreach ($cart as $productId => $qty) {
            $product = $productRepository->find($productId);
            
            // Créer la commande
            $order = new \App\Entity\Order();
            $order->setUser($user);
            $order->setProduct($product);
            $order->setQuantity($qty);
            $order->setOrderDate(new \DateTimeImmutable());
            $order->setStatus('pending');
            $em->persist($order);
            $orders[] = $order;

            // Diminuer le stock (temporairement, on pourrait aussi le faire après le paiement)
            $newStock = $product->getStock() - $qty;
            $product->setStock($newStock);
        }

        $em->flush();
        $session->remove('cart');

        // Redirect to a collective checkout session
        $checkoutUrl = $paymentService->createCheckoutSession($orders);
        return $this->redirect($checkoutUrl, 303);
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

            $this->addFlash('success', 'Votre offre de sponsoring a été créée avec succès !');

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

        // Logique de création (pour la modale)
        $newSponsor = new Sponsor();
        $newSponsor->setNomSociete($user->getNom() . ' ' . $user->getPrenom());
        $newSponsor->setSponsor($user);

        $form = $this->createForm(SponsorType::class, $newSponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($newSponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Votre offre de sponsoring a été créée avec succès !');

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
            throw $this->createNotFoundException('Offre non trouvée');
        }

        // Vérifier que le sponsor est bien le propriétaire de l'offre
        if ($sponsor->getSponsor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre');
        }

        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre offre de sponsoring a été mise à jour avec succès !');

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
            throw $this->createNotFoundException('Offre non trouvée');
        }

        // Vérifier que le sponsor est bien le propriétaire de l'offre
        if ($sponsor->getSponsor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette offre');
        }

        if ($this->isCsrfTokenValid('delete'.$sponsor->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sponsor);
            $entityManager->flush();
            $this->addFlash('success', 'Votre offre de sponsoring a été supprimée.');
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
            throw $this->createNotFoundException('Offre non trouvée');
        }

        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($httpRequest);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
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

    #[Route('/marketplace/search/ajax', name: 'front_marketplace_search_ajax', methods: ['GET'])]
    public function searchProductsAjax(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        $products = $productRepository->searchByName($query);
        
        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => number_format($product->getPrice(), 2, '.', ' '),
                'image' => $product->getImage(),
                'stock' => $product->getStock(),
                'category' => $product->getCategory() ? $product->getCategory()->getName() : 'Général',
                'url_add' => $this->generateUrl('front_marketplace_add', ['id' => $product->getId()]),
                'url_contact' => ($this->getUser() && $product->getSeller() && $product->getSeller() !== $this->getUser()) 
                    ? $this->generateUrl('app_messages_start', ['id' => $product->getId()]) 
                    : null
            ];
        }

        return $this->json($results);
    }
}
