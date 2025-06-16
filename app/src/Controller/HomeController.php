<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_home')]
    public function index()
    {
        return $this->render('home/index.html.twig');
    }

    #[Route(path: '/access-denied', name: 'access-denied')]
    public function accessDenied()
    {
        return $this->redirectToRoute('app_logout');
    }

}
