<?php

namespace App\Services;

use App\Entity\Device;
use App\Entity\DeviceOptions;
use App\Entity\Sensor;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
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

    /**
     * @var array[] $writeIntervalSettings
     */
    private $writeIntervalSettings = [
        ['description'=>'Každou minutu','cron'=>'* * * * *','secondsSteps'=>60],
        ['description'=>'Každých 5 minut','cron'=>'*/5 * * * *','secondsSteps'=>300],
        ['description'=>'Každých 15 minut','cron'=>'*/15 * * * *','secondsSteps'=>900],
    ];

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

    public function getWriteIntervals()
    {
        $writeIntervals = [];
        if ($this->writeIntervalSettings)
        {
            foreach($this->writeIntervalSettings as $writeIntervalSetting)
            {
                $writeIntervals[$writeIntervalSetting['description']] = $writeIntervalSetting['cron'];
            }
        }
        return $writeIntervals;
    }

    public function getWriteParametersForCron(string $cron)
    {
        $writeParameters = [];
        if ($this->writeIntervalSettings)
        {
            foreach($this->writeIntervalSettings as $writeIntervalSetting)
            {
                if($writeIntervalSetting['cron'] == $cron)
                {
                    $writeParameters['description'] = $writeIntervalSetting['description'];
                    $writeParameters['cron'] = $writeIntervalSetting['cron'];
                    $writeParameters['secondsSteps'] = $writeIntervalSetting['secondsSteps'];
                }
            }
        }
        return $writeParameters;
    }

    /**
     * @throws InternalErrorException
     */
    public function isDeviceActive(Device $device, bool $sensorActivity = false)
    {
        $active = false;
        try {
            $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
            foreach ($sensors as $sensor)
            {
                if($sensorActivity)
                {
                    $active = true;
                }
            }
        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
        return $active;
    }

    /**
     * @throws InternalErrorException
     */
    public function hasConfiguration(Device $device)
    {
        try {
            $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));
            if($deviceOptions)
            {
                return true;
            }
        }catch (Exception $exception){
            throw new InternalErrorException($exception);
        }
        return false;
    }
}
