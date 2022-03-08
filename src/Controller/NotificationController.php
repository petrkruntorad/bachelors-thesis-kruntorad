<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceNotifications;
use App\Entity\DeviceOptions;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

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
     * @IsGranted("ROLE_ADMIN")
     */
    public function index()
    {
        //loads all notifications ordered by state
        $notifications = $this->em->getRepository(DeviceNotifications::class)->findBy(array(),array('state'=>'ASC'));

        return $this->render('admin/notifications/index.html.twig',[
            'notifications'=>$notifications
        ]);
    }

    /**
     * @Route("/admin/notifications/confirm/{id}/{origin}", name="notifications_confirm")
     * @IsGranted("ROLE_USER")
     */
    public function confirm(DeviceNotifications $notification, string $origin)
    {
        try {
            //checks if the user is admin, otherwise user can confirm only notifications that are meant for him
            if($this->isGranted('ROLE_ADMIN')){
                //checks actual state and set the opposite one
                if($notification->getState()){
                    $notification->setState(false);
                }else{
                    $notification->setState(true);
                }
                //saves changes
                $this->em->persist($notification);
                $this->em->flush();

                if($notification->getState() == false){
                    $this->addFlash(
                        'good',
                        'Potvrzení oznámení bylo úspěšně zrušeno.'
                    );
                }else{
                    $this->addFlash(
                        'good',
                        'Oznámení bylo potvrzeno.'
                    );
                }
            }else{
                $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$notification->getParentDevice()));
                //check if sending of notifications is enabled
                if($deviceOptions->getNotificationsStatus() == true)
                {
                    //checks if notification target user match the current user
                    if($this->getUser() == $deviceOptions->getNotificationsTargetUser())
                    {
                        //checks actual state and set the opposite one
                        if($notification->getState()){
                            $notification->setState(false);
                        }else{
                            $notification->setState(true);
                        }
                        //saves changes
                        $this->em->persist($notification);
                        $this->em->flush();

                        $this->addFlash(
                            'bad',
                            'Oznámení bylo potvrzeno.'
                        );
                    }else{
                        $this->addFlash(
                            'good',
                            'Nelze potvrdit oznámení, které nespadá pod Váš účet.'
                        );
                    }
                }
            }
        }catch (Exception $exception){
            //in case of exception returns message
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }
        return $this->redirectToRoute($origin);
    }

    /**
     * @Route("/admin/notifications/remove/{id}", name="notifications_remove")
     * @IsGranted("ROLE_ADMIN")
     */
    public function remove(DeviceNotifications $notification)
    {
        try {
            //remove specified record
            $this->em->remove($notification);
            //saves changes
            $this->em->flush();
            $this->addFlash(
                'good',
                'Oznámení bylo úspěšně smazáno'
            );
        } catch (Exception $exception) {
            //in case of exception returns message
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }

        return $this->redirectToRoute('notifications_index');
    }
}
