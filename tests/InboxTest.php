<?php

namespace Lukaskolista\Inbox\Tests;

use Faker\Factory;
use Lukaskolista\Inbox\Driver\Amqp\AmqpMessageConsumer;
use Lukaskolista\Inbox\Driver\Amqp\AmqpMessageDispatcher;
use Lukaskolista\Inbox\ExistingMessagePolicy\ThrowExceptionPolicy;
use Lukaskolista\Inbox\Inbox;
use Lukaskolista\Inbox\Driver\Console\ConsoleMessageConsumer;
use Lukaskolista\Inbox\Inbox\MessageHandler;
use Lukaskolista\Inbox\MessageHandler as CustomMessageHandler;
use Lukaskolista\Inbox\MessageRepository;
use Lukaskolista\Inbox\Storage\Mongo\MongoMessageRepository;
use MongoDB\Client as MongoDBClient;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MongoInboxTest extends TestCase
{
    #[Test]
    public function putMessageWithUniqueIdOnConsoleDriverSuccessfully(): void
    {
        $faker = Factory::create();
        $messageHandler = new class implements CustomMessageHandler {
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
        $consoleMessageConsumer = new ConsoleMessageConsumer(
            $messageRepository,
            new MessageHandler($messageHandler, $messageRepository)
        );
        $inbox = new Inbox(
            $messageRepository,
            null,
            new ThrowExceptionPolicy(),
            1
        );

        $name = $faker->name();
        $color = $faker->colorName();

        $inbox->put($faker->uuid(), (object) ['name' => $name, 'color' => $color]);

        $consoleMessageConsumer->consume(1, 1);

        self::assertCount(1, $messageHandler->getMessages());
        self::assertEquals((object) ['name' => $name, 'color' => $color], $messageHandler->getMessages()[0]);
    }

    #[Test]
    public function putMessageWithUniqueIdOnAmqpDriverSuccessfully(): void
    {
        $faker = Factory::create();
        $channel = $this->createRabbitMQChannel();
        $messageHandler = new class ($channel) implements CustomMessageHandler {
            public function __construct(private AMQPChannel $channel) {}

            private array $messages = [];

            public function handle(object $message): void
            {
                $this->messages[] = $message;
                $this->channel->stopConsume();
            }

            public function getMessages(): array
            {
                return $this->messages;
            }
        };
        $messageRepository = $this->createMongoDBMessageRepository();
        $amqpMessageConsumer = new AmqpMessageConsumer(
            $channel,
            $messageRepository,
            new MessageHandler($messageHandler, $messageRepository)
        );
        $inbox = new Inbox(
            $messageRepository,
            new AmqpMessageDispatcher($channel, 'test'),
            new ThrowExceptionPolicy(),
            1
        );

        $name = $faker->name();
        $color = $faker->colorName();

        $inbox->put($faker->uuid(), (object) ['name' => $name, 'color' => $color]);

        $amqpMessageConsumer->consume();

        self::assertCount(1, $messageHandler->getMessages());
        self::assertEquals((object) ['name' => $name, 'color' => $color], $messageHandler->getMessages()[0]);
    }

    #[Test]
    public function putMessageWithNonUniqueIdFailed(): void
    {
        $faker = Factory::create();
        $messageId = $faker->uuid();

        $messageRepository = $this->createMongoDBMessageRepository();
        $consoleMessageConsumer = new ConsoleMessageConsumer(
            $messageRepository,
            new MessageHandler(
                new class implements CustomMessageHandler {
                    public function handle(object $message): void {}
                },
                $messageRepository
            )
        );
        $inbox = new Inbox(
            $messageRepository,
            null,
            new ThrowExceptionPolicy(),
            1
        );
        $inbox->put($messageId, new \stdClass());
        $consoleMessageConsumer->consume(1, 1);

        $this->expectException(\Throwable::class);
        $inbox->put($messageId, new \stdClass());
    }

    private function createMongoDBMessageRepository(): MessageRepository
    {
        $mongoClient = new MongoDBClient('mongodb://root:root@127.0.0.1:27022');
        $collection = $mongoClient->selectCollection('inbox-test', 'messages');

        return new MongoMessageRepository($collection);
    }

    private function createRabbitMQChannel(): AMQPChannel
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

        $channel = $connection->channel();
        $channel->queue_declare('test');

        return $channel;
    }
}
