<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\FormRepository;
use App\Repository\QuestionRepository;
use App\Repository\TemplateRepository;
use App\Repository\User\UserRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use App\Service\Template\TemplateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/template', name: 'template_')]
class TemplateController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly TemplateRepository $templateRepository,
        private readonly FormRepository $formRepository,
        private readonly Security $security,
        private readonly QuestionRepository $questionRepository,
        private readonly TemplateService $templateService
    )
    {}

    #[Route('/ajax/templates', name: 'ajax', methods: ['GET'])]
    public function handleAjaxTemplatesRequest(Request $request): JsonResponse
    {
        $data = $this->templateService->getTemplatesForDataTable($request);
        return new JsonResponse($data);
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(TemplateRepository $templateRepository): Response
    {
        return $this->render('template/list.html.twig', []);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $this->templateService->createTemplateFromRequest($request);
                $this->addFlash('success', 'Template created successfully!');
                return $this->redirectToRoute('template_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating template: ' . $e->getMessage());
            }
        }

        return $this->render('template/create.html.twig');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        Template $template,
    ): Response {
        if ($request->isMethod('POST')) {
            try {
                $this->templateService->updateTemplateFromRequest($template, $request);
                $this->addFlash('success', 'Template updated successfully.');
                return $this->redirectToRoute('template_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Update failed: ' . $e->getMessage());
            }
        }

        $editData = $this->templateService->prepareTemplateEditData($template);
        $forms = $this->formRepository->findByTemplateId($template->getId());

        return $this->render('template/edit.html.twig', array_merge($editData, [
            'template' => $template,
            'forms' => $forms,
        ]));
    }

    #[Route('/ajax/topics', name: 'ajax_topics')]
    public function fetchTopics(): JsonResponse
    {
        $data = $this->templateService->getAllTopics();
        return new JsonResponse($data);
    }

    #[Route('/tag/search', name: 'tag_search')]
    public function tagSearch(Request $request): JsonResponse
    {
        $term = $request->query->get('term', '');
        $results = $this->templateService->searchTags($term);

        return new JsonResponse($results);
    }

    #[Route('/user-search', name: 'user_search', methods: ['GET'])]
    public function userSearch(Request $request, TemplateService $templateService): JsonResponse
    {
        $term = $request->query->get('term', '');
        $sort = $request->query->get('sort', 'name');
        $results = $templateService->searchUsers($term, $sort);
        return new JsonResponse($results);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(int $id): Response
    {
        try {
            $this->templateService->deleteTemplate($id);
            $this->addFlash('success', 'Template deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('template_list');
    }

    #[Route('/{id}/fill', name: 'fill', methods: ['GET', 'POST'])]
    public function fill(int $id, Request $request): Response
    {
        $template = $this->templateRepository->find($id);
        if (!$template) {
            throw $this->createNotFoundException('Template not found.');
        }

        if ($request->isMethod('POST')) {
            $user = $this->userRepo->find($this->security->getUser()->getId());
            if (!$user) {
                throw $this->createAccessDeniedException('You must be logged in to fill this form.');
            }

            $this->templateService->submitForm($request, $template, $user);
            $this->addFlash('success', 'Form successfully submitted!');

            return $this->redirectToRoute('template_list');
        }

        $template_questions = $this->questionRepository->findBy(['template' => $template], ['position' => 'ASC']);

        return $this->render('template/fill.html.twig', [
            'template' => $template,
            'template_questions' => $template_questions,
            'request' => $request->request->all(),
        ]);
    }

    #[Route('/{id}/results/data', name: 'template_results_data', methods: ['GET'])]
    public function resultsData(int $id,): JsonResponse {
        $template = $this->templateRepository->find($id);
        if (!$template) {
            return new JsonResponse(['error' => 'Template not found'], 404);
        }

        $data = $this->templateService->getResultsData($template);
        return new JsonResponse(['data' => $data]);
    }

    #[Route('/myfiledforms', name: 'myfiledforms', methods: ['GET', 'POST'])]
    public function myfiledforms(
        Request $request,
        DataTablesAjaxRequestService $dataTablesRequest
    ): JsonResponse {

        $user = $this->userRepo->find($this->security->getUser());
        $result = $this->templateService->getMyFiledFormsData($user, $request, $dataTablesRequest);

        return new JsonResponse($result);
    }

    #[Route('/bulk-delete', name: 'bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (!is_array($ids) || empty($ids)) {
            return new JsonResponse(['error' => 'No IDs provided'], 400);
        }

        $this->templateService->bulkDeleteTemplates($ids);
        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/{id}/aggregate', name: 'template_aggregate', methods: ['GET'])]
    public function aggregate(
        int $id,
        TemplateRepository $templateRepository,
        TemplateService $templateService
    ): JsonResponse {
        $template = $templateRepository->find($id);

        if (!$template) {
            return new JsonResponse(['error' => 'Template not found'], 404);
        }

        $aggregates = $templateService->getTemplateAggregates($template);
        return new JsonResponse($aggregates);
    }


}
