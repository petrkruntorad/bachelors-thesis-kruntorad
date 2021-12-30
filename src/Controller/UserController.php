<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
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
     * @Route ("/users", name="users_index")
     */
    public function index()
    {
        $users = $this->em->getRepository(User::class)->findAll();

        return $this->render('admin/users/index.html.twig',[
            'users'=>$users,
        ]);
    }

    /**
     * @Route ("/users/create", name="user_create")
     */
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher)
    {

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
                    'placeholder'=>'Heslo'
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



        if($form->isSubmitted() && $form->isValid()) {
            try {
                $email = $form['email']->getData();
                $username = $form['username']->getData();
                $password = $form['password']->getData();

                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setRoles(['ROLE_ADMIN']);

                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );

                $user->setPassword($hashedPassword);

                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash(
                    'good',
                    'Prvotní registrace proběhla úspěšně.'
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

        return $this->render('admin/users/create.html.twig', [
            'form' => $form->createView(),

        ]);
    }
}
