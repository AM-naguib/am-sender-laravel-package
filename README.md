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
composer require am-sender/am-sender
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

## Exception Handling

The package provides specific exception classes for different error scenarios:

- `AMSenderException` - Base exception class
- `UserNotFoundException` - User not found
- `SubscriptionExpiredException` - Subscription has expired
- `DeviceNotFoundException` - Device not found or inactive
- `InvalidImageException` - Invalid image URL
- `AuthKeyNotValidException` - Invalid authentication key
- `LimitExceededException` - API limits exceeded
- `ValidationException` - Validation errors

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

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Guzzle HTTP client

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support and questions about the AM-Sender API, please visit [https://am-sender.com](https://am-sender.com).

For package-specific issues, please create an issue in the repository.
