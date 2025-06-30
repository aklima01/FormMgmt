<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    //    /**
    //     * @return Tag[] Returns an array of Tag objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tag
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function deleteUnusedTags(): void
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();

        $qb->delete(Tag::class, 't')
            ->where(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $em->createQueryBuilder()
                            ->select('1')
                            ->from('App\Entity\Template', 'tpl')
                            ->innerJoin('tpl.tags', 'tt')
                            ->where('tt.id = t.id')
                            ->getDQL()
                    )
                )
            );

        $qb->getQuery()->execute();
    }
}
