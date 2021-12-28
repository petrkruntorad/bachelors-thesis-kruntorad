<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function index(): Response
    {
        $users = $this->em->getRepository(User::class)->findAll();

        if(count($users) === 0){
            return $this->redirectToRoute('register');
        }

        return $this->render('security/login.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }

    /**
     * @Route ("/register", name="register")
     */
    public function register(): Response
    {
        $users = $this->em->getRepository(User::class)->findAll();

        if(count($users)>0){
            $this->addFlash(
                'bad',
                'Výchozí účet byl již vytvořen.'
            );
            return $this->redirectToRoute('login');
        }

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class)
        ->getForm();

        return $this->render('security/register.html.twig', [

        ]);
    }
}
