<?php

namespace App\Service\Template;

use App\Entity\Answer;
use App\Entity\Form;
use App\Entity\Question;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Topic;
use App\Entity\User;
use App\Repository\AnswerRepository;
use App\Repository\FormRepository;
use App\Repository\QuestionRepository;
use App\Repository\TemplateRepository;
use App\Repository\TopicRepository;
use App\Repository\User\UserRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TemplateRepository $templateRepository,
        private Security $security,
        private ParameterBagInterface $params,
        private UserRepository $userRepo,
        private readonly S3Client $s3,
        private readonly TopicRepository $topicRepo,
        private readonly QuestionRepository $questionRepository,
        private readonly FormRepository $formRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly LoggerInterface $logger,

    ) {}

    /**
     * Get most popular templates by number of filled forms.
     *
     * @param int $limit
     * @return array
     */
    public function getMostPopularTemplates(int $limit = 5): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('t.id', 't.title', 't.description', 'a.name AS author', 'COUNT(f.id) AS filledForms')
            ->from(Template::class, 't')
            ->leftJoin('t.author', 'a')
            ->leftJoin(Form::class, 'f', 'WITH', 'f.template = t')
            ->groupBy('t.id, t.title, t.description, a.name')
            ->orderBy('filledForms', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get latest templates data formatted for JSON response.
     *
     * @param int $limit
     * @return array
     */
    public function getLatestTemplatesData(int $limit = 10): array
    {
        $queryBuilder = $this->templateRepository->createQueryBuilder('t')
            ->leftJoin('t.author', 'a')
            ->addSelect('a')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults($limit);

        $templates = $queryBuilder->getQuery()->getResult();

        $data = [];
        foreach ($templates as $template) {
            $data[] = [
                'id' => $template->getId(),
                'title' => $template->getTitle(),
                'description' => $template->getDescription(),
                'author' => $template->getAuthor()->getName(),
            ];
        }

        return $data;
    }

    public function getTemplateAggregates(Template $template): array
    {
        $questions = $this->questionRepository->findBy(['template' => $template], ['position' => 'ASC']);
        $results = [];

        foreach ($questions as $question) {
            $type = $question->getType();

            $answers = $this->answerRepository->createQueryBuilder('a')
                ->select('a')
                ->join('a.form', 'f')
                ->where('a.question = :question')
                ->andWhere('f.template = :template')
                ->setParameter('question', $question)
                ->setParameter('template', $template)
                ->getQuery()
                ->getResult();

            $values = array_map(fn($answer) => $answer->getValue(), $answers);
            $summary = null;

            if ($type === 'Number') {
                $numeric = array_filter($values, fn($v) => is_numeric($v));
                $summary = count($numeric) > 0 ? array_sum($numeric) / count($numeric) : null;
            } elseif (in_array($type, ['Single_line_text', 'Text'], true)) {
                $counts = array_count_values(array_filter($values, fn($v) => !empty($v)));
                arsort($counts);
                $summary = array_key_first($counts);
            } elseif ($type === 'Checkbox') {
                $checked = count(array_filter($values, fn($v) => $v === true));
                $total = count($values);
                $summary = "$checked / $total checked";
            }

            $results[] = [
                'question' => $question->getTitle(),
                'type' => $type,
                'summary' => $summary,
            ];
        }

        return $results;
    }


    public function bulkDeleteTemplates(array $ids) : Response
    {
        if (!is_array($ids) || empty($ids)) {
            return new JsonResponse(['error' => 'Invalid input'], Response::HTTP_BAD_REQUEST);
        }

        $templates = $this->templateRepository->findBy(['id' => $ids]);

        foreach ($templates as $template) {
            $questions = $this->questionRepository->findBy(['template' => $template]);
            foreach ($questions as $question) {
                $this->em->remove($question);
            }

            $forms = $this->formRepository->findBy(['template' => $template]);
            foreach ($forms as $form) {
                foreach ($form->getAnswers() as $answer) {
                    $this->em->remove($answer);
                }
                $this->em->remove($form);
            }

            $tags  = $template->getTags();
            foreach ($tags as $tag) {
                $template->removeTag($tag);
            }

            $imageUrl = $template->getImageUrl();
            if ($imageUrl) {
                $publicUrl = rtrim($this->params->get('r2_public_url'), '/');
                if (str_starts_with($imageUrl, $publicUrl)) {
                    $key = ltrim(str_replace($publicUrl, '', $imageUrl), '/');

                    try {
                        $this->s3->deleteObject([
                            'Bucket' => $this->params->get('r2_bucket'),
                            'Key'    => $key,
                        ]);
                    } catch (\Exception $e) {
                         $this->logger->error('Failed to delete image from S3: ' . $e->getMessage());
                    }
                }
            }

            $this->em->remove($template);

            $tagRepository = $this->em->getRepository(Tag::class);
            $tagRepository->deleteUnusedTags();
        }

        $this->em->flush();
        return new JsonResponse(['success' => 'Templates deleted successfully'], Response::HTTP_OK);
    }

    public function getResultsData(Template $template): array
    {
        $forms = $this->formRepository->findBy(['template' => $template]);
        $data = [];

        foreach ($forms as $form) {
            $formUser = $form->getUser();
            $answers = [];

            foreach ($form->getAnswers() as $answer) {
                $question = $answer->getQuestion();
                if ($question->isShowInTable()) {
                    $answers[] = sprintf('%s: %s', $question->getTitle(), $answer->getValue());
                }
            }

            $data[] = [
                'id' => $form->getId(),
                'name' => $formUser->getName(),
                'email' => $formUser->getEmail(),
                'date' => $form->getSubmittedAt()?->format('Y-m-d H:i'),
                'keyAnswers' => implode('<br>', $answers),
            ];
        }

        return $data;
    }

    public function handleFormSubmission(Request $request, Template $template, User $user)
    {
        $formEntity = new Form();
        $formEntity->setTemplate($template);
        $formEntity->setUser($user);

        foreach ($template->getQuestions() as $question) {
            $fieldName = 'question_' . $question->getId();
            $rawValue = $request->request->get($fieldName);

            $answer = new Answer();
            $answer->setForm($formEntity);
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
                    $answer->setStringValue(trim($rawValue));
            }

            $formEntity->getAnswers()->add($answer);
        }

        $this->em->persist($formEntity);
        $this->em->flush();

        return $formEntity;
    }

    public function searchUsers(string $term, string $sort = 'name'): array
    {
        $qb = $this->userRepo->createQueryBuilder('u')
            ->where('LOWER(u.name) LIKE :term OR LOWER(u.email) LIKE :term')
            ->setParameter('term', '%' . strtolower($term) . '%');

        $allowedSorts = ['name', 'email'];
        if (in_array($sort, $allowedSorts, true)) {
            $qb->orderBy('u.' . $sort, 'ASC');
        }

        $users = $qb->getQuery()->getResult();

        return array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName(),
            'email' => $u->getEmail(),
        ], $users);
    }

    public function searchTags(string $term): array
    {
        $qb = $this->em->getRepository(Tag::class)->createQueryBuilder('t');
        $tags = $qb
            ->where('LOWER(t.name) LIKE :term')
            ->setParameter('term', strtolower($term) . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();

        return array_map(fn($tag) => ['id' => $tag['id'], 'text' => $tag['name']], $tags);
    }

    public function getAllTopics(): array
    {
        $topics = $this->topicRepo->findAll();

        return array_map(fn($topic) => [
            'id' => $topic->getId(),
            'name' => $topic->getName()
        ], $topics);
    }

    public function prepareTemplateEditData(Template $template): array
    {
        $topics = $this->topicRepo->findAll();

        $selectedTags = array_map(fn($tag) => [
            'id' => $tag->getId(),
            'name' => $tag->getName()
        ], $template->getTags()->toArray());

        $selectedUsers = array_map(fn($user) => [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail()
        ], $template->getUsers()->toArray());

        $templateQuestionsJson = array_map(fn($q) => [
            'id' => $q->getId(),
            'title' => $q->getTitle(),
            'description' => $q->getDescription(),
            'type' => $q->getType(),
            'showInTable' => $q->isShowInTable(),
        ], $template->getQuestions()->toArray());

        return [
            'topics' => $topics,
            'selectedTagsJson' => json_encode($selectedTags),
            'selectedUsersJson' => json_encode($selectedUsers),
            'template_questions_json' => json_encode($templateQuestionsJson),
        ];
    }

    public function updateTemplateFromRequest(
        Template $template,
        Request $request,
    ): void
    {
        $template->setTitle($request->request->get('title'));
        $template->setDescription($request->request->get('description'));

        // Topic
        $topicId = $request->request->get('topic_id');
        $topic = $topicId ? $this->em->getRepository(Topic::class)->find($topicId) : null;
        $template->setTopic($topic);

        // Tags
        $tagsInput = $request->request->get('tags', '');
        $tagNames = array_filter(array_map('trim', explode(',', $tagsInput)));
        $template->getTags()->clear();

        foreach ($tagNames as $tagName) {
            $tag = $this->em->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setName($tagName);
                $this->em->persist($tag);
            }
            $template->addTag($tag);
        }

        // Image upload
        $file = $request->files->get('image');
        if ($file) {
            $uuid = uniqid();
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $uuid . '.' . $ext;
            $bucket = $this->params->get('r2_bucket');
            $key = "uploads/{$filename}";

            $this->s3->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => fopen($file->getPathname(), 'rb'),
                'ACL' => 'public-read',
                'ContentType' => $file->getMimeType(),
            ]);

            $template->setImageUrl($this->params->get('r2_public_url') . '/' . $key);
        }

        // Access
        $template->setAccess($request->request->get('access'));

        foreach ($template->getUsers() as $existingUser) {
            $template->removeUser($existingUser);
        }

        if ($template->getAccess() === 'private') {
            $userIds = explode(',', $request->request->get('user_ids', ''));
            $users = $this->userRepo->findBy(['id' => $userIds]);
            foreach ($users as $user) {
                $template->addUser($user);
            }
        }

        // Questions
        $questionIds = $request->request->all('question_id');
        $titles = $request->request->all('question_title');
        $descriptions = $request->request->all('question_description');
        $types = $request->request->all('question_type');
        $showInTable = $request->request->all('question_show_in_table');

        $existingQuestions = [];
        foreach ($this->questionRepository->findBy(['template' => $template], ['position' => 'ASC']) as $q) {
            $existingQuestions[$q->getId()] = $q;
        }

        $usedIds = [];

        foreach ($titles as $i => $title) {
            $questionId = $questionIds[$i] ?? null;

            if ($questionId && isset($existingQuestions[$questionId])) {
                $question = $existingQuestions[$questionId];
            } else {
                $question = new Question();
                $question->setTemplate($template);
            }

            $question->setTitle($title);
            $question->setDescription($descriptions[$i] ?? '');
            $question->setType($types[$i] ?? 'Single_line_text');
            $question->setShowInTable(!empty($showInTable[$i]));
            $question->setPosition($i + 1);

            $this->em->persist($question);

            if ($questionId) {
                $usedIds[] = $questionId;
            }
        }

        // Remove deleted questions
        foreach ($existingQuestions as $id => $q) {
            if (!in_array($id, $usedIds)) {
                $this->em->remove($q);
            }
        }

        $this->em->flush();
    }


    public function createTemplateFromRequest(int $userId,Request $request): Template
    {
        $template = new Template();

        // Handle file upload
        $file = $request->files->get('image');
        if ($file) {
            $uuid = uniqid();
            $originalName = $file->getClientOriginalName();
            $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $uuid . '.' . $ext;
            $bucket = $this->params->get('r2_bucket');
            $key = "uploads/{$filename}";

            $this->s3->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => fopen($file->getPathname(), 'rb'),
                'ACL' => 'public-read',
                'ContentType' => $file->getMimeType(),
            ]);

            $template->setImageUrl($this->params->get('r2_public_url') . '/' . $key);
        }

        // Set core data
        $template->setTitle($request->request->get('title'));
        $template->setDescription($request->request->get('description'));
        $template->setAccess($request->request->get('access'));

        // Topic
        if ($topicId = $request->request->get('topic_id')) {
            $topic = $this->em->getRepository(Topic::class)->find($topicId);
            $template->setTopic($topic);
        }

        // Tags
        $tagNames = array_filter(array_map('trim', explode(',', $request->request->get('tags', ''))));
        foreach ($tagNames as $tagName) {
            $tag = $this->em->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setName($tagName);
                $this->em->persist($tag);
            }
            $template->addTag($tag);
        }

        // Access control users (for private templates)
        if ($template->getAccess() === 'private') {
            $userIds = explode(',', $request->request->get('user_ids', ''));
            $users = $this->userRepo->findBy(['id' => $userIds]);
            foreach ($users as $user) {
                $template->addUser($user);
            }
        }

        // Add questions
        $titles = $request->request->all('question_title');
        $descriptions = $request->request->all('question_description');
        $types = $request->request->all('question_type');
        $showInTable = $request->request->all('question_show_in_table');

        foreach ($titles as $i => $qTitle) {
            $question = new Question();
            $question->setTemplate($template);
            $question->setTitle($qTitle);
            $question->setDescription($descriptions[$i] ?? '');
            $question->setType($types[$i] ?? 'Single_line_text');
            $question->setShowInTable(!empty($showInTable[$i]));
            $question->setPosition($i + 1);
            $this->em->persist($question);
        }

        $user = $this->userRepo->find($userId);
        $template->setAuthor($user);

        $this->em->persist($template);
        $this->em->flush();

        return $template;
    }

    public function getTemplatesByUserId(Request $request, int $userId)
    {
        $dtRequest = new DataTablesAjaxRequestService($request);

        $start = $dtRequest->getStart();
        $length = $dtRequest->getLength();
        $search = $dtRequest->getSearchText();

        $columnsMap = [
            0 => null,
            1 => 't.id',
            2 => 't.title',
            3 => null,
            4 => 't.description',
            5 => 'author.name',
        ];

        $orderBy = $dtRequest->getSortText($columnsMap) ?: 't.id desc';
        $orderParts = explode(' ', explode(',', $orderBy)[0]);
        $orderColumn = $orderParts[0];
        $orderDir = strtolower($orderParts[1] ?? 'asc');

        // Prevent invalid sorting
        if ($orderColumn === 'null' || $orderColumn === null) {
            $orderColumn = 't.id';
            $orderDir = 'desc';
        }

        $user = $this->userRepo->find($userId);

        $qb = $this->em->createQueryBuilder()
            ->select('t', 'author')
            ->from(Template::class, 't')
            ->leftJoin('t.author', 'author')
            ->where('author.id = :currentUserId')
            ->setParameter('currentUserId', $user?->getId());

        if ($search) {
            $qb->andWhere('t.title LIKE :search OR t.description LIKE :search OR author.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $filteredCount = (clone $qb)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->orderBy($orderColumn, $orderDir)
            ->setFirstResult($start)
            ->setMaxResults($length);

        $templates = $qb->getQuery()->getResult();
        $total = $this->templateRepository->count([]);

        $data = array_map(function (Template $t) {
            return [
                'id' => $t->getId(),
                'title' => $t->getTitle(),
                'imageUrl' => $t->getImageUrl(),
                'description' => $t->getDescription(),
                'author' => $t->getAuthor()?->getName() ?? '',
            ];
        }, $templates);

        return [
            'draw' => $dtRequest->getRequestData()['draw'] ?? 0,
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ];
    }

}
