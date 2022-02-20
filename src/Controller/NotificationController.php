<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceNotifications;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 */
class NotificationController extends AbstractController
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
     * @Route("/admin/notifications", name="notifications_index")
     * @IsGranted("ROLE_USER")
     */
    public function index()
    {
        $notifications = $this->em->getRepository(DeviceNotifications::class)->findBy(array(),array('state'=>'ASC'));


        return $this->render('admin/notifications/index.html.twig',[
            'notifications'=>$notifications
        ]);
    }

    /**
     * @Route("/admin/notifications/confirm/{id}/{state}", name="notifications_confirm")
     */
    public function confirm(DeviceNotifications $notification, bool $state)
    {
        try {
            $notification->setState($state);
            $this->em->persist($notification);
            $this->em->flush();
        }catch (Exception $exception){
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }
        return $this->redirectToRoute('notifications_index');
    }
}
