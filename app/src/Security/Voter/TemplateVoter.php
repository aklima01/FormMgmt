<?php

namespace App\Security\Voter;

use App\Entity\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class TemplateVoter extends Voter
{
    public const MANAGE = 'TEMPLATE_MANAGE'; // for managing (edit/delete)
    public const FILL = 'TEMPLATE_FILL';     // for viewing/filling the template

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::MANAGE, self::FILL], true) && $subject instanceof Template;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Template $template */
        $template = $subject;

        // Admins can always manage or fill
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::MANAGE:
                // Only creator can manage
                return $template->getCreatedBy() === $user;

            case self::FILL:

                if ($template->getCreatedBy() === $user) {
                    return true;
                }

                // Logic for fill (view) permission:
                // If template is public, any authenticated user can fill
                if ($template->getAccess() === 'public') {
                    return true;
                }

                // Otherwise, check if user is in allowed users
                return $template->getUsers()->contains($user) ;

            default:
                return false;
        }
    }
}
