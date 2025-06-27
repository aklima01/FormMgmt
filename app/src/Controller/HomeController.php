<?php

namespace App\Controller;

use App\Entity\Form;
use App\Entity\Tag;
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
    public function latestTemplatesAjax(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $queryBuilder = $this->templateRepository->createQueryBuilder('t')
            ->leftJoin('t.author', 'a')
            ->addSelect('a')
            ->orderBy('t.id', 'DESC') // Assuming higher ID = newer
            ->setMaxResults(10);

        $templates = $queryBuilder->getQuery()->getResult();

        $data = [];
        foreach ($templates as $template) {
            $data[] = [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
                'description' => $template->getDescription(),
                'author' => $template->getAuthor()->getName(),
            ];
        }

        return new JsonResponse([
            'draw' => (int) ($request->query->get('draw', 0)),
            'recordsTotal' => 10,
            'recordsFiltered' => 10,
            'data' => $data,
        ]);
    }

    #[Route('/templates/popular', name: 'templates_popular_ajax', methods: ['GET'])]
    public function mostPopularTemplates(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->createQueryBuilder();

        $qb->select('t.id', 't.title', 't.description', 'a.name AS author', 'COUNT(f.id) AS filledForms')
            ->from(Template::class, 't')
            ->leftJoin('t.author', 'a')
            ->leftJoin(Form::class, 'f', 'WITH', 'f.template = t')
            ->groupBy('t.id, t.title, t.description, a.name')
            ->orderBy('filledForms', 'DESC')
            ->setMaxResults(10);

        $results = $qb->getQuery()->getArrayResult();

        return new JsonResponse([
            'data' => $results,
        ]);
    }

    #[Route('/tags/popular', name: 'tags_popular_ajax', methods: ['GET'])]
    public function popularTags(EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->createQueryBuilder();

        $qb->select('tag.name', 'COUNT(t.id) AS templateCount')
            ->from(Tag::class, 'tag')
            ->leftJoin('tag.templates', 't')
            ->groupBy('tag.id')
            ->orderBy('templateCount', 'DESC')
            ->setMaxResults(10);

        $tags = $qb->getQuery()->getArrayResult();

        return new JsonResponse([
            'data' => $tags
        ]);
    }


    #[Route(path: '/access-denied', name: 'access-denied')]
    public function accessDenied() :Response
    {
        return $this->render('home/access_denied.html.twig');
    }

}
