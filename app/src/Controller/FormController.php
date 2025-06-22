<?php

namespace App\Controller;

use App\Entity\Form;
use App\Repository\AnswerRepository;
use App\Repository\FormRepository;
use App\Repository\QuestionRepository;
use App\Repository\TemplateRepository;
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
    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly QuestionRepository $questionRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly TemplateRepository $templateRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {

    }

    #[Route('/{id}/view', name: '_view')]
    public function view(int $id): Response
    {
        $form = $this->formRepository->find($id);

        if (!$form) {
            throw $this->createNotFoundException('Form not found');
        }

        $answers = $this->answerRepository->findBy(['form' => $form]);
        $templateId = $form->getTemplate()?->getId();
        $questions = $this->questionRepository->findBy(['template' => $templateId], ['position' => 'ASC']);


        return $this->render('form/view.html.twig', [
            'form' => $form,
            'questions' => $questions,
            'answers' => $answers,

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

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $form = $this->formRepository->find($id);

        if (!$form) {
            throw $this->createNotFoundException('Form not found');
        }

        $template = $form->getTemplate();
        $questions = $this->questionRepository->findBy(['template' => $template], ['position' => 'ASC']);
        $answers = $this->answerRepository->findBy(['form' => $form]);

        // Index existing answers by question ID for quick lookup
        $answerMap = [];
        foreach ($answers as $answer) {
            $answerMap[$answer->getQuestion()->getId()] = $answer;
        }

        if ($request->isMethod('POST')) {
            foreach ($questions as $question) {
                $fieldName = 'question_' . $question->getId();
                $rawValue = $request->request->get($fieldName);

                $answer = $answerMap[$question->getId()] ?? new Answer();
                $answer->setForm($form);
                $answer->setQuestion($question);

                switch ($question->getType()) {
                    case 'Single_line_text':
                    case 'Text':
                        $answer->setStringValue(trim($rawValue));
                        break;

                    case 'Number':
                        $answer->setIntValue((int)$rawValue);
                        break;

                    case 'Checkbox':
                        $answer->setBoolValue($rawValue === '1' || $rawValue === 'on');
                        break;

                    default:
                        $answer->setStringValue(trim($rawValue)); // fallback
                }

                $this->entityManager->persist($answer); // handles both new and updated answers
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Form updated successfully.');

            return $this->redirectToRoute('template_list');
        }

        return $this->render('form/edit.html.twig', [
            'form' => $form,
            'template' => $template,
            'questions' => $questions,
            'answers' => $answerMap, // pass as map for easier rendering
        ]);
    }



}
