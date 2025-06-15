<?php

namespace App\Service\User;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

interface UserManagementServiceInterface
{
    public function handleAjaxUsersRequest(Request $request): JsonResponse;

    public function handleBulkActionRequest(Request $request): RedirectResponse;
}
