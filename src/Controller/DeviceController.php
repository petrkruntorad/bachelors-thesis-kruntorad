<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceNotifications;
use App\Entity\DeviceOptions;
use App\Entity\Sensor;
use App\Entity\SensorData;
use App\Entity\User;
use App\Services\DeviceService;
use App\Services\NotificationService;
use App\Services\SensorService;
use Doctrine\ORM\EntityManagerInterface;
use SebastianBergmann\CodeCoverage\Report\Text;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class DeviceController extends AbstractController
{

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var DeviceService
     */
    private $ds;

    /**
     * @var SensorService $sensorService
     */
    private $sensorService;

    /**
     * @var NotificationService $notificationService
     */
    private $notificationService;

    /**
     * @param EntityManagerInterface $em
     * @param DeviceService $ds
     * @param SensorService $sensorService
     */
    public function __construct(
        EntityManagerInterface $em,
        DeviceService $ds,
        SensorService $sensorService,
        NotificationService $notificationService
    )
    {
        $this->em = $em;
        $this->ds = $ds;
        $this->sensorService = $sensorService;
        $this->notificationService = $notificationService;
    }

    /**
     * @Route ("/admin/devices", name="devices_index")
     * @IsGranted("ROLE_USER")
     */
    public function index()
    {
        //array init
        $devicesConfig = [];
        //loads devices where isAllowed is set to 1 from database
        $devices = $this->em->getRepository(Device::class)->findBy(['isAllowed'=>1]);
        try {
            //checks if each device has config, otherwise the config for specific device cannot be downloaded
            foreach ($devices as $key => $device)
            {
                //if device has config than id is added to array
                if($this->ds->hasConfiguration($device))
                {
                    $devicesConfig[$key]['id'] = $device->getId();
                    $devicesConfig[$key]['hasConfiguration'] = $this->ds->hasConfiguration($device);
                }
            }
        }catch (InternalErrorException $exception){
            //error message in case of exception
            $this->addFlash(
                'bad',
                'Nepodařilo se ověřit existenci konfigurace jednotlivých zařízení: '.$exception
            );
        }

        return $this->render('admin/devices/index.html.twig',[
            'devices'=>$devices,
            'devicesConfig'=>$devicesConfig
        ]);
    }

    /**
     * @Route ("/admin/devices/wating", name="devices_waiting")
     * @IsGranted("ROLE_USER")
     */
    public function waiting()
    {
        $devicesConfig = [];
        //loads devices that are waiting for approval
        $devices = $this->em->getRepository(Device::class)->getWaitingDevices();
        try {
            //checks if each device has config, otherwise the config for specific device cannot be downloaded
            foreach ($devices as $key => $device)
            {
                //if device has config than id is added to array
                if($this->ds->hasConfiguration($device))
                {
                    $devicesConfig[$key]['id'] = $device->getId();
                    $devicesConfig[$key]['hasConfiguration'] = $this->ds->hasConfiguration($device);
                }
            }
        }catch (InternalErrorException $exception){
            //error message in case of exception
            $this->addFlash(
                'bad',
                'Nepodařilo se ověřit existenci konfigurace jednotlivých zařízení: '.$exception
            );
        }

        return $this->render('admin/devices/waiting.html.twig',[
            'devices'=>$devices,
            'devicesConfig'=>$devicesConfig
        ]);
    }

    /**
     * @Route ("/admin/devices/not-activated", name="devices_not_activated")
     * @IsGranted("ROLE_USER")
     */
    public function not_activated()
    {
        $devicesConfig = [];
        //loads devices where first connection and MAC address is unset
        $devices = $this->em->getRepository(Device::class)->findBy(['firstConnection' => null, 'macAddress' => null]);
        try {
            //checks if each device has config, otherwise the config for specific device cannot be downloaded
            foreach ($devices as $key => $device)
            {
                //if device has config than id is added to array
                if($this->ds->hasConfiguration($device))
                {
                    $devicesConfig[$key]['id'] = $device->getId();
                    $devicesConfig[$key]['hasConfiguration'] = $this->ds->hasConfiguration($device);
                }
            }
        }catch (InternalErrorException $exception){
            //error message in case of exception
            $this->addFlash(
                'bad',
                'Nepodařilo se ověřit existenci konfigurace jednotlivých zařízení: '.$exception
            );
        }

        return $this->render('admin/devices/not-activated.html.twig',[
            'devices'=>$devices,
            'devicesConfig'=>$devicesConfig
        ]);
    }

    /**
     * @Route ("/admin/devices/detail/{id}/{origin}", name="devices_detail")
     * @IsGranted("ROLE_USER")
     * @throws InternalErrorException
     */
    public function detail(Device $device, $origin)
    {
        //loads sensors for current device
        $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
        //if total count of sensors for current device is smaller than 1, the error message is returned
        if(count($sensors)<1)
        {
            $this->addFlash(
                'bad',
                'Zařízení nemá přidružené senzory.'
            );
        }
        $sensorIds = [];
        $sensorStates = [];

        //checks if device is active by activity of sensors
        if(!$this->sensorService->isDeviceActive($device)){
            $content = 'Zařízení ('.$device->getName().') není aktivní. Zkontrolujte jeho stav.';
            //creates notification
            $this->notificationService->createNotification($content, $device, null, 'activity');
        }

        //checks if all sensors for current device are active
        foreach ($sensors as $key => $sensor)
        {
            //if sensor is not active, the notification is created
            if(!$this->sensorService->isSensorActive($sensor->getId()))
            {
                $content = 'Senzor ('.$sensor->getHardwareId().') není aktivní. Zkontrolujte jeho správné zapojení.';
                //creates notification
                $this->notificationService->createNotification($content, $sensor->getParentDevice(), $sensor, 'activity');
            }
            //sets state to each sensor for frontend usage
            $sensorStates[$key]['id'] = $sensor->getId();
            $sensorStates[$key]['state'] = $this->sensorService->isSensorActive($sensor->getId());
            array_push($sensorIds, $sensor->getHardwareId());
        }
        //loads options for current devices
        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));
        //if options are not configured the error message is returned and user is redirected back to page where was detail called
        if(!$deviceOptions)
        {
            $this->addFlash(
                'bad',
                'Zařízení nebylo prozatím nastaveno.'
            );
            //redirects to page from which was detail opened
            return $this->redirectToRoute($origin);
        }
        return $this->render('admin/devices/detail.html.twig',[
            'device'=>$device,
            'deviceOptions'=>$deviceOptions,
            'writeInterval'=>$this->ds->getWriteParametersForCron($deviceOptions->getWriteInterval()),
            'origin'=>$origin,
            'sensors'=>$sensors,
            'sensorIds'=>json_encode($sensorIds, JSON_UNESCAPED_UNICODE),
            'sensorsState'=>$sensorStates,
        ]);
    }
    /**
     * @Route ("/admin/devices/create/{origin}", name="devices_create")
     * @IsGranted("ROLE_ADMIN")
     */
    public function create(Request $request, $origin){

        //form init
        $form = $this->createFormBuilder()
            ->add('name', TextType::class,[
                'label'=> 'Název zařízení',
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Název zařízení',
                    'maxlength'=>50
                ]
            ])
            ->add('note', TextType::class,[
                'label'=> 'Poznámka',
                'required'=>false,
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Poznámka',
                    'maxlength'=>200
                ]
            ])
            ->add('create', SubmitType::class,[
                'label'=>'Přidat',
                'attr'=> [
                    'class'=> 'btn btn-primary',
                ],
            ])
            ->getForm();

        $form->handleRequest($request);
        //if form is submitted and is valid by values on the backend
        if($form->isSubmitted() && $form->isValid()) {
            try {
                //creates new device and sets values from form
                $device = new Device();
                $device->setName($form['name']->getData());
                $device->setNote($form['note']->getData());
                $device->setIsAllowed(0);
                $device->setUniqueHash(Uuid::v4());
                //saves data
                $this->em->persist($device);
                $this->em->flush();
                //returns success message
                $this->addFlash(
                    'good',
                    'Zařízení '.$device->getName().' bylo úspěšně přidáno jako neaktivní zařízení. Pro dokončení nastavení přejděte <a href="'.$this->generateUrl('devices_settings',['id'=>$device->getId(), 'origin'=>$origin]).'" title="zde">zde</a>.'
                );
                //redirects to page from which was current page opened
                return $this->redirectToRoute($origin);
            }
            catch (Exception $exception)
            {
                //in case of exception returns message
                $this->addFlash(
                    'bad',
                    'Nastala neočekávaná vyjímka: '.$exception
                );
            }
        }else{
            //catches all errors from form and prints them
            foreach ($form->getErrors(true) as $formError) {
                $this->addFlash(
                    'bad',
                    $formError->getMessage()
                );
            }
        }

        return $this->render('admin/devices/create.html.twig', [
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route ("/admin/devices/update/{id}/{origin}", name="devices_update")
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(Request $request, Device $device, $origin){

        //form init with data for specified device from database
        $form = $this->createFormBuilder($device)
            ->add('name', TextType::class,[
                'label'=> 'Název zařízení',
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Název zařízení',
                    'maxlength'=>50
                ]
            ])
            ->add('is_allowed', ChoiceType::class, [
                'label'=> 'Je povoleno',
                'attr'=>[
                    'class'=>'select2',
                    'data-placeholder'=>'Vyberte, zda je povolen zápis do systému',
                    'style'=>"width: 100%;",
                ],
                'choices' => [
                    'Ano' => 1,
                    'Ne' => 0,
                ]
            ])
            ->add('note', TextType::class,[
                'label'=> 'Poznámka',
                'required'=>false,
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Poznámka',
                    'maxlength'=>200
                ]
            ])
            ->add('save', SubmitType::class,[
                'label'=>'Uložit',
                'attr'=> [
                    'class'=> 'btn btn-primary',
                ],
            ])
            ->getForm();

        $form->handleRequest($request);
        //if form is submitted and is valid by values on the backend
        if($form->isSubmitted() && $form->isValid()) {
            try {
                //saves changes
                $this->em->persist($device);
                $this->em->flush();

                //return success message
                $this->addFlash(
                    'good',
                    'Zařízení '.$device->getName().' bylo úspěšně upraveno.'
                );

                //checks if origin page is detail
                if($origin == 'devices_detail')
                {
                    //redirects to detail page with all required parameters
                    return $this->redirectToRoute($origin, array('id'=>$device->getId(), 'origin'=>$origin));
                }
                return $this->redirectToRoute($origin);
            }
            catch (Exception $exception)
            {
                //in case of exception returns message
                $this->addFlash(
                    'bad',
                    'Nastala neočekávaná vyjímka: '.$exception
                );
            }
        }else{
            //catches all errors from form and prints them
            foreach ($form->getErrors(true) as $formError) {
                $this->addFlash(
                    'bad',
                    $formError->getMessage()
                );
            }
        }

        return $this->render('admin/devices/update.html.twig', [
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route ("/admin/devices/{id}/settings/{origin}", name="devices_settings")
     * @IsGranted("ROLE_ADMIN")
     */
    public function settings(Device $device,Request $request, $origin)
    {
        //loads settings for specific device from database
        $settings = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

        //if settings are missing then inits predefined settings and saves them
        if (!$settings)
        {
            $settings = new DeviceOptions();
            $settings->setNotificationsStatus(0);
            $settings->setParentDevice($device);
            $settings->setWriteInterval('*/5 * * * *');
            $this->em->persist($settings);
            $this->em->flush();
        }
        //loads all users
        $users = $this->em->getRepository(User::class)->findAll();

        $userSelection = [''=>''];
        //if users than fill array with username and id
        if ($users)
        {
            foreach ($users as $user)
            {
                $userSelection[$user->getUserIdentifier()] = $user->getId();
            }
        }
        //inits form with settings from database
        $form = $this->createFormBuilder($settings)
            ->add('notifications_status', ChoiceType::class,[
                'label'=> 'Odesílání oznámení',
                'attr'=>[
                    'class'=>'select2',
                    'data-placeholder'=>'Odesílání notifkací',
                    'style'=>"width: 100%;",
                ],
                'choices'  => [
                    'Vypnuto' => 0,
                    'Zapnuto' => 1,
                ],
            ])
            ->add('notifications_target_user', EntityType::class, [
                'label'=> 'Příjemce oznámení',
                'class' => User::class,
                'required'=>false,
                'placeholder'=>'Nevybráno',
                'attr'=>[
                    'class'=>'select2',
                    'style'=>"width: 100%;",
                ],
                'choice_label' => 'username',
            ])
            ->add('write_interval', ChoiceType::class, [
                'label'=> 'Interval zápisu dat',
                'attr'=>[
                    'class'=>'select2',
                    'data-placeholder'=>'Interval zápisu dat',
                    'style'=>"width: 100%;",
                ],
                'choices' => $this->ds->getWriteIntervals()
            ])
            ->add('temperature_limit', NumberType::class,[
                'label'=> 'Teplotní limit pro odeslání oznámení',
                'required'=>false,
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Teplotní limit pro odeslání oznámení',
                    'min'=>0
                ]
            ])
            ->add('save', SubmitType::class,[
                'label'=>'Uložit',
                'attr'=> [
                    'class'=> 'btn btn-primary',
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        //if form is submitted and is valid by settings on the backend
        if($form->isSubmitted() && $form->isValid()) {
            try {
                //if notifications are switched to enabled, it verifies that the target user was selected as well, otherwise, it disables the notification
                if ($form['notifications_status']->getData() == 1 && !$form['notifications_target_user']->getData()) {
                    //throws information about missing notification target
                    $this->addFlash(
                        'bad',
                        'Pro aktivaci odesílání oznámení musí být zvolen cílový uživatel.'
                    );
                    $settings->setNotificationsStatus(0);
                }
                //saves settings
                $this->em->persist($settings);
                $this->em->flush();

                //return success message
                $this->addFlash(
                    'good',
                    'Nastavení bylo úspěšně dokončeno'
                );

                if ($origin == 'devices_detail') {
                    return $this->redirectToRoute($origin, array('id' => $device->getId(), 'origin' => $origin));
                }
                return $this->redirectToRoute($origin);
            } catch (Exception $exception) {
                //if exception occurs returns error message
                $this->addFlash(
                    'bad',
                    'Nastala neočekávaná vyjímka: ' . $exception
                );
            }
        }else{
            //returns each form error if occurs
            foreach ($form->getErrors(true) as $formError) {
                $this->addFlash(
                    'bad',
                    $formError->getMessage()
                );
            }
        }

        return $this->render('admin/devices/settings.html.twig', [
            'form' => $form->createView(),
            'device'=>$device,
        ]);
    }

    /**
     * @Route ("/admin/devices/remove/{id}/{origin}", name="devices_remove")
     * @IsGranted("ROLE_ADMIN")
     */
    public function remove(Device $device, $origin)
    {
        try {
            //loads device options by parent device
            $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

            //removes specified device options
            if ($deviceOptions)
            {
                $this->em->remove($deviceOptions);
                $this->em->flush();
            }
            //loads every notification for device
            $notifications = $this->em->getRepository(DeviceNotifications::class)->findBy(array('parentDevice'=>$device));
            //checks if notifications exists
            if($notifications){
                //iterates each notification and removes it from database
                foreach ($notifications as $notification)
                {
                    //removes each notification
                    $this->em->remove($notification);
                    $this->em->flush();
                }
            }

            //loads every sensor for device
            $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
            if ($sensors)
            {
                //iterates each sensor and removes received data from database
                foreach ($sensors as $sensor)
                {
                    //loads every received data for specified sensor
                    $sensorData = $this->em->getRepository(SensorData::class)->findBy(array('parentSensor'=>$sensor));
                    //checks if any record exist
                    if ($sensorData)
                    {
                        //iterates each record
                        foreach ($sensorData as $data)
                        {
                            //removes each record
                            $this->em->remove($data);
                            $this->em->flush();
                        }
                    }
                    //removes each sensor
                    $this->em->remove($sensor);
                    $this->em->flush();
                }

            }

            //removes device and saves changes
            $this->em->remove($device);
            $this->em->flush();

            //returns success message
            $this->addFlash(
                'good',
                'Zařízení a jeho nastavení byla úspěšně odebrána.'
            );
        }
        catch (Exception $exception)
        {
            //if exception occurs returns error message
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }

        return $this->redirectToRoute($origin);
    }

    /**
     * @Route ("/admin/device/activate/{id}/{origin}", name="device_activate")
     * @IsGranted("ROLE_ADMIN")
     * @throws Exception
     */
    public function activate(Device $device, $origin)
    {
        try {
            //checks if device was set up, device without settings cannot be allowed
            if($this->ds->hasConfiguration($device)){
                //sets is allowed to 1
                $device->setIsAllowed(1);
                //saves changes
                $this->em->persist($device);
                $this->em->flush();

                //returns success message
                $this->addFlash(
                    'good',
                    'Zařízení bylo úšpěšně potvrzeno.'
                );
            }else{
                //if configuration does not exist, returns error message
                $this->addFlash(
                    'bad',
                    'Před schválením zařízení je nejprve potřeba dokončit konfiguraci.'
                );
            }
        }
        catch (Exception $exception)
        {
            //if exception occurs returns error message
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }
        return $this->redirectToRoute($origin);
    }

    /**
     * @Route ("/admin/device/deactivate/{id}/{origin}", name="device_deactivate")
     * @IsGranted("ROLE_ADMIN")
     * @throws Exception
     */
    public function deactivate(Device $device, $origin)
    {
        try {
            //IsAllowed is changed to 0 so new data for that device cannot be added
            $device->setIsAllowed(0);

            //saves change
            $this->em->persist($device);
            $this->em->flush();

            //returns success message
            $this->addFlash(
                'good',
                'Zařízení bylo úšpěšně zakázáno.'
            );
        }
        catch (Exception $exception)
        {
            //if exception occurs returns error message
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }
        return $this->redirectToRoute($origin);
    }

    /**
     * @Route ("/device/write-data", name="device_write_data")
     * @throws Exception
     */
    public function write_data()
    {
        try {
            //checks if unique hash is defined
            if (!$_POST['uniqueHash'])
            {
                return new Response('Unique hash is missing.');
            }
            //checks if MAC address is defined
            if (!$_POST['macAddress'])
            {
                return new Response('MAC address is missing.');
            }
            //checks if sensor ID is defined
            if (!$_POST['sensorId'])
            {
                return new Response('Sensor Id is missing.');
            }
            //checks if raw sensor data are defined
            if (!$_POST['rawSensorData'])
            {
                return new Response('Sensor data are missing.');
            }

            //assigns values from post to variables
            $sensorId = strval($_POST['sensorId']);
            $uniqueHash = strval($_POST['uniqueHash']);
            $macAddress = strval($_POST['macAddress']);
            $rawSensorData = floatval($_POST['rawSensorData']);

            //checks if device with specified unique hash exists
            $device = $this->em->getRepository(Device::class)->findOneBy(array('uniqueHash'=>$uniqueHash,'macAddress'=>$macAddress));

            //if device is not defined throws exception
            if (!$device)
            {
                return new Response('No such device with a specified unique hash or MAC address.');
            }

            //gets options for device
            $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

            //if device is not defined throws exception
            if (!$deviceOptions)
            {
                return new Response('Device is not configured. Please try again later.');
            }

            //checks if device is allowed otherwise throws exception
            if (!$device->getIsAllowed()){
                return new Response('The device is not allowed yet. Please allow the device in the administration first.');
            }

            //loads information about specified sensor
            $sensor = $this->em->getRepository(Sensor::class)->findOneBy(array('hardwareId'=>$sensorId, 'parentDevice'=>$device));

            //checks if sensor exist otherwise creates new record
            if (!$sensor)
            {
                $sensor = new Sensor();
                $sensor->setParentDevice($device);
                $sensor->setHardwareId($sensorId);
                $this->em->persist($sensor);
                $this->em->flush();
            }

            //checks if limit is set

            if($deviceOptions->getTemperatureLimit() != NULL){
                //checks if real value is higher than limit
                if($rawSensorData > $deviceOptions->getTemperatureLimit()){
                    //notifications message
                    $content = "Naměřená hodnota (".$rawSensorData." °C) senzoru (".$sensor->getHardwareId().") u zařízení překročila nastavenou teplotu (".$deviceOptions->getTemperatureLimit()." °C)";
                    //calls service for notification creation
                    try {
                        $this->notificationService->createNotification($content, $device, $sensor, 'temperature');
                    }catch (Exception $exception){
                        throw new Exception($exception);
                    }

                }
            }

            //adds new data to database and saves them
            $newSensorData = new SensorData();
            $newSensorData->setParentSensor($sensor);
            $newSensorData->setSensorData($rawSensorData);
            $newSensorData->setWriteTimestamp(new \DateTime("now"));
            $this->em->persist($newSensorData);
            $this->em->flush();

            //returns success message
            return new Response('Data were write successfully.');
        }
        catch (Exception $exception)
        {
            throw new Exception("Something went wrong: ".$exception);
        }
    }

    /**
     * @Route ("/device/touch-server", name="device_touch")
     * @throws Exception
     */
    public function touchServer()
    {
        try {
            //checks if unique hash is defined
            if (!$_POST['uniqueHash'])
            {
                throw new Exception("Unique hash is missing.");
            }
            //checks if MAC address is defined
            if (!$_POST['macAddress'])
            {
                throw new Exception("MAC address is missing.");
            }


            //assigns values from post to variables
            $uniqueHash = strval($_POST['uniqueHash']);
            $macAddress = strval($_POST['macAddress']);

            //checks if device with specified unique hash exists
            $device = $this->em->getRepository(Device::class)->findOneBy(array('uniqueHash'=>$uniqueHash));

            //if device is not defined throws exception
            if (!$device)
            {
                throw new Exception("No such device with a specified unique hash.");
            }

            //checks if device first connection is not set and set current datetime
            if (!$device->getFirstConnection())
            {
                $device->setFirstConnection(new \DateTime("now"));
            }
            //sets current datetime as last connection
            $device->setLastConnection(new \DateTime("now"));

            //sets MAC address if is not set
            if($device->getMacAddress() == NULL) {
                $device->setMacAddress($macAddress);
            }

            //checks if IP address is defined and then adds it to database
            if($_POST['ipAddress']){
                $device->setLocalIpAddress(strval($_POST['ipAddress']));
            }
            //saves changes
            $this->em->persist($device);
            $this->em->flush();

            //checks every allowed device activity
            $this->sensorService->checkEveryAllowedDevice();

            return new Response('Device data changed successfully');
        }
        catch (Exception $exception)
        {
            //throws an exception in case some error occurs
            throw new Exception("Something went wrong: ".$exception);
        }
    }

    /**
     * @Route ("/device/get-updates", name="device_getUpdates")
     * @throws Exception
     */
    public function getUpdates()
    {
        try {
            //checks if uniqueHash is defined
            if (!$_POST['uniqueHash'])
            {
                //throws an exception in case that unique hash is missing
                throw new Exception("Unique hash is missing.");
            }
            //assigns uniqueHash to variable
            $uniqueHash = strval($_POST['uniqueHash']);

            //checks if macAddress is defined
            if (!$_POST['macAddress'])
            {
                //throws an exception in case that unique hash is missing
                throw new Exception("MAC address is missing.");
            }
            //assigns macAddress to variable
            $macAddress = strval($_POST['macAddress']);

            //checks if device with specified uniqueHash is in database
            $device = $this->em->getRepository(Device::class)->findOneBy(array('uniqueHash'=>$uniqueHash,'macAddress'=>$macAddress));

            //if device is not defined throws exception
            if (!$device)
            {
                throw new Exception("No such device with a specified unique hash.");
            }

            //inits the response with generated json
            $response = new Response($this->ds->generateConfigFile($device));

            //inits the disposition of file
            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                'config.json'
            );
            //sets header
            $response->headers->set('Content-Disposition', $disposition);

            //returns response
            return $response;
        }
        catch (Exception $exception)
        {
            //throws an exception in case some error occurs
            throw new Exception("Something went wrong: ".$exception);
        }
    }

    /**
     * @Route ("/devices/get_config/{id}", name="devices_get_config")
     * @throws Exception
     */
    public function get_config(Device $device)
    {


        try {
            //inits array with all files that should be included in zip file
            $files = array("main.py", "functions.py", "init.py");

            //inits ZipArchive
            $zip = new \ZipArchive();

            //defines zip name
            $filename = "install.zip";

            //opens specified zip file
            $zip->open($filename,  \ZipArchive::CREATE);

            //Adds generated config from database to JSON file and then adds JSON to zip file
            $zip->addFromString("config.json", $this->ds->generateConfigFile($device));
            foreach ($files as $file) {
                $zip->addFile($this->getParameter('client_folder').'/'.basename($file), $file);
            }

            //closes file
            $zip->close();

            //sets response with all headers
            $response = new Response(file_get_contents($filename));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
            $response->headers->set('Content-length', filesize($filename));

            //deletes the temp file for download
            @unlink($filename);

            return $response;

        }catch (Exception $exception)
        {
            //throws an exception in case some error occurs
            throw new Exception($exception);
        }
    }


}
