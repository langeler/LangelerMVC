<?php

declare(strict_types=1);

namespace App\Abstracts\Support;

use App\Utilities\Traits\ArrayTrait;

abstract class Mailable
{
    use ArrayTrait;

    /**
     * @var list<array{address:string,name:?string}>
     */
    protected array $to = [];

    /**
     * @var list<array{address:string,name:?string}>
     */
    protected array $cc = [];

    /**
     * @var list<array{address:string,name:?string}>
     */
    protected array $bcc = [];

    /**
     * @var array{address:string,name:?string}|null
     */
    protected ?array $replyTo = null;

    protected string $subject = '';
    protected ?string $htmlBody = null;
    protected ?string $textBody = null;
    private bool $built = false;

    abstract protected function build(): void;

    public function to(string $address, ?string $name = null): static
    {
        $this->to[] = ['address' => $address, 'name' => $name];

        return $this;
    }

    public function cc(string $address, ?string $name = null): static
    {
        $this->cc[] = ['address' => $address, 'name' => $name];

        return $this;
    }

    public function bcc(string $address, ?string $name = null): static
    {
        $this->bcc[] = ['address' => $address, 'name' => $name];

        return $this;
    }

    public function replyTo(string $address, ?string $name = null): static
    {
        $this->replyTo = ['address' => $address, 'name' => $name];

        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function html(string $html): static
    {
        $this->htmlBody = $html;

        return $this;
    }

    public function text(string $text): static
    {
        $this->textBody = $text;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function message(): array
    {
        if (!$this->built) {
            $this->build();
            $this->built = true;
        }

        return [
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->replyTo,
            'subject' => $this->subject,
            'html' => $this->htmlBody,
            'text' => $this->textBody,
        ];
    }
}
