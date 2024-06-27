<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationData extends DataDTO
{
    public OperationRequest $request;
    public OperationContractors $contractors;
    public string $differences;
}
