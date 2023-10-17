<?php

namespace Lukaskolista\Inbox\MessageConsumer;

use Lukaskolista\Inbox\MessageHandler;
use Lukaskolista\Inbox\MessageRepository;

class SimpleMessageConsumer
{
    public function __construct(
        private MessageRepository $messageRepository,
        private MessageHandler $messageHandler
    ) {}

    public function consume(int $messagesLimit, int $attemptsLimit): void
    {
        $messages = $this->messageRepository->findForConsume($messagesLimit, $attemptsLimit);

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
