<?php

namespace App\Controller;

use App\Service\Tag\TagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    public function __construct
    (
        private readonly TagService $tagService,
    )
    {}
    #[Route('/tags/popular', name: 'tags_popular_ajax', methods: ['GET'])]
    public function popularTags(): JsonResponse
    {
        $tags = $this->tagService->getPopularTags();

        return new JsonResponse([
            'data' => $tags
        ]);
    }

}
