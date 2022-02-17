<?php

namespace App\Entity;

use App\Repository\DeviceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DeviceRepository::class)
 */
class Device
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column (name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column (name="note", type="string", length=255, nullable=true)
     */
    private $note;

    /**
     * @ORM\Column (name="first_connection", type="datetime", nullable=true)
     */
    private $firstConnection;

    /**
     * @ORM\Column (name="is_allowed", type="boolean")
     */
    private $isAllowed;

    /**
     * @ORM\Column (name="mac_address", type="string", length=255, nullable=true)
     */
    private $macAddress;

    /**
     * @ORM\Column (name="unique_hash", type="string", length=255, unique=true)
     */
    private $uniqueHash;

    /**
     * @ORM\Column (name="local_ip_address", type="string")
     */
    private $localIpAddress;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param mixed $note
     */
    public function setNote($note): void
    {
        $this->note = $note;
    }

    /**
     * @return mixed
     */
    public function getIsAllowed()
    {
        return $this->isAllowed;
    }

    /**
     * @param mixed $isAllowed
     */
    public function setIsAllowed($isAllowed): void
    {
        $this->isAllowed = $isAllowed;
    }

    /**
     * @return mixed
     */
    public function getFirstConnection()
    {
        return $this->firstConnection;
    }

    /**
     * @param mixed $firstConnection
     */
    public function setFirstConnection($firstConnection): void
    {
        $this->firstConnection = $firstConnection;
    }

    /**
     * @return mixed
     */
    public function getMacAddress()
    {
        return $this->macAddress;
    }

    /**
     * @param mixed $macAddress
     */
    public function setMacAddress($macAddress): void
    {
        $this->macAddress = $macAddress;
    }

    /**
     * @return mixed
     */
    public function getUniqueHash()
    {
        return $this->uniqueHash;
    }

    /**
     * @param mixed $uniqueHash
     */
    public function setUniqueHash($uniqueHash): void
    {
        $this->uniqueHash = $uniqueHash;
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



}
