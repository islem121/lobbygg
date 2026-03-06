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
use App\Entity\Notification;
use App\Repository\ProductRepository;
use App\Repository\ContractRepository;
use App\Repository\SponsorRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Address;

use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\GeminiService;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    public function sponsorRequests(
        DocumentRepository $documentRepository, 
        SponsorRepository $sponsorRepository,
        ContractRepository $contractRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');
        
        $user = $this->getUser();
        $requests = $documentRepository->findBySponsor($user);
        $offersCount = count($sponsorRepository->findBy(['sponsor' => $user]));

        // Statistiques Sponsor
        $total = count($requests);
        $acceptedCount = count(array_filter($requests, fn($r) => $r->getStatus() === 'accepté'));
        $acceptanceRate = $total > 0 ? round(($acceptedCount / $total) * 100, 2) : 0.0;

        // Contrats actifs et budget investi (somme des montants des offres liées aux contrats)
        $contracts = $contractRepository->findBy(['sponsor' => $user]);
        $activeContracts = count($contracts);
        $budgetInvested = 0.0;
        foreach ($contracts as $contract) {
            $offer = $contract->getRequest()->getOffer();
            if ($offer && $offer->getAmount()) {
                $budgetInvested += (float) $offer->getAmount();
            }
        }

        return $this->render('front/modules/sponsor_requests.html.twig', [
            'page' => 'sponsor_requests',
            'requests' => $requests,
            'offersCount' => $offersCount,
            'stats' => [
                'total' => $total,
                'accepted' => $acceptedCount,
                'acceptanceRate' => $acceptanceRate,
                'activeContracts' => $activeContracts,
                'budgetInvested' => $budgetInvested
            ]
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
        $this->addFlash('success', 'Commande créée avec succès.');
        return $this->redirectToRoute('front_marketplace');
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
        $document->setNomClient($this->getUser()->getNom() . ' ' . $this->getUser()->getPrenom());
        $document->setEmailClient($this->getUser()->getEmail());
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
        // Ne pas forcer le nom par défaut si on veut laisser le sponsor le saisir
        // $sponsor->setNomSociete($user->getNom() . ' ' . $user->getPrenom());
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
        SponsorRepository $sponsorRepository,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');

        $sponsor = $sponsorRepository->find($id);
        if (!$sponsor) {
            throw $this->createNotFoundException('Offre non trouvée');
        }

        $document = new Document();
        $document->setClient($this->getUser());
        $document->setOffer($sponsor);
        $document->setNomClient($this->getUser()->getNom() . ' ' . $this->getUser()->getPrenom());
        $document->setEmailClient($this->getUser()->getEmail());
        
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($httpRequest);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $dossierFile */
            $dossierFile = $form->get('dossierFile')->getData();

            if ($dossierFile) {
                $originalFilename = pathinfo($dossierFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$dossierFile->guessExtension();

                try {
                    $dossierFile->move(
                        $this->getParameter('sponsoring_directory'),
                        $newFilename
                    );
                    $document->setDossier($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement du dossier');
                }
            }

            $entityManager->persist($document);

            // Notification: nouvelle demande -> notifier le sponsor propriétaire de l'offre
            $notif = new Notification();
            $notif->setUser($sponsor->getSponsor()); // destinataire: sponsor
            $notif->setActor($this->getUser()); // acteur: client
            $notif->setType('doc_received');
            $notif->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($notif);

            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de sponsoring a été envoyée avec succès !');
        } else {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $errorMessage = 'Une erreur est survenue lors de l\'envoi de votre demande.';
            if (!empty($errors)) {
                $errorMessage .= ' : ' . implode(' ', $errors);
            }
            $this->addFlash('error', $errorMessage);
        }

        return $this->redirectToRoute('front_sponsoring');
    }

    #[Route('/sponsoring/document/{id}/status/{status}', name: 'front_document_update_status')]
    public function updateDocumentStatus(
        int $id, 
        string $status, 
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
        ContractRepository $contractRepository,
        MailerInterface $mailer
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
                $targetContract = $existingContract;
                if (!$existingContract) {
                    $newContract = new Contract();
                    $newContract->setRequest($document);
                    $newContract->setSponsor($this->getUser());
                    $newContract->setClient($document->getClient());
                    
                    $clientName = $document->getNomClient() ?: ($document->getClient()->getNom() . " " . $document->getClient()->getPrenom());
                    $newContract->setContent("Contrat de sponsoring entre " . $document->getOffer()->getNomSociete() . " et " . $clientName . ".");
                    
                    $entityManager->persist($newContract);
                    $entityManager->flush(); // Flush ici pour obtenir l'ID
                    $targetContract = $newContract;

                    // Notification: Contrat créé -> notifier sponsor
                    $notifContract = new Notification();
                    $notifContract->setUser($this->getUser());
                    $notifContract->setActor($document->getClient());
                    $notifContract->setType('contract_created');
                    $notifContract->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($notifContract);
                }

                if ($targetContract) {
                    $printUrl = $this->generateUrl('front_contract_print', ['id' => $targetContract->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    
                    // Récupération de l'expéditeur (priorité: .env MAILER_FROM > Sponsor Email > Gmail de secours)
                    $fromEmail = $this->getParameter('mailer_from') ?: ($this->getUser()->getEmail() ?: 'ammarsiwar59@gmail.com');
                    $toEmail = $document->getEmailClient() ?: $document->getClient()->getEmail();
                    
                    $email = (new TemplatedEmail())
                        ->from(new Address($fromEmail, 'Lobby.gg Sponsoring'))
                        ->to($toEmail)
                        ->subject('Votre contrat de sponsoring #CTR-' . sprintf('%04d', $targetContract->getId()))
                        ->htmlTemplate('emails/contract_email.html.twig')
                        ->context([
                            'contract' => $targetContract,
                            'printUrl' => $printUrl,
                        ]);
                    $mailer->send($email);
                }
            }
            
            // Notifications: dossier accepté/refusé -> notifier joueur (client)
            if (in_array($status, ['accepté', 'refusé'])) {
                $notif = new Notification();
                $notif->setUser($document->getClient());
                $notif->setActor($this->getUser());
                $notif->setType($status === 'accepté' ? 'doc_accepted' : 'doc_refused');
                $notif->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($notif);
            }
            
            $entityManager->flush();
            $this->addFlash('success', 'Le statut du contrat a été mis à jour : ' . $status);
        }

        return $this->redirectToRoute('front_sponsor_requests');
    }

    #[Route('/sponsoring/document/analyze/{id}', name: 'front_document_ai_analyze', methods: ['GET'])]
    public function analyzeDocument(int $id, DocumentRepository $documentRepository, GeminiService $geminiService): JsonResponse
    {
        // On essaye de trouver le document
        $document = $documentRepository->find($id);

        if (!$document) {
            return new JsonResponse(['error' => 'Document introuvable (ID: '.$id.')'], 404);
        }

        // Vérification de sécurité
        if ($document->getOffer()->getSponsor() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }

        try {
            $result = $geminiService->analyzeDossier(
                $document->getOffer()->getNomSociete(),
                $document->getMotivation() ?? '',
                $document->getMessage() ?? ''
            );
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'analyse : ' . $e->getMessage()], 500);
        }
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
                
                $clientName = $doc->getNomClient() ?: ($doc->getClient()->getNom() . " " . $doc->getClient()->getPrenom());
                $contract->setContent("Contrat de sponsoring récupéré automatiquement pour " . $doc->getOffer()->getNomSociete() . " (" . $clientName . ").");
                
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

    #[Route('/contracts/{id}/print', name: 'front_contract_print')]
    public function printContract(
        int $id,
        ContractRepository $contractRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $contract = $contractRepository->find($id);
        if (!$contract) {
            throw $this->createNotFoundException('Contrat non trouvé');
        }
        if ($contract->getSponsor() !== $user && $contract->getClient() !== $user) {
            throw $this->createAccessDeniedException('Accès refusé à ce contrat');
        }
        return $this->render('front/contract_print.html.twig', [
            'contract' => $contract,
        ]);
    }

    #[Route('/contracts/{id}/sign', name: 'front_contract_sign', methods: ['POST'])]
    public function signContract(
        int $id,
        Request $request,
        ContractRepository $contractRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $contract = $contractRepository->find($id);
        if (!$contract) {
            return new JsonResponse(['error' => 'Contrat non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $signature = $data['signature'] ?? null;
        $role = $data['role'] ?? null;

        if (!$signature || !$role) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }

        if ($role === 'sponsor' && $contract->getSponsor() === $user) {
            $contract->setSponsorSignature($signature);
        } elseif ($role === 'client' && $contract->getClient() === $user) {
            $contract->setClientSignature($signature);
        } else {
            return new JsonResponse(['error' => 'Action non autorisée'], 403);
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/contracts/{id}/send-email', name: 'front_contract_send_email', methods: ['POST', 'GET'])]
    public function sendContractEmail(
        int $id,
        ContractRepository $contractRepository,
        MailerInterface $mailer
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $contract = $contractRepository->find($id);
        if (!$contract) {
            throw $this->createNotFoundException('Contrat non trouvé');
        }
        if ($contract->getSponsor() !== $user) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        $printUrl = $this->generateUrl('front_contract_print', ['id' => $contract->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // Récupération de l'expéditeur (priorité: .env MAILER_FROM > Sponsor Email > Gmail de secours)
        $fromEmail = $this->getParameter('mailer_from') ?: ($contract->getSponsor()->getEmail() ?: 'ammarsiwar59@gmail.com');
        $toEmail = $contract->getRequest()->getEmailClient() ?: $contract->getClient()->getEmail();
        
        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, 'Lobby.gg Sponsoring'))
            ->to($toEmail)
            ->subject('Votre contrat de sponsoring #CTR-' . sprintf('%04d', $contract->getId()))
            ->htmlTemplate('emails/contract_email.html.twig')
            ->context([
                'contract' => $contract,
                'printUrl' => $printUrl,
            ]);
        $mailer->send($email);
        $this->addFlash('success', 'Contrat envoyé par email au client.');
        return $this->redirectToRoute('front_contracts');
    }

    #[Route('/contracts/{id}/analyze', name: 'front_contract_analyze_ai', methods: ['POST'])]
    public function analyzeContractAI(
        int $id,
        ContractRepository $contractRepository,
        GeminiService $geminiService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $contract = $contractRepository->find($id);
        if (!$contract) {
            return new JsonResponse(['error' => 'Contrat non trouvé'], 404);
        }

        // Seul le client peut lancer cette analyse spécifique
        if ($contract->getClient() !== $user) {
            return new JsonResponse(['error' => 'Seul le client peut analyser le contrat.'], 403);
        }

        $analysis = $geminiService->analyzeContract([
            'sponsor_name' => $contract->getSponsor()->getNom() . ' ' . $contract->getSponsor()->getPrenom(),
            'client_name' => $contract->getClient()->getNom() . ' ' . $contract->getClient()->getPrenom(),
            'content' => $contract->getContent(),
            'has_sponsor_signature' => !empty($contract->getSponsorSignature()),
            'has_client_signature' => !empty($contract->getClientSignature()),
        ]);

        return new JsonResponse($analysis);
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
                
                $clientName = $document->getNomClient() ?: ($document->getClient()->getNom() . " " . $document->getClient()->getPrenom());
                $contract->setContent("Contrat de sponsoring historique récupéré pour " . $document->getOffer()->getNomSociete() . " (" . $clientName . ").");
                
                $contract->setCreatedAt($document->getCreatedAt()); // Garder la date originale
                
                $entityManager->persist($contract);
                $count++;
            }
        }

        $entityManager->flush();
        $this->addFlash('success', "$count contrats historiques ont été récupérés et stockés.");

        return $this->redirectToRoute('front_contracts');
    }
}
