<?php

namespace App\Services;

use App\Entity\Device;
use App\Entity\DeviceOptions;
use App\Entity\Sensor;
use App\Entity\SensorData;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

class SensorService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var DeviceService
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

    public function checkSensorsActivityForDevice(Device $device)
    {
        $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
        foreach ($sensors as $sensor)
        {
            if($this->isSensorActive($sensor->getId()))
            {
                $active = true;
            }
        }
    }

    /**
     * @throws InternalErrorException
     */
    public function isDeviceActive(Device $device)
    {

        try {
            $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
            foreach ($sensors as $sensor)
            {
                if($this->isSensorActive($sensor->getId()))
                {
                    return true;
                }
            }
        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
        return false;
    }
}
