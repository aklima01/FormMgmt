<?php

namespace App\Service\Salesforce;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SalesforceService
{
    private ?string $accessToken = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $tokenUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $instanceUrl,
    ) {}

    public function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', $this->tokenUrl, [
                'body' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $result = $response->toArray();

            if (!empty($result['access_token'])) {
                $this->accessToken = $result['access_token'];
                return $this->accessToken;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Salesforce token request failed: ' . $e->getMessage());
        }

        return null;
    }

    public function createAccountAndContact(array $userData): bool
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ];

        // Create Account
        $accountPayload = [
            'Name'           => $userData['account_name'],
            'Phone'          => $userData['phone'] ?? '',
            'AccountNumber'  => $userData['account_number'] ?? '',
            'Website'        => $userData['website'] ?? '',
        ];

        $accountResponse = $this->makeRequest('/services/data/v59.0/sobjects/Account', $headers, $accountPayload);
        if (empty($accountResponse['id'])) {
            return false;
        }

        // Create Contact
        $contactPayload = [
            'LastName'  => $userData['account_name'],
            'Email'     => $userData['email'] ?? '',
            'Phone'     => $userData['phone'] ?? '',
            'AccountId' => $accountResponse['id'],
        ];

        $contactResponse = $this->makeRequest('/services/data/v59.0/sobjects/Contact', $headers, $contactPayload);
        return !empty($contactResponse['id']);
    }

    private function makeRequest(string $path, array $headers, array $data): ?array
    {
        try {
            $response = $this->httpClient->request('POST', $this->instanceUrl . $path, [
                'headers' => $headers,
                'json'    => $data,
            ]);

            return $response->toArray(false); // keep errors in array
        } catch (\Throwable $e) {
            $this->logger->error('Salesforce API request failed: ' . $e->getMessage());
            return null;
        }
    }
}
