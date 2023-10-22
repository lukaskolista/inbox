<?php

namespace Lukaskolista\Inbox\Tests;

use Lukaskolista\Inbox\MessageMapper\Attribute\DataToMessage;
use Lukaskolista\Inbox\MessageMapper\Attribute\MessageToData;
use Lukaskolista\Inbox\MessageMapper\Attribute\Type;

#[Type('test')]
final readonly class TestMessage
{
    public function __construct(public string $name, public string $color) {}

    #[DataToMessage]
    public static function dataToMessage(object $data): self
    {
        return new self($data['name'], $data['color']);
    }

    #[MessageToData]
    public function messageToData(): object
    {
        return (object) [
            'name' => $this->name,
            'color' => $this->color
        ];
    }
}
