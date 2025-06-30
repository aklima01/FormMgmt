<?php

namespace App\Service\Like;

use App\Entity\Like;
use App\Entity\Template;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LikeService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function toggleLike(User $user, Template $template): array
    {
        $repo = $this->em->getRepository(Like::class);
        $existing = $repo->findOneBy(['user' => $user, 'template' => $template]);

        if ($existing) {
            $this->em->remove($existing);
            $liked = false;
        } else {
            $like = new Like();
            $like->setUser($user);
            $like->setTemplate($template);
            $this->em->persist($like);
            $liked = true;
        }

        $this->em->flush();

        return [
            'liked' => $liked,
            'count' => $template->getLikesCount(),
        ];
    }

}
