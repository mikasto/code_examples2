<?php

namespace NW\WebService\References\Operations\Notification;

interface NotifierInterface
{
    public function notifyEmployeeByMail(string $event_type): bool;

    public function notifyClientByMail(): bool;

    public function notifyClientByMessenger(): MessengerResponce;
}
