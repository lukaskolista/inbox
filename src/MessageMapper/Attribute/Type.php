<?php

namespace Lukaskolista\Inbox\MessageMapper\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Type
{
    public function __construct(public string $value)
    {
        if (strlen($this->value) === 0) {
            throw new \InvalidArgumentException();
        }
    }
}
