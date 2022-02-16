<?php

namespace App\Services;

use App\Entity\Device;
use App\Entity\DeviceNotifications;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

class NotificationService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * @throws InternalErrorException
     */
    public function createNotification(string $content, Device $device)
    {
        try {
            $notification = new DeviceNotifications();
            $notification->setNotification($content);
            $notification->setParentDevice($device);
            $notification->setState(0);
            $this->em->persist($notification);
            $this->em->flush();
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
}
