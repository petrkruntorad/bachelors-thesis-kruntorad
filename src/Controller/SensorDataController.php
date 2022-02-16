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
        $data = [];

        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device->getId()));

        $sensor = $this->em->getRepository(Sensor::class)->findOneBy(array('hardwareId'=>$hardwareId, 'parentDevice'=>$device->getId()));
        $sensorDataLenght = 0;
        $temperaturesArray=[];
        $defaultTemperature = 0;

        if($this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval()))
        {
            $steps = $this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval())['secondsSteps'];
            $seconds = $steps;
            $totalSeconds = $steps*90;
        }else{
            $steps = 60;
            $seconds = $steps;
            $totalSeconds = $steps*90;
        }
        //$seconds = 60;
        $currentTimestamp = strtotime(date("Y-m-d H:i:s"));

        if ($push == 0){
            $sensorData = $this->em->getRepository(SensorData::class)->getNumberOfTemperatures($sensor->getId(),90);
            //count of values in array
            if (count($sensorData)>0){
                $sensorDataLenght = count($sensorData)-1;
            }
            //check if minimal count of values in array is 89
            if($sensorDataLenght>=89){
                for ($i = 0; $i < 90;) {
                    //check if write time is not null
                    if ($sensorData[$i]->getWriteTimestamp() != NULL){
                        $writeTime = strtotime($sensorData[$i]->getWriteTimestamp()->format('Y-m-d H:i:s'));
                        $secondsFromWrite = $currentTimestamp - $writeTime;
                        if ($seconds<=$totalSeconds){
                            if($secondsFromWrite<=$seconds){
                                $temperaturesArray[] = $sensorData[$i]->getSensorData();
                                $i = $i+1;
                            }else{
                                $temperaturesArray[] = 0;
                            }
                            $seconds = $seconds + $steps;
                        }else{
                            $i = 90;
                        }
                    }else{
                        $temperaturesArray[] = 0;
                    }
                }
            }else{
                $temperaturesInit = array_fill(0, 90, 0);
                for($i = 0; $i <= count($temperaturesInit)-1;)
                {
                    $currentSecondsPosition = ($i+1) * $steps;
                    foreach ($sensorData as $key => $row) {
                        if ($row->getWriteTimestamp() != NULL){
                            $writeTime = strtotime($row->getWriteTimestamp()->format('Y-m-d H:i:s'));
                            $secondsFromWrite = $currentTimestamp - $writeTime;
                            if ($secondsFromWrite <= $totalSeconds){
                                if($currentSecondsPosition >= $secondsFromWrite){
                                    $temperaturesInit[$i] = $row->getSensorData();
                                    unset($sensorData[$key]);
                                }
                            }
                        }
                    }
                    $i++;
                }
                $temperaturesArray = $temperaturesInit;
            }
            $data['temperatures'] = array_reverse($temperaturesArray);
        }else{
            $sensorData = $this->em->getRepository(SensorData::class)->findOneBy(array('parentSensor'=>$sensor->getId()),array('id'=>'DESC'));
            if(empty($sensorData)){
                //create a fake object
                $fakeSensorData = new SensorData();
                $fakeSensorData->setParentSensor($sensor);
                $fakeSensorData->setSensorData(0);
                $fakeSensorData->setWriteTimestamp(new \DateTime('1977-01-01'));
            }
            //check if date is not null
            if ($sensorData->getWriteTimestamp() != NULL) {
                $writeTime = strtotime($sensorData->getWriteTimestamp()->format('Y-m-d H:i:s'));
                $currentTimestamp = strtotime(date("Y-m-d H:i:s"));
                $secondsFromWrite = $currentTimestamp - $writeTime;
                //check if the value is not older than specified amount of seconds
                if ($secondsFromWrite <= $seconds) {
                    $defaultTemperature = $sensorData->getSensorData();
                }
            }

            //fill the array*/
            $data['temperatures'] = $defaultTemperature;

        }
        //response creation
        $finalResponse = json_encode($data);
        $response = new Response($finalResponse);
        $response->headers->set('Content-Type', 'application/json');
        //return the response
        return $response;

    }
}
