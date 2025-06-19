<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Tag;
use App\Entity\Template;
use App\Entity\Topic;
use App\Entity\User;
use App\Repository\TemplateRepository;
use App\Repository\TopicRepository;
use App\Repository\User\UserRepository;
use App\Service\Common\DataTablesAjaxRequestService;
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/template', name: 'template_')]
class TemplateController extends AbstractController
{
    private $entityManager;


    public function __construct(
        EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $params,
        private readonly UserRepository $userRepo,
        private  readonly TopicRepository $topicRepository,
        private readonly TemplateRepository $templateRepository
    )
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(TemplateRepository $templateRepository): Response
    {
        return $this->render('template/list.html.twig', [

        ]);
    }

    #[Route('/ajax/templates', name: 'ajax', methods: ['GET'])]
    public function handleAjaxTemplatesRequest(Request $request, EntityManagerInterface $em, TemplateRepository $templateRepository): JsonResponse
    {
        $dtRequest = new DataTablesAjaxRequestService($request);

        $start = $dtRequest->getStart();
        $length = $dtRequest->getLength();
        $search = $dtRequest->getSearchText();

        $columnsMap = [
            1 => 't.title',
            2 => 't.imageUrl',
            3 => 't.description',
            4 => 'author.name', // assuming relation
        ];

        $orderBy = $dtRequest->getSortText($columnsMap) ?: 't.title asc';

        $orderParts = explode(' ', explode(',', $orderBy)[0]);
        $orderColumn = $orderParts[0];
        $orderDir = strtolower($orderParts[1] ?? 'asc');

//        $qb = $em->createQueryBuilder()
//            ->select('t', 'author')
//            ->from(Template::class, 't')
//            ->leftJoin('t.author', 'author');

        $currentUserId = $this->getUser()?->getId();
        $qb = $em->createQueryBuilder()
            ->select('t', 'author')
            ->from(Template::class, 't')
            ->leftJoin('t.author', 'author')
            ->where('author.id = :currentUserId')
            ->setParameter('currentUserId', $currentUserId);

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
        $total = $templateRepository->count([]);

        $data = array_map(function (Template $t) {
            return [
                'id' => $t->getId(),
                'title' => $t->getTitle(),
                'image' => $t->getImageUrl() ? sprintf('<img src="%s" class="img-fluid" width="50" height="50"/>', $t->getImageUrl()) : '<em>No image</em>',
                'description' => $t->getDescription() ?: '<em>No description</em>',
                'author' => $t->getAuthor()?->getName() ?? '',
                'actions' => [
                    'editUrl' => $this->generateUrl('template_edit', ['id' => $t->getId()]),
                    'id' => $t->getId(),
                    'csrfToken' => $this->container->get('security.csrf.token_manager')->getToken('delete' . $t->getId())->getValue(),
                ],
            ];
        }, $templates);

        return new JsonResponse([
            'draw' => $dtRequest->getRequestData()['draw'] ?? 0,
            'recordsTotal' => $total,
            'recordsFiltered' => $filteredCount,
            'data' => $data,
        ]);
    }


    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, S3Client $s3): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $topic_id = $request->request->get('topic_id');
            $tagsInput = $request->request->get('tags');
            $file = $request->files->get('image');

            $template = new Template();

            if($file){
                $uuid = uniqid();
                $originalName = $file->getClientOriginalName();
                $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg';
                $filename = $uuid . '.' . $ext;
                $bucket = $this->params->get('r2_bucket');
                $key = "uploads/{$filename}";

                try {
                    $s3->putObject([
                        'Bucket' => $bucket,
                        'Key' => $key,
                        'Body' => fopen($file->getPathname(), 'rb'),
                        'ACL' => 'public-read',
                        'ContentType' => $file->getMimeType(),
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Upload failed: ' . $e->getMessage());
                    //return $this->redirectToRoute('image_create');
                }

                $template->setImageUrl($this->params->get('r2_public_url') . '/' . $key);

            }

            $template->setTitle($title);
            $template->setDescription($description);

            if($topic_id) {
                $topic = $this->entityManager->getRepository(Topic::class)->find($topic_id);
                $template->setTopic($topic);
            }

            $tagNames = array_filter(array_map('trim', explode(',', $tagsInput)));

            foreach ($tagNames as $tagName) {
                $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['name' => $tagName]);

                if (!$tag) {
                    $tag = new Tag();
                    $tag->setName($tagName);
                    $this->entityManager->persist($tag);
                }

                $template->addTag($tag);
            }


            $template->setAccess($request->request->get('access'));
            if ($template->getAccess() === 'private') {
                $userIds = explode(',', $request->request->get('user_ids'));
                $users = $this->userRepo->findBy(['id' => $userIds]);

                foreach ($users as $user) {
                    $template->addUser($user);
                }
            }

            $this->entityManager->persist($template);

            //Questions
            $titles = $request->request->all('question_title');
            $descriptions = $request->request->all('question_description');
            $types = $request->request->all('question_type');
            $showInTable = $request->request->all('question_show_in_table');

            foreach ($titles as $i => $title) {
                $question = new Question();
                $question->setTemplate($template);
                $question->setTitle($title);
                $question->setDescription($descriptions[$i] ?? '');
                $question->setType($types[$i] ?? 'Single_line_text');
                $question->setShowInTable(array_key_exists($i, $showInTable));
                $question->setPosition($i + 1);

                $this->entityManager->persist($question);
            }

            $currentUser = $this->getUser();
            $user = $this->userRepo->find($currentUser->getId());

            if (!$user) {
                throw $this->createAccessDeniedException('User must be logged in.');
            }

            $template->setAuthor($user);

            $this->entityManager->flush();
            return $this->redirectToRoute('template_list');
        }
        return $this->render('template/create.html.twig');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $id,Request $request, Template $template, S3Client $s3,LoggerInterface $logger): Response
    {
        if ($request->isMethod('POST')) {
            $template = $this->templateRepository->find($id);
            $template->setTitle($request->request->get('title'));
            $template->setDescription($request->request->get('description'));

            // Handle Topic
            $topicId = $request->request->get('topic_id');
            if ($topicId) {
                $topic = $this->entityManager->getRepository(Topic::class)->find($topicId);
                $template->setTopic($topic);
            } else {
                $template->setTopic(null);
            }

            // Handle Tags
            $tagsIds = $request->request->get('tags', '');
            $tagNames = array_filter(array_map('trim', explode(',', $tagsIds)));
           // $logger->info('$tagNames', ['$tagNames' => $tagNames]);
            $template->clearTags(); // Clear existing tags

            foreach ($tagNames as $tagName) {
                $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
                if (!$tag) {
                    $tag = new Tag();
                    $tag->setName($tagName);
                    $this->entityManager->persist($tag);
                }
                $template->addTag($tag);
            }

            // Handle Access and Users
            $template->setAccess($request->request->get('access'));
            $template->clearUsers(); // Clear existing users

            if ($template->getAccess() === 'private') {
                $userIdsRaw = $request->request->get('user_ids', '');
                $userIds = array_filter(array_map('trim', explode(',', $userIdsRaw)));
                if (!empty($userIds)) {
                    $users = $this->userRepo->findBy(['id' => $userIds]);
                    foreach ($users as $user) {
                        $template->addUser($user);
                    }
                }
            }

            // Handle Image Upload
            $file = $request->files->get('image');
            if ($file) {
                $uuid = uniqid();
                $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = $uuid . '.' . $ext;
                $bucket = $this->params->get('r2_bucket');
                $key = "uploads/{$filename}";

                try {
                    $s3->putObject([
                        'Bucket' => $bucket,
                        'Key' => $key,
                        'Body' => fopen($file->getPathname(), 'rb'),
                        'ACL' => 'public-read',
                        'ContentType' => $file->getMimeType(),
                    ]);
                    $template->setImageUrl($this->params->get('r2_public_url') . '/' . $key);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Upload failed: ' . $e->getMessage());
                }
            }


            //Questions
            $questionIds = $request->request->all('question_id');
            $titles = $request->request->all('question_title');
            $descriptions = $request->request->all('question_description');
            $types = $request->request->all('question_type');
            $showInTable = $request->request->all('question_show_in_table');


            // Preload all existing questions for the template and index by ID
            $existingQuestions = [];
            foreach ($template->getQuestions() as $question) {
                $existingQuestions[$question->getId()] = $question;
            }

            $usedQuestionIds = [];

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
                $question->setShowInTable(array_key_exists($i, $showInTable));
                $question->setPosition($i + 1);

                $this->entityManager->persist($question);

                if ($questionId) {
                    $usedQuestionIds[] = $questionId;
                }
            }

            //Remove questions that were not submitted (deleted by user)
            foreach ($existingQuestions as $id => $existingQuestion) {
                if (!in_array($id, $usedQuestionIds)) {
                    $this->entityManager->remove($existingQuestion);
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Template updated successfully.');

            return $this->redirectToRoute('template_list');
        }


        // Fetch the Template entity by id
        $template = $this->templateRepository->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template not found');
        }

        // Fetch all topics to populate the dropdown
        $topics = $this->topicRepository->findAll();
        // Prepare selected tags as JSON
        $selectedTags = [];
        foreach ($template->getTags() as $tag) {
            $selectedTags[] = [
                'id' => $tag->getId(),
                'name' => $tag->getName()
            ];
        }

        $selectedUsers = [];
        foreach ($template->getUsers() as $user) {
            $selectedUsers[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail()
            ];
        }

        $template_questions_json = [];
        foreach ($template->getQuestions() as $question) {
            $template_questions_json[] = [
                'id' => $question->getId(),
                'title' => $question->getTitle(),
                'description' => $question->getDescription(),
                'type' => $question->getType(),
                'showInTable' => $question->isShowInTable(),
            ];
        }

        // Pass both to Twig
        return $this->render('template/edit.html.twig', [
            'template' => $template,
            'topics' => $topics,
            'selectedTagsJson' => json_encode($selectedTags),
            'selectedUsersJson' => json_encode($selectedUsers),
            'template_questions_json' => json_encode($template_questions_json),
        ]);
    }

    #[Route('/ajax/topics', name: 'ajax_topics')]
    public function fetchTopics(TopicRepository $topicRepository): JsonResponse
    {
        $topics = $topicRepository->findAll();

        $data = array_map(fn($topic) => [
            'id' => $topic->getId(),
            'name' => $topic->getName()
        ], $topics);

        return new JsonResponse($data);
    }

    #[Route('/tag/search', name: 'tag_search')]
    public function tagSearch(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $term = strtolower($request->query->get('term', ''));
        $tags = $em->getRepository(Tag::class)
            ->createQueryBuilder('t')
            ->where('LOWER(t.name) LIKE :term')
            ->setParameter('term', $term . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();

        $results = array_map(fn($tag) => ['id' => $tag['id'], 'text' => $tag['name']], $tags);
        return new JsonResponse($results);
    }

    #[Route('/user-search', name: 'user_search', methods: ['GET'])]
    public function userSearch(Request $request, UserRepository $userRepo): JsonResponse
    {
        $term = $request->query->get('term', '');
        $sort = $request->query->get('sort', 'name');

        $qb = $userRepo->createQueryBuilder('u')
            ->where('LOWER(u.name) LIKE :term OR LOWER(u.email) LIKE :term')
            ->setParameter('term', '%' . strtolower($term) . '%');

        $allowedSorts = ['name', 'email'];
        if (in_array($sort, $allowedSorts, true)) {
            $qb->orderBy('u.' . $sort, 'ASC');
        }

        $users = $qb->getQuery()->getResult();

        $data = array_map(fn(User $u) => [
            'id' => $u->getId(),
            'name' => $u->getName(),
            'email' => $u->getEmail(),
        ], $users);

        return new JsonResponse($data);
    }



    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, TemplateRepository $templateRepository, EntityManagerInterface $em): Response
    {
        $template = $templateRepository->find($id);

        if (!$template) {
            throw $this->createNotFoundException('Template not found.');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete'.$id, $submittedToken)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($template);
        $em->flush();

        return $this->redirectToRoute('template_list');
    }





}
