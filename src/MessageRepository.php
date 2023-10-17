<?php

namespace Lukaskolista\Inbox;

interface MessageRepository
{
    public function save(Message $message): void;

    public function find(string $id): ?Message;

    /**
     * @return iterable<Message>
     */
    public function findForConsume(int $messagesLimit, int $attemptsLimit): iterable;
}
