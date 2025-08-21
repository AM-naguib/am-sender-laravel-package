<?php

namespace AMSender\Helpers;

class ImageHelper
{
    /**
     * Test if an image URL is accessible and valid.
     *
     * @param string $imageUrl
     * @return array
     */
    public static function testImageUrl(string $imageUrl): array
    {
        $result = [
            'is_valid' => false,
            'is_accessible' => false,
            'is_image' => false,
            'content_type' => null,
            'file_size' => null,
            'errors' => [],
            'warnings' => []
        ];

        // Basic URL validation
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $result['errors'][] = 'Invalid URL format';
            return $result;
        }

        $result['is_valid'] = true;

        // Check URL accessibility
        try {
            $headers = @get_headers($imageUrl, 1);
            if ($headers === false) {
                $result['errors'][] = 'URL is not accessible';
                return $result;
            }

            // Parse HTTP response code
            $httpCode = 0;
            if (isset($headers[0])) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches);
                $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;
            }

            if ($httpCode < 200 || $httpCode >= 400) {
                $result['errors'][] = "HTTP error code: {$httpCode}";
                return $result;
            }

            $result['is_accessible'] = true;

            // Check content type
            $contentType = null;
            if (isset($headers['Content-Type'])) {
                $contentType = is_array($headers['Content-Type']) 
                    ? end($headers['Content-Type']) 
                    : $headers['Content-Type'];
            } elseif (isset($headers['content-type'])) {
                $contentType = is_array($headers['content-type']) 
                    ? end($headers['content-type']) 
                    : $headers['content-type'];
            }

            $result['content_type'] = $contentType;

            // Check if it's an image
            if ($contentType) {
                $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'];
                $result['is_image'] = in_array(strtolower($contentType), $imageTypes);
                
                if (!$result['is_image']) {
                    $result['warnings'][] = "Content type '{$contentType}' may not be a valid image type";
                }
            } else {
                $result['warnings'][] = 'No content type header found';
            }

            // Get file size
            if (isset($headers['Content-Length'])) {
                $contentLength = is_array($headers['Content-Length']) 
                    ? end($headers['Content-Length']) 
                    : $headers['Content-Length'];
                $result['file_size'] = (int)$contentLength;
                
                // Warn about large files
                if ($result['file_size'] > 5 * 1024 * 1024) { // 5MB
                    $result['warnings'][] = 'Image file is larger than 5MB, which may cause slow loading';
                }
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Error checking URL: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get suggestions for fixing common image URL issues.
     *
     * @param string $imageUrl
     * @return array
     */
    public static function getImageUrlSuggestions(string $imageUrl): array
    {
        $suggestions = [];

        // Check for common issues
        if (!str_starts_with($imageUrl, 'http')) {
            $suggestions[] = 'Add HTTP or HTTPS protocol to the URL';
        }

        if (str_contains($imageUrl, 'localhost') || str_contains($imageUrl, '127.0.0.1')) {
            $suggestions[] = 'Localhost URLs are not accessible from external services. Use a public URL instead';
        }

        if (str_contains($imageUrl, 'file://')) {
            $suggestions[] = 'File:// URLs are not accessible over the internet. Upload the image to a web server';
        }

        if (!preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i', $imageUrl)) {
            $suggestions[] = 'Consider adding a valid image file extension to the URL';
        }

        if (str_contains($imageUrl, ' ')) {
            $suggestions[] = 'URL contains spaces. Encode them as %20 or remove them';
        }

        return $suggestions;
    }

    /**
     * Convert a local file path to a suggestion for web hosting.
     *
     * @param string $localPath
     * @return string
     */
    public static function getWebHostingSuggestion(string $localPath): string
    {
        return "To use '{$localPath}', you need to:\n" .
               "1. Upload the image to a web server or cloud storage (AWS S3, Cloudinary, etc.)\n" .
               "2. Get the public URL of the uploaded image\n" .
               "3. Use that URL instead of the local file path";
    }
}
