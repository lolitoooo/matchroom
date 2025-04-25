<?php

namespace App\Repository;

use App\Entity\RoomType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoomType>
 *
 * @method RoomType|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomType|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomType[]    findAll()
 * @method RoomType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomType::class);
    }

    public function save(RoomType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RoomType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les types de chambres disponibles dans une plage de prix
     */
    public function findAvailableByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.available = :available')
            ->andWhere('r.basePrice >= :minPrice')
            ->andWhere('r.basePrice <= :maxPrice')
            ->setParameter('available', true)
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('r.basePrice', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Recherche avancée de chambres selon plusieurs critères
     */
    public function findBySearchCriteria(?string $destination = null, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null, ?string $roomType = null, ?float $budget = null, ?int $persons = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.hotel', 'h')
            ->andWhere('r.available = :available')
            ->setParameter('available', true);
        
        // Filtre par destination (ville, région, pays)
        if ($destination) {
            $qb->andWhere('h.city LIKE :destination OR h.address LIKE :destination OR h.name LIKE :destination')
               ->setParameter('destination', '%' . $destination . '%');
        }
        
        // Filtre par type de chambre
        if ($roomType && $roomType !== '') {
            $qb->andWhere('r.name LIKE :roomType')
               ->setParameter('roomType', '%' . $roomType . '%');
        }
        
        // Filtre par budget maximum
        if ($budget) {
            $qb->andWhere('r.basePrice <= :budget')
               ->setParameter('budget', $budget);
        }
        
        // Filtre par nombre de personnes
        if ($persons) {
            $qb->andWhere('r.capacity >= :persons')
               ->setParameter('persons', $persons);
        }
        
        // Filtre par disponibilité aux dates spécifiées
        if ($startDate && $endDate) {
            // Cette partie nécessiterait une logique plus complexe pour vérifier les disponibilités
            // en fonction des réservations existantes, mais pour simplifier :
            $qb->leftJoin('r.negotiations', 'n')
               ->leftJoin('n.bookings', 'b')
               ->andWhere('b.id IS NULL OR NOT (b.startDate <= :endDate AND b.endDate >= :startDate)')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate);
        }
        
        return $qb->orderBy('r.basePrice', 'ASC')
                 ->getQuery()
                 ->getResult();
    }
}
