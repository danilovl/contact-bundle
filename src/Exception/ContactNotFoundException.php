<?php declare(strict_types=1);

namespace Danilovl\ContactBundle\Exception;

use RuntimeException;

class ContactNotFoundException extends RuntimeException
{
    public static function create(int $id): self
    {
        $message = sprintf('Contact "%d" not found.', $id);

        return new self($message);
    }
}
