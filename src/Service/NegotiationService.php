<?php

namespace App\Service;

use App\Entity\Hotel;
use App\Entity\Negotiation;
use App\Entity\RoomType;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NegotiationRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NegotiationService
{
    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;
    private NegotiationRepository $negotiationRepository;
    private ?HubInterface $hub = null;
    private ?EmailService $emailService = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        NotificationRepository $notificationRepository,
        NegotiationRepository $negotiationRepository,
        ?HubInterface $hub = null,
        ?EmailService $emailService = null
    ) {
        $this->entityManager = $entityManager;
        $this->notificationRepository = $notificationRepository;
        $this->negotiationRepository = $negotiationRepository;
        $this->hub = $hub;
        $this->emailService = $emailService;
    }

    /**
     * Crée une nouvelle négociation
     */
    public function createNegotiation(User $client, RoomType $roomType, \DateTime $startDate, \DateTime $endDate, float $proposedPrice): Negotiation
    {
        $negotiation = new Negotiation();
        $negotiation->setClient($client);
        $negotiation->setRoomType($roomType);
        $negotiation->setStartDate($startDate);
        $negotiation->setEndDate($endDate);
        $negotiation->setProposedPrice((string) $proposedPrice);
        $negotiation->setStatus(Negotiation::STATUS_PENDING);

        $this->entityManager->persist($negotiation);
        $this->entityManager->flush();

        // Notifier l'hôtelier
        $this->notifyHotelier($negotiation);

        return $negotiation;
    }

    /**
     * Traite automatiquement une négociation selon les règles définies
     */
    public function processNegotiation(Negotiation $negotiation): void
    {
        // S'assurer que la négociation est persistée avant de la traiter
        $this->entityManager->persist($negotiation);
        $this->entityManager->flush();
        
        $roomType = $negotiation->getRoomType();
        $hotel = $roomType->getHotel();
        $basePrice = (float) $roomType->getBasePrice();
        $proposedPrice = (float) $negotiation->getProposedPrice();
        $discountPercentage = 100 - ($proposedPrice * 100 / $basePrice);
        
        $negotiationRules = $hotel->getNegotiationRules();
        $autoAcceptThreshold = $negotiationRules['autoAcceptThreshold'] ?? 20;
        $autoRejectThreshold = $negotiationRules['autoRejectThreshold'] ?? 50;

        if ($discountPercentage <= $autoAcceptThreshold) {
            // Acceptation automatique
            $this->acceptNegotiation($negotiation, true);
        } elseif ($discountPercentage >= $autoRejectThreshold) {
            // Refus automatique
            $this->rejectNegotiation($negotiation, true);
        } else {
            // Mettre la négociation en attente pour que l'hôtelier puisse faire une contre-proposition manuellement
            $negotiation->setStatus(Negotiation::STATUS_PENDING);
            $this->entityManager->persist($negotiation);
            $this->entityManager->flush();
        }
    }

    /**
     * Accepte une négociation
     */
    public function acceptNegotiation(Negotiation $negotiation, bool $isAutomatic = false): void
    {
        $negotiation->setStatus(Negotiation::STATUS_ACCEPTED);
        $negotiation->setRespondedAt(new \DateTime());
        
        if ($isAutomatic) {
            $negotiation->setHotelResponse("Votre offre a été automatiquement acceptée par l'hôtel.");
        }
        
        $this->entityManager->flush();
        
        // Notifier le client
        $this->notifyClient($negotiation, "Votre offre pour {$negotiation->getRoomType()->getName()} a été acceptée!");
        $this->hub->publish(new Update(
            "hotel/{$negotiation->getRoomType()->getHotel()->getUser()->getId()}/negotiations",
            json_encode([
                'type' => 'hotel_response',
                'negotiationId' => $negotiation->getId(),
                'status' => $negotiation->getStatus(),
                'message' => $negotiation->getHotelResponse(),
                'respondedAt' => $negotiation->getRespondedAt()->format('Y-m-d H:i'),
            ])
        ));        
    }

    /**
     * Rejette une négociation
     */
    public function rejectNegotiation(Negotiation $negotiation, bool $isAutomatic = false): void
    {
        $negotiation->setStatus(Negotiation::STATUS_REJECTED);
        $negotiation->setRespondedAt(new \DateTime());
        
        if ($isAutomatic) {
            $negotiation->setHotelResponse("Votre offre a été automatiquement refusée par l'hôtel.");
        }
        
        $this->entityManager->flush();
        
        $this->notifyClient($negotiation, "Votre offre pour {$negotiation->getRoomType()->getName()} a été refusée.");
        $this->hub->publish(new Update(
            "hotel/{$negotiation->getRoomType()->getHotel()->getUser()->getId()}/negotiations",
            json_encode([
                'type' => 'hotel_response',
                'negotiationId' => $negotiation->getId(),
                'status' => $negotiation->getStatus(),
                'message' => $negotiation->getHotelResponse(),
                'respondedAt' => $negotiation->getRespondedAt()->format('Y-m-d H:i'),
            ])
        ));        
    }

    /**
     * Fait une contre-offre
     */
    public function makeCounterOffer(Negotiation $negotiation, float $counterOfferPrice, bool $isAutomatic = false): void
    {
        $negotiation->setStatus(Negotiation::STATUS_COUNTER_OFFER);
        $negotiation->setProposedPrice((string) $counterOfferPrice);
        $negotiation->setRespondedAt(new \DateTime());
        
        if ($isAutomatic) {
            $negotiation->setHotelResponse("L'hôtel vous propose une contre-offre à {$counterOfferPrice} €.");
        }
        
        $this->entityManager->flush();
        
        $this->notifyClient($negotiation, "L'hôtel vous a fait une contre-offre pour {$negotiation->getRoomType()->getName()}.");
        $this->hub->publish(new Update(
            "hotel/{$negotiation->getRoomType()->getHotel()->getUser()->getId()}/negotiations",
            json_encode([
                'type' => 'hotel_response',
                'negotiationId' => $negotiation->getId(),
                'status' => $negotiation->getStatus(),
                'message' => $negotiation->getHotelResponse(),
                'respondedAt' => $negotiation->getRespondedAt()->format('Y-m-d H:i'),
            ])
        ));        
    }

    /**
     * Notifie l'hôtelier d'une nouvelle négociation
     */
    private function notifyHotelier(Negotiation $negotiation): void
    {
        $hotel = $negotiation->getRoomType()->getHotel();
        $hotelier = $hotel->getUser();
        
        $notification = new Notification();
        $notification->setUser($hotelier);
        $notification->setType(Notification::TYPE_EMAIL);
        $notification->setMessage("Nouvelle demande de négociation pour {$negotiation->getRoomType()->getName()}");
        
        $this->notificationRepository->save($notification, true);
        
        if ($this->emailService) {
            $this->emailService->sendNegotiationNotification($hotelier, $negotiation);
        }
        
        if ($this->hub) {
            $update = new Update(
                "user/{$hotelier->getId()}/negotiations",
                json_encode([
                    'type' => 'new_negotiation',
                    'negotiationId' => $negotiation->getId(),
                    'message' => "Nouvelle demande de négociation pour {$negotiation->getRoomType()->getName()}"
                ])
            );
            
            $this->hub->publish($update);
        }
    }

    /**
     * Notifie le client d'une réponse à sa négociation
     */
    private function notifyClient(Negotiation $negotiation, string $message): void
    {
        $client = $negotiation->getClient();
        
        $notification = new Notification();
        $notification->setUser($client);
        $notification->setType(Notification::TYPE_EMAIL);
        $notification->setMessage($message);
        
        $this->notificationRepository->save($notification, true);
        
        if ($this->emailService) {
            $this->emailService->sendNegotiationResponseNotification($client, $negotiation);
        }
        
        if ($this->hub) {
            $update = new Update(
                "user/{$client->getId()}/negotiations",
                json_encode([
                    'type' => 'negotiation_response',
                    'negotiationId' => $negotiation->getId(),
                    'status' => $negotiation->getStatus(),
                    'message' => $message
                ])
            );
            
            $this->hub->publish($update);
        }
    }

    public function respondManually(Negotiation $negotiation, string $action, ?float $counterOffer = null): void
    {
        match ($action) {
            'accept' => $this->acceptNegotiation($negotiation),
            'reject' => $this->rejectNegotiation($negotiation),
            'counter' => $this->makeCounterOffer($negotiation, $counterOffer),
            default => throw new \InvalidArgumentException('Action inconnue'),
        };
        $this->hub->publish(new Update(
            "hotel/{$negotiation->getRoomType()->getHotel()->getUser()->getId()}/negotiations",
            json_encode([
                'type' => 'hotel_response',
                'negotiationId' => $negotiation->getId(),
                'status' => $negotiation->getStatus(),
                'message' => $negotiation->getHotelResponse(),
                'respondedAt' => $negotiation->getRespondedAt()->format('Y-m-d H:i'),
            ])
        ));        
    }   
}
