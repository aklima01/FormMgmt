<?php

namespace App\Controller;

use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    public function __construct
    (
        private readonly EntityManagerInterface $em,
        private readonly TemplateRepository $templateRepository,
    )
    {}

    #[Route('/search', name: 'template_search')]
    public function search(Request $request): Response
    {
        $term = $request->query->get('q');
        $templates = $term ? $this->templateRepository->fullTextSearch($term) : [];

        return $this->render('search/results.html.twig', [
            'templates' => $templates,
            'query' => $term,
        ]);
    }


    #[Route('/refresh-materialized-view', name: 'refresh-materialized-view')]
    public function refreshMaterializedView(): Response
    {
        $conn = $this->em->getConnection();

        $sql = 'REFRESH MATERIALIZED VIEW template_search_view;';
        $conn->executeStatement($sql);

        return new Response('Materialized view refreshed successfully.');
    }

}
