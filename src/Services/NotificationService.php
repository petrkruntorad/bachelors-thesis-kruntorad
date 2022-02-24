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
    public function createNotification(string $content, Device $device = null, Sensor $sensor = null, string $notificationType = null)
    {

        try {
            //checks if notifications for specified sensor and device exists
            if(!$this->checkIfNotificationExists($device, $sensor, $notificationType))
            {
                //checks if device and sensor is set
                if(!$device && !$sensor){
                    throw new ParameterNotFoundException('Nebylo poskytnuto zařízení nebo senzor.');
                }

                //if device is null, device is set from parent device of sensor
                if($device == null)
                {
                    $device = $sensor->getParentDevice();
                }

                //loads device options
                $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

                //creates device notifications object and sets values
                $notification = new DeviceNotifications();
                $notification->setNotificationContent($content);
                $notification->setParentDevice($device);
                //if sensors is specified sets value to object
                if($sensor)
                {
                    $notification->setSensor($sensor);
                }
                $notification->setState(0);
                $notification->setOccurrence(new \DateTime());
                $notification->setNotificationType($notificationType);
                //saves changes
                $this->em->persist($notification);
                $this->em->flush();

                //checks if sending of notifications via email is enabled
                if($deviceOptions->getNotificationsStatus()){
                    //checks if targeted user is set
                    if($deviceOptions->getNotificationsTargetUser()){
                        //sends notification to user
                        $this->mailerService->sendNotificationEmail($deviceOptions->getNotificationsTargetUser()->getEmail(),$content);
                    }else{
                        //throws notification if target user is missing
                        throw new Exception('Pro zařízení není nastaven cílový uživatel.');
                    }
                }
            }else{
                //if device and sensor is null throws exception
                if($device == null && $sensor == null)
                {
                    throw new InternalErrorException("Nebylo poskytnuto žádné zařízení nebo senzor.");
                }
                //if device is not set but sensor is provided, the parent device value is used
                if(!$device && $sensor){
                    $device = $sensor->getParentDevice();
                }

                //checks what value were provided and chooses the right query
                if ($device && $sensor == null) {
                    $notification = $this->em->getRepository(DeviceNotifications::class)->findOneBy(array('state'=>false, 'parentDevice'=>$device, 'sensor'=>null, 'notificationType' => $notificationType));
                }else{
                    $notification = $this->em->getRepository(DeviceNotifications::class)->findOneBy(array('state'=>false, 'parentDevice'=>$device, 'sensor'=>$sensor, 'notificationType' => $notificationType));
                }
                //sets current datetime
                $notification->setOccurrence(new \DateTime());
                //changes notification content
                if($notificationType == 'temperature'){
                    $notification->setNotificationContent($content);
                }
                //saves changes
                $this->em->persist($notification);
                $this->em->flush();
            }
        }catch (Exception $exception){
            //throws exception if error occurs
            throw new InternalErrorException($exception);
        }
    }

    /**
     * @throws InternalErrorException
     */
    public function changeNotificationState(DeviceNotifications $deviceNotifications, bool $state)
    {
        try {
            //sets specified state to notification
            $deviceNotifications->setState($state);
            $this->em->persist($deviceNotifications);
            $this->em->flush();
        }catch (Exception $exception){
            //throws exception if error occurs
            throw new InternalErrorException($exception);
        }
    }

    /**
     * @throws InternalErrorException
     */
    public function checkIfNotificationExists(Device $device = null, Sensor $sensor = null, string $notificationType = null)
    {
        try {
            //checks if device and sensor is not null else throws exception
            if($device == null && $sensor == null)
            {
                throw new InternalErrorException("Nebylo poskytnuto žádné zařízení nebo senzor.");
            }

            //if device is null parent device of sensor is used
            if($device==null){
                $device = $sensor->getParentDevice();
            }

            //if sensor and device is set, get notification with specified params
            if($device && $sensor) {
                $notification = $this->em->getRepository(DeviceNotifications::class)->findOneBy(array('state'=>false, 'parentDevice'=>$device, 'sensor'=>$sensor, 'notificationType' => $notificationType));
                //if notification exist returns true
                if($notification){
                    return true;
                }
            }elseif ($device && $sensor == null) {
                //find sensor by device
                $notification = $this->em->getRepository(DeviceNotifications::class)->findOneBy(array('state'=>false, 'parentDevice'=>$device, 'sensor'=>null, 'notificationType' => $notificationType));
                //if notification exist returns true
                if($notification){
                    return true;
                }
            }
        }catch (Exception $exception){
            //throws exception if error occurs
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
            //loads notifications from database
            $notifications = $this->em->getRepository(DeviceNotifications::class)->findBy(array('state'=>false));
            //counts notification
            $totalRecords = count($notifications);
        }catch (Exception $exception){
            //throws exception if error occurs
            throw new InternalErrorException($exception);
        }
        return $totalRecords;
    }
}
