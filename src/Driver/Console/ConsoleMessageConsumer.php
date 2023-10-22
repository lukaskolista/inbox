<?php

namespace Lukaskolista\Inbox\Driver\Console;

use Lukaskolista\Inbox\Inbox\MessageHandler;
use Lukaskolista\Inbox\MessageRepository;

final readonly class ConsoleMessageConsumer
{
    public function __construct(
        private MessageRepository $messageRepository,
        private MessageHandler $messageHandler
    ) {}

    public function consume(int $messagesLimit, int $attemptsLimit): void
    {
        $messages = $this->messageRepository->findForConsume($messagesLimit, $attemptsLimit);

        foreach ($messages as $message) {
            $this->messageHandler->handle($message);
        }
    }
}
