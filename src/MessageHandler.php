<?php

namespace Lukaskolista\Inbox;

interface MessageHandler
{
    public function handle(object $message): void;
}
