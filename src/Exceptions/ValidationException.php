<?php

namespace AMSender\Exceptions;

class ValidationException extends AMSenderException
{
    /**
     * Array of validation errors.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Create a new validation exception.
     *
     * @param string $message
     * @param array $errors
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', array $errors = [], int $code = 422, \Throwable $previous = null)
    {
        $this->errors = $errors;
        
        if (empty($message) && !empty($errors)) {
            $message = 'Validation failed: ' . implode(', ', $errors);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are validation errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Add a validation error.
     *
     * @param string $error
     * @return void
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }
}
