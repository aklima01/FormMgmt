<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\TemplateRepository;
use App\Service\Common\DataTablesAjaxRequestService;
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
    public function __construct
    (
        private readonly TemplateRepository $templateRepository,
        private readonly EntityManagerInterface $entityManager,

    )
    {

    }

    #[Route(path: '/', name: 'app_home')]
    public function index() : Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('templates/latest', name: 'templates_latest_ajax', methods: ['GET'])]
    public function latestTemplatesAjax(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): JsonResponse
    {
        $queryParams = $request->query->all();

        $start = $queryParams['start'] ?? 0;
        $length = $queryParams['length'] ?? 10;
        $page = (int) floor($start / $length) + 1;

        $orderColumnIndex = null;
        $orderDir = 'asc';
        $columns = [];

        if (isset($queryParams['order'][0]['column'])) {
            $orderColumnIndex = $queryParams['order'][0]['column'];
        }

        if (isset($queryParams['order'][0]['dir'])) {
            $orderDir = $queryParams['order'][0]['dir'];
        }

        if (isset($queryParams['columns'])) {
            $columns = $queryParams['columns'];
        }

        $queryBuilder = $this->templateRepository->createQueryBuilder('t');

        $orderColumnName = 't.id'; // default

        if ($orderColumnIndex !== null && isset($columns[$orderColumnIndex]['data'])) {
            $columnData = $columns[$orderColumnIndex]['data'];

            switch ($columnData) {
                case 'id':
                    $orderColumnName = 't.id';
                    break;
                case 'title':
                    $orderColumnName = 't.title';
                    break;
                case 'description':
                    $orderColumnName = 't.description';
                    break;
                case 'author':
                    $queryBuilder->leftJoin('t.author', 'a');
                    $orderColumnName = 'a.name';
                    break;
                default:
                    $orderColumnName = 't.id';
                    break;
            }
        }

        $queryBuilder->orderBy($orderColumnName, $orderDir);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $page,
            $length
        );

        $data = [];
        foreach ($pagination->getItems() as $template) {
            $data[] = [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
                'description' => $template->getDescription(),
                'author' => $template->getAuthor()->getName(),
            ];
        }

        return new JsonResponse([
            'draw' => (int) ($queryParams['draw'] ?? 0),
            'recordsTotal' => $pagination->getTotalItemCount(),
            'recordsFiltered' => $pagination->getTotalItemCount(),
            'data' => $data,
        ]);
    }


    #[Route(path: '/access-denied', name: 'access-denied')]
    public function accessDenied() :Response
    {
        $this->addFlash('error', 'You do not have permission to access this page.');
        return $this->redirectToRoute('app_logout');
    }

}
