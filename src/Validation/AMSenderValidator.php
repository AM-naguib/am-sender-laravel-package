<?php

namespace AMSender\Validation;

use AMSender\Exceptions\ValidationException;

class AMSenderValidator
{
    /**
     * Validate device creation data.
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    public static function validateCreateDevice(array $data): void
    {
        // Device name validation
        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new ValidationException('Device name is required and cannot be empty.');
        }

        if (strlen(trim($data['name'])) < 3) {
            throw new ValidationException('Device name must be at least 3 characters long.');
        }

        if (strlen(trim($data['name'])) > 50) {
            throw new ValidationException('Device name cannot exceed 50 characters.');
        }

        // Check for invalid characters
        if (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', trim($data['name']))) {
            throw new ValidationException('Device name can only contain letters, numbers, spaces, hyphens, and underscores.');
        }
    }

    /**
     * Validate message sending data.
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    public static function validateSendMessage(array $data): void
    {
        // Message validation
        self::validateMessage($data);
        
        // Receivers validation
        self::validateReceivers($data);
        
        // Device IDs validation
        self::validateDeviceIds($data);
        
        // Optional fields validation
        self::validateOptionalFields($data);
    }

    /**
     * Validate message content.
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private static function validateMessage(array $data): void
    {
        if (!isset($data['message']) || !is_string($data['message'])) {
            throw new ValidationException('Message is required and must be a string.');
        }

        $message = trim($data['message']);
        
        if (empty($message)) {
            throw new ValidationException('Message cannot be empty.');
        }

        if (strlen($message) < 3) {
            throw new ValidationException('Message must be at least 3 characters long.');
        }

        if (strlen($message) > 4096) {
            throw new ValidationException('Message cannot exceed 4096 characters.');
        }
    }

    /**
     * Validate receivers array.
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private static function validateReceivers(array $data): void
    {
        if (!isset($data['receivers']) || !is_array($data['receivers'])) {
            throw new ValidationException('Receivers must be an array.');
        }

        // Filter out empty receivers
        $filteredReceivers = array_filter($data['receivers'], function($receiver) {
            return !empty(trim($receiver));
        });

        if (empty($filteredReceivers)) {
            throw new ValidationException('At least one valid receiver is required.');
        }

        if (count($filteredReceivers) > 1000) {
            throw new ValidationException('Cannot send to more than 1000 receivers at once.');
        }

        foreach ($filteredReceivers as $index => $receiver) {
            self::validatePhoneNumber($receiver, $index);
        }

        // Check for duplicates (after filtering)
        $uniqueReceivers = array_unique($filteredReceivers);
        if (count($uniqueReceivers) !== count($filteredReceivers)) {
            throw new ValidationException('Duplicate phone numbers found in receivers list.');
        }
    }

    /**
     * Validate device IDs array.
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private static function validateDeviceIds(array $data): void
    {
        if (!isset($data['device_ids']) || !is_array($data['device_ids'])) {
            throw new ValidationException('Device IDs must be an array.');
        }

        if (empty($data['device_ids'])) {
            throw new ValidationException('At least one device ID is required.');
        }

        if (count($data['device_ids']) > 10) {
            throw new ValidationException('Cannot use more than 10 devices at once.');
        }

        foreach ($data['device_ids'] as $index => $deviceId) {
            if (!is_string($deviceId) || empty(trim($deviceId))) {
                throw new ValidationException("Device ID at index {$index} is invalid. Must be a non-empty string.");
            }

            if (strlen(trim($deviceId)) < 3) {
                throw new ValidationException("Device ID at index {$index} is too short. Minimum 3 characters required.");
            }

            if (strlen(trim($deviceId)) > 100) {
                throw new ValidationException("Device ID at index {$index} is too long. Maximum 100 characters allowed.");
            }
        }

        // Check for duplicates
        $uniqueDeviceIds = array_unique($data['device_ids']);
        if (count($uniqueDeviceIds) !== count($data['device_ids'])) {
            throw new ValidationException('Duplicate device IDs found.');
        }
    }

    /**
     * Validate optional fields.
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    private static function validateOptionalFields(array $data): void
    {
        // Validate delay_time
        if (isset($data['delay_time'])) {
            if (!is_numeric($data['delay_time'])) {
                throw new ValidationException('Delay time must be a number.');
            }

            $delayTime = (int) $data['delay_time'];
            if ($delayTime < 0) {
                throw new ValidationException('Delay time cannot be negative.');
            }

            if ($delayTime > 3600) {
                throw new ValidationException('Delay time cannot exceed 3600 seconds (1 hour).');
            }
        }

        // Validate image URL
        if (isset($data['image']) && !empty($data['image'])) {
            self::validateImageUrl($data['image']);
        }
    }

    /**
     * Validate phone number format.
     *
     * @param mixed $phoneNumber
     * @param int $index
     * @return void
     * @throws ValidationException
     */
    private static function validatePhoneNumber($phoneNumber, int $index): void
    {
        if (!is_string($phoneNumber)) {
            throw new ValidationException("Phone number at index {$index} must be a string.");
        }

        $phoneNumber = trim($phoneNumber);

        if (empty($phoneNumber)) {
            throw new ValidationException("Phone number at index {$index} cannot be empty.");
        }

        // Basic validation - just check it's not empty and has reasonable length
        if (strlen($phoneNumber) < 5) {
            throw new ValidationException("Phone number at index {$index} is too short. Minimum 5 characters required.");
        }

        if (strlen($phoneNumber) > 20) {
            throw new ValidationException("Phone number at index {$index} is too long. Maximum 20 characters allowed.");
        }

        // Allow only numbers, spaces, hyphens, parentheses, and plus sign
        if (!preg_match('/^[0-9\s\-\(\)\+]+$/', $phoneNumber)) {
            throw new ValidationException("Phone number at index {$index} contains invalid characters. Only numbers, spaces, hyphens, parentheses, and plus sign are allowed.");
        }
    }

    /**
     * Validate image URL with comprehensive checks.
     *
     * @param string $imageUrl
     * @return void
     * @throws ValidationException
     */
    public static function validateImageUrl(string $imageUrl): void
    {
        if (!is_string($imageUrl)) {
            throw new ValidationException('Image must be a string URL.');
        }

        $imageUrl = trim($imageUrl);

        if (empty($imageUrl)) {
            return; // Allow empty image URLs
        }

        if (strlen($imageUrl) > 2048) {
            throw new ValidationException('Image URL cannot exceed 2048 characters.');
        }

        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Image must be a valid URL format.');
        }

        // Check URL scheme
        $parsedUrl = parse_url($imageUrl);
        if (!isset($parsedUrl['scheme']) || !in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])) {
            throw new ValidationException('Image URL must use HTTP or HTTPS protocol.');
        }

        // Check if URL points to an image file
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $path = $parsedUrl['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if (!empty($extension) && !in_array($extension, $imageExtensions)) {
            throw new ValidationException('Image URL must point to a valid image file (jpg, jpeg, png, gif, webp, bmp, svg).');
        }

        // Basic URL accessibility check (optional warning)
        if (!self::isUrlAccessible($imageUrl)) {
            throw new ValidationException('Warning: Image URL appears to be inaccessible. Please verify the URL is publicly accessible.');
        }
    }

    /**
     * Check if URL is accessible (basic check).
     *
     * @param string $url
     * @return bool
     */
    private static function isUrlAccessible(string $url): bool
    {
        // Use get_headers for a quick check
        try {
            $headers = @get_headers($url, 1);
            if ($headers === false) {
                return false;
            }

            // Check if we got a successful response
            $httpCode = 0;
            if (isset($headers[0])) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches);
                $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;
            }

            return $httpCode >= 200 && $httpCode < 400;
        } catch (\Exception $e) {
            // If we can't check, assume it's accessible to avoid blocking valid URLs
            return true;
        }
    }

    /**
     * Validate auth key format.
     *
     * @param string|null $authKey
     * @return void
     * @throws ValidationException
     */
    public static function validateAuthKey(?string $authKey): void
    {
        if (empty($authKey)) {
            throw new ValidationException('Auth key is required. Please set AM_SENDER_AUTH_KEY in your environment.');
        }

        if (strlen($authKey) < 10) {
            throw new ValidationException('Auth key appears to be invalid. It should be at least 10 characters long.');
        }

        if (strlen($authKey) > 255) {
            throw new ValidationException('Auth key is too long. Maximum 255 characters allowed.');
        }

        // Basic format validation (alphanumeric and some special characters)
        if (!preg_match('/^[a-zA-Z0-9\-_\.]+$/', $authKey)) {
            throw new ValidationException('Auth key contains invalid characters. Only letters, numbers, hyphens, underscores, and dots are allowed.');
        }
    }
}
