<?php

namespace App\Repository\User;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository  extends ServiceEntityRepository implements UserRepositoryInterface
{
    private readonly EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry,EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, User::class);
        $this->entityManager = $entityManager;
    }

    public function save(User $user, bool $flush = true): void
    {
        $this->entityManager->persist($user);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    // src/Repository/UserRepository.php
    public function findAdminEmails(): array
    {
        $users = $this->createQueryBuilder('u')
            ->getQuery()
            ->getResult();

        $adminEmails = [];

        foreach ($users as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $adminEmails[] = $user->getEmail();
            }
        }

        return $adminEmails;
    }





}
