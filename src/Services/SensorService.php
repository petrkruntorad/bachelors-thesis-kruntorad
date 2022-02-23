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

    /**
     * @var NotificationService $notificationService
     */
    private $notificationService;

    /**
     * @param EntityManagerInterface $em
     * @param DeviceService $ds
     */
    public function __construct(
        EntityManagerInterface $em,
        DeviceService $ds,
        NotificationService $notificationService
    ) {
        $this->em = $em;
        $this->ds = $ds;
        $this->notificationService = $notificationService;
    }

    public function isSensorActive(int $sensorId){
        //loads sensor by id
        $sensor = $this->em->getRepository(Sensor::class)->findOneBy(array('id'=>$sensorId));

        //loads device options by sensor
        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$sensor->getParentDevice()->getId()));

        //checks if write parameters exists
        if($this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval()))
        {
            //gets inactive seconds, 1.5x of write steps
            $inactiveSeconds = $this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval())['secondsSteps']*1.5;
        }else{
            $inactiveSeconds = 90;
        }

        //loads last record
        $lastRecord = $this->em->getRepository(SensorData::class)->getLastRecordForSensor($sensor->getId());

        //checks if last record exists
        if($lastRecord){
            $lastRecord = $lastRecord[0];
            //checks if write timestamp is not null
            if($lastRecord->getWriteTimestamp() != NULL)
            {
                $currentTimestamp = strtotime(date("Y-m-d H:i:s"));
                $writeTime = strtotime($lastRecord->getWriteTimestamp()->format('Y-m-d H:i:s'));

                //calculates diff in seconds between write timestamp and current datetime
                $secondsFromWrite = $currentTimestamp - $writeTime;
                //if data are in tolerance
                if($secondsFromWrite <= $inactiveSeconds){
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @throws InternalErrorException
     */
    public function isDeviceActive(Device $device)
    {
        try {
            //loads sensors for device
            $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
            //iterates each sensor
            foreach ($sensors as $sensor)
            {
                //checks if some sensor is active
                if($this->isSensorActive($sensor->getId()))
                {
                    return true;
                }
            }
        }catch (Exception $exception){
            //throws exception in case of error
            throw new InternalErrorException($exception);
        }
        return false;
    }

    public function checkEveryDevice()
    {
        try {
            $sensors = $this->em->getRepository(Sensor::class)->findAll();
            foreach ($sensors as $key => $sensor)
            {
                if(!$this->isSensorActive($sensor->getId())){
                    $content = 'Senzor ('.$sensor->getHardwareId().') není aktivní. Zkontrolujte jeho správné zapojení.';
                    //creates notification
                    $this->notificationService->createNotification($content, $sensor->getParentDevice(), $sensor, 'activity');
                }
                //checks if device is active by activity of sensors
                if(!$this->isDeviceActive($sensor->getParentDevice())){
                    $content = 'Zařízení ('.$sensor->getParentDevice()->getName().') není aktivní. Zkontrolujte jeho stav.';
                    //creates notification
                    $this->notificationService->createNotification($content, $sensor->getParentDevice(), null, 'activity');
                }
            }

        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
    }
}
