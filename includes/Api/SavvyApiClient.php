<?php

namespace SavvyWebFulfilment\Api;

use SavvyWebFulfilment\Admin\SavvyPluginConfig;
use SavvyWebFulfilment\Logger;

class SavvyApiClient
{
    protected string $baseUrl; 
    protected string $accessToken;
    protected string $lastEndpoint = '';
    private SavvyPluginConfig $savvyPluginConfig;

    public function __construct()
    {

        $this->savvyPluginConfig = new SavvyPluginConfig();
        $this->baseUrl = $this->savvyPluginConfig->getSavvyApiUrl();

        $this->accessToken = get_option('savvy_web_access_token');

        if (empty($this->accessToken)) {
            throw new \Exception('Access token is missing. Please set it in plugin settings.');
        }
    }

    public function post(string $endpoint, array $body = [], string $logRef = 'general')
    {
        $this->lastEndpoint = $endpoint;
        
        $url = $this->buildUrl($endpoint);

        $response = wp_remote_post($url, [
            'headers' => $this->getHeaders(),
            'timeout' => 20,
            'body' => wp_json_encode($body),
        ]);

        return $this->handleResponse('POST', $response, $logRef);
    }

    protected function buildUrl(string $endpoint, array $params = []): string
    {
        $clientCode = get_option('savvy_web_client_code');
        $storeIdentifier = get_option('savvy_web_store_identifier');

        if (empty($clientCode) || empty($storeIdentifier)) {
            throw new \Exception('Client Code or Store Identifier is not set in plugin settings.');
        }

        // Append the client code and store identifier to the endpoint
        $endpoint = rtrim($endpoint, '/') . '/' . rawurlencode($clientCode) . '/' . rawurlencode($storeIdentifier);

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    protected function handleResponse($method, $response, $logRef)
    {
        if (is_wp_error($response)) {
            Logger::log($logRef, $method, 'unknown', 0, 'error', $response->get_error_message());
            return ['error' => true, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $endpoint = $this->lastEndpoint ?? 'unknown';

        if ($code >= 400) {
            $message = $data['message'] ?? 'Unknown error';
            Logger::log($logRef, $method, $endpoint, $code, 'error', $message);
            return ['error' => true, 'message' => $message];
        }

        Logger::log($logRef, $method, $endpoint, $code, 'success', 'Request succeeded');
        return $data;
    }


}
