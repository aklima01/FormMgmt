<?php

namespace App\Controller;

use App\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ACTIVE_USER')]
class ProfileController extends AbstractController
{
    public function __construct
    (
        private readonly UserRepository $userRepository,
    )
    {}

    #[Route('/profile/{id}', name: 'app_profile')]
    public function index(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) throw $this->createNotFoundException('User not found');
        $this->denyAccessUnlessGranted('view_profile', $user);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);

    }

}
