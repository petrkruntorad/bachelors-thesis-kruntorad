<?php

namespace App\Repository;

use App\Entity\DeviceOptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeviceOptions|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceOptions|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceOptions[]    findAll()
 * @method DeviceOptions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceOptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceOptions::class);
    }

    // /**
    //  * @return DeviceOptions[] Returns an array of DeviceOptions objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DeviceOptions
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
