<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\FormRepository;
use App\Repository\QuestionRepository;
use App\Repository\TemplateRepository;
use App\Repository\User\UserRepository;
use App\Service\Template\TemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/template', name: 'template_')]
class TemplateController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly TemplateRepository $templateRepository,
        private readonly FormRepository $formRepository,
        private readonly Security $security,
        private readonly QuestionRepository $questionRepository,
        private readonly TemplateService $templateService,
        private readonly EntityManagerInterface $em,
    )
    {}

    #[IsGranted('ACTIVE_USER')]
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('template/list.html.twig', []);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/ajax/templates', name: 'ajax', methods: ['GET'])]
    public function getTemplates(Request $request): JsonResponse
    {
        $data = $this->templateService->getTemplates($request);
        return new JsonResponse($data);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ajax/templates/{userId}', name: 'ajax_user', methods: ['GET'])]
    public function getTemplatesByUserId(int $userId, Request $request): JsonResponse
    {
        $data = $this->templateService->getTemplatesByUserId($request, $userId);
        return new JsonResponse($data);
    }

    #[IsGranted('ACTIVE_USER')]
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

    #[IsGranted('ACTIVE_USER')]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        Template $template,
    ): Response {

        $this->isAuthorized($id);

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

    #[IsGranted('ACTIVE_USER')]
    #[Route('/ajax/topics', name: 'ajax_topics')]
    public function fetchTopics(): JsonResponse
    {
        $data = $this->templateService->getAllTopics();
        return new JsonResponse($data);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/tag/search', name: 'tag_search')]
    public function tagSearch(Request $request): JsonResponse
    {
        $term = $request->query->get('term', '');
        $results = $this->templateService->searchTags($term);

        return new JsonResponse($results);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/user-search', name: 'user_search', methods: ['GET'])]
    public function userSearch(Request $request, TemplateService $templateService): JsonResponse
    {
        $term = $request->query->get('term', '');
        $sort = $request->query->get('sort', 'name');
        $results = $templateService->searchUsers($term, $sort);
        return new JsonResponse($results);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/{id}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(int $id): Response
    {
        $this->isAuthorized($id);
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
            $this->denyAccessUnlessGranted('ACTIVE_USER');
            $user = $this->userRepo->find($this->security->getUser()->getId());
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

    #[IsGranted('ACTIVE_USER')]
    #[Route('/{id}/results/data', name: 'template_results_data', methods: ['GET'])]
    public function resultsData(int $id): JsonResponse
    {
        $this->isAuthorized($id);

        $template = $this->templateRepository->find($id);
        if (!$template) {
            return new JsonResponse(['error' => 'Template not found'], 404);
        }

        $data = $this->templateService->getResultsData($template);
        return new JsonResponse(['data' => $data]);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/{id}/aggregate', name: 'template_aggregate', methods: ['GET'])]
    public function aggregate
    (
        int $id,
        TemplateRepository $templateRepository,
        TemplateService $templateService

    ): JsonResponse
    {
        $this->isAuthorized($id);
        $template = $templateRepository->find($id);

        if (!$template) {
            return new JsonResponse(['error' => 'Template not found'], 404);
        }

        $aggregates = $templateService->getTemplateAggregates($template);
        return new JsonResponse($aggregates);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/bulk-delete', name: 'bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];
        $templtes= $this->templateRepository->findBy(['id' => $ids]);
        $this->checkTemplatesAuthorization($templtes);

        return $this->templateService->bulkDeleteTemplates($ids);
    }

    private function isAuthorized($templateId) : void
    {
        $template = $this->em->getRepository(Template::class)->find($templateId);
        $this->denyAccessUnlessGranted('TEMPLATE_MANAGE', $template);
    }

    private function checkTemplatesAuthorization(array $templtes) : void
    {
        foreach ($templtes as $template) {
            $this->denyAccessUnlessGranted('TEMPLATE_MANAGE', $template);
        }
    }

}
