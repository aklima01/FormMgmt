<?php

namespace App\Controller;

use App\Repository\TemplateRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'template_search')]
    public function search(Request $request, TemplateRepository $repo): Response
    {
        $term = $request->query->get('q');
        $templates = $term ? $repo->fullTextSearch($term) : [];

        return $this->render('search/results.html.twig', [
            'templates' => $templates,
            'query' => $term,
        ]);
    }

}
