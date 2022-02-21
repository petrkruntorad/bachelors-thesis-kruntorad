<?php

namespace App\Services;

use App\Entity\Device;
use App\Entity\DeviceNotifications;
use App\Entity\DeviceOptions;
use App\Entity\Sensor;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class NotificationService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var MailerService $mailerService
     */
    private $mailerService;

    public function __construct(
        EntityManagerInterface $em,
        MailerService $mailerService
    ) {
        $this->em = $em;
        $this->mailerService = $mailerService;
    }

    /**
     * @throws InternalErrorException
     */
    public function createNotification(string $content, Device $device = null, Sensor $sensor = null)
    {
        try {
            if(!$this->checkIfNotificationExists($device, $sensor))
            {
                if(!$device && !$sensor){
                    throw new ParameterNotFoundException('Nebylo poskytnuto zařízení nebo senzor.');
                }
                if($device == null)
                {
                    $device = $sensor->getParentDevice();
                }

                $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

                $notification = new DeviceNotifications();
                $notification->setNotificationContent($content);
                $notification->setParentDevice($device);
                if($sensor)
                {
                    $notification->setSensor($sensor);
                }
                $notification->setState(0);
                $notification->setOccurrence(new \DateTime());
                $this->em->persist($notification);
                $this->em->flush();

                if($deviceOptions->getNotificationsStatus()){
                    if($deviceOptions->getNotificationsTargetUser()){
                        $this->mailerService->sendEmail($deviceOptions->getNotificationsTargetUser()->getEmail(),$content);
                    }else{
                        throw new Exception('Pro zařízení není nastaven cílový uživatel.');
                    }

                }
            }
        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
    }

    /**
     * @throws InternalErrorException
     */
    public function changeNotificationState(DeviceNotifications $deviceNotifications, bool $state)
    {
        try {
            $deviceNotifications->setState($state);
            $this->em->persist($deviceNotifications);
            $this->em->flush();
        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
    }

    /**
     * @throws InternalErrorException
     */
    public function checkIfNotificationExists(Device $device = null, Sensor $sensor = null)
    {
        try {
            if($device == null && $sensor == null)
            {
                throw new InternalErrorException("Nebylo poskytnuto žádné zařízení nebo senzor.");
            }
            if($device==null){
                $device = $sensor->getParentDevice();
            }

            if($device && $sensor) {
                $notification = $this->em->getRepository(DeviceNotifications::class)->findOneBy(array('state'=>false, 'parentDevice'=>$device, 'sensor'=>$sensor));
                if($notification){
                    return true;
                }
            }elseif ($device && $sensor == null) {
                $notification = $this->em->getRepository(DeviceNotifications::class)->findOneBy(array('state'=>false, 'parentDevice'=>$device, 'sensor'=>null));
                if($notification){
                    return true;
                }
            }
        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
        return false;
    }

    /**
     * @throws InternalErrorException
     */
    public function getNumberOfActiveNotifications()
    {
        $totalRecords = 0;
        try {
            $notifications = $this->em->getRepository(DeviceNotifications::class)->findBy(array('state'=>false));
            $totalRecords = count($notifications);
        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
        return $totalRecords;
    }
}
