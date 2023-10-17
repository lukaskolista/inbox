<?php

namespace Lukaskolista\Inbox;

interface MessageRepository
{
    public function save(Message $message): void;

    public function clearConsumedMessagesAndLeaveNewest(int $newestMessagesToLeave): void;

    public function clearConsumedMessagesOlderThanOrEqual(\DateTimeInterface $dateTime): void;

    public function find(string $id): ?Message;

    /**
     * @return iterable<Message>
     */
    public function findForConsume(int $messagesLimit, int $attemptsLimit): iterable;
}
