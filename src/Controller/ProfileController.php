<?php

namespace App\Controller;

use App\Entity\User;
use Exception;
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
     * @Route ("/profile/update/{id}", name="profile_update")
     */
    public function update(Request $request, User $user)
    {
        if ($user->getId() != $this->getUser()->getId())
        {
            $this->addFlash(
                'bad',
                'Nelze editovat jiné uživatelské profily.'
            );
            return $this->redirectToRoute('profile_update',['id'=>$this->getUser()->getId()]);
        }
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



        if($form->isSubmitted() && $form->isValid()) {
            try {

                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash(
                    'good',
                    'Údaje byly úspěšně uloženy.'
                );

                return $this->redirectToRoute('users_index');
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

        return $this->render('admin/profile/update.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    /**
     * @Route ("/profile/password-change/{id}", name="profile_password_change")
     */
    public function password_change(Request $request, UserPasswordHasherInterface $passwordHasher, User $user)
    {

        if ($user->getId() != $this->getUser()->getId())
        {
            $this->addFlash(
                'bad',
                'Nelze měnit hesla jiným uživatelům.'
            );
            return $this->redirectToRoute('profile_password_change',['id'=>$this->getUser()->getId()]);
        }

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
                        'placeholder'=>'Heslo'
                    ]
                ],
                'second_options' => [
                    'attr' => [
                        'label' => 'Heslo znovu',
                        'class'=>'form-control',
                        'placeholder'=>'Heslo znovu'
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



        if($form->isSubmitted() && $form->isValid()) {
            try {

                $password = $form['password']->getData();

                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );

                $user->setPassword($hashedPassword);

                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash(
                    'good',
                    'Heslo bylo úšpěšně změněno'
                );
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

        return $this->render('admin/profile/password.html.twig', [
            'form' => $form->createView(),

        ]);
    }
}
