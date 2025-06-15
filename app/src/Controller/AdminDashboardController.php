<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Render the admin dashboard view
        return $this->render('admin/dashboard.html.twig');
    }

}
