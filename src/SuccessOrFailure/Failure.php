<?php

namespace Lukaskolista\Inbox\SuccessOrFailure;

use Lukaskolista\Inbox\SuccessOrFailure;

final readonly class Failure implements SuccessOrFailure
{
    private array $arguments;

    public function __construct()
    {
        $this->arguments = func_get_args();
    }

    public function on(?callable $success = null, ?callable $failure = null): mixed
    {
        return $failure !== null ? $failure(...$this->arguments) : new Nothing();
    }

    public function toBool(): bool
    {
        return false;
    }
}
