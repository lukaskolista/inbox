<?php

namespace Lukaskolista\Inbox;

interface ExistingMessagePolicy
{
    public function handle(string $id, object $currentMessage, object $newMessage): void;
}
