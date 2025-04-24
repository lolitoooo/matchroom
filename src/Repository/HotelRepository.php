<?php

namespace App\Repository;

use App\Entity\Hotel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hotel>
 *
 * @method Hotel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hotel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hotel[]    findAll()
 * @method Hotel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotel::class);
    }

    public function save(Hotel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Hotel $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les hôtels dans une zone géographique spécifique
     */
    public function findByGeoArea(float $minLat, float $maxLat, float $minLng, float $maxLng): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.latitude >= :minLat')
            ->andWhere('h.latitude <= :maxLat')
            ->andWhere('h.longitude >= :minLng')
            ->andWhere('h.longitude <= :maxLng')
            ->setParameter('minLat', $minLat)
            ->setParameter('maxLat', $maxLat)
            ->setParameter('minLng', $minLng)
            ->setParameter('maxLng', $maxLng)
            ->orderBy('h.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve les hôtels dans une zone géographique définie par des limites
     */
    public function findByBounds(array $bounds): array
    {
        $qb = $this->createQueryBuilder('h');
        
        if (!empty($bounds['north']) && !empty($bounds['south']) && !empty($bounds['east']) && !empty($bounds['west'])) {
            $qb->andWhere('h.latitude <= :north')
               ->andWhere('h.latitude >= :south')
               ->andWhere('h.longitude <= :east')
               ->andWhere('h.longitude >= :west')
               ->setParameter('north', $bounds['north'])
               ->setParameter('south', $bounds['south'])
               ->setParameter('east', $bounds['east'])
               ->setParameter('west', $bounds['west']);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les hôtels dans un polygone dessiné sur la carte
     */
    public function findByPolygon(array $polygon): array
    {
        // Cette méthode nécessiterait une extension PostGIS pour être implémentée correctement
        // Pour simplifier, nous allons juste retourner tous les hôtels
        return $this->findAll();
    }
}
