<?php

namespace App\Repository;

use App\Entity\RoomPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoomPhoto>
 *
 * @method RoomPhoto|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomPhoto|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomPhoto[]    findAll()
 * @method RoomPhoto[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomPhoto::class);
    }

    public function save(RoomPhoto $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RoomPhoto $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les photos d'une chambre triées par ordre d'affichage
     */
    public function findByRoomTypeOrderedByDisplayOrder(int $roomTypeId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.roomType = :roomTypeId')
            ->setParameter('roomTypeId', $roomTypeId)
            ->orderBy('p.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
