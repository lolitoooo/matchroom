<?php

namespace App\Repository;

use App\Entity\Negotiation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Negotiation>
 *
 * @method Negotiation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Negotiation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Negotiation[]    findAll()
 * @method Negotiation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NegotiationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Negotiation::class);
    }

    public function save(Negotiation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Negotiation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les négociations en attente pour un hôtelier
     */
    public function findPendingForHotelier(int $hotelOwnerId): array
    {
        return $this->createQueryBuilder('n')
            ->join('n.roomType', 'r')
            ->join('r.hotel', 'h')
            ->join('h.user', 'u')
            ->andWhere('u.id = :hotelOwnerId')
            ->andWhere('n.status = :status')
            ->setParameter('hotelOwnerId', $hotelOwnerId)
            ->setParameter('status', Negotiation::STATUS_PENDING)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les négociations pour un client
     */
    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
