<?php

namespace App\Entity;

use App\Repository\SensorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SensorRepository::class)
 */
class Sensor
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
     * @ORM\Column(name="hardware_id", type="string", length=255, unique=true)
     */
    private $hardwareId;

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
    public function getHardwareId()
    {
        return $this->hardwareId;
    }

    /**
     * @param mixed $hardwareId
     */
    public function setHardwareId($hardwareId): void
    {
        $this->hardwareId = $hardwareId;
    }

}
