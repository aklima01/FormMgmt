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
        $filename = 'support_ticket_' . $dateTime . '_' . $this->generateUUIDv4() . '.json';
        $this->dropboxUploader->uploadJson(json_encode($json, JSON_PRETTY_PRINT), $filename);

        $this->logger->info('Support ticket submitted.', ['ticket' => $json]);
    }

    private function generateUUIDv4(): string {
        $data = random_bytes(16);

        // Set version to 0100
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        // Format UUID string
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
