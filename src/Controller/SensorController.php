<?php

namespace App\Controller;

use App\Entity\Sensor;
use App\Entity\SensorData;
use App\Services\DeviceService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SensorController extends AbstractController
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
     * @Route("/admin/sensor/remove/{id}/{origin}", name="sensor_remove")
     */
    public function remove(Sensor $sensor, string $origin)
    {
        try{
            $sensorData = $this->em->getRepository(SensorData::class)->findBy(array('parentSensor'=>$sensor));
            foreach ($sensorData as $row)
            {
                $this->em->remove($row);
            }
            $this->em->flush();

            $this->em->remove($sensor);
            $this->em->flush();

            $this->addFlash(
                'good',
                'Senzor byl úspěšně odstraněn.'
            );
        }catch (Exception $exception){
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }
        return $this->redirectToRoute('devices_detail',[
            'id'=>$sensor->getParentDevice()->getId(),
            'origin'=>$origin
        ]);
    }
}
