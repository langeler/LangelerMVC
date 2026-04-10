<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

class SessionException extends AppException
{
    public function __construct(string $message = 'Session error occurred.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
