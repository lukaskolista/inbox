<?php

namespace Lukaskolista\Inbox;

final class Message
{
    public function __construct(
        private readonly string $id,
        private readonly object $payload,
        private readonly float $time,
        private bool $consumed,
        private int $attempts
    ) {}

    public static function new(string $id, object $payload): self
    {
        return new Message($id, $payload, microtime(true), false, 0);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPayload(): object
    {
        return $this->payload;
    }

    public function getTime(): float
    {
        return $this->time;
    }

    public function markAsConsumed(): void
    {
        $this->consumed = true;
    }

    public function isConsumed(): bool
    {
        return $this->consumed;
    }

    public function incrementAttemptsCounter(): void
    {
        $this->attempts++;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }
}
