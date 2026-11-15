<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Exception;

use RuntimeException;

class ContactValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Contact data is invalid.');
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
