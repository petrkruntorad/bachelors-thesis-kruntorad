<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceOptions;
use App\Entity\Sensor;
use App\Entity\SensorData;
use App\Services\DeviceService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SensorDataController extends AbstractController
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
    )
    {
        $this->em = $em;
        $this->ds = $ds;
    }


    /**
     * @Route("/device/sensor/get-data/{id}/{hardwareId}/{push}", name="sensor_getData")
     * @IsGranted("ROLE_USER")
     */
    public function getData(Device $device, string $hardwareId, $push=0){
        //inits empty array
        $data = [];

        //loads options for device
        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device->getId()));

        //loads sensor by hardware id and parent device
        $sensor = $this->em->getRepository(Sensor::class)->findOneBy(array('hardwareId'=>$hardwareId, 'parentDevice'=>$device->getId()));

        //sets default temperature
        $defaultTemperature = 0;

        //checks if write interval is set
        if($this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval()))
        {
            //loads steps from device service
            $steps = $this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval())['secondsSteps'];
        }else{
            $steps = 60;
        }
        //sets seconds and total seconds by steps
        $seconds = $steps;
        $totalSeconds = $steps*90;
        //current timestamp converted to seconds from 1.1.1970
        $currentTimestamp = strtotime(date("Y-m-d H:i:s"));

        //checks if current push is init when page is loading or not
        if ($push == 0){
            //loads data from database by write time, limit 90
            $sensorData = $this->em->getRepository(SensorData::class)->getNumberOfTemperatures($sensor->getId(),90);

            //creates array filled with zeros to limit 90 records
            $temperaturesInit = array_fill(0, 90, 0);

            //for each key in array
            for($i = 0; $i <= count($temperaturesInit)-1;)
            {
                //sets current position in seconds by steps and number of iteration
                $currentSecondsPosition = ($i+1) * $steps;
                //iterates every sensor data
                foreach ($sensorData as $key => $row) {
                    //checks if write timestamp is not null
                    if ($row->getWriteTimestamp() != NULL){
                        //loads write timestamp for current iteration and converts it to second from 1.1.1970
                        $writeTime = strtotime($row->getWriteTimestamp()->format('Y-m-d H:i:s'));
                        //removes writetime from currentTimestamp
                        $secondsFromWrite = $currentTimestamp - $writeTime;
                        //checks if seconds from write are lower or equal to total seconds
                        if ($secondsFromWrite <= $totalSeconds){
                            //checks if current seconds position is higher than seconds from write of current value
                            if($currentSecondsPosition >= $secondsFromWrite){
                                //sets value to array
                                $temperaturesInit[$i] = $row->getSensorData();
                                //remove used value from array
                                unset($sensorData[$key]);
                            }
                        }
                    }
                }
                $i++;
            }
            //assigns array with values to $data aray and reverse the order
            $data['temperatures'] = array_reverse($temperaturesInit);
        }else{
            //loads single last value from sensordata
            $sensorData = $this->em->getRepository(SensorData::class)->findOneBy(array('parentSensor'=>$sensor->getId()),array('id'=>'DESC'));
            //checks
            if(empty($sensorData)){
                //creates a fake object
                $fakeSensorData = new SensorData();
                $fakeSensorData->setParentSensor($sensor);
                $fakeSensorData->setSensorData(0);
                $fakeSensorData->setWriteTimestamp(new \DateTime('1977-01-01'));
            }
            //check if date is not null
            if ($sensorData->getWriteTimestamp() != NULL) {
                //loads write timestamp for current iteration and converts it to second from 1.1.1970
                $writeTime = strtotime($sensorData->getWriteTimestamp()->format('Y-m-d H:i:s'));
                //removes writetime from currentTimestamp
                $secondsFromWrite = $currentTimestamp - $writeTime;
                //check if the value is not older than specified amount of seconds
                if ($secondsFromWrite <= $seconds) {
                    $defaultTemperature = $sensorData->getSensorData();
                }
            }

            //assign the default value to $data array
            $data['temperatures'] = $defaultTemperature;

        }
        //response creation
        //encodes array to json
        $finalResponse = json_encode($data);
        $response = new Response($finalResponse);
        //sets header
        $response->headers->set('Content-Type', 'application/json');
        //return the response
        return $response;

    }
}
