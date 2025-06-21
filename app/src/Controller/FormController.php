<?php

namespace App\Controller;

use App\Entity\Form;
use App\Repository\FormRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/form', name: 'form_')]
class FormController extends AbstractController
{
    #[Route('/form/{id}/view', name: '_view')]
    public function view(int $id): Response
    {
        $form = $this->getDoctrine()->getRepository(Form::class)->find($id);

        if (!$form) {
            throw $this->createNotFoundException('Form not found');
        }

        // Authorization check (if needed)
        $user = $this->getUser();
        $isOwner = $form->getUser() === $user;
        $isTemplateOwner = $form->getTemplate()->getUser() === $user;
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isOwner && !$isTemplateOwner && !$isAdmin) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('form/view.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Form $form,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('edit', $form); // Optional security

        if ($request->isMethod('POST')) {
            foreach ($form->getAnswers() as $answer) {
                $fieldName = 'answer_' . $answer->getId();
                if ($request->request->has($fieldName)) {
                    $answer->setValue($request->request->get($fieldName));
                }
            }

            $em->flush();

            $this->addFlash('success', 'Form updated successfully.');
            return $this->redirectToRoute('form_view', ['id' => $form->getId()]);
        }

        return $this->render('form/edit.html.twig', [
            'formEntity' => $form,
        ]);
    }

    #[Route('/ajax/forms', name: 'ajax_forms', methods: ['GET'])]
    public function getForms(
        Request $request,
        EntityManagerInterface $em,
        FormRepository $formRepository,
        Security $security // Inject Symfony Security service
    ): JsonResponse {
        $dtRequest = new DataTablesAjaxRequestService($request);

        $start = $dtRequest->getStart();
        $length = $dtRequest->getLength();
        $search = $dtRequest->getSearchText();

        // Map DataTables column indexes to DQL aliases/fields for sorting
        $columnsMap = [
            0 => 'f.id',
            1 => 'template.title',
            2 => 'f.submittedAt',
        ];

        $orderBy = $dtRequest->getSortText($columnsMap) ?: 'f.id desc';
        [$orderColumn, $orderDir] = explode(' ', $orderBy) + [null, 'asc'];
        $orderDir = strtolower($orderDir);

        $user = $security->getUser(); // Get current user
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $qb = $em->createQueryBuilder()
            ->select('f', 'template')
            ->from(Form::class, 'f')
            ->leftJoin('f.template', 'template')
            ->where('f.user = :user') // Filter by user
            ->setParameter('user', $user);

        // Apply search
        if ($search) {
            $qb->andWhere('LOWER(template.title) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Count after filter
        $filteredCount = (clone $qb)
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply order and pagination
        $qb->orderBy($orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $forms = $qb->getQuery()->getResult();

        // Count total forms for this user (without filters)
        $total = $formRepository->count(['user' => $user]);

        $data = array_map(function (Form $f) {
            return [
                'id' => $f->getId(),
                'template' => $f->getTemplate()?->getTitle() ?? '<em>Deleted</em>',
                'submittedAt' => $f->getSubmittedAt()?->format('Y-m-d H:i:s') ?? '',
            ];
        }, $forms);

        return new JsonResponse([
            'draw' => $dtRequest->getRequestData()['draw'] ?? 0,
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ]);
    }


    #[Route('/data', name: 'submitted_data', methods: ['GET'])]
    public function fetchForms(Request $request, FormRepository $formRepository): JsonResponse
    {
        $templateId = $request->query->get('templateId');

        if (!$templateId) {
            return new JsonResponse(['data' => []]); // or return all if preferred
        }

        $forms = $formRepository->findBy(['template' => $templateId]);

        $data = [];
        foreach ($forms as $form) {
            $data[] = [
                'id' => $form->getId(),
                'template' => $form->getTemplate()->getTitle(), // adjust method if needed
                'submittedAt' => $form->getSubmittedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json(['data' => $data]);
    }


}
