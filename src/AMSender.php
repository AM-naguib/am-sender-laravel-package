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
use AMSender\Helpers\ImageHelper;
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

        // Handle image-related errors with more specific messages
        if (str_contains($errorMessage, 'failed to fetch image url') || 
            str_contains($errorMessage, 'image url') || 
            str_contains($errorMessage, 'url does not point to an image')) {
            throw new InvalidImageException($this->getImageErrorMessage($errorMessage));
        }

        if (str_contains($errorMessage, 'validation')) {
            throw new ValidationException($errorMessage);
        }
    }

    /**
     * Get user-friendly image error message.
     *
     * @param string $originalMessage
     * @return string
     */
    protected function getImageErrorMessage(string $originalMessage): string
    {
        if (str_contains($originalMessage, 'failed to fetch image url')) {
            return 'Image URL is not accessible. Please check: 1) URL is publicly accessible, 2) URL points to a valid image file, 3) Image server allows external access. Original error: ' . $originalMessage;
        }

        if (str_contains($originalMessage, 'url does not point to an image')) {
            return 'The provided URL does not point to a valid image file. Please ensure the URL ends with a valid image extension (jpg, jpeg, png, gif, webp, bmp). Original error: ' . $originalMessage;
        }

        return 'Image error: ' . $originalMessage . '. Please verify the image URL is publicly accessible and points to a valid image file.';
    }

    /**
     * Test an image URL for accessibility and validity.
     *
     * @param string $imageUrl
     * @return array
     */
    public function testImageUrl(string $imageUrl): array
    {
        return ImageHelper::testImageUrl($imageUrl);
    }

    /**
     * Get suggestions for fixing image URL issues.
     *
     * @param string $imageUrl
     * @return array
     */
    public function getImageUrlSuggestions(string $imageUrl): array
    {
        return ImageHelper::getImageUrlSuggestions($imageUrl);
    }
}
