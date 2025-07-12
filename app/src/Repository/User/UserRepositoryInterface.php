<?php

namespace App\Repository\User;

use App\Entity\User;

interface UserRepositoryInterface
{
    public function save(User $user, bool $flush = true): void;
    public function findAll(): array;

    public function findAdminEmails(): array;

}
