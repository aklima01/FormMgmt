<?php

namespace App\Security\Voter;

use App\Entity\Form;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class FormVoter extends Voter
{
    public const MANAGE = 'FORM_MANAGE'; // e.g., delete, edit answers
    public const VIEW = 'FORM_VIEW';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::MANAGE, self::VIEW], true) && $subject instanceof Form;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Form $form */
        $form = $subject;

        // Admins can do everything
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::MANAGE:
                return $form->getCreatedBy() === $user;

            case self::VIEW:
                // Check if user is the form owner
                if ($form->getCreatedBy() === $user) {
                    return true;
                }

                // Check if user is the creator of the form's template
                $template = $form->getTemplate();
                if ($template && $template->getCreatedBy() === $user) {
                    return true;
                }

                return false;
        }

        return false;
    }
}
