<?php

namespace App\Controller;

use App\Entity\User;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;
    }
    /**
     * @Route ("/admin/profile/update/{id}", name="profile_update")
     * @IsGranted("ROLE_USER")
     */
    public function update(Request $request, User $user)
    {
        //checks if current user id and id of account that is edited is same
        if ($user->getId() != $this->getUser()->getId())
        {
            //throws error message
            $this->addFlash(
                'bad',
                'Nelze editovat jiné uživatelské profily.'
            );

            //redirects back to current user profile
            return $this->redirectToRoute('profile_update',['id'=>$this->getUser()->getId()]);
        }

        //inits form
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
                //inits varaible unique with default value
                $unique = true;
                //inits variable with data from form
                $email = $form->getData('email');
                $username = $form->getData('username');
                //checks if in database exist user with specified username
                $existingUsers = $this->em->getRepository(User::class)->findBy(array('username'=>$username));
                //user with specified username exist
                if(count($existingUsers)>0)
                {
                    //iterates each record with specified username
                    foreach ($existingUsers as $existingUser){
                        //if current user id and email is not same as record returns false
                        if($existingUser->getId() != $user->getId() && $existingUser->getEmail() == $email)
                        {
                            $unique = false;
                        }
                        //if current user id and username is not same as record returns false
                        if($existingUser->getId() != $user->getId() && $existingUser->getUserIdentifier() == $username)
                        {
                            $unique = false;
                        }
                    }
                }
                //if username is unique saves changes
                if($unique){
                    $this->em->persist($user);
                    $this->em->flush();

                    //returns success message
                    $this->addFlash(
                        'good',
                        'Údaje byly úspěšně uloženy.'
                    );
                }else{
                    //returns error message
                    $this->addFlash(
                        'bad',
                        'Uživatelské jméno nebo heslo je zabrané.'
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
        }else{
            //returns each form error if occurs
            foreach ($form->getErrors(true) as $formError) {
                $this->addFlash(
                    'bad',
                    $formError->getMessage()
                );
            }
        }

        return $this->render('admin/profile/update.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route ("/admin/profile/password-change/{id}", name="profile_password_change")
     * @IsGranted("ROLE_USER")
     */
    public function password_change(Request $request, UserPasswordHasherInterface $passwordHasher, User $user)
    {
        //checks if current user id and id of account that is edited is same
        if ($user->getId() != $this->getUser()->getId())
        {
            //returns error message
            $this->addFlash(
                'bad',
                'Nelze měnit hesla jiným uživatelům.'
            );
            //redirects back to current user password change
            return $this->redirectToRoute('profile_password_change',['id'=>$this->getUser()->getId()]);
        }
        //inits form
        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class,[
                'type' => PasswordType::class,
                'invalid_message' => 'Zadaná hesla se musí shodovat.',
                'options' => ['attr' => ['class' => 'password-field']],
                'error_bubbling'=>true,
                'first_options'  => [
                    'label' => 'Heslo',
                    'error_bubbling'=>true,
                    'attr' => [
                        'type' => 'password',
                        'class'=>'form-control',
                        'placeholder'=>'Heslo',
                        'minlength'=>8
                    ]
                ],
                'second_options' => [
                    'label' => 'Heslo znovu',
                    'attr' => [
                        'class'=>'form-control',
                        'placeholder'=>'Heslo znovu',
                        'minlength'=>8
                    ]
                ],
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
                //assigns value from form
                $password = $form['password']->getData();

                //hashes password
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );

                //sets hashed password to database
                $user->setPassword($hashedPassword);

                //saves changes
                $this->em->persist($user);
                $this->em->flush();

                //returns error message
                $this->addFlash(
                    'good',
                    'Heslo bylo úšpěšně změněno'
                );
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
            //returns each form error if occurs
            foreach ($form->getErrors(true) as $formError) {
                $this->addFlash(
                    'bad',
                    $formError->getMessage()
                );
            }
        }

        return $this->render('admin/profile/password.html.twig', [
            'form' => $form->createView(),

        ]);
    }
}
