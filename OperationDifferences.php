<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationDifferences extends DataDTO
{
    public function __construct(
        public int $from,
        public int $to,
    ) {
    }
}
