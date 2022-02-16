<?php

namespace App\Services;

use App\Entity\DeviceOptions;
use App\Entity\Sensor;
use App\Entity\SensorData;
use Doctrine\ORM\EntityManagerInterface;

class SensorService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param DeviceService
     */
    private $ds;

    public function __construct(
        EntityManagerInterface $em,
        DeviceService $ds
    ) {
        $this->em = $em;
        $this->ds = $ds;
    }

    public function isSensorActive(int $sensorId){
        $status = false;

        $sensor = $this->em->getRepository(Sensor::class)->findOneBy(array('id'=>$sensorId));

        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$sensor->getParentDevice()->getId()));

        if($this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval()))
        {
            $inactiveSeconds = $this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval())['secondsSteps']*1.5;
        }else{
            $inactiveSeconds = 90;
        }

        $lastRecord = $this->em->getRepository(SensorData::class)->getLastRecordForSensor($sensor->getId());

        if($lastRecord->getWriteTimestamp() != NULL)
        {
            $currentTimestamp = strtotime(date("Y-m-d H:i:s"));
            $writeTime = strtotime($lastRecord->getWriteTimestamp()->format('Y-m-d H:i:s'));

            $secondsFromWrite = $currentTimestamp - $writeTime;
            if($secondsFromWrite <= $inactiveSeconds){
                $status = true;
            }
        }

        return $status;
    }
}
