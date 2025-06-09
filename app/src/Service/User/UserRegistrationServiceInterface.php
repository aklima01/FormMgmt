<?php

namespace App\Service\User;

use App\Entity\User;

interface UserRegistrationServiceInterface
{
    public function register(User $user, string $plainPassword): void;
}
