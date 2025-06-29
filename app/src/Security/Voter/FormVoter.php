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

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE && $subject instanceof Form;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Form $form */
        $form = $subject;

        // Allow if user is admin
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Allow if user is the creator of the form
        return $form->getCreatedBy() === $user;
    }
}
