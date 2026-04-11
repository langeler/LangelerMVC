<?php

declare(strict_types=1);

namespace App\Support;

use App\Abstracts\Support\Mailable;

class ArrayMailable extends Mailable
{
    /**
     * @param array<string, mixed> $message
     */
    public function __construct(private readonly array $message)
    {
    }

    protected function build(): void
    {
        foreach ((array) ($this->message['to'] ?? []) as $recipient) {
            if (is_array($recipient)) {
                $this->to((string) ($recipient['address'] ?? ''), isset($recipient['name']) ? (string) $recipient['name'] : null);
                continue;
            }

            $this->to((string) $recipient);
        }

        foreach ((array) ($this->message['cc'] ?? []) as $recipient) {
            if (is_array($recipient)) {
                $this->cc((string) ($recipient['address'] ?? ''), isset($recipient['name']) ? (string) $recipient['name'] : null);
                continue;
            }

            $this->cc((string) $recipient);
        }

        foreach ((array) ($this->message['bcc'] ?? []) as $recipient) {
            if (is_array($recipient)) {
                $this->bcc((string) ($recipient['address'] ?? ''), isset($recipient['name']) ? (string) $recipient['name'] : null);
                continue;
            }

            $this->bcc((string) $recipient);
        }

        if (is_array($this->message['reply_to'] ?? null)) {
            $this->replyTo(
                (string) ($this->message['reply_to']['address'] ?? ''),
                isset($this->message['reply_to']['name']) ? (string) $this->message['reply_to']['name'] : null
            );
        }

        $this->subject((string) ($this->message['subject'] ?? 'Notification'));

        if (isset($this->message['html'])) {
            $this->html((string) $this->message['html']);
        }

        if (isset($this->message['text'])) {
            $this->text((string) $this->message['text']);
        }
    }
}
