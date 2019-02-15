<?php

namespace App\Repository;

use App\Entity\FoodCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FoodCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodCache[]    findAll()
 * @method FoodCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodCacheRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FoodCache::class);
    }

    // /**
    //  * @return FoodCache[] Returns an array of FoodCache objects
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
    public function findOneBySomeField($value): ?FoodCache
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
