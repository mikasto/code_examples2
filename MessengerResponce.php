<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class MessengerResponce extends DTO
{
    public bool $isSent;
    public string $error;
}
