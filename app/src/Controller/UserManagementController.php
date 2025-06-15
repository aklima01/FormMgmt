<?php

namespace App\Controller;

use App\Service\User\UserManagementServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users', name: 'admin_users_')]
class UserManagementController extends AbstractController
{
    private UserManagementServiceInterface $userAdminService;

    public function __construct(UserManagementServiceInterface $userAdminService)
    {
        $this->userAdminService = $userAdminService;
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list()
    {
        return $this->render('admin/users/list.html.twig');
    }

    #[Route('/ajax', name: 'ajax', methods: ['GET', 'POST'])]
    public function ajaxUsers(Request $request)
    {
        return $this->userAdminService->handleAjaxUsersRequest($request);
    }

    #[Route('/bulk-action', name: 'bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request)
    {
        return $this->userAdminService->handleBulkActionRequest($request);
    }
}
