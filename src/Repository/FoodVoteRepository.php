<?php

namespace App\Repository;

use App\Entity\FoodVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FoodVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodVote[]    findAll()
 * @method FoodVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodVoteRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FoodVote::class);
    }

    // /**
    //  * @return FoodVote[] Returns an array of FoodVote objects
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
    public function findOneBySomeField($value): ?FoodVote
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
