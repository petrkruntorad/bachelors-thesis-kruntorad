<?php

namespace App\Entity;

use App\Repository\DeviceNotificationsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DeviceNotificationsRepository::class)
 */
class DeviceNotifications
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Device")
     */
    private $parentDevice;

    /**
     * @ORM\ManyToOne (targetEntity="App\Entity\Sensor")
     * @ORM\JoinColumn(name="sensor_id", referencedColumnName="id", nullable=true)
     */
    private $sensor;

    /**
     * @ORM\Column(name="notification_content", type="string", length=255)
     */
    private $notificationContent;

    /**
     * @ORM\Column(name="occurrence", type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $occurrence;

    /**
     * @ORM\Column(name="state", type="boolean")
     */
    private $state;

    /**
     * @ORM\Column(name="notificationType", type="string", length=255, nullable=true)
     */
    private $notificationType;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getParentDevice()
    {
        return $this->parentDevice;
    }

    /**
     * @param mixed $parentDevice
     */
    public function setParentDevice($parentDevice): void
    {
        $this->parentDevice = $parentDevice;
    }

    /**
     * @return mixed
     */
    public function getSensor()
    {
        return $this->sensor;
    }

    /**
     * @param mixed $sensor
     */
    public function setSensor($sensor): void
    {
        $this->sensor = $sensor;
    }

    /**
     * @return mixed
     */
    public function getNotificationContent()
    {
        return $this->notificationContent;
    }

    /**
     * @param mixed $notificationContent
     */
    public function setNotificationContent($notificationContent): void
    {
        $this->notificationContent = $notificationContent;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state): void
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getOccurrence()
    {
        return $this->occurrence;
    }

    /**
     * @param mixed $occurrence
     */
    public function setOccurrence($occurrence): void
    {
        $this->occurrence = $occurrence;
    }

    /**
     * @return mixed
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * @param mixed $notificationType
     */
    public function setNotificationType($notificationType): void
    {
        $this->notificationType = $notificationType;
    }

}
