<?php

namespace Lukaskolista\Inbox\MessageConsumer;

use Lukaskolista\Inbox\MessageConsumer;
use Lukaskolista\Inbox\MessageHandler;
use Lukaskolista\Inbox\MessageRepository;

class SimpleMessageConsumer implements MessageConsumer
{
    public function __construct(
        private MessageRepository $messageRepository,
        private int $messagesLimit,
        private int $attemptsLimit,
        private MessageHandler $messageHandler
    ) {}

    public function consume(): void
    {
        $messages = $this->messageRepository->findForConsume($this->messagesLimit, $this->attemptsLimit);

        foreach ($messages as $message) {
            try {
                $this->messageHandler->handle($message->getPayload());
                $message->markAsConsumed();
            } catch (\Throwable) {
            } finally {
                $message->incrementAttemptsCounter();
            }

            $this->messageRepository->save($message);
        }
    }
}
