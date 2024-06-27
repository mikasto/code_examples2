<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationDataDTO extends DTO
{
    public OperationRequestDTO $request;
    public OperationContractorsDTO $contractors;
    public string $differences;
}
