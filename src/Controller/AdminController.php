<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 */
class AdminController extends AbstractController
{

    /**
     * @Route("/admin/dashboard", name="admin_homepage")
     */
    public function index() {

        return $this->render('admin/dashboard/index.html.twig');
    }
}
