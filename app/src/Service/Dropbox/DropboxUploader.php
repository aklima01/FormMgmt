<?php

namespace App\Service\Dropbox;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DropboxUploader
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $params,
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function uploadJson(string $data, string $filename): void
    {
        $tempFile = sys_get_temp_dir() . '/' . $filename;

        if (file_put_contents($tempFile, $data) === false) {
            $this->logger->error('Failed to write ticket data to temp file.', ['file' => $tempFile]);
            throw new \RuntimeException('Failed to write data to temp file.');
        }

        try {
            $this->uploadToDropbox($tempFile, $filename);
        } finally {
            @unlink($tempFile);
        }
    }

    private function getAccessToken(): string
    {
        $clientId = $this->params->get('dropbox_client_id');
        $clientSecret = $this->params->get('dropbox_client_secret');
        $refreshToken = $this->params->get('dropbox_refresh_token');

        try {
            $response = $this->httpClient->request('POST', 'https://api.dropboxapi.com/oauth2/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ],
            ]);

            $data = $response->toArray(false);

            return $data['access_token'] ?? throw new \RuntimeException('Access token not found in response.');
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to get Dropbox access token: ' . $e->getMessage(), 0, $e);
        }
    }

    private function uploadToDropbox(string $filepath, string $filename): void
    {
        if (!file_exists($filepath)) {
            throw new \RuntimeException('Temp file not found: ' . $filepath);
        }

        $accessToken = $this->getAccessToken();
        $dropboxPath = "/support_tickets/{$filename}";
        $fileContents = file_get_contents($filepath);

        try {
            $response = $this->httpClient->request('POST', 'https://content.dropboxapi.com/2/files/upload', [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $accessToken,
                    'Content-Type'      => 'application/octet-stream',
                    'Dropbox-API-Arg'   => json_encode([
                        'path' => $dropboxPath,
                        'mode' => 'add',
                        'autorename' => true,
                        'mute' => false,
                        'strict_conflict' => false,
                    ]),
                ],
                'body' => $fileContents,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $content = $response->getContent(false); // keep error message
                $this->logger->error('Dropbox upload failed.', ['response' => $content]);
                throw new \RuntimeException("Dropbox upload failed. Status: $statusCode. Response: $content");
            }

            $this->logger->info('Upload to Dropbox successful.', [
                'filepath' => $filepath,
                'dropboxPath' => $dropboxPath,
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Dropbox upload error: ' . $e->getMessage(), 0, $e);
        }
    }
}
