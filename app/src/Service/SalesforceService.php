<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class SalesforceService
{
    private ?string $accessToken = null;

    public function __construct(
        private string $tokenUrl,
        private string $clientId,
        private string $clientSecret,
        private string $instanceUrl,
    ) {}

    public function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        $ch = curl_init($this->tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_POST, true);
        $response = curl_exec($ch);
        if ($response === false) {
            $this->logger->error('cURL error during token request: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);


        $result = json_decode($response, true);
        if (!empty($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            return $this->accessToken;
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
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ];

        // 1. Create Account
        $accountPayload = [
            'Name'           => $userData['account_name'],
            'Phone'          => $userData['phone'] ?? '',
            'AccountNumber'  => $userData['account_number'] ?? '',
            'Website'        => $userData['website'] ?? '',
        ];

        $accountResponse = $this->makeRequest("/services/data/v59.0/sobjects/Account", $headers, $accountPayload);

        if (empty($accountResponse['id'])) {
            return false;
        }

        $accountId = $accountResponse['id'];

        // 2. Create Contact
        $contactPayload = [
            'LastName'  => $userData['account_name'],
            'Email'     => $userData['email'] ?? '',
            'Phone'     => $userData['phone'] ?? '',
            'AccountId' => $accountId,
        ];

        $contactResponse = $this->makeRequest("/services/data/v59.0/sobjects/Contact", $headers, $contactPayload);

        if (empty($contactResponse['id'])) {
            return false;
        }

        return true;
    }

    private function makeRequest(string $path, array $headers, array $data): ?array
    {
        $ch = curl_init($this->instanceUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if ($response === false) {
            $this->logger->error('cURL error during Salesforce request: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        return json_decode($response, true);
    }
}
