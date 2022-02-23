<?php

namespace App\Controller;

use App\Entity\DeviceOptions;
use App\Entity\User;
use App\Services\MailerService;
use App\Services\UserService;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class UserController extends AbstractController
{

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserService $userService
     */
    private $userService;

    /**
     * @var MailerService $mailerService
     */
    private $mailerService;

    public function __construct(
        EntityManagerInterface $em,
        UserService $userService,
        MailerService $mailerService
    )
    {
        $this->em = $em;
        $this->userService = $userService;
        $this->mailerService = $mailerService;
    }

    /**
     * @Route ("/admin/users", name="users_index")
     */
    public function index()
    {
        //loads users
        $users = $this->em->getRepository(User::class)->findAll();

        return $this->render('admin/users/index.html.twig',[
            'users'=>$users,
        ]);
    }

    /**
     * @Route ("/admin/users/create", name="user_create")
     */
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        //assigns random password to variable
        $randomPassword = $this->userService->generatePassword();
        //form init
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class,[
                'label'=> 'E-mail',
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'E-mail',
                    'autocomplete'=>'email'
                ]
            ])
            ->add('username', TextType::class,[
                'label'=> 'Uživatelské jméno',
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Uživatelské jméno',
                    'autocomplete'=>'username'
                ]
            ])
            ->add('password', TextType::class,[
                'label'=> 'Heslo',
                'attr' => [
                    'type' => 'password',
                    'class'=>'form-control',
                    'placeholder'=>'Heslo',
                    'minlength'=>8,
                    'value'=>$randomPassword
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label'=>'Role',
                'multiple'=>true,
                'attr'=>[
                    'class'=>'select2',
                    'data-placeholder'=>"Vyberte roli",
                    'style'=>"width: 100%;",

                ],
                'choices'=> array(
                    ''=>'',
                    'Administrátor'=>'ROLE_ADMIN',
                    'Uživatel'=>'ROLE_USER',
                ),
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
                //assigns values from form
                $email = $form['email']->getData();
                $username = $form['username']->getData();
                $password = $form['password']->getData();
                $roles = $form->getData()['roles'];

                //creates new object user with values from form
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setRoles($roles);

                //uses password hasher to hash password
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );

                //sets hashed password
                $user->setPassword($hashedPassword);

                //saves user to database
                $this->em->persist($user);
                $this->em->flush();

                //sends email to user mail
                $this->mailerService->sendNewAccountEmail($user->getEmail(), $user->getUserIdentifier(), $password);

                //returns success message
                $this->addFlash(
                    'good',
                    'Uživatel '.$username.' byl úspěšně přidán.'
                );

                //redirects to users overview
                return $this->redirectToRoute('users_index');
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

        return $this->render('admin/users/create.html.twig', [
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route ("/admin/users/update/{id}", name="user_update")
     */
    public function update(Request $request, UserPasswordHasherInterface $passwordHasher, User $user)
    {
        //assigns original password to variable
        $originalPassword = $user->getPassword();

        //form init with data for specified user from database
        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class,[
                'label'=> 'E-mail',
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'E-mail',
                    'autocomplete'=>'email'
                ]
            ])
            ->add('username', TextType::class,[
                'label'=> 'Uživatelské jméno',
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Uživatelské jméno',
                    'autocomplete'=>'username'
                ]
            ])
            ->add('password', PasswordType::class,[
                'label'=> 'Heslo',
                'required'=>false,
                'empty_data' => '',
                'attr' => [
                    'type' => 'password',
                    'class'=>'form-control',
                    'placeholder'=>'Heslo',
                    'minlength'=>8,
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'label'=>'Role',
                'multiple'=>true,
                'attr'=>[
                    'class'=>'select2',
                    'data-placeholder'=>"Vyberte roli",
                    'style'=>"width: 100%;",

                ],
                'choices'=> array(
                    ''=>'',
                    'Administrátor'=>'ROLE_ADMIN',
                    'Uživatel'=>'ROLE_USER',
                ),
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
                if($form['password']->getData())
                {
                    $password = $form['password']->getData();
                    //uses password hasher to hash password
                    $hashedPassword = $passwordHasher->hashPassword(
                        $user,
                        $password
                    );
                    //sets new password
                    $user->setPassword($hashedPassword);
                    //sends email with new password
                    $this->mailerService->sendPasswordChangeEmail($user->getEmail(),$user->getUserIdentifier(), $password);
                }else{
                    //sets original password to prevent change
                    $user->setPassword($originalPassword);
                }

                //saves changes
                $this->em->persist($user);
                $this->em->flush();

                //returns success message and redirects to user overview
                $this->addFlash(
                    'good',
                    'Změny byly úspěšně uloženy.'
                );

                return $this->redirectToRoute('users_index');
            }
            catch (Exception $exception)
            {
                //throws exception if occurs
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

        return $this->render('admin/users/update.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route ("/admin/users/remove/{id}", name="user_remove")
     */
    public function remove(User $user): RedirectResponse
    {
        try {
            //checks that current user is not same as user that should be removed from database
            if ($user->getId() != $this->getUser()->getId())
            {
                //loads all device options where is specified user used
                $deviceOptions = $this->em->getRepository(DeviceOptions::class)->findBy(array('notificationsTargetUser'=>$user));
                foreach ($deviceOptions as $singleDeviceOptions)
                {
                    //removes specified user from notifications target and saves cahgnes
                    $singleDeviceOptions->setNotificationsStatus(0);
                    $singleDeviceOptions->setNotificationsTargetUser(NULL);
                    $this->em->persist($singleDeviceOptions);
                }
                $this->em->flush();

                //removes user
                $this->em->remove($user);
                $this->em->flush();

                //returns success message
                $this->addFlash(
                    'good',
                    'Uživatel: <b>'.$user->getUserIdentifier().'</b> byl úspěšně smazán.'
                );
            }
            else
            {
                //returns error message
                $this->addFlash(
                    'bad',
                    'Nelze smazat vlastní účet.'
                );
            }
        }
        catch (Exception $exception)
        {
            //in case of exception returns message
            $this->addFlash(
                'bad',
                'Nastala neočekávaná vyjímka: '.$exception
            );
        }


        return $this->redirectToRoute('users_index');
    }
}
