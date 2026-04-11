<?php

declare(strict_types=1);

namespace App\Drivers\Notifications;

use App\Contracts\Support\NotificationChannelInterface;
use App\Contracts\Support\NotificationInterface;
use App\Support\ArrayMailable;
use App\Utilities\Managers\Support\MailManager;

class MailNotificationChannel implements NotificationChannelInterface
{
    public function __construct(private readonly MailManager $mailManager)
    {
    }

    public function name(): string
    {
        return 'mail';
    }

    public function send(array $notifiable, NotificationInterface $notification): array|bool|null
    {
        $message = $notification->toMail($notifiable);

        if ($message === null) {
            return null;
        }

        if (is_array($message)) {
            $recipients = [];
            $address = (string) ($notifiable['routes']['mail'] ?? $notifiable['email'] ?? '');

            if ($address !== '') {
                $recipients[] = ['address' => $address, 'name' => $notifiable['name'] ?? null];
            }

            $message = new ArrayMailable([
                'to' => $message['to'] ?? $recipients,
                'cc' => $message['cc'] ?? [],
                'bcc' => $message['bcc'] ?? [],
                'reply_to' => $message['reply_to'] ?? null,
                'subject' => $message['subject'] ?? $notification->type(),
                'html' => $message['html'] ?? null,
                'text' => $message['text'] ?? null,
            ]);
        }

        return [
            'channel' => $this->name(),
            'sent' => $this->mailManager->send($message),
            'notification' => $notification->type(),
        ];
    }
}
