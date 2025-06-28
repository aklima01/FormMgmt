<?php

namespace App\Security\Voter;

use App\Entity\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class TemplateVoter extends Voter
{
    public const MANAGE = 'TEMPLATE_MANAGE'; // custom attribute

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE && $subject instanceof Template;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Template $template */
        $template = $subject;

        // Admins can always manage
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Only the creator can manage
        return $template->getCreatedBy() === $user;
    }
}
