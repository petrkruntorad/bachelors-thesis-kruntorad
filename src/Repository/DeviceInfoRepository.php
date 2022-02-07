<?php

namespace App\Repository;

use App\Entity\DeviceInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeviceInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceInfo[]    findAll()
 * @method DeviceInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceInfo::class);
    }

    // /**
    //  * @return DeviceInfo[] Returns an array of DeviceInfo objects
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
    public function findOneBySomeField($value): ?DeviceInfo
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
