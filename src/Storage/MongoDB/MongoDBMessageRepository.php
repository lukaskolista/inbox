<?php

namespace Lukaskolista\Inbox\Storage\MongoDB;

use Lukaskolista\Inbox\Message;
use Lukaskolista\Inbox\MessageRepository;
use MongoDB\Collection;

class MongoDBMessageRepository implements MessageRepository
{
    public function __construct(private Collection $collection) {}

    public function save(Message $message): void
    {
        $this->collection->updateOne(
            ['_id' => $message->getId()],
            [
                '$set' => [
                    'payload' => $message->getPayload(),
                    'time' => $message->getTime(),
                    'consumed' => $message->isConsumed(),
                    'attempts' => $message->getAttempts()
                ],
                '$setOnInsert' => [
                    '_id' => $message->getId()
                ]
            ],
            ['upsert' => true]
        );
    }

    public function find(string $id): ?Message
    {
        $document = $this->collection->findOne(['_id' => $id]);

        return $document !== null ? $this->createMessage($document) : null;
    }

    public function findForConsume(int $messagesLimit, int $attemptsLimit): iterable
    {
        $documents = $this->collection->find(
            ['consumed' => false, 'attempts' => ['$lte' => $attemptsLimit]],
            ['sort' => ['time' => 1], 'limit' => $messagesLimit]
        );

        foreach ($documents as $document) {
            yield $this->createMessage($document);
        }
    }

    private function createMessage(object $document): Message
    {
        return new Message(
            $document->_id,
            $document->payload->jsonSerialize(),
            $document->time,
            $document->consumed,
            $document->attempts
        );
    }
}