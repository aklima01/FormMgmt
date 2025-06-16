<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ActiveUserVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === 'ACTIVE_USER';
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        return method_exists($user, 'getStatus') && $user->getStatus() === 'active';
    }
}
