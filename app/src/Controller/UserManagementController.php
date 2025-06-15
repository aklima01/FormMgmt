<?php

namespace App\Controller;

use App\Service\User\UserManagementServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users', name: 'admin_users_')]
class UserManagementController extends AbstractController
{
    private UserManagementServiceInterface $userManagementService;

    public function __construct(UserManagementServiceInterface $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list()
    {
        return $this->render('admin/users/list.html.twig');
    }

    #[Route('/ajax', name: 'ajax', methods: ['GET', 'POST'])]
    public function ajaxUsers(Request $request)
    {
        return $this->userManagementService->handleAjaxUsersRequest($request);
    }

    #[Route('/bulk-action', name: 'bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request)
    {
        return $this->userManagementService->handleBulkActionRequest($request);
    }
}
