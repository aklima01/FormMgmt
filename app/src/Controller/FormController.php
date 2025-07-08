<?php

namespace App\Controller;

use App\Repository\FormRepository;
use App\Repository\User\UserRepository;
use App\Service\Form\FormService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ACTIVE_USER')]
#[Route('/forms')]
class FormController extends AbstractController
{
    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly UserRepository $userRepository,
        private readonly FormService $formService,
    )
    {}

    #[Route('/{id}/view', name: 'form_view' , methods: ['GET'])]
    public function view(int $id): Response
    {
        $data = $this->formService->getFormViewData($id);

        return $this->render('form/view.html.twig', $data);
    }

    #[Route('/list/{userId}', name: 'form_list', methods: ['GET'])]
    public function getForms(int $userId, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 401);

        $data = $this->formService->getForms($request, $user);
        return new JsonResponse($data);
    }

    #[Route('/{id}/edit', name: 'form_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $form = $this->formRepository->find($id);
        if (!$form) throw $this->createNotFoundException('Form not found');

        $result = $this->formService->handleFormEdit($request, $form);
        $userId = $form->getUser()->getId();

        if (isset($result['success']) && $result['success'] === true) {
            $this->addFlash('success', 'Form updated successfully.');
            return $this->redirectToRoute('app_profile', ['id' => $userId]);
        }

        return $this->render('form/edit.html.twig', array_merge([
            'form' => $form,
        ], $result));
    }

    #[Route('/bulk-delete', name: 'form_bulk_delete', methods: ['POST'])]
    public function bulkDeleteForms(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];
        $result = $this->formService->bulkDelete($ids);

        if (isset($result['error'])) return new JsonResponse($result, 400);

        return new JsonResponse($result);
    }

}
