<?php

namespace NW\WebService\References\Operations\Notification;

final class OperationDataMapper
{
    private readonly OperationData $operationData;

    public function __construct(readonly array $data_array)
    {
    }

    public function getData(): OperationData
    {
        $this->validateDataArray();
        $this->fillDtoByArray(obj: $this->operationData, values: $this->data_array);
        $this->validateOperationData();
        return $this->operationData;
    }

    /**
     * @throws \Exception
     */
    private function validateDataArray()
    {
        if (!is_array($this->data_array)) {
            throw new \Exception('Request data is not valid array type', 400);
        }
    }

    private function fillDtoByArray(DataDTO $obj, array $values)
    {
        foreach ($values as $key => $value) {
            if (!property_exists($obj, $key)) {
                continue;
            }
            if (is_scalar($value)) {
                $obj->{$key} = $value;
            }
            if (is_array($value)) {
                $this->fillDtoByArray($obj->{$key}, $value);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function validateOperationData()
    {
        $this->validateReseller();
        $this->validateNotificationType();
    }

    /**
     * @throws \Exception
     */
    private function validateReseller()
    {
        if (empty($this->operationData->resellerId)) {
            throw new \Exception('Empty resellerId', 400);
        }
        if (is_null(Seller::getById($this->operationData->resellerId))) {
            throw new \Exception('Seller not found!', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function validateNotificationType()
    {
        if (empty($this->operationData->notificationType)) {
            throw new \Exception('Empty notificationType', 400);
        }
    }
}
