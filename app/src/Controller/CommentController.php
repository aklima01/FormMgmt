<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Template;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    #[Route('/template/{id}/comment', name: 'add_comment', methods: ['POST'])]
    public function add(Request $request, Template $template, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = trim($data['content'] ?? '');

        if (empty($content)) {
            return new JsonResponse(['error' => 'Empty comment'], 400);
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setUser($this->getUser());
        $comment->setTemplate($template);

        $em->persist($comment);
        $em->flush();

        return new JsonResponse([
            'id' => $comment->getId(),
            'author' => $comment->getAuthor()->getUserIdentifier(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/template/{id}/comments', name: 'get_comments', methods: ['GET'])]
    public function list(Template $template, EntityManagerInterface $em): JsonResponse
    {
        $comments = $em->getRepository(Comment::class)->findBy([
            'template' => $template
        ], ['createdAt' => 'ASC']);

        $data = array_map(fn($comment) => [
            'id' => $comment->getId(),
            'author' => $comment->getUser()->getName(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s')
        ], $comments);

        return new JsonResponse($data);
    }
}
