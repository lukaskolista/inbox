<?php

namespace Lukaskolista\Inbox\Driver\Amqp;

use Lukaskolista\Inbox\Message;
use Lukaskolista\Inbox\MessageDispatcher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class AmqpMessageDispatcher implements MessageDispatcher
{
    public function __construct(private AMQPChannel $channel, private string $routingKey) {}

    public function dispatch(Message $message): void
    {
        $this->channel->basic_publish(new AMQPMessage($message->getId()), routing_key: $this->routingKey);
    }
}
