<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class MessengerResponce extends AbstractDTO
{
    public bool $isSent;
    public string $error;
}
