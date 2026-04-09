<?php

namespace App\Exceptions\Data;

use Exception;
use Throwable;

class CryptoException extends Exception
{
    public function __construct(string $message = 'Crypto error occurred', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
