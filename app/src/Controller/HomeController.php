<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_home')]
    public function index() : Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route(path: '/access-denied', name: 'access-denied')]
    public function accessDenied() :Response
    {
        return $this->render('home/access_denied.html.twig');
    }

    #[Route('/health-check', name: 'app_health_check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTime::ATOM),
        ]);
    }

}
