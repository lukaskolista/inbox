<?php

namespace Lukaskolista\Inbox;

class Inbox
{
    public function __construct(
        private MessageRepository $messageRepository,
        private ?MessageDispatcher $messageDispatcher,
        private ExistingMessagePolicy $existingMessagePolicy,
        private int $attemptsLimit
    ) {}

    public function put(string $id, object $message): void
    {
        $currentMessage = $this->messageRepository->find($id);

        if ($currentMessage !== null) {
            $this->existingMessagePolicy->handle($id, $currentMessage->getPayload(), $message);

            return;
        }

        $wrappedMessage = Message::new($id, $message, $this->attemptsLimit);

        $this->messageRepository->save($wrappedMessage);
        $this->messageDispatcher?->dispatch($wrappedMessage);
    }
}
