<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fcm {

    private $projectId = 'deshishad-push'; // Your Firebase project ID
    private $serviceAccountPath;
    private $accessToken;

    public function __construct() {
        $this->serviceAccountPath = APPPATH . 'libraries/firebase-service-account.json';
    }

    public function send($token, $title, $body, $data = []) {
        $this->accessToken = $this->getAccessToken();
        if (!$this->accessToken) {
            return ['error' => 'Failed to retrieve access token'];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }

    private function getAccessToken() {
        $googleCredentials = json_decode(file_get_contents($this->serviceAccountPath), true);

        $now = time();
        $jwtHeader = ['alg' => 'RS256', 'typ' => 'JWT'];
        $jwtClaim = [
            'iss' => $googleCredentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $jwtHeaderEncoded = $this->base64UrlEncode(json_encode($jwtHeader));
        $jwtClaimEncoded = $this->base64UrlEncode(json_encode($jwtClaim));

        $dataToSign = $jwtHeaderEncoded . '.' . $jwtClaimEncoded;
        openssl_sign($dataToSign, $signature, $googleCredentials['private_key'], 'sha256');
        $jwtSignatureEncoded = $this->base64UrlEncode($signature);

        $jwt = $dataToSign . '.' . $jwtSignatureEncoded;

        $response = $this->httpPost('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        return $response['access_token'] ?? null;
    }

    private function httpPost($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    private function base64UrlEncode($input) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($input));
    }
}