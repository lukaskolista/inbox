<?php

namespace Lukaskolista\Inbox\MessageMapper;

use Lukaskolista\Inbox\MessageMapper;
use Lukaskolista\Inbox\MessageMapper\Attribute\DataToMessage;
use Lukaskolista\Inbox\MessageMapper\Attribute\MessageToData;
use Lukaskolista\Inbox\MessageMapper\Attribute\Type;

final readonly class AttributeMessageMapper implements MessageMapper
{
    private array $map;

    public function __construct(array $messageClasses)
    {
        $map = [];

        foreach ($messageClasses as $messageClass) {
            $reflectionClass = new \ReflectionClass($messageClass);
            $attributes = $reflectionClass->getAttributes(Type::class);

            if (!isset($attributes[0])) {
                throw new \RuntimeException(sprintf(
                    'Missing %s attribute in %s',
                    Type::class,
                    $messageClass
                ));
            }

            $map[$messageClass] = $attributes[0]->newInstance()->value;
        }

        $this->map = $map;
    }

    public function mapDataToMessage(object $data): object
    {
        $typesToClasses = array_flip($this->map);

        if (!isset($typesToClasses[$data->type])) {
            throw new \InvalidArgumentException();
        }

        $messageClass = $typesToClasses[$data->type];
        $reflectionClass = new \ReflectionClass($messageClass);

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $attributes = $reflectionMethod->getAttributes(DataToMessage::class);

            if (isset($attributes[0])) {
                if (!$reflectionMethod->isStatic() || !$reflectionMethod->isPublic()) {
                    throw new \RuntimeException();
                }

                return call_user_func([$messageClass, $reflectionMethod->getName()], $data->data);
            }
        }

        throw new \RuntimeException();
    }

    public function mapMessageToData(object $message): object
    {
        if (!isset($this->map[$message::class])) {
            throw new \InvalidArgumentException();
        }

        $reflectionClass = new \ReflectionClass($message::class);

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $attributes = $reflectionMethod->getAttributes(MessageToData::class);

            if (isset($attributes[0])) {
                if ($reflectionMethod->isStatic() || !$reflectionMethod->isPublic()) {
                    throw new \RuntimeException();
                }

                return (object) [
                    'type' => $this->map[$message::class],
                    'data' => $reflectionMethod->invoke($message)
                ];
            }
        }

        throw new \RuntimeException();
    }
}
