<?php

namespace App\Repository;

use App\Entity\SessionResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionResponse>
 *
 * @method SessionResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method SessionResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method SessionResponse[]    findAll()
 * @method SessionResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionResponse::class);
    }

    public function add(SessionResponse $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SessionResponse $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return SessionResponse|null Return the next question
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNextQuestion(): ?SessionResponse
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.answeredAt IS NULL')
            ->orderBy('s.displayedAt', 'ASC')
            ->orderBy('s.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return SessionResponse[] Returns an array of SessionResponse objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SessionResponse
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
