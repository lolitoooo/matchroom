<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Negotiation;
use App\Entity\RoomType;
use App\Form\NegotiationFormType;
use App\Repository\BookingRepository;
use App\Repository\NegotiationRepository;
use App\Repository\RoomTypeRepository;
use App\Service\NegotiationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/negotiation')]
class NegotiationController extends AbstractController
{
    #[Route('/pending', name: 'app_negotiation_pending')]
    public function pendingNegotiations(NegotiationRepository $negotiationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }
        
        // Récupérer les négociations en attente pour les hôtels de l'utilisateur
        $pendingNegotiations = $negotiationRepository->findPendingForHotelier($user->getId());
        
        return $this->render('negotiation/pending.html.twig', [
            'negotiations' => $pendingNegotiations,
        ]);
    }
    
    #[Route('/respond/{id}/{action}', name: 'app_negotiation_respond')]
    public function respondToNegotiation(Negotiation $negotiation, string $action, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }
        
        // Vérifier que l'utilisateur est l'hôtelier
        if ($negotiation->getRoomType()->getHotel()->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à répondre à cette négociation.');
        }
        
        // Vérifier que la négociation est en attente
        if ($negotiation->getStatus() !== Negotiation::STATUS_PENDING) {
            $this->addFlash('error', 'Cette négociation n\'est plus en attente de réponse.');
            return $this->redirectToRoute('app_negotiation_pending');
        }
        
        if ($action === 'accept') {
            // Accepter l'offre
            $this->negotiationService->acceptNegotiation($negotiation, false);
            $this->addFlash('success', 'L\'offre a été acceptée avec succès.');
        } elseif ($action === 'reject') {
            // Rejeter l'offre
            $this->negotiationService->rejectNegotiation($negotiation, false);
            $this->addFlash('success', 'L\'offre a été refusée.');
        } elseif ($action === 'counter') {
            // Rediriger vers le formulaire de contre-offre
            return $this->redirectToRoute('app_negotiation_counter_offer', ['id' => $negotiation->getId()]);
        } else {
            $this->addFlash('error', 'Action non reconnue.');
        }
        
        return $this->redirectToRoute('app_negotiation_pending');
    }
    
    #[Route('/counter-offer/{id}', name: 'app_negotiation_counter_offer')]
    public function counterOffer(Negotiation $negotiation, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }
        
        // Vérifier que l'utilisateur est l'hôtelier
        if ($negotiation->getRoomType()->getHotel()->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à faire une contre-offre.');
        }
        
        // Vérifier que la négociation est en attente
        if ($negotiation->getStatus() !== Negotiation::STATUS_PENDING) {
            $this->addFlash('error', 'Cette négociation n\'est plus en attente de réponse.');
            return $this->redirectToRoute('app_negotiation_pending');
        }
        
        // Formulaire pour la contre-offre
        $form = $this->createFormBuilder()
            ->add('counterOfferPrice', NumberType::class, [
                'label' => 'Prix proposé',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan([
                        'value' => 0,
                        'message' => 'Le prix doit être supérieur à 0.'
                    ])
                ]
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $counterOfferPrice = $form->get('counterOfferPrice')->getData();
            
            // Faire une contre-offre
            $this->negotiationService->makeCounterOffer($negotiation, $counterOfferPrice, false);
            
            $this->addFlash('success', 'Votre contre-offre a été envoyée avec succès.');
            return $this->redirectToRoute('app_negotiation_pending');
        }
        
        return $this->render('negotiation/counter_offer.html.twig', [
            'negotiation' => $negotiation,
            'form' => $form->createView(),
        ]);
    }
    private NegotiationService $negotiationService;
    private EntityManagerInterface $entityManager;

    public function __construct(NegotiationService $negotiationService, EntityManagerInterface $entityManager)
    {
        $this->negotiationService = $negotiationService;
        $this->entityManager = $entityManager;
    }

    #[Route('/make-offer/{id}', name: 'app_make_offer')]
    public function makeOffer(Request $request, RoomType $roomType, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $hotel = $roomType->getHotel();
        $negotiation = new Negotiation();
        $negotiation->setClient($user);
        $negotiation->setRoomType($roomType);
        
        // Définir les dates par défaut
        $startDate = new \DateTime();
        $endDate = (new \DateTime())->modify('+1 day');
        $negotiation->setStartDate($startDate);
        $negotiation->setEndDate($endDate);

        $form = $this->createForm(NegotiationFormType::class, $negotiation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si les dates sélectionnées sont disponibles
            $overlappingBookings = $bookingRepository->findPendingBookingsOverlappingPeriod(
                $roomType->getId(),
                $negotiation->getStartDate(),
                $negotiation->getEndDate()
            );
            
            // Si des réservations en attente chevauchent la période, les annuler
            foreach ($overlappingBookings as $booking) {
                $booking->setStatus(Booking::STATUS_CANCELLED);
                $this->entityManager->persist($booking);
            }
            
            // Traiter l'offre avec le moteur de négociation
            $this->negotiationService->processNegotiation($negotiation);
            
            return $this->redirectToRoute('app_negotiation_status', ['id' => $negotiation->getId()], Response::HTTP_SEE_OTHER);
        }
        
        // Récupérer les dates déjà réservées pour cette chambre
        $bookedDates = $bookingRepository->findBookedDatesByRoomType($roomType->getId());

        return $this->render('negotiation/make_offer.html.twig', [
            'roomType' => $roomType,
            'hotel' => $hotel,
            'negotiation' => $negotiation,
            'form' => $form->createView(),
            'autoAcceptThreshold' => $hotel->getAutoAcceptThreshold(),
            'autoRejectThreshold' => $hotel->getAutoRejectThreshold(),
            'bookedDates' => json_encode($bookedDates),
        ]);
    }

    #[Route('/status/{id}', name: 'app_negotiation_status')]
    public function status(Negotiation $negotiation): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        // Vérifier que l'utilisateur est autorisé à voir cette négociation
        if ($negotiation->getClient() !== $user && $negotiation->getRoomType()->getHotel()->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette négociation.');
        }

        return $this->render('negotiation/status.html.twig', [
            'negotiation' => $negotiation,
        ]);
    }

    #[Route('/accept-counter-offer/{id}', name: 'app_accept_counter_offer')]
    public function acceptCounterOffer(Negotiation $negotiation): Response
    {
        $user = $this->getUser();
        if (!$user || $negotiation->getClient() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accepter cette contre-offre.');
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à accepter cette contre-offre.');
        }

        if ($negotiation->getStatus() !== Negotiation::STATUS_COUNTER_OFFER) {
            $this->addFlash('error', 'Cette négociation n\'est pas en attente de votre réponse.');
            return $this->redirectToRoute('app_negotiation_status', ['id' => $negotiation->getId()]);
        }

        $negotiation->setStatus(Negotiation::STATUS_ACCEPTED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Vous avez accepté la contre-offre de l\'hôtel.');
        return $this->redirectToRoute('app_negotiation_status', ['id' => $negotiation->getId()]);
    }

    #[Route('/reject-counter-offer/{id}', name: 'app_reject_counter_offer')]
    public function rejectCounterOffer(Negotiation $negotiation): Response
    {
        $user = $this->getUser();
        if (!$user || $negotiation->getClient() !== $user) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à rejeter cette contre-offre.');
        }

        if ($negotiation->getStatus() !== Negotiation::STATUS_COUNTER_OFFER) {
            $this->addFlash('error', 'Cette négociation n\'est pas en attente de votre réponse.');
            return $this->redirectToRoute('app_negotiation_status', ['id' => $negotiation->getId()]);
        }

        $negotiation->setStatus(Negotiation::STATUS_REJECTED);
        $this->entityManager->flush();

        $this->addFlash('info', 'Vous avez refusé la contre-offre de l\'hôtel.');
        return $this->redirectToRoute('app_negotiation_status', ['id' => $negotiation->getId()]);
    }

    #[Route('/my-negotiations', name: 'app_my_negotiations')]
    public function myNegotiations(NegotiationRepository $negotiationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $negotiations = $negotiationRepository->findBy(['client' => $user], ['createdAt' => 'DESC']);

        return $this->render('negotiation/my_negotiations.html.twig', [
            'negotiations' => $negotiations,
        ]);
    }

    #[Route('/hotel-negotiations', name: 'app_hotel_negotiations')]
    public function hotelNegotiations(NegotiationRepository $negotiationRepository): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $hotel = $user->getHotels()->first();
        if (!$hotel) {
            throw $this->createNotFoundException('Hôtel non trouvé.');
        }

        $roomTypes = $hotel->getRoomTypes();
        $roomTypeIds = [];
        foreach ($roomTypes as $roomType) {
            $roomTypeIds[] = $roomType->getId();
        }

        $negotiations = $negotiationRepository->findByRoomTypes($roomTypeIds);

        return $this->render('negotiation/hotel_negotiations.html.twig', [
            'negotiations' => $negotiations,
            'hotel' => $hotel,
        ]);
    }

    #[Route('/respond/{id}', name: 'app_respond_to_negotiation')]
    public function respondToNegotiationLegacy(Request $request, Negotiation $negotiation): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_HOTEL', $user->getRoles())) {
            throw new AccessDeniedException('Vous devez être connecté en tant qu\'hôtelier pour accéder à cette page.');
        }

        $hotel = $negotiation->getRoomType()->getHotel();
        if ($hotel->getUser() !== $user) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à répondre à cette négociation.');
        }

        if ($negotiation->getStatus() !== Negotiation::STATUS_PENDING) {
            $this->addFlash('error', 'Cette négociation n\'est plus en attente de réponse.');
            return $this->redirectToRoute('app_hotel_negotiations');
        }

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $counterOfferPrice = $request->request->get('counter_offer_price');
            $response = $request->request->get('response');

            $negotiation->setHotelResponse($response);
            $negotiation->setRespondedAt(new \DateTime());

            if ($action === 'accept') {
                $this->negotiationService->acceptNegotiation($negotiation);
                $this->addFlash('success', 'Vous avez accepté l\'offre du client.');
            } elseif ($action === 'reject') {
                $this->negotiationService->rejectNegotiation($negotiation);
                $this->addFlash('info', 'Vous avez refusé l\'offre du client.');
            } elseif ($action === 'counter_offer' && $counterOfferPrice) {
                $this->negotiationService->makeCounterOffer($negotiation, (float) $counterOfferPrice);
                $this->addFlash('success', 'Vous avez fait une contre-offre au client.');
            }

            return $this->redirectToRoute('app_hotel_negotiations');
        }

        return $this->render('negotiation/respond.html.twig', [
            'negotiation' => $negotiation,
        ]);
    }
}
