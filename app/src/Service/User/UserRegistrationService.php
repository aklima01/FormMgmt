<?php

namespace App\Service\User;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Repository\User\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService implements UserRegistrationServiceInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function register(User $user, string $plainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        //$user->setRoles(['ROLE_ADMIN']);
        $user->setCreatedAt();

        $this->userRepository->save($user);
    }

    public function processRegistration(FormInterface $form): void
    {
        /** @var User $user */
        $user = $form->getData();

        $user->setName($form->get('name')->getData());
        $user->setEmail($form->get('email')->getData());
        $plainPassword = $form->get('Password')->getData();

        $this->register($user, $plainPassword);
    }


}
