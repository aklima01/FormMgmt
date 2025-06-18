<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Template;
use App\Repository\TopicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/template', name: 'template_')]
class TemplateController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $topic = $request->request->get('topic');
            $tagsInput = $request->request->get('tags');

            $template = new Template();
            $template->setTitle($title);
            $template->setDescription($description);
            $template->setTopic($topic);

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


            $this->entityManager->persist($template);
            $this->entityManager->flush();

            //return $this->redirectToRoute('template_list');
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



}
