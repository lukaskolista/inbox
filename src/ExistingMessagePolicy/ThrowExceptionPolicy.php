<?php

namespace Lukaskolista\Inbox\ExistingMessagePolicy;

use Lukaskolista\Inbox\ExistingMessagePolicy;

class ThrowExceptionPolicy implements ExistingMessagePolicy
{
    public function handle(string $id, object $currentMessage, object $newMessage): void
    {
        throw new \Exception();
    }
}
