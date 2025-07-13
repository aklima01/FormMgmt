<?php

namespace App\Controller;

use App\Repository\TemplateRepository;

use App\Service\SupportTicket\SupportTicketService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SupportController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        public readonly TemplateRepository $templateRepository,
        private readonly SupportTicketService $supportTicketService,
    ) {}

    #[Route('/support-ticket-form', name: 'app_support_ticket_form', methods: ['GET'])]
    public function showForm(Request $request): Response
    {
        return $this->render('support/ticket_form.html.twig', [
            'currentUrl' => $request->headers->get('referer'),
        ]);
    }

    #[Route('/support-ticket', name: 'app_support_ticket', methods: ['POST'])]
    public function submit(Request $request, Security $security): Response
    {
        try {
            $this->supportTicketService->handle($request, $security->getUser());
            $this->addFlash('success', 'Support ticket submitted.');
        } catch (\Throwable $e) {
            $this->logger->error('Support ticket failed.', ['exception' => $e]);
            $this->addFlash('error', 'Failed to submit ticket.');
        }

        return $this->redirectToRoute('app_support_ticket_form');
    }

}
