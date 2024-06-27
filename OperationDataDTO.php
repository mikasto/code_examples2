<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationDataDTO extends AbstractDTO
{
    public OperationRequestDTO $request;
    public OperationContractorsDTO $contractors;
    public string $differences;
}
