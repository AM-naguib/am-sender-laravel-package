<?php

namespace AMSender;

use AMSender\Exceptions\AMSenderException;
use AMSender\Exceptions\AuthKeyNotValidException;
use AMSender\Exceptions\DeviceNotFoundException;
use AMSender\Exceptions\InvalidImageException;
use AMSender\Exceptions\LimitExceededException;
use AMSender\Exceptions\SubscriptionExpiredException;
use AMSender\Exceptions\UserNotFoundException;
use AMSender\Exceptions\ValidationException;
use AMSender\Validation\AMSenderValidator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AMSender
{
    protected PendingRequest $client;
    protected string $authKey;

    public function __construct()
    {
        $this->authKey = config('am-sender.auth_key');
        
        // Validate auth key
        AMSenderValidator::validateAuthKey($this->authKey);

        $this->client = Http::baseUrl(config('am-sender.base_url'))
            ->timeout(config('am-sender.timeout', 30))
            ->retry(
                config('am-sender.retry.times', 3),
                config('am-sender.retry.sleep', 1000)
            );
    }

    /**
     * List all devices for the authenticated user.
     *
     * @return array
     * @throws AMSenderException
     */
    public function listDevices(): array
    {
        $response = $this->client->get('/devices', [
            'auth_key' => $this->authKey,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Create a new device.
     *
     * @param string $name
     * @return array
     * @throws AMSenderException
     */
    public function createDevice(string $name): array
    {
        // Validate input data
        AMSenderValidator::validateCreateDevice(['name' => $name]);

        $response = $this->client->post('/devices/create', [
            'name' => trim($name),
            'auth_key' => $this->authKey,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Send a message.
     *
     * @param array $payload
     * @return array
     * @throws AMSenderException
     */
    public function send(array $payload): array
    {
        // Validate input data
        AMSenderValidator::validateSendMessage($payload);

        // Clean and prepare payload - filter out empty receivers
        $cleanReceivers = array_filter(
            array_map('trim', $payload['receivers']), 
            function($receiver) {
                return !empty($receiver);
            }
        );

        $cleanPayload = [
            'message' => trim($payload['message']),
            'receivers' => array_values($cleanReceivers), // Re-index array after filtering
            'device_ids' => array_map('trim', $payload['device_ids']),
            'auth_key' => $this->authKey,
        ];

        // Add optional fields if present
        if (isset($payload['delay_time'])) {
            $cleanPayload['delay_time'] = (int) $payload['delay_time'];
        }

        if (isset($payload['image']) && !empty($payload['image'])) {
            $cleanPayload['image'] = trim($payload['image']);
        }

        $response = $this->client->post('/sender', $cleanPayload);

        return $this->handleResponse($response);
    }

    /**
     * Handle the API response and throw appropriate exceptions.
     *
     * @param Response $response
     * @return array
     * @throws AMSenderException
     */
    protected function handleResponse(Response $response): array
    {
        $data = $response->json();

        if ($response->successful() && isset($data['success']) && $data['success']) {
            return $data;
        }

        // Handle error responses
        $errorMessage = $data['message'] ?? $data['error'] ?? 'Unknown error occurred';

        // Map specific error messages to exceptions
        $this->throwSpecificException($errorMessage);

        // Default exception if no specific match
        throw new AMSenderException($errorMessage, $response->status());
    }

    /**
     * Throw specific exceptions based on error message.
     *
     * @param string $errorMessage
     * @throws AMSenderException
     */
    protected function throwSpecificException(string $errorMessage): void
    {
        $errorMessage = strtolower($errorMessage);

        if (str_contains($errorMessage, 'user not found')) {
            throw new UserNotFoundException($errorMessage);
        }

        if (str_contains($errorMessage, 'subscription expired')) {
            throw new SubscriptionExpiredException($errorMessage);
        }

        if (str_contains($errorMessage, 'device') && str_contains($errorMessage, 'not found')) {
            throw new DeviceNotFoundException($errorMessage);
        }

        if (str_contains($errorMessage, 'limit') && str_contains($errorMessage, 'exceed')) {
            throw new LimitExceededException($errorMessage);
        }

        if (str_contains($errorMessage, 'auth key not valid')) {
            throw new AuthKeyNotValidException($errorMessage);
        }

        if (str_contains($errorMessage, 'image') || str_contains($errorMessage, 'url')) {
            throw new InvalidImageException($errorMessage);
        }

        if (str_contains($errorMessage, 'validation')) {
            throw new ValidationException($errorMessage);
        }
    }
}
