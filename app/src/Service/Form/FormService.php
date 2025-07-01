<?php

namespace App\Service\Form;

use App\Entity\Answer;
use App\Entity\User;
use App\Repository\FormRepository;
use App\Repository\QuestionRepository;
use App\Repository\AnswerRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Form;

class FormService
{
    public function __construct
    (
        private readonly FormRepository $formRepository,
        private readonly QuestionRepository $questionRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly EntityManagerInterface $em,
    )
    {}

    public function bulkDelete(array $ids, FormRepository $formRepository): array
    {
        if (empty($ids)) {
            return ['error' => 'No IDs provided'];
        }

        $forms = $formRepository->findBy(['id' => $ids]);

        foreach ($forms as $form) {
            if (!$this->authorizationChecker->isGranted('FORM_MANAGE', $form)) {
                throw new AccessDeniedHttpException('Access denied for one or more forms.');
            }
            $this->em->remove($form);
        }

        $this->em->flush();

        return ['status' => 'success'];
    }

    public function handleFormEdit(Request $request, Form $form)
    {
        if (!$this->authorizationChecker->isGranted('FORM_MANAGE', $form)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $template = $form->getTemplate();
        $questions = $this->questionRepository->findBy(['template' => $template], ['position' => 'ASC']);
        $answers = $this->answerRepository->findBy(['form' => $form]);

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
                        $answer->setIntValue((int) $rawValue);
                        break;
                    case 'Checkbox':
                        $answer->setBoolValue($rawValue === '1' || $rawValue === 'on');
                        break;
                    default:
                        $answer->setStringValue(trim($rawValue));
                }

                $this->em->persist($answer);
            }

            $this->em->flush();

            return ['success' => true];
        }

        return [
            'template' => $template,
            'questions' => $questions,
            'answers' => $answerMap,
        ];
    }

    public function getForms(Request $request, User $user): array
    {
        $dtRequest = new DataTablesAjaxRequestService($request);

        $start = $dtRequest->getStart();
        $length = $dtRequest->getLength();
        $search = $dtRequest->getSearchText();

        $columnsMap = [
            0 => 'f.id',
            1 => 'template.title',
            2 => 'f.submittedAt',
        ];

        $orderBy = $dtRequest->getSortText($columnsMap) ?: 'f.id desc';
        [$orderColumn, $orderDir] = explode(' ', $orderBy) + [null, 'asc'];
        $orderDir = strtolower($orderDir);

        $qb = $this->em->createQueryBuilder()
            ->select('f', 'template')
            ->from(Form::class, 'f')
            ->leftJoin('f.template', 'template')
            ->where('f.user = :user')
            ->setParameter('user', $user);

        if ($search) {
            $qb->andWhere('LOWER(template.title) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $filteredCount = (clone $qb)
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->orderBy($orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $forms = $qb->getQuery()->getResult();
        $total = $this->formRepository->count(['user' => $user]);

        $data = array_map(function (Form $f) {
            return [
                'id' => $f->getId(),
                'template' => $f->getTemplate()?->getTitle() ?? '<em>Deleted</em>',
                'submittedAt' => $f->getSubmittedAt()?->format('Y-m-d H:i:s') ?? '',
            ];
        }, $forms);

        return [
            'draw' => $dtRequest->getRequestData()['draw'] ?? 0,
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ];
    }


    public function getAjaxFormsData(Request $request, UserInterface $user): array
    {
        $dtRequest = new DataTablesAjaxRequestService($request);

        $start = $dtRequest->getStart();
        $length = $dtRequest->getLength();
        $search = $dtRequest->getSearchText();

        $columnsMap = [
            0 => 'f.id',
            1 => 'template.title',
            2 => 'f.submittedAt',
        ];

        $orderBy = $dtRequest->getSortText($columnsMap) ?: 'f.id desc';
        [$orderColumn, $orderDir] = explode(' ', $orderBy) + [null, 'asc'];
        $orderDir = strtolower($orderDir);

        $qb = $this->em->createQueryBuilder()
            ->select('f', 'template')
            ->from(Form::class, 'f')
            ->leftJoin('f.template', 'template')
            ->where('f.user = :user')
            ->setParameter('user', $user);

        if ($search) {
            $qb->andWhere('LOWER(template.title) LIKE :search')
                ->setParameter('search', '%' . strtolower($search) . '%');
        }

        $filteredCount = (clone $qb)
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->orderBy($orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $forms = $qb->getQuery()->getResult();
        $total = $this->formRepository->count(['user' => $user]);

        $data = array_map(function (Form $f) {
            return [
                'id' => $f->getId(),
                'template' => $f->getTemplate()?->getTitle() ?? '<em>Deleted</em>',
                'submittedAt' => $f->getSubmittedAt()?->format('Y-m-d H:i:s') ?? '',
            ];
        }, $forms);

        return [
            'draw' => $dtRequest->getRequestData()['draw'] ?? 0,
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ];
    }

    public function getFormViewData(int $id): array
    {
        $form = $this->formRepository->find($id);

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        if (!$this->authorizationChecker->isGranted('FORM_VIEW', $form)) {
            throw new AccessDeniedException();
        }

        $answers = $this->answerRepository->findBy(['form' => $form]);
        $templateId = $form->getTemplate()?->getId();
        $questions = $this->questionRepository->findBy(['template' => $templateId], ['position' => 'ASC']);

        return [
            'form' => $form,
            'questions' => $questions,
            'answers' => $answers,
        ];
    }

}
