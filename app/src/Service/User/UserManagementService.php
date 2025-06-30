<?php

namespace App\Service\User;

use App\Entity\User;
use App\Repository\User\UserRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserManagementService implements UserManagementServiceInterface
{
    private SessionInterface $session;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private RouterInterface $router;

    private TokenStorageInterface $tokenStorage;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $em,
        RouterInterface $router,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage
    ) {
        $this->userRepository = $userRepository;
        $this->em = $em;
        $this->router = $router;
        $this->session = $requestStack->getSession();
        $this->tokenStorage = $tokenStorage;
    }

    public function getCurrentUserId()
    {
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof User) {
            return $token->getUser()->getId();
        }
        return null;

    }

    public function findUserById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function handleAjaxUsersRequest(Request $request): JsonResponse
    {
        $dtRequest = new DataTablesAjaxRequestService($request);

        $start = $dtRequest->getStart();
        $length = $dtRequest->getLength();
        $search = $dtRequest->getSearchText();

        $columnsMap = [
            1 => 'u.name',
            2 => 'u.email',
            3 => 'u.status',
            4 => 'u.roles',
            5 => 'u.createdAt',
        ];

        $orderBy = $dtRequest->getSortText($columnsMap);

        if (empty($orderBy)) {
            $orderBy = 'u.name asc';
        }

        $orderParts = explode(' ', explode(',', $orderBy)[0]);
        $orderColumn = $orderParts[0];
        $orderDir = strtolower($orderParts[1] ?? 'asc');

        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        if ($search) {
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $filteredCount = (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->orderBy($orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $users = $qb->getQuery()->getResult();

        $totalUsers = $this->userRepository->count([]);

        $data = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'status' => $user->getStatus(),
                'roles' => implode(', ', $user->getRoles()),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }, $users);

        return new JsonResponse([
            'draw' => $dtRequest->getRequestData()['draw'] ?? 0,
            'recordsTotal' => $totalUsers,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ]);
    }

    public function handleBulkActionRequest(Request $request): RedirectResponse
    {
        $userIdsCsv = $request->request->get('user_ids', '');
        $userIds = array_filter(array_map('trim', explode(',', $userIdsCsv)));

        $action = $request->request->get('action');

//        if (empty($userIds) || !$action) {
//            $this->session->getFlashBag()->add('success', 'Bulk action performed successfully.');
//            return new RedirectResponse($this->router->generate('admin_users_list'));
//        }

        $users = $this->userRepository->findBy(['id' => $userIds]);

        foreach ($users as $user) {
            switch ($action) {
                case 'block':
                    $user->setStatus('blocked');
                    $this->em->persist($user);
                    $this->session->getFlashBag()->add('success', 'Users blocked successfully.');
                    break;

                case 'unblock':
                    $user->setStatus('active');
                    $this->em->persist($user);
                    $this->session->getFlashBag()->add('success', 'Users unblocked successfully.');
                    break;

                case 'make_admin':
                    $roles = $user->getRoles();
                    if (!in_array('ROLE_ADMIN', $roles, true)) {
                        $roles[] = 'ROLE_ADMIN';
                        $user->setRoles($roles);
                        $this->em->persist($user);
                        $this->session->getFlashBag()->add('success', 'Users made admin successfully.');
                    }
                    break;

                case 'remove_admin':
                    $roles = $user->getRoles();
                    if (in_array('ROLE_ADMIN', $roles, true)) {
                        $roles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
                        $user->setRoles(array_values($roles));
                        $this->em->persist($user);
                        $this->session->getFlashBag()->add('success', 'Admin role removed successfully.');
                    }
                    break;

                case 'delete':
                    $user->setStatus('deleted');
                    $this->em->persist($user);
                    $this->session->getFlashBag()->add('success', 'Users deleted successfully.');
                    break;
            }
        }

        $this->em->flush();

        return new RedirectResponse($this->router->generate('user_index'));
    }
}
