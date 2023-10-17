<?php

namespace Lukaskolista\Inbox;

interface MessageConsumer
{
    public function consume(): void;
}
