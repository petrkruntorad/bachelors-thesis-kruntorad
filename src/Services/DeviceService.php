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

    //inits array with default value for write interval
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

    /**
     * @throws Exception
     */
    public function generateConfigFile(Device $device)
    {
        $fileContent = [];
        try {
            //loads options for specified device
            $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

            //sets values with specified keys to array
            $fileContent['uniqueHash'] = $device->getUniqueHash();
            $fileContent['writeInterval'] = $deviceOptions->getWriteInterval();
            $fileContent['writeUrl'] = $this->router->generate('device_write_data', array(), UrlGeneratorInterface::ABSOLUTE_URL);
            $fileContent['updateUrl'] = $this->router->generate('device_getUpdates', array(), UrlGeneratorInterface::ABSOLUTE_URL);
            $fileContent['touchUrl'] = $this->router->generate('device_touch', array(), UrlGeneratorInterface::ABSOLUTE_URL);

            //returns json with correct formatting
            return json_encode($fileContent, JSON_UNESCAPED_UNICODE);
        } catch (Exception $exception) {
            //throws exception if error occurs
            throw new Exception($exception);
        }

    }

    public function getWriteIntervals()
    {
        $writeIntervals = [];
        //checks if write interval settings are set
        if ($this->writeIntervalSettings)
        {
            //iterates each item in array
            foreach($this->writeIntervalSettings as $writeIntervalSetting)
            {
                //gets cron and sets it to array
                $writeIntervals[$writeIntervalSetting['description']] = $writeIntervalSetting['cron'];
            }
        }
        return $writeIntervals;
    }

    public function getWriteParametersForCron(string $cron)
    {
        $writeParameters = [];
        //checks if write interval settings are set
        if ($this->writeIntervalSettings)
        {
            //iterates each item in array
            foreach($this->writeIntervalSettings as $writeIntervalSetting)
            {
                //checks if cron value was provided
                if($writeIntervalSetting['cron'] == $cron)
                {
                    //sets values with keys to array
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
    public function hasConfiguration(Device $device)
    {
        try {
            //loads options for device
            $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));
            //if device options exists
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
