<?php

namespace App\Repository;

use App\Entity\DeviceNotifications;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DeviceNotifications|null find($id, $lockMode = null, $lockVersion = null)
 * @method DeviceNotifications|null findOneBy(array $criteria, array $orderBy = null)
 * @method DeviceNotifications[]    findAll()
 * @method DeviceNotifications[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeviceNotificationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceNotifications::class);
    }
    
    // /**
    //  * @return Sensor[] Returns an array of Sensor objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Sensor
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
