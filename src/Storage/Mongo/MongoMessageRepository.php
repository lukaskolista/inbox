<?php

namespace Lukaskolista\Inbox\Storage\Mongo;

use Lukaskolista\Inbox\Message;
use Lukaskolista\Inbox\MessageMapper;
use Lukaskolista\Inbox\MessageRepository;
use MongoDB\Collection;

class MongoMessageRepository implements MessageRepository
{
    public function __construct(private Collection $collection, private MessageMapper $messageMapper) {}

    public function save(Message $message): void
    {
        $this->collection->updateOne(
            ['_id' => $message->getId()],
            [
                '$set' => [
                    'payload' => $this->messageMapper->mapMessageToData($message->getPayload()),
                    'time' => $message->getTime(),
                    'consumed' => $message->isConsumed(),
                    'attemptsLimit' => $message->getAttemptsLimit(),
                    'attempts' => $message->getAttempts()
                ],
                '$setOnInsert' => [
                    '_id' => $message->getId()
                ]
            ],
            ['upsert' => true]
        );
    }

    public function clearConsumedMessagesAndLeaveNewest(int $newestMessagesToLeave): void
    {
        $document = $this->collection->findOne(
            ['consumed' => true],
            ['sort' => ['time' => -1], 'skip' => $newestMessagesToLeave]
        );

        $this->collection->deleteMany(['consumed' => true, 'time' => ['$lte' => $document->time]]);
    }

    public function clearConsumedMessagesOlderThanOrEqual(\DateTimeInterface $dateTime): void
    {
        $this->collection->deleteMany(['consumed' => true, 'time' => ['$lte' => $dateTime->getTimestamp()]]);
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
            $this->messageMapper->mapDataToMessage($document->payload->jsonSerialize()),
            $document->time,
            $document->consumed,
            $document->attemptsLimit,
            $document->attempts
        );
    }
}
