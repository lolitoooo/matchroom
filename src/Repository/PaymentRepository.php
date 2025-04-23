<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 *
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Payment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les paiements réussis pour un client
     */
    public function findCompletedPaymentsByClient(int $clientId): array
    {
        return $this->createQueryBuilder('p')
            ->join('App\Entity\Booking', 'b', 'WITH', 'b.payment = p.id')
            ->join('b.negotiation', 'n')
            ->andWhere('n.client = :clientId')
            ->andWhere('p.status = :status')
            ->setParameter('clientId', $clientId)
            ->setParameter('status', Payment::STATUS_COMPLETED)
            ->orderBy('p.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
