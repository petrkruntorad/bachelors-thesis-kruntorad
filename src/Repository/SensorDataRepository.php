<?php

namespace App\Repository;

use App\Entity\SensorData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SensorData|null find($id, $lockMode = null, $lockVersion = null)
 * @method SensorData|null findOneBy(array $criteria, array $orderBy = null)
 * @method SensorData[]    findAll()
 * @method SensorData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SensorDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SensorData::class);
    }

    public function getNumberOfTemperatures($sensorId,$limit){
        return $this->createQueryBuilder('sd')
            ->where('sd.parentSensor=:parentSensor')
            ->setParameter('parentSensor',$sensorId)
            ->orderBy('sd.id','DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getLastRecordForSensor(int $sensorId)
    {
        return $this->createQueryBuilder('sd')
            ->where('sd.parentSensor=:parentSensor')
            ->setParameter('parentSensor',$sensorId)
            ->orderBy('sd.id','DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return SensorData[] Returns an array of SensorData objects
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
    public function findOneBySomeField($value): ?SensorData
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
