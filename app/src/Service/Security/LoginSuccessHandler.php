<?php

namespace App\Service\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private AuthorizationCheckerInterface $authChecker,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        if ($this->authChecker->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('admin_users_list'));
        }

        if ($this->authChecker->isGranted('ROLE_USER')) {
            return new RedirectResponse($this->router->generate('user_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_home'));
    }
}
