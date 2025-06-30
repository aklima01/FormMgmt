<?php

namespace App\Controller;

use App\Service\User\UserManagementServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct
    (
        private readonly UserManagementServiceInterface $userManagementService
    )
    {}

    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index()
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/user-list', name: 'user_list', methods: ['GET', 'POST'])]
    public function getUserList(Request $request)
    {
        return $this->userManagementService->handleAjaxUsersRequest($request);
    }

    #[Route('/bulk-action', name: 'user_bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request)
    {
        return $this->userManagementService->handleBulkActionRequest($request);
    }

}
