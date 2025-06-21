<?php

namespace App\Controller;

use App\Entity\Form;
use App\Repository\FormRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/form', name: 'form_')]
class FormController extends AbstractController
{
    #[Route('/view/{id}', name: 'form_view', methods: ['GET'])]
    public function view(int $id, FormRepository $formRepository): Response
    {
        $form = $formRepository->find($id);

        if (!$form) {
            throw $this->createNotFoundException('Form not found.');
        }

        return $this->render('form/view.html.twig', [
            'form' => $form,
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
        FormRepository $formRepository
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

        // Get sort text like 'f.id asc' or 'template.title desc' from DataTablesAjaxRequestService
        $orderBy = $dtRequest->getSortText($columnsMap) ?: 'f.id desc';

        // Parse order by: "field direction" (e.g., "template.title asc")
        $orderParts = explode(' ', $orderBy);
        $orderColumn = $orderParts[0];
        $orderDir = strtolower($orderParts[1] ?? 'asc');

        $qb = $em->createQueryBuilder()
            ->select('f', 'template')
            ->from(Form::class, 'f')
            ->leftJoin('f.template', 'template');

        // Apply search filter on template title or form ID (cast ID to string for LIKE)
        if ($search) {
            $qb->andWhere('LOWER(template.title) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Get filtered count with a clone query builder
        $filteredCount = (clone $qb)
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply sorting, paging
        $qb->orderBy($orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $forms = $qb->getQuery()->getResult();

        // Total count of all records (without filtering)
        $total = $formRepository->count([]);

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


}
