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
use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
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
    )
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(TemplateRepository $templateRepository): Response
    {
        return $this->render('template/list.html.twig', [
            'templates' => $templateRepository->findAll(),
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


            $this->entityManager->flush();
            return $this->redirectToRoute('template_list');
        }
        return $this->render('template/create.html.twig');
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



}
