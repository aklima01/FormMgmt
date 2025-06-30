<?php

namespace App\Controller;

use App\Entity\Template;
use App\Service\Like\LikeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class LikeController extends AbstractController
{
    #[Route('/like/{id}', name: 'like_toggle', methods: ['POST'])]
    public function toggleLike(
        Template $template,
        LikeService $likeService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $result = $likeService->toggleLike($user, $template);

        return new JsonResponse($result);
    }
}
