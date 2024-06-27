<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class MessengerResponce extends DataDTO
{
    public bool $isSent;
    public string $error;
}
