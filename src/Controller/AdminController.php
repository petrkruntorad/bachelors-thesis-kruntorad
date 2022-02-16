<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\User;
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

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/dashboard", name="admin_homepage")
     * @IsGranted("ROLE_USER")
     */
    public function index() {
        $allowedDevices = $this->em->getRepository(Device::class)->findBy(array('isAllowed'=>1));
        $waitingDevices = $this->em->getRepository(Device::class)->getWaitingDevices();
        $notActivatedDevices = $this->em->getRepository(Device::class)->findBy(['firstConnection' => null, 'macAddress' => null]);
        $users = $this->em->getRepository(User::class)->findAll();


        return $this->render('admin/dashboard/index.html.twig',[
            'allowedDevices'=>$allowedDevices,
            'waitingDevices'=>$waitingDevices,
            'notActivatedDevices'=>$notActivatedDevices,
            'users'=>$users
        ]);
    }
}
