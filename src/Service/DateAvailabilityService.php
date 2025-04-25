<?php

namespace App\Service;

use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;

class DateAvailabilityService
{
    private EntityManagerInterface $entityManager;
    private BookingRepository $bookingRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        BookingRepository $bookingRepository
    ) {
        $this->entityManager = $entityManager;
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * Récupère toutes les dates réservées pour un type de chambre spécifique
     */
    public function getBookedDatesForRoomType(int $roomTypeId): array
    {
        $bookedDates = [];
        
        // Récupérer toutes les réservations confirmées pour ce type de chambre
        $bookings = $this->bookingRepository->findConfirmedBookingsByRoomType($roomTypeId);
        
        foreach ($bookings as $booking) {
            $negotiation = $booking->getNegotiation();
            $startDate = $negotiation->getStartDate();
            $endDate = $negotiation->getEndDate();
            
            // Générer toutes les dates entre la date de début et la date de fin
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $bookedDates[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }
        }
        
        return array_unique($bookedDates);
    }

    /**
     * Récupère toutes les dates réservées pour tous les types de chambres
     */
    public function getAllBookedDates(): array
    {
        $bookedDates = [];
        
        // Récupérer toutes les réservations confirmées
        $bookings = $this->bookingRepository->findBy(['status' => 'confirmed']);
        
        foreach ($bookings as $booking) {
            $negotiation = $booking->getNegotiation();
            $startDate = $negotiation->getStartDate();
            $endDate = $negotiation->getEndDate();
            
            // Générer toutes les dates entre la date de début et la date de fin
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $bookedDates[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }
        }
        
        return array_unique($bookedDates);
    }
}
