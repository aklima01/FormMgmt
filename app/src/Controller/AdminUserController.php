<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users', name: 'admin_users_')]
class AdminUserController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $em;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/users/list.html.twig');
    }

    #[Route('/ajax', name: 'ajax', methods: ['GET', 'POST'])]
    public function ajaxUsers(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $params = $request->query->all();

        $start = (int)($params['start'] ?? 0);
        $length = (int)($params['length'] ?? 10);
        $search = $params['search']['value'] ?? '';
        $orderColumnIndex = $params['order'][0]['column'] ?? 1;
        $orderDir = $params['order'][0]['dir'] ?? 'asc';

        $columnsMap = [
            1 => 'u.name',
            2 => 'u.email',
            3 => 'u.status',
            4 => 'u.roles',
            5 => 'u.createdAt',
        ];

        $orderColumn = $columnsMap[$orderColumnIndex] ?? 'u.name';

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

        $qb->orderBy($orderColumn, $orderDir);

        $qb->setFirstResult($start)
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
            'draw' => (int)($params['draw'] ?? 0),
            'recordsTotal' => $totalUsers,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ]);
    }

    #[Route('/bulk-action', name: 'bulk_action', methods: ['POST'])]
    public function bulkAction(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userIdsCsv = $request->request->get('user_ids', '');
        $userIds = array_filter(array_map('trim', explode(',', $userIdsCsv)));

        $action = $request->request->get('action');

        if (empty($userIds) || !$action) {
            $this->addFlash('warning', 'No users selected or action not specified.');
            return $this->redirectToRoute('admin_users_list');
        }

        $users = $this->userRepository->findBy(['id' => $userIds]);

        foreach ($users as $user) {
            switch ($action) {
                case 'block':
                    $user->setStatus('blocked');
                    $this->em->persist($user);
                    break;

                case 'unblock':
                    $user->setStatus('active');
                    $this->em->persist($user);
                    break;

                case 'make_admin':
                    $roles = $user->getRoles();
                    if (!in_array('ROLE_ADMIN', $roles, true)) {
                        $roles[] = 'ROLE_ADMIN';
                        $user->setRoles($roles);
                        $this->em->persist($user);
                    }
                    break;

                case 'remove_admin':
                    $roles = $user->getRoles();
                    if (in_array('ROLE_ADMIN', $roles, true)) {
                        $roles = array_filter($roles, fn($role) => $role !== 'ROLE_ADMIN');
                        $user->setRoles(array_values($roles));
                        $this->em->persist($user);
                    }
                    break;

                case 'delete':
                    $this->em->remove($user);
                    break;
            }
        }

        $this->em->flush();

        $this->addFlash('success', 'Bulk action performed successfully.');
        return $this->redirectToRoute('admin_users_list');
    }
}
