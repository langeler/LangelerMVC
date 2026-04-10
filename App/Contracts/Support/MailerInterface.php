<?php

declare(strict_types=1);

namespace App\Contracts\Support;

use App\Abstracts\Support\Mailable;

interface MailerInterface
{
    public function send(Mailable $mailable): bool;

    public function driverName(): string;

    /**
     * @return array<string, mixed>
     */
    public function capabilities(): array;

    public function supports(string $feature): bool;
}
