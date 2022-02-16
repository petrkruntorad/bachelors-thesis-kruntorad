<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceOptions;
use App\Entity\Sensor;
use App\Entity\SensorData;
use App\Entity\User;
use App\Services\DeviceService;
use App\Services\SensorService;
use Doctrine\ORM\EntityManagerInterface;
use SebastianBergmann\CodeCoverage\Report\Text;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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

    public function __construct(
        EntityManagerInterface $em,
        DeviceService $ds,
        SensorService $sensorService
    )
    {
        $this->em = $em;
        $this->ds = $ds;
        $this->sensorService = $sensorService;
    }

    /**
     * @Route ("/admin/devices", name="devices_index")
     * @IsGranted("ROLE_USER")
     */
    public function index()
    {
        $devices = $this->em->getRepository(Device::class)->findBy(['isAllowed'=>1]);

        return $this->render('admin/devices/index.html.twig',[
            'devices'=>$devices,
        ]);
    }

    /**
     * @Route ("/admin/devices/wating", name="devices_waiting")
     * @IsGranted("ROLE_USER")
     */
    public function waiting()
    {
        $devices = $this->em->getRepository(Device::class)->getWaitingDevices();

        return $this->render('admin/devices/waiting.html.twig',[
            'devices'=>$devices,
        ]);
    }

    /**
     * @Route ("/admin/devices/not-activated", name="devices_not_activated")
     * @IsGranted("ROLE_USER")
     */
    public function not_activated()
    {
        $devices = $this->em->getRepository(Device::class)->findBy(['firstConnection' => null, 'macAddress' => null]);

        return $this->render('admin/devices/not-activated.html.twig',[
            'devices'=>$devices,
        ]);
    }

    /**
     * @Route ("/admin/devices/detail/{id}/{origin}", name="devices_detail")
     * @IsGranted("ROLE_USER")
     */
    public function detail(Device $device, $origin)
    {
        $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
        if(count($sensors)<1)
        {
            $this->addFlash(
                'bad',
                'Zařízení nemá přidružené senzory.'
            );
        }
        $sensorIds = [];
        $sensorStates = [];
        foreach ($sensors as $key => $sensor)
        {
            $sensorStates[$key]['id'] = $sensor->getId();
            $sensorStates[$key]['state'] = $this->sensorService->isSensorActive($sensor->getId());

            array_push($sensorIds, $sensor->getHardwareId());
        }
        $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));
        if(!$deviceOptions)
        {
            $this->addFlash(
                'bad',
                'Zařízení nebylo prozatím nastaveno.'
            );
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

        if($form->isSubmitted() && $form->isValid()) {
            try {
                $name = $form['name']->getData();
                $note = $form['note']->getData();
                $isAllowed = 0;

                $device = new Device();
                $device->setName($name);
                $device->setNote($note);
                $device->setIsAllowed($isAllowed);
                $device->setUniqueHash(Uuid::v4());

                $this->em->persist($device);
                $this->em->flush();

                $this->addFlash(
                    'good',
                    'Zařízení '.$name.' bylo úspěšně přidáno jako neaktivní zařízení. Pro dokončení nastavení přejděte <a href="'.$this->generateUrl('devices_settings',['id'=>$device->getId(), 'origin'=>$origin]).'" title="zde">zde</a>.'
                );

                return $this->redirectToRoute($origin);
            }
            catch (Exception $exception)
            {
                $this->addFlash(
                    'bad',
                    'Nastala neočekávaná vyjímka: '.$exception
                );
            }
        }else{
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

        if($form->isSubmitted() && $form->isValid()) {
            try {

                $this->em->persist($device);
                $this->em->flush();

                $this->addFlash(
                    'good',
                    'Zařízení '.$device->getName().' bylo úspěšně upraveno.'
                );

                if($origin == 'devices_detail')
                {
                    return $this->redirectToRoute($origin, array('id'=>$device->getId(), 'origin'=>$origin));
                }
                return $this->redirectToRoute($origin);
            }
            catch (Exception $exception)
            {
                $this->addFlash(
                    'bad',
                    'Nastala neočekávaná vyjímka.'
                );
            }
        }else{
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
        $settings = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

        if (!$settings)
        {
            $settings = new DeviceOptions();
            $settings->setNotificationsStatus(0);
            $settings->setParentDevice($device);
            $settings->setWriteInterval('*/5 * * * *');
            $this->em->persist($settings);
            $this->em->flush();
        }

        $users = $this->em->getRepository(User::class)->findAll();

        $userSelection = [''=>''];

        if ($users)
        {
            foreach ($users as $user)
            {
                $userSelection[$user->getUserIdentifier()] = $user->getId();
            }
        }

        $form = $this->createFormBuilder($settings)
            ->add('notifications_status', ChoiceType::class,[
                'label'=> 'Odesílání notifkací',
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
                'label'=> 'Příjemce notifikací',
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
                'label'=> 'Interval zápisu notifikací',
                'attr'=>[
                    'class'=>'select2',
                    'data-placeholder'=>'Interval zápisu notifikací',
                    'style'=>"width: 100%;",
                ],
                'choices' => $this->ds->getWriteIntervals()
            ])
            ->add('save', SubmitType::class,[
                'label'=>'Uložit',
                'attr'=> [
                    'class'=> 'btn btn-primary',
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
           try {
                $this->em->persist($settings);
                $this->em->flush();

                $this->addFlash(
                    'good',
                    'Nastavení bylo úspěšně dokončeno'
                );

                if($origin == 'devices_detail')
                {
                    return $this->redirectToRoute($origin, array('id'=>$device->getId(), 'origin'=>$origin));
                }
                return $this->redirectToRoute($origin);
            }
            catch (Exception $exception)
            {
                $this->addFlash(
                    'bad',
                    'Nastala neočekávaná vyjímka.'
                );
            }
        }else{
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
            $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findOneBy(array('parentDevice'=>$device));

            if ($deviceOptions)
            {
                $this->em->remove($deviceOptions);
                $this->em->flush();
            }

            $sensors = $this->em->getRepository(Sensor::class)->findBy(array('parentDevice'=>$device));
            if ($sensors)
            {
                foreach ($sensors as $sensor)
                {
                    $sensorData = $this->em->getRepository(SensorData::class)->findBy(array('parentSensor'=>$sensor));
                    if ($sensorData)
                    {
                        foreach ($sensorData as $data)
                        {
                            $this->em->remove($data);
                            $this->em->flush();
                        }
                    }
                    $this->em->remove($sensor);
                    $this->em->flush();
                }

            }

            $this->em->remove($device);
            $this->em->flush();

            $this->addFlash(
                'good',
                'Zařízení a jeho nastavení byla úspěšně odebrána.'
            );
        }
        catch (Exception $exception)
        {
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
            $device->setIsAllowed(1);
            $this->em->persist($device);
            $this->em->flush();

            $this->addFlash(
                'good',
                'Zařízení bylo úšpěšně potvrzeno.'
            );
        }
        catch (Exception $exception)
        {
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
            $device->setIsAllowed(0);
            $this->em->persist($device);
            $this->em->flush();

            $this->addFlash(
                'good',
                'Zařízení bylo úšpěšně zakázáno.'
            );
        }
        catch (Exception $exception)
        {
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
            if (!$_POST['uniqueHash'])
            {
                throw new Exception("Unique hash is missing.");
            }
            if (!$_POST['sensorId'])
            {
                throw new Exception("Sensor Id is missing.");
            }
            if (!$_POST['rawSensorData'])
            {
                throw new Exception("Sensor data are missing.");
            }
            $sensorId = strval($_POST['sensorId']);
            $uniqueHash = strval($_POST['uniqueHash']);
            $rawSensorData = floatval($_POST['rawSensorData']);

            $device = $this->em->getRepository(Device::class)->findOneBy(array('uniqueHash'=>$uniqueHash));

            if (!$device)
            {
                throw new Exception("No such device with a specified unique hash.");
            }

            if (!$device->getIsAllowed()){
                throw new Exception("The device is not allowed yet. Please allow the device in the administration first.");
            }

            $sensor = $this->em->getRepository(Sensor::class)->findOneBy(array('hardwareId'=>$sensorId, 'parentDevice'=>$device));

            if (!$sensor)
            {
                $sensor = new Sensor();
                $sensor->setParentDevice($device);
                $sensor->setHardwareId($sensorId);
                $this->em->persist($sensor);
                $this->em->flush();
            }

            $newSensorData = new SensorData();
            $newSensorData->setParentSensor($sensor);
            $newSensorData->setSensorData($rawSensorData);
            $newSensorData->setWriteTimestamp(new \DateTime("now"));
            $this->em->persist($newSensorData);
            $this->em->flush();

            return new Response('Success');
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
            if (!$_POST['uniqueHash'])
            {
                throw new Exception("Unique hash is missing.");
            }

            if (!$_POST['macAddress'])
            {
                throw new Exception("MAC address is missing.");
            }

            $uniqueHash = strval($_POST['uniqueHash']);
            $macAddress = strval($_POST['macAddress']);

            $device = $this->em->getRepository(Device::class)->findOneBy(array('uniqueHash'=>$uniqueHash));

            if (!$device)
            {
                throw new Exception("No such device with a specified unique hash.");
            }

            if (!$device->getFirstConnection())
            {
                $device->setFirstConnection(new \DateTime("now"));
            }
            $device->setMacAddress($macAddress);

            $this->em->persist($device);
            $this->em->flush();

            return new Response('Success');
        }
        catch (Exception $exception)
        {
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
            if (!$_POST['uniqueHash'])
            {
                throw new Exception("Unique hash is missing.");
            }
            $uniqueHash = strval($_POST['uniqueHash']);
            $device = $this->em->getRepository(Device::class)->findOneBy(array('uniqueHash'=>$uniqueHash));

            if (!$device)
            {
                throw new Exception("No such device with a specified unique hash.");
            }

            $fileContent = [];

            $response = new Response($this->ds->generateConfigFile($device));

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                'config.json'
            );

            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        }
        catch (Exception $exception)
        {
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
            $files = array("main.py", "functions.py", "init.py", "testCron.py");

            $zip = new \ZipArchive();

            $filename = "install.zip";

            $zip->open($filename,  \ZipArchive::CREATE);

            $zip->addFromString("config.json", $this->ds->generateConfigFile($device));
            foreach ($files as $file) {
                $zip->addFile($this->getParameter('client_folder').'/'.basename($file), $file);
            }

            $zip->close();

            $response = new Response(file_get_contents($filename));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
            $response->headers->set('Content-length', filesize($filename));

            @unlink($filename);

            return $response;

        }catch (Exception $exception)
        {
            throw new Exception($exception);
        }
    }


}
