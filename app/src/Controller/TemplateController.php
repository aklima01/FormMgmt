<?php

namespace App\Controller;

use App\Entity\Template;
use App\Repository\FormRepository;
use App\Repository\QuestionRepository;
use App\Repository\TemplateRepository;
use App\Repository\User\UserRepository;
use App\Security\Voter\TemplateVoter;
use App\Service\Template\TemplateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/templates')]
class TemplateController extends AbstractController
{
    public function __construct
    (
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
    #[Route('/user/{userId}', name: 'template_list', methods: ['GET'])]
    public function getTemplatesByUserId(int $userId, Request $request): JsonResponse
    {
        $data = $this->templateService->getTemplatesByUserId($request, $userId);
        return new JsonResponse($data);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/create/{id}', name: 'template_create', methods: ['GET', 'POST'])]
    public function create(int $id, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $this->templateService->createTemplateFromRequest( $id,$request);
                $this->addFlash('success', 'Template created successfully!');

                return $this->redirectToRoute('app_profile', ['id' => $id]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating template: ' . $e->getMessage());
            }
        }

        return $this->render('template/create.html.twig', [
            'userId' => $id,
        ]);

    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/{id}/edit', name: 'template_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request,): Response
    {
        $template = $this->templateRepository->find($id);
        if( !$template) throw $this->createNotFoundException('Template not found.');

        $this->denyAccessUnlessGranted(TemplateVoter::MANAGE, $template);

        if ($request->isMethod('POST')) {
            $userId = $template->getAuthor()->getId();
            try {
                $this->templateService->updateTemplateFromRequest($template, $request);

                $this->addFlash('success', 'Template updated successfully.');
                return $this->redirectToRoute('app_profile', ['id' => $userId]);
            } catch (\Exception $e)
            {
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
    #[Route('/ajax/topics', name: 'template_ajax_topics')]
    public function fetchTopics(): JsonResponse
    {
        $data = $this->templateService->getAllTopics();
        return new JsonResponse($data);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/tag/search', name: 'template_tag_search')]
    public function tagSearch(Request $request): JsonResponse
    {
        $term = $request->query->get('term', '');
        $results = $this->templateService->searchTags($term);

        return new JsonResponse($results);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/user-search', name: 'template_user_search', methods: ['GET'])]
    public function userSearch(Request $request, TemplateService $templateService): JsonResponse
    {
        $term = $request->query->get('term', '');
        $sort = $request->query->get('sort', 'name');
        $results = $templateService->searchUsers($term, $sort);
        return new JsonResponse($results);
    }

    #[Route('/{id}/fill', name: 'template_fill', methods: ['GET', 'POST'])]
    public function fill(int $id, Request $request): Response
    {
        $template = $this->templateRepository->find($id);
        if (!$template) throw $this->createNotFoundException('Template not found.');

        $this->denyAccessUnlessGranted('TEMPLATE_FILL', $template);

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('ACTIVE_USER');
            $user = $this->userRepo->find($this->security->getUser()->getId());
            $formEntity =  $this->templateService->handleFormSubmission($request, $template, $user);

            if( !$formEntity) {
                $this->addFlash('error', 'Error submitting form. Please try again.');
                return $this->redirectToRoute('template_fill', ['id' => $id]);
            }
            $this->addFlash('success', 'Form successfully submitted!');

            return $this->redirectToRoute('app_home');
        }

        $template_questions = $this->questionRepository->findBy(['template' => $template], ['position' => 'ASC']);

        return $this->render('template/fill.html.twig', [
            'template' => $template,
            'template_questions' => $template_questions,
        ]);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/{id}/results/data', name: 'template_results', methods: ['GET'])]
    public function resultsData(int $id): JsonResponse
    {
        $template = $this->templateRepository->find($id);
        if (!$template) return new JsonResponse(['error' => 'Template not found'], 404);
        $this->denyAccessUnlessGranted(TemplateVoter::MANAGE, $template);

        $data = $this->templateService->getResultsData($template);
        return new JsonResponse(['data' => $data]);
    }


    #[IsGranted('ACTIVE_USER')]
    #[Route('/{id}/aggregate', name: 'template_aggregate', methods: ['GET'])]
    public function aggregate( int $id ): JsonResponse
    {
        $template = $this->templateRepository->find($id);
        if (!$template) return new JsonResponse(['error' => 'Template not found'], 404);
        $this->denyAccessUnlessGranted(TemplateVoter::MANAGE, $template);

        $aggregates = $this->templateService->getTemplateAggregates($template);
        return new JsonResponse($aggregates);
    }

    #[IsGranted('ACTIVE_USER')]
    #[Route('/bulk-delete', name: 'template_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];
        $templtes= $this->templateRepository->findBy(['id' => $ids]);

        foreach ($templtes as $template) {
            $this->denyAccessUnlessGranted('TEMPLATE_MANAGE', $template);
        }

        return $this->templateService->bulkDeleteTemplates($ids);
    }

    #[Route('/latest', name: 'template_latest_ajax', methods: ['GET'])]
    public function latestTemplatesAjax(Request $request, TemplateService $templateService): JsonResponse
    {
        $limit = 10;
        $data = $templateService->getLatestTemplatesData($limit);

        return new JsonResponse([
            'draw' => (int) $request->query->get('draw', 0),
            'recordsTotal' => $limit,
            'recordsFiltered' => $limit,
            'data' => $data,
        ]);
    }

    #[Route('/popular', name: 'template_popular_ajax', methods: ['GET'])]
    public function mostPopularTemplates(Request $request, TemplateService $templateService): JsonResponse
    {
        $limit = 5;
        $results = $templateService->getMostPopularTemplates($limit);

        return new JsonResponse([
            'data' => $results,
        ]);
    }

}
