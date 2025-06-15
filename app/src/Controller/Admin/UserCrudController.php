<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator      $urlGenerator
    ) {
    }

    /* ------------------------------------------------------------------ */
    /*  Basic CRUD config                                                 */
    /* ------------------------------------------------------------------ */

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Users')
            ->setEntityLabelInSingular('User')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'name', 'email']);
    }

    public function configureFields(string $pageName): iterable
    {
       // yield IdField::new('id')->onlyOnIndex();

        yield TextField::new('name');

        yield EmailField::new('email');

        yield ChoiceField::new('status')
            ->setChoices([
                'Active'  => 'active',
                'Blocked' => 'blocked',
            ])
            ->renderAsBadges([
                'active'  => 'success',
                'blocked' => 'danger',
            ]);

        yield ArrayField::new('roles')
            ->formatValue(fn ($value) => implode(', ', $value))
            ->hideOnForm();            // keep roles read-only in the form
        yield DateTimeField::new('createdAt')
            ->onlyOnIndex();

    }

    /* ------------------------------------------------------------------ */
    /*  Custom actions                                                    */
    /* ------------------------------------------------------------------ */

    public function configureActions(Actions $actions): Actions
    {
        // ----  Block  ----
        $block = Action::new('block', 'Block')
            ->addCssClass('btn btn-warning')
            ->displayIf(fn (User $u) => $u->getStatus() !== 'blocked')
            ->linkToCrudAction('blockUser');

        // ----  Un-block  ----
        $unblock = Action::new('unblock', 'Un-block')
            ->addCssClass('btn btn-success')
            ->displayIf(fn (User $u) => $u->getStatus() === 'blocked')
            ->linkToCrudAction('unblockUser');

        // ----  Grant admin  ----
        $makeAdmin = Action::new('makeAdmin', 'Grant admin')
            ->addCssClass('btn btn-primary')
            ->displayIf(fn (User $u) => !in_array('ROLE_ADMIN', $u->getRoles(), true))
            ->linkToCrudAction('makeAdmin');

        // ----  Remove admin  ----
        $removeAdmin = Action::new('removeAdmin', 'Revoke admin')
            ->addCssClass('btn btn-secondary')
            ->displayIf(fn (User $u) => in_array('ROLE_ADMIN', $u->getRoles(), true))
            ->linkToCrudAction('removeAdminRole');

        return $actions
            // keep the default ones
            ->add(Crud::PAGE_INDEX, $block)
            ->add(Crud::PAGE_INDEX, $unblock)
            ->add(Crud::PAGE_INDEX, $makeAdmin)
            ->add(Crud::PAGE_INDEX, $removeAdmin)

            ->add(Crud::PAGE_DETAIL, $block)
            ->add(Crud::PAGE_DETAIL, $unblock)
            ->add(Crud::PAGE_DETAIL, $makeAdmin)
            ->add(Crud::PAGE_DETAIL, $removeAdmin);
    }

    /* ------------------------------------------------------------------ */
    /*  Action handlers                                                   */
    /* ------------------------------------------------------------------ */

    public function blockUser(AdminContext $ctx): RedirectResponse
    {
        /** @var User $user */
        $user = $ctx->getEntity()->getInstance();
        $user->setStatus('blocked');

        $this->em->flush();
        $this->addFlash('success', sprintf('User %s has been blocked.', $user->getEmail()));

        return $this->backToIndex();
    }

    public function unblockUser(AdminContext $ctx): RedirectResponse
    {
        /** @var User $user */
        $user = $ctx->getEntity()->getInstance();
        $user->setStatus('active');

        $this->em->flush();
        $this->addFlash('success', sprintf('User %s is active again.', $user->getEmail()));

        return $this->backToIndex();
    }

    public function makeAdmin(AdminContext $ctx): RedirectResponse
    {
        /** @var User $user */
        $user = $ctx->getEntity()->getInstance();
        $roles = $user->getRoles();
        $roles[] = 'ROLE_ADMIN';

        $user->setRoles(array_unique($roles));
        $this->em->flush();
        $this->addFlash('success', sprintf('User %s is now an administrator.', $user->getEmail()));

        return $this->backToIndex();
    }

    public function removeAdminRole(AdminContext $ctx): RedirectResponse
    {
        /** @var User $user */
        $user = $ctx->getEntity()->getInstance();
        $roles = array_filter($user->getRoles(), fn ($r) => $r !== 'ROLE_ADMIN');

        $user->setRoles($roles);
        $this->em->flush();
        $this->addFlash('success', sprintf('Administrator role removed from %s.', $user->getEmail()));

        return $this->backToIndex();
    }

    /* ------------------------------------------------------------------ */
    /*  Helper                                                            */
    /* ------------------------------------------------------------------ */

    private function backToIndex(): RedirectResponse
    {
        $url = $this->urlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
