<?php

namespace Lukaskolista\Inbox;

interface MessageDispatcher
{
    public function dispatch(Message $message): void;
}
