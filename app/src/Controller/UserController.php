<?php

namespace App\Controller;

use App\Repository\User\UserRepository;
use App\Service\User\UserManagementServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ACTIVE_USER')]
class UserController extends AbstractController
{

    private UserManagementServiceInterface $userManagementService;

    public function __construct
    (
        UserManagementServiceInterface $userManagementService,
    )
    {
        $this->userManagementService = $userManagementService;
    }

    #[Route('/', name: 'admin_users_list', methods: ['GET'])]
    public function list()
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/ajax', name: 'admin_users_ajax', methods: ['GET', 'POST'])]
    public function ajaxUsers(Request $request)
    {
        return $this->userManagementService->handleAjaxUsersRequest($request);
    }

    #[Route('/bulk-action', name: 'admin_users_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request)
    {
        return $this->userManagementService->handleBulkActionRequest($request);
    }


    #[Route('/user/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('admin/userprofile.html.twig', [
            'user' => $user,
        ]);
    }

}
