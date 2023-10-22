<?php

namespace Lukaskolista\Inbox\Driver\Amqp;

use Lukaskolista\Inbox\Inbox\MessageHandler;
use Lukaskolista\Inbox\Message;
use Lukaskolista\Inbox\MessageRepository;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

final class AmqpMessageConsumer
{
    private bool $stop = false;

    public function __construct(
        private readonly AMQPChannel $channel,
        private readonly MessageRepository $messageRepository,
        private readonly MessageHandler $messageHandler
    ) {}

    public function consume(): void
    {
        $this->channel->basic_consume(callback: function (AMQPMessage $amqpMessage): void {
            $message = $this->messageRepository->find($amqpMessage->getBody());

            $this->messageHandler
                ->handle($message)
                ->on(
                    success: function () use ($amqpMessage): void {
                        $amqpMessage->ack();
                    },
                    failure: function (Message $message) use ($amqpMessage): void {
                        if (!$message->shouldRepeat()) {
                            $amqpMessage->ack();

                            return;
                        }

                        $amqpMessage->nack();
                    }
                );
        });

        $this->channel->consume();
    }
}
