<?php

namespace Lukaskolista\Inbox\Inbox;

use Lukaskolista\Inbox\Message;
use Lukaskolista\Inbox\MessageHandler as ApplicationMessageHandler;
use Lukaskolista\Inbox\MessageRepository;
use Lukaskolista\Inbox\SuccessOrFailure;
use Lukaskolista\Inbox\SuccessOrFailure\Failure;
use Lukaskolista\Inbox\SuccessOrFailure\Success;

final readonly class MessageHandler
{
    public function __construct(
        private ApplicationMessageHandler $messageHandler,
        private MessageRepository $messageRepository
    ) {}

    public function handle(Message $message): SuccessOrFailure
    {
        if ($message->isConsumed()) {
            return new Success($message);
        }

        try {
            $this->messageHandler->handle($message->getPayload());
            $message->markAsConsumed();

            $result = new Success($message);
        } catch (\Throwable) {
            $result = new Failure($message);
        } finally {
            $message->incrementAttemptsCounter();
        }

        $this->messageRepository->save($message);

        return $result;
    }
}
