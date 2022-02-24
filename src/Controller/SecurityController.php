<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

class SecurityController extends AbstractController
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
     * @Route ("/login", name="login")
     */
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        //loads data from users
        $users = $this->em->getRepository(User::class)->findAll();

        //if users is null redirects to register form
        if(count($users) === 0){
            return $this->redirectToRoute('register');
        }

        //if user is logged automatically redirects to admin
        if($this->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            return $this->redirectToRoute('admin_homepage');
        }

        //gets all errors from login form
        $error = $authenticationUtils->getLastAuthenticationError();
        return $this->render('security/login.html.twig', [
            'error'=> $error,
        ]);
    }

    /**
     * @Route ("/register", name="register")
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        //loads data from users
        $users = $this->em->getRepository(User::class)->findAll();

        //if number of users is higher than zero regirects to login form
        if(count($users)>0){
            //returns success message
            $this->addFlash(
                'bad',
                'Prvotní registrace byla již provedena.'
            );
            return $this->redirectToRoute('login');
        }

        //init form
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class,[
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'E-mail',
                    'autocomplete'=>'email'
                ]
            ])
            ->add('username', TextType::class,[
                'attr'=>[
                    'class'=>'form-control',
                    'placeholder'=>'Uživatelské jméno',
                     'autocomplete'=>'username'
                ]
            ])
            ->add('password', RepeatedType::class,[
                'type' => PasswordType::class,
                'invalid_message' => 'Zadaná hesla se musí shodovat.',
                'options' => ['attr' => ['class' => 'password-field']],
                'error_bubbling'=>true,
                'first_options'  => [
                    'error_bubbling'=>true,
                    'attr' => [
                        'type' => 'password',
                        'class'=>'form-control',
                        'placeholder'=>'Heslo',
                        'minlength'=>8
                    ]
                ],
                'second_options' => [
                    'attr' => [
                        'class'=>'form-control',
                        'placeholder'=>'Heslo znovu',
                         'minlength'=>8
                    ]
                ],
            ])
            ->add('create', SubmitType::class,[
                'label'=>'Vytvořit účet',
                'attr'=> [
                    'class'=> 'btn btn-primary btn-block',
                ],
            ])
        ->getForm();

        $form->handleRequest($request);

        //if form is submitted and is valid by settings on the backend
        if($form->isSubmitted() && $form->isValid()) {
            try {
                //assigns values from form to variables
                $email = $form['email']->getData();
                $username = $form['username']->getData();
                $password = $form['password']->getData();

                //inits user object
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setRoles(['ROLE_ADMIN']);

                //hashes password
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $password
                );

                //sets password
                $user->setPassword($hashedPassword);

                //saves changes
                $this->em->persist($user);
                $this->em->flush();

                //returns success message
                $this->addFlash(
                    'good',
                    'Prvotní registrace proběhla úspěšně.'
                );

                //redirects to login
                return $this->redirectToRoute('login');
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

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),

        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
