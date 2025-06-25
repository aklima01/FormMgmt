<?php

namespace App\Controller;

use App\Entity\Template;
use App\Entity\Like;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class LikeController extends AbstractController
{
    #[Route('/like/{id}', name: 'like_toggle', methods: ['POST'])]
    public function toggleLike(Template $template, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $existing = $em->getRepository(Like::class)->findOneBy(['user' => $user, 'template' => $template]);

        if ($existing) {
            $em->remove($existing);
            $liked = false;
        } else {
            $like = new Like();
            $like->setUser($user);
            $like->setTemplate($template);
            $em->persist($like);
            $liked = true;
        }

        $em->flush();

        return new JsonResponse([
            'liked' => $liked,
            'count' => $template->getLikesCount(),
        ]);
    }
}
