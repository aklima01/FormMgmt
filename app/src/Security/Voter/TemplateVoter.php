<?php

namespace App\Security\Voter;

use App\Entity\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


final class TemplateVoter extends Voter
{
    public const MANAGE = 'TEMPLATE_MANAGE';
    public const FILL = 'TEMPLATE_FILL';

    public function __construct
    (
        private Security $security,
    )
    {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::MANAGE, self::FILL], true) && $subject instanceof Template;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        /** @var Template $template */
        $template = $subject;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::MANAGE:
                if ($template->getAuthor()?->getId() === $user->getId()) {
                    return true;
                }
                return false;

            case self::FILL:
                if ($template->getAccess() === 'public') {
                    return true;
                }

                if ($template->getAuthor()?->getId() === $user?->getId()) {
                    return true;
                }

                return $template->getUsers()->contains($user);
        }

        return false;
    }

}
