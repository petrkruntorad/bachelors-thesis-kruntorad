<?php

namespace App\Entity;

use App\Repository\DeviceInfoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DeviceInfoRepository::class)
 */
class DeviceInfo
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
     * @ORM\Column (name="ip_address", type="string")
     */
    private $ipAddress;

    /**
     * @ORM\Column (name="local_ip_address", type="string")
     */
    private $localIpAddress;

    /**
     * @ORM\Column (name="total_storage", type="float")
     */
    private $totalStorage;

    /**
     * @ORM\Column (name="used_storage", type="float")
     */
    private $usedStorage;

    /**
     * @ORM\Column (name="device_name", type="string")
     */
    private $deviceName;

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
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ipAddress
     */
    public function setIpAddress($ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return mixed
     */
    public function getLocalIpAddress()
    {
        return $this->localIpAddress;
    }

    /**
     * @param mixed $localIpAddress
     */
    public function setLocalIpAddress($localIpAddress): void
    {
        $this->localIpAddress = $localIpAddress;
    }

    /**
     * @return mixed
     */
    public function getTotalStorage()
    {
        return $this->totalStorage;
    }

    /**
     * @param mixed $totalStorage
     */
    public function setTotalStorage($totalStorage): void
    {
        $this->totalStorage = $totalStorage;
    }

    /**
     * @return mixed
     */
    public function getUsedStorage()
    {
        return $this->usedStorage;
    }

    /**
     * @param mixed $usedStorage
     */
    public function setUsedStorage($usedStorage): void
    {
        $this->usedStorage = $usedStorage;
    }

    /**
     * @return mixed
     */
    public function getDeviceName()
    {
        return $this->deviceName;
    }

    /**
     * @param mixed $deviceName
     */
    public function setDeviceName($deviceName): void
    {
        $this->deviceName = $deviceName;
    }
}
