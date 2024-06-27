<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationContractors extends DataDTO
{
    public Contractor $client;
    public Contractor $creator;
    public Contractor $expert;
}
