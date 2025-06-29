<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\User\UserRepository;
use App\Service\Comment\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class CommentController extends AbstractController
{
    public function __construct
    (
        private readonly CommentService $commentService,
        private readonly UserRepository $userRepo,
    )
    {}

    #[IsGranted('ACTIVE_USER')]
    #[Route('/template/{id}/comment', name: 'add_comment', methods: ['POST'])]
    public function add(
        Request $request,
        Template $template,
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        $content = trim($data['content'] ?? '');

        if (empty($content)) {
            return new JsonResponse(['error' => 'Empty comment'], 400);
        }

        $user = $this->userRepo->findOneBy(['id' => $this->getUser()->getId()]);
        $comment = $this->commentService->addComment($user, $template, $content);

        return new JsonResponse([
            'id' => $comment->getId(),
            'author' => $comment->getUser()->getUserIdentifier(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/template/{id}/comments', name: 'get_comments', methods: ['GET'])]
    public function list(Template $template, CommentService $commentService): JsonResponse
    {
        $comments = $commentService->getCommentsForTemplate($template);

        $data = array_map(fn($comment) => [
            'id' => $comment->getId(),
            'author' => $comment->getUser()->getName(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s')
        ], $comments);

        return new JsonResponse($data);
    }
}
