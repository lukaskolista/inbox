<?php

namespace Lukaskolista\Inbox\Tests;

use Faker\Factory;
use Lukaskolista\Inbox\ExistingMessagePolicy\ThrowExceptionPolicy;
use Lukaskolista\Inbox\Inbox;
use Lukaskolista\Inbox\MessageConsumer\SimpleMessageConsumer;
use Lukaskolista\Inbox\MessageHandler;
use Lukaskolista\Inbox\MessageRepository;
use Lukaskolista\Inbox\Storage\MongoDB\MongoDBMessageRepository as MongoDBMessageRepository;
use MongoDB\Client as MongoDBClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MongoInboxTest extends TestCase
{
    #[Test]
    public function putMessageWithUniqueIdSuccessfully(): void
    {
        $faker = Factory::create();
        $messageHandler = new class implements MessageHandler {
            private array $messages = [];

            public function handle(object $message): void
            {
                $this->messages[] = $message;
            }

            public function getMessages(): array
            {
                return $this->messages;
            }
        };
        $messageRepository = $this->createMongoDBMessageRepository();
        $simpleMessageConsumer = new SimpleMessageConsumer(
            $messageRepository,
            1,
            1,
            $messageHandler
        );
        $inbox = new Inbox(
            $messageRepository,
            null,
            new ThrowExceptionPolicy()
        );

        $name = $faker->name();
        $color = $faker->colorName();

        $inbox->put($faker->uuid(), (object) ['name' => $name, 'color' => $color]);

        $simpleMessageConsumer->consume();

        self::assertCount(1, $messageHandler->getMessages());
        self::assertEquals((object) ['name' => $name, 'color' => $color], $messageHandler->getMessages()[0]);
    }

    #[Test]
    public function putMessageWithNonUniqueIdFailed(): void
    {
        $faker = Factory::create();
        $messageId = $faker->uuid();

        $messageRepository = $this->createMongoDBMessageRepository();
        $simpleMessageConsumer = new SimpleMessageConsumer(
            $messageRepository,
            1,
            1,
            new class implements MessageHandler {
                public function handle(object $message): void {}
            }
        );
        $inbox = new Inbox(
            $messageRepository,
            null,
            new ThrowExceptionPolicy()
        );
        $inbox->put($messageId, new \stdClass());
        $simpleMessageConsumer->consume();

        $this->expectException(\Throwable::class);
        $inbox->put($messageId, new \stdClass());
    }

    private function createMongoDBMessageRepository(): MessageRepository
    {
        $mongoClient = new MongoDBClient('mongodb://root:root@127.0.0.1:27022');
        $collection = $mongoClient->selectCollection('inbox-test', 'messages');

        return new MongoDBMessageRepository($collection);
    }
}
