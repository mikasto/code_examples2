<?php

namespace NW\WebService\References\Operations\Notification;

interface DataMapperInterface
{
    /**
     * @throws \Exception
     */
    public function getData(): OperationDataDTO;
}
