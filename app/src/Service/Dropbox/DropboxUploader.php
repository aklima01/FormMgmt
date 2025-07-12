<?php

namespace App\Service\Dropbox;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DropboxUploader
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $params
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

        $ch = curl_init('https://api.dropboxapi.com/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]),
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \RuntimeException('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($statusCode !== 200) {
            throw new \RuntimeException("Failed to get access token. Status code: $statusCode. Response: $response");
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? throw new \RuntimeException('Access token not found in response.');
    }

    private function uploadToDropbox(string $filepath, string $filename): void
    {
        if (!file_exists($filepath)) {
            throw new \RuntimeException('Temp file not found: ' . $filepath);
        }

        $accessToken = $this->getAccessToken();
        $dropboxPath = "/support_tickets/{$filename}";
        $fileContents = file_get_contents($filepath);

        $ch = curl_init('https://content.dropboxapi.com/2/files/upload');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/octet-stream',
                'Dropbox-API-Arg: ' . json_encode([
                    'path' => $dropboxPath,
                    'mode' => 'add',
                    'autorename' => true,
                    'mute' => false,
                    'strict_conflict' => false,
                ]),
            ],
            CURLOPT_POSTFIELDS => $fileContents,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \RuntimeException('Curl error during upload: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($statusCode !== 200) {
            $this->logger->error('Dropbox upload failed.', ['response' => $response]);
            throw new \RuntimeException("Dropbox upload failed. Status: $statusCode. Response: $response");
        }

        $this->logger->info('Upload to Dropbox successful.', [
            'filepath' => $filepath,
            'dropboxPath' => $dropboxPath,
        ]);
    }
}
