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
        $users = $this->em->getRepository(User::class)->findAll();

        if(count($users) === 0){
            return $this->redirectToRoute('register');
        }
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
        $users = $this->em->getRepository(User::class)->findAll();

        if(count($users)>0){
            $this->addFlash(
                'bad',
                'Prvotní registrace byla již provedena.'
            );
            return $this->redirectToRoute('login');
        }

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
                        'placeholder'=>'Heslo'
                    ]
                ],
                'second_options' => [
                    'attr' => [
                        'class'=>'form-control',
                        'placeholder'=>'Heslo znovu'
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



        if($form->isSubmitted() && $form->isValid()) {
            try {
                $email = $form['email']->getData();
                $username = $form['username']->getData();
                $password = $form['password']->getData();

                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setRoles(['ROLE_SUPER_ADMIN']);

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
