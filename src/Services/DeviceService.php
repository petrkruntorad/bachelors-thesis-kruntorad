<?php

namespace App\Services;

use App\Entity\Device;
use App\Entity\DeviceOptions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class DeviceService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    public function __construct(
        EntityManagerInterface $em,
        UrlGeneratorInterface $router
    ) {
        $this->em = $em;
        $this->router = $router;
    }

    public function generateConfigFile(Device $device)
    {
        $fileContent = [];

        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

        $fileContent['uniqueHash'] = $device->getUniqueHash();
        $fileContent['writeInterval'] = $deviceOptions->getWriteInterval();
        $fileContent['writeUrl'] = $this->router->generate('device_write_data', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $fileContent['updateUrl'] = $this->router->generate('device_getUpdates', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $fileContent['touchUrl'] = $this->router->generate('device_touch', array(), UrlGeneratorInterface::ABSOLUTE_URL);

        return json_encode($fileContent, JSON_UNESCAPED_UNICODE);
    }

}
