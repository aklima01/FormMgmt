<?php

namespace App\Repository;

use App\Entity\Form;
use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Form>
 */
class FormRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Form::class);
    }

    // src/Repository/FormRepository.php

    public function findByTemplateId(int $templateId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.template = :templateId')
            ->setParameter('templateId', $templateId)
            ->orderBy('f.submittedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return Form[] Returns an array of Form objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Form
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

}
