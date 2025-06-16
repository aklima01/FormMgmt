<?php

namespace App\Controller;

use App\Entity\Template;
use App\Form\TemplateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



class UserDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'user_dashboard')]
    public function index(): Response {
        return $this->render('user/dashboard.html.twig');
    }


}
