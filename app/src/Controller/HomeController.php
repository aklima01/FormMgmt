<?php

namespace App\Controller;

use App\Entity\Form;
use App\Entity\Tag;
use App\Entity\Template;
use App\Repository\TemplateRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use App\Service\Tag\TagService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

}
