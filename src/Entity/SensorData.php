<?php

namespace App\Entity;

use App\Repository\SensorDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SensorDataRepository::class)
 */
class SensorData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Sensor")
     */
    private $parentSensor;

    /**
     * @ORM\Column (name="sensor_data",type="float")
     */
    private $sensorData;

    /**
     * @ORM\Column (name="write_timestamp", type="datetime")
     */
    private $writeTimestamp;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getParentSensor()
    {
        return $this->parentSensor;
    }

    /**
     * @param mixed $parentSensor
     */
    public function setParentSensor($parentSensor): void
    {
        $this->parentSensor = $parentSensor;
    }

    /**
     * @return mixed
     */
    public function getSensorData()
    {
        return $this->sensorData;
    }

    /**
     * @param mixed $sensorData
     */
    public function setSensorData($sensorData): void
    {
        $this->sensorData = $sensorData;
    }

    /**
     * @return mixed
     */
    public function getWriteTimestamp()
    {
        return $this->writeTimestamp;
    }

    /**
     * @param mixed $writeTimestamp
     */
    public function setWriteTimestamp($writeTimestamp): void
    {
        $this->writeTimestamp = $writeTimestamp;
    }

}
