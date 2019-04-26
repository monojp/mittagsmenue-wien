<?php

namespace App\Repository;

use App\Entity\FoodUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FoodUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodUser[]    findAll()
 * @method FoodUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodUserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FoodUser::class);
    }

    // /**
    //  * @return FoodUser[] Returns an array of FoodUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FoodUser
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
