<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\Sensor;
use App\Entity\SensorData;
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

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;
    }


    /**
     * @Route("/device/sensor/get-data/{id}/{hardwareId}/{push}", name="sensor_getData")
     * @IsGranted("ROLE_USER")
     */
    public function getData(Device $device, string $hardwareId, $push=0){
        $data = [];

        $sensor = $this->em->getRepository(Sensor::class)->findOneBy(array('hardwareId'=>$hardwareId, 'parentDevice'=>$device->getId()));
        $sensorDataLenght = 0;
        $temperaturesArray=[];
        $defaultTemperature = 0;
        $seconds = 60;

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
                        $currentTimestamp = strtotime(date("Y-m-d H:i:s"));
                        $secondsFromWrite = $currentTimestamp - $writeTime;
                        if ($seconds<=5400){
                            if($secondsFromWrite<=$seconds){
                                $temperaturesArray[] = $sensorData[$i]->getSensorData();
                                $i = $i+1;
                            }else{
                                $temperaturesArray[] = 0;
                            }
                            $seconds = $seconds + 60;
                        }else{
                            $i = 90;
                        }

                    }else{
                        $temperaturesArray[] = 0;
                    }
                }
            }else{
                //create a fake object
                $fakeSensorData = new SensorData();
                $fakeSensorData->setParentSensor($sensor);
                $fakeSensorData->setSensorData(0);
                $fakeSensorData->setWriteTimestamp(new \DateTime('1977-01-01'));
                if($sensorDataLenght<90){
                    $missingvalues = 89-$sensorDataLenght;
                    for ($i=0; $i <= $missingvalues; $i++) {
                        array_push($temperaturesArray, $fakeSensorData);
                    }

                }
                for ($i = 0; $i <= 89;) {
                    //check if write time is not null
                    if ($sensorData[$i]->getWriteTimestamp() != NULL){
                        $writeTime = strtotime($sensorData[$i]->getWriteTimestamp()->format('Y-m-d H:i:s'));
                        $currentTimestamp = strtotime(date("Y-m-d H:i:s"));
                        $secondsFromWrite = $currentTimestamp - $writeTime;
                        //check if the value is not older than 90 minutes
                        if ($seconds<=5400){
                            if($secondsFromWrite<=$seconds){
                                $temperaturesArray[] = $sensorData[$i]->getSensorData();
                                $i++;
                            }else{
                                $temperaturesArray[] = 0;
                            }
                            $seconds = $seconds + 60;
                        }else{
                            $i = 90;
                        }

                    }else{
                        $temperaturesArray[] = 0;
                    }
                }
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
                //check if the value is not older than 60 seconds
                if ($secondsFromWrite <= 60) {
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
