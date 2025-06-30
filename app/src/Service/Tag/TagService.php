<?php

namespace App\Service\Tag;

use Doctrine\ORM\EntityManagerInterface;

class TagService
{
    public function __construct
    (
       private readonly EntityManagerInterface $em
    )
    {}

    public function getPopularTags(int $limit = 10): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('tag.name', 'COUNT(t.id) AS templateCount')
            ->from('App\Entity\Tag', 'tag')
            ->leftJoin('tag.templates', 't')
            ->groupBy('tag.id')
            ->orderBy('templateCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

}
