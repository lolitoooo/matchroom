<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 *
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les réservations confirmées pour un client
     */
    public function findConfirmedByClient(int $clientId): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.negotiation', 'n')
            ->andWhere('n.client = :clientId')
            ->andWhere('b.status = :status')
            ->setParameter('clientId', $clientId)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations confirmées pour un hôtelier
     */
    public function findConfirmedByHotelier(int $hotelierId): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.negotiation', 'n')
            ->join('n.roomType', 'r')
            ->join('r.hotel', 'h')
            ->andWhere('h.user = :hotelierId')
            ->andWhere('b.status = :status')
            ->setParameter('hotelierId', $hotelierId)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve les dates réservées pour une chambre spécifique
     * 
     * @param int $roomTypeId L'ID du type de chambre
     * @return array Un tableau de périodes réservées avec les dates de début et de fin
     */
    public function findBookedDatesByRoomType(int $roomTypeId): array
    {
        $bookings = $this->createQueryBuilder('b')
            ->join('b.negotiation', 'n')
            ->andWhere('n.roomType = :roomTypeId')
            ->andWhere('b.status = :status')
            ->setParameter('roomTypeId', $roomTypeId)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->getQuery()
            ->getResult();
            
        $bookedPeriods = [];
        foreach ($bookings as $booking) {
            $bookedPeriods[] = [
                'start' => $booking->getStartDate()->format('Y-m-d'),
                'end' => $booking->getEndDate()->format('Y-m-d')
            ];
        }
        
        return $bookedPeriods;
    }
    
    /**
     * Trouve les réservations en attente qui chevauchent une période donnée pour une chambre spécifique
     * 
     * @param int $roomTypeId L'ID du type de chambre
     * @param \DateTimeInterface $startDate Date de début
     * @param \DateTimeInterface $endDate Date de fin
     * @return array Les réservations en attente qui chevauchent la période
     */
    public function findPendingBookingsOverlappingPeriod(int $roomTypeId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.negotiation', 'n')
            ->andWhere('n.roomType = :roomTypeId')
            ->andWhere('b.status = :status')
            ->andWhere('b.startDate <= :endDate')
            ->andWhere('b.endDate >= :startDate')
            ->setParameter('roomTypeId', $roomTypeId)
            ->setParameter('status', Booking::STATUS_PENDING)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }
}
