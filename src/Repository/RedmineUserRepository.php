<?php

namespace App\Repository;

use App\Entity\RedmineUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RedmineUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method RedmineUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method RedmineUser[]    findAll()
 * @method RedmineUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RedmineUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RedmineUser::class);
    }

    // /**
    //  * @return RedmineUser[] Returns an array of RedmineUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RedmineUser
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
