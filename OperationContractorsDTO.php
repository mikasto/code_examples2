<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationContractorsDTO extends AbstractDTO
{
    public Contractor $client;
    public Contractor $creator;
    public Contractor $expert;
}
