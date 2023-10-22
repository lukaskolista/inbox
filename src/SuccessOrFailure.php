<?php

namespace Lukaskolista\Inbox;

interface SuccessOrFailure
{
    public function on(?callable $success = null, ?callable $failure = null): mixed;
}
