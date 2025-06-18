<?php

namespace App\Controller;

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

            $template = new Template();
            $template->setTitle($title);
            $template->setDescription($description);
            $template->setTopic($topic);

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



}
