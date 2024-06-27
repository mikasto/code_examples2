<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationContractorsDTO extends DTO
{
    public Contractor $client;
    public Contractor $creator;
    public Contractor $expert;
}
