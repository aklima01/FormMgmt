<?php

namespace App\Controller;

use App\Service\Tag\TagService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    #[Route('/tags/popular', name: 'tags_popular_ajax', methods: ['GET'])]
    public function popularTags(TagService $tagService): JsonResponse
    {
        $tags = $tagService->getPopularTags();

        return new JsonResponse([
            'data' => $tags
        ]);
    }

}
