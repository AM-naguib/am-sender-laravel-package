# AM-Sender Laravel Package

A Laravel package for integrating with the AM-Sender API (https://am-sender.com/api) to send WhatsApp messages programmatically.

## Features

- Easy integration with Laravel 10+
- Auto-discovery support
- Facade-based API
- Comprehensive error handling
- Configurable HTTP client with retry logic
- Device management
- Message sending with support for images

## Installation

Install the package via Composer:

```bash
composer require am-naguib/am-sender
```

The package will be automatically discovered by Laravel.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="AMSender\AMSenderServiceProvider"
```

This will create a `config/am-sender.php` file. Add your AM-Sender credentials to your `.env` file:

```env
AM_SENDER_AUTH_KEY=your-auth-key-here
AM_SENDER_BASE_URL=https://am-sender.com/api
AM_SENDER_TIMEOUT=30
AM_SENDER_RETRY_TIMES=3
AM_SENDER_RETRY_SLEEP=1000
```

## Usage

### Using the Facade

```php
use AMSender\Facades\AMSender;

// List all devices
try {
    $devices = AMSender::listDevices();
    echo "Devices: " . json_encode($devices);
} catch (\AMSender\Exceptions\AMSenderException $e) {
    echo "Error: " . $e->getMessage();
}

// Create a new device
try {
    $device = AMSender::createDevice('My Device');
    echo "Device created: " . json_encode($device);
} catch (\AMSender\Exceptions\AMSenderException $e) {
    echo "Error: " . $e->getMessage();
}

// Send a message
try {
    $result = AMSender::send([
        'message' => 'Hello from Laravel!',
        'receivers' => ['+1234567890', '+0987654321'],
        'device_ids' => ['device-id-1', 'device-id-2'],
        'delay_time' => 5,
        'image' => 'https://example.com/image.jpg' // optional
    ]);
    echo "Message sent: " . json_encode($result);
} catch (\AMSender\Exceptions\AMSenderException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Using Dependency Injection

```php
use AMSender\AMSender;

class MessageController extends Controller
{
    public function sendMessage(AMSender $amSender)
    {
        try {
            $result = $amSender->send([
                'message' => 'Hello World!',
                'receivers' => ['+1234567890'],
                'device_ids' => ['your-device-id']
            ]);
            
            return response()->json($result);
        } catch (\AMSender\Exceptions\AMSenderException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

## API Methods

### listDevices()

Retrieve all devices associated with your account.

```php
$devices = AMSender::listDevices();
```

**Returns:**
```json
{
    "success": true,
    "devices": [
        {
            "device_id": "device-123",
            "name": "My Device",
            "status": "active",
            "qr": "qr-code-data"
        }
    ]
}
```

### createDevice(string $name)

Create a new device.

```php
$device = AMSender::createDevice('My New Device');
```

**Returns:**
```json
{
    "success": true,
    "data": {
        "device_id": "device-456",
        "name": "My New Device",
        "status": "pending",
        "qr": "qr-code-data"
    }
}
```

### send(array $payload)

Send a message to one or more recipients.

```php
$result = AMSender::send([
    'message' => 'Your message here',
    'receivers' => ['+1234567890', '+0987654321'],
    'device_ids' => ['device-id-1'],
    'delay_time' => 5, // optional delay in seconds
    'image' => 'https://example.com/image.jpg' // optional image URL
]);
```

**Parameters:**
- `message` (required): The message text to send
- `receivers` (required): Array of phone numbers (with country code)
- `device_ids` (required): Array of device IDs to send from
- `delay_time` (optional): Delay between messages in seconds
- `image` (optional): URL of image to send with the message

**Returns:**
```json
{
    "success": true,
    "message": "Message Sent Successfully"
}
```

## Input Validation

The package automatically validates all input data before sending requests to the API. This helps catch errors early and provides clear feedback.

### Validation Rules

#### Device Name Validation:
- **Required**: Cannot be empty
- **Length**: 3-50 characters
- **Characters**: Letters, numbers, spaces, hyphens, and underscores only

#### Message Validation:
- **Required**: Cannot be empty  
- **Length**: 3-4096 characters

#### Phone Number Validation:
- **Format**: Flexible format - accepts any phone number format
- **Length**: 5-20 characters
- **Characters**: Numbers, spaces, hyphens, parentheses, and plus sign allowed
- **Auto-Cleaning**: Empty phone numbers are automatically filtered out
- **No Duplicates**: Duplicate numbers are automatically detected

#### Device IDs Validation:
- **Required**: At least one device ID
- **Limit**: Maximum 10 devices per request
- **Length**: 3-100 characters per device ID
- **No Duplicates**: Duplicate device IDs are detected

#### Optional Fields:
- **delay_time**: 0-3600 seconds (1 hour max)
- **image**: Valid URL pointing to image file (jpg, jpeg, png, gif, webp, bmp)

### Validation Examples

```php
use AMSender\Facades\AMSender;
use AMSender\Exceptions\ValidationException;

try {
    // This will throw ValidationException - device name too short
    AMSender::createDevice('Hi');
} catch (ValidationException $e) {
    echo $e->getMessage(); // "Device name must be at least 3 characters long."
}

try {
    // This will throw ValidationException - phone number too short
    AMSender::send([
        'message' => 'Hello!',
        'receivers' => ['123'], // Too short
        'device_ids' => ['device-1']
    ]);
} catch (ValidationException $e) {
    echo $e->getMessage(); // "Phone number at index 0 is too short. Minimum 5 characters required."
}

try {
    // This will throw ValidationException - message too short
    AMSender::send([
        'message' => 'Hi', // Too short
        'receivers' => ['+1234567890'],
        'device_ids' => ['device-1']
    ]);
} catch (ValidationException $e) {
    echo $e->getMessage(); // "Message must be at least 3 characters long."
}

// Valid example
try {
    $result = AMSender::send([
        'message' => 'Hello from Laravel!',
        'receivers' => ['+1234567890', '+0987654321'],
        'device_ids' => ['device-1'],
        'delay_time' => 5,
        'image' => 'https://example.com/image.jpg'
    ]);
    echo "Message sent successfully!";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage();
}
```

## Image Handling

The package provides comprehensive image URL validation and helpful error messages for image-related issues.

### Image URL Requirements:
- Must be publicly accessible (no localhost or private URLs)
- Must use HTTP or HTTPS protocol
- Should point to a valid image file (jpg, jpeg, png, gif, webp, bmp, svg)
- Maximum URL length: 2048 characters

### Common Image Issues and Solutions:

```php
use AMSender\Facades\AMSender;
use AMSender\Exceptions\InvalidImageException;
use AMSender\Helpers\ImageHelper;

try {
    $result = AMSender::send([
        'message' => 'Check this image!',
        'receivers' => ['+1234567890'],
        'device_ids' => ['device-1'],
        'image' => 'https://example.com/image.jpg'
    ]);
} catch (InvalidImageException $e) {
    echo "Image error: " . $e->getMessage();
    
    // Get suggestions for fixing the URL
    $suggestions = ImageHelper::getImageUrlSuggestions('https://example.com/image.jpg');
    foreach ($suggestions as $suggestion) {
        echo "- " . $suggestion . "\n";
    }
}

// Test an image URL before sending
$imageUrl = 'https://example.com/image.jpg';
$testResult = ImageHelper::testImageUrl($imageUrl);

if (!$testResult['is_accessible']) {
    echo "Image URL is not accessible\n";
    foreach ($testResult['errors'] as $error) {
        echo "Error: " . $error . "\n";
    }
}

if ($testResult['warnings']) {
    foreach ($testResult['warnings'] as $warning) {
        echo "Warning: " . $warning . "\n";
    }
}
```

### Image Error Messages:

```php
// Common error scenarios:

// 1. URL not accessible
"Image URL is not accessible. Please check: 1) URL is publicly accessible, 2) URL points to a valid image file, 3) Image server allows external access."

// 2. Invalid file type
"The provided URL does not point to a valid image file. Please ensure the URL ends with a valid image extension (jpg, jpeg, png, gif, webp, bmp)."

// 3. URL format issues
"Image must be a valid URL format."
"Image URL must use HTTP or HTTPS protocol."
```

## Exception Handling

The package provides specific exception classes for different error scenarios:

- `AMSenderException` - Base exception class
- `UserNotFoundException` - User not found
- `SubscriptionExpiredException` - Subscription has expired
- `DeviceNotFoundException` - Device not found or inactive
- `InvalidImageException` - Invalid or inaccessible image URL
- `AuthKeyNotValidException` - Invalid authentication key
- `LimitExceededException` - API limits exceeded
- `ValidationException` - Input validation errors (422 status code)

```php
use AMSender\Exceptions\SubscriptionExpiredException;
use AMSender\Exceptions\DeviceNotFoundException;

try {
    $result = AMSender::send($payload);
} catch (SubscriptionExpiredException $e) {
    // Handle subscription expiry
    echo "Your subscription has expired: " . $e->getMessage();
} catch (DeviceNotFoundException $e) {
    // Handle device issues
    echo "Device error: " . $e->getMessage();
} catch (\AMSender\Exceptions\AMSenderException $e) {
    // Handle other AM-Sender errors
    echo "AM-Sender error: " . $e->getMessage();
}
```

## Configuration Options

The `config/am-sender.php` file provides several configuration options:

```php
return [
    // API base URL
    'base_url' => env('AM_SENDER_BASE_URL', 'https://am-sender.com/api'),
    
    // Your authentication key
    'auth_key' => env('AM_SENDER_AUTH_KEY'),
    
    // Request timeout in seconds
    'timeout' => env('AM_SENDER_TIMEOUT', 30),
    
    // Retry configuration
    'retry' => [
        'times' => env('AM_SENDER_RETRY_TIMES', 3),
        'sleep' => env('AM_SENDER_RETRY_SLEEP', 1000),
    ],
];
```

## Best Practices

### Input Sanitization
The package automatically trims whitespace from all string inputs and validates data formats. However, you should still sanitize your inputs before passing them to the package.

### Error Handling
Always wrap your API calls in try-catch blocks to handle both validation errors and API errors gracefully:

```php
use AMSender\Facades\AMSender;
use AMSender\Exceptions\ValidationException;
use AMSender\Exceptions\AMSenderException;

try {
    $result = AMSender::send($payload);
    // Handle success
} catch (ValidationException $e) {
    // Handle validation errors (400-level errors)
    Log::warning('Validation error: ' . $e->getMessage());
} catch (AMSenderException $e) {
    // Handle API errors (500-level errors)
    Log::error('API error: ' . $e->getMessage());
}
```

### Phone Number Formatting
The package accepts flexible phone number formats and automatically filters out empty numbers:

```php
// ✅ All these formats are accepted
$receivers = [
    '+1234567890',      // International format
    '01234567890',      // Local format
    '123-456-7890',     // With hyphens
    '(123) 456-7890',   // With parentheses
    '123 456 7890',     // With spaces
    '',                 // Empty - will be filtered out automatically
    '   ',              // Whitespace only - will be filtered out
];

// Result: Empty numbers are automatically removed
// Final receivers: ['+1234567890', '01234567890', '123-456-7890', '(123) 456-7890', '123 456 7890']
```

### Batch Processing
For large recipient lists, consider breaking them into smaller batches:

```php
$allReceivers = ['+1111111111', '+2222222222', /* ... many more */];
$chunks = array_chunk($allReceivers, 100); // Process 100 at a time

foreach ($chunks as $chunk) {
    try {
        AMSender::send([
            'message' => 'Bulk message',
            'receivers' => $chunk,
            'device_ids' => ['device-1'],
            'delay_time' => 2 // Add delay between batches
        ]);
        sleep(5); // Wait between batches to avoid rate limits
    } catch (Exception $e) {
        Log::error("Batch failed: " . $e->getMessage());
    }
}
```

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Guzzle HTTP client

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support and questions about the AM-Sender API, please visit [https://am-sender.com](https://am-sender.com).

For package-specific issues, please create an issue in the repository.
