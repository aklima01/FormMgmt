<?php

namespace App\Service\Comment;

use App\Entity\Comment;
use App\Entity\Template;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CommentService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function addComment(User $user, Template $template, string $content): Comment
    {
        $comment = new Comment();
        $comment->setContent($content);
        $comment->setUser($user);
        $comment->setTemplate($template);

        $this->em->persist($comment);
        $this->em->flush();

        return $comment;
    }

    public function getCommentsForTemplate(Template $template): array
    {
        return $this->em
            ->getRepository(Comment::class)
            ->findBy(['template' => $template], ['createdAt' => 'ASC']);
    }

}
