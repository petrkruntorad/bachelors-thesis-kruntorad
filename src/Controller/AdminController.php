<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceNotifications;
use App\Entity\DeviceOptions;
use App\Entity\User;
use App\Services\SensorService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 */
class AdminController extends AbstractController
{

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SensorService $sensorService
     */
    private $sensorService;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(
        EntityManagerInterface $em,
        SensorService $sensorService
    )
    {
        $this->em = $em;
        $this->sensorService = $sensorService;
    }

    /**
     * @Route("/admin/dashboard", name="admin_homepage")
     * @IsGranted("ROLE_USER")
     */
    public function index() {
        //loads allowed devices
        $allowedDevices = $this->em->getRepository(Device::class)->findBy(array('isAllowed'=>1));
        //loads waiting devices
        $waitingDevices = $this->em->getRepository(Device::class)->getWaitingDevices();
        //loads not activated devices
        $notActivatedDevices = $this->em->getRepository(Device::class)->findBy(['firstConnection' => null, 'macAddress' => null]);
        //loads users
        $users = $this->em->getRepository(User::class)->findAll();

        //loads options for devices where target of notifications is current user
        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findBy(array('notificationsTargetUser'=>$this->getUser()));
        $deviceIds = array();
        //appends ids of all devices for current user
        foreach ($deviceOptions as $option){
            array_push($deviceIds, $option->getParentDevice()->getId());
        }
        //loads notifications for specific devices
        $notifications = $this->em->getRepository(DeviceNotifications::class)->findBy(array('parentDevice'=>$deviceIds, 'state'=>false));

        //checks every device activity
        $this->sensorService->checkEveryDevice();
        return $this->render('admin/dashboard/index.html.twig',[
            'allowedDevices'=>$allowedDevices,
            'waitingDevices'=>$waitingDevices,
            'notActivatedDevices'=>$notActivatedDevices,
            'users'=>$users,
            'notifications'=>$notifications
        ]);
    }
}
