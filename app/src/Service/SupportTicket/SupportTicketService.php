<?php

namespace App\Service\SupportTicket;

use App\Repository\TemplateRepository;
use App\Repository\User\UserRepositoryInterface;
use App\Service\Dropbox\DropboxUploader;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class SupportTicketService
{
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly DropboxUploader $dropboxUploader,
        private readonly LoggerInterface $logger,

    ) {}

    public function handle(Request $request, ?UserInterface $user): void
    {
        $summary = $request->request->get('summary');
        $priority = $request->request->get('priority');
        $currentUrl = $request->request->get('refererUrl');

        $templateId = null;
        if (preg_match('/\/templates\/(\d+)/', $currentUrl, $matches) ||
            preg_match('/templates[^?]*[?&]id=(\d+)/', $currentUrl, $matches)) {
            $templateId = (int) $matches[1];
        }

        $templateName = '';
        if ($templateId) {
            $template = $this->templateRepository->find($templateId);
            $templateName = $template?->getTitle() ?? '';
        }

        $adminEmails = $this->userRepository->findAdminEmails();

        $json = [
            'Reported by' => (string) ($user?->getUserIdentifier() ?? 'Anonymous'),
            'Template' => $templateName,
            'Link' => $currentUrl,
            'Priority' => in_array($priority, ['High', 'Average', 'Low']) ? $priority : 'Average',
            'Summary' => $summary,
            'Admins' => array_filter($adminEmails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)),
        ];

        $dateTime = date('Y-m-d_H-i-s');
        $filename = 'support_ticket_' . $dateTime . '_' . $this->generateUniqueId() . '.json';
        $this->dropboxUploader->uploadJson(json_encode($json, JSON_PRETTY_PRINT), $filename);

        $this->logger->info('Support ticket submitted.', ['ticket' => $json]);
    }

    private function generateUniqueId(): int {
        return bin2hex(random_bytes(16));;
    }
}
