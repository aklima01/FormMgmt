<?php

namespace App\Service\User;

use App\Entity\User;
use Symfony\Component\Form\FormInterface;


interface UserRegistrationServiceInterface
{
    public function register(User $user, string $plainPassword): void;
    public function processRegistration(FormInterface $form): void;
}
