<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Service\User\UserRegistrationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    private UserRegistrationServiceInterface $registrationService;

    public function __construct(UserRegistrationServiceInterface $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setName($form->get('name')->getData());
            $user->setEmail($form->get('email')->getData());
            $plainPassword = $form->get('Password')->getData();
            $this->registrationService->register($user, $plainPassword);
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
