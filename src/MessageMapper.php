<?php

namespace Lukaskolista\Inbox;

interface MessageMapper
{
    public function mapDataToMessage(object $data): object;

    public function mapMessageToData(object $message): object;
}
