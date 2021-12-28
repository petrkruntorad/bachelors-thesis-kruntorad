<?php

namespace App\Entity;

use App\Repository\DeviceOptionsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DeviceOptionsRepository::class)
 */
class DeviceOptions
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Device")
     */
    private $parentDevice;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="email")
     *
     */
    private $notificationsTargetUser;

    /**
     * @ORM\Column(name="notifications_status", type="boolean")
     */
    private $notificationsStatus;

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
    public function getNotificationsTargetUser()
    {
        return $this->notificationsTargetUser;
    }

    /**
     * @param mixed $notificationsTargetUser
     */
    public function setNotificationsTargetUser($notificationsTargetUser): void
    {
        $this->notificationsTargetUser = $notificationsTargetUser;
    }

    /**
     * @return mixed
     */
    public function getNotificationsStatus()
    {
        return $this->notificationsStatus;
    }

    /**
     * @param mixed $notificationsStatus
     */
    public function setNotificationsStatus($notificationsStatus): void
    {
        $this->notificationsStatus = $notificationsStatus;
    }

}