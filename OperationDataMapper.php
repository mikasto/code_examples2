<?php

namespace NW\WebService\References\Operations\Notification;

final class OperationDataMapper
{
    private readonly OperationRequest $request;

    public function __construct(readonly array $data_array)
    {
    }

    /**
     * @throws \Exception
     */
    public function getData(): OperationData
    {
        $this->validateDataArray();

        $data = new OperationData();

        $this->request = new OperationRequest();
        $this->fillDtoByArray(obj: $this->request, values: $this->data_array);
        $this->validateRequest($this->request);
        $data->request = $this->request;

        $contractors = new OperationContractors();
        $contractors->client = $this->getClient();
        $contractors->creator = $this->getCreator();
        $contractors->expert = $this->getExpert();
        $data->contractors = $contractors;

        $data->differences = $this->getDifferences();

        return $data;
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
            if (is_scalar($value) || is_object($value)) {
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
    private function validateRequest(OperationRequest $request)
    {
        $this->validateReseller($request);
        $this->validateNotificationType($request);
    }

    /**
     * @throws \Exception
     */
    private function validateReseller(OperationRequest $request)
    {
        if (empty($request->resellerId)) {
            throw new \Exception('Empty resellerId', 400);
        }
        if (is_null(Seller::getById($request->resellerId))) {
            throw new \Exception('Seller not found!', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function validateNotificationType(OperationRequest $request)
    {
        if (empty($request->notificationType)) {
            throw new \Exception('Empty notificationType', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function getClient(): Contractor
    {
        $client = Contractor::getById($this->request->clientId);
        $this->validateClient($client);
        return $client;
    }

    /**
     * @throws \Exception
     */
    private function validateClient(?Contractor $client)
    {
        if (is_null($client)) {
            throw new \Exception('client not found!', 400);
        }
        if ($client->type !== Contractor::TYPE_CUSTOMER) {
            throw new \Exception('bad client type', 400);
        }
        if ($client->Seller->id !== $this->request->resellerId) {
            throw new \Exception('client and reseller can not be equals', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function getCreator(): Contractor
    {
        $creator = Employee::getById($this->request->creatorId);
        $this->validateCreator($creator);
        return $creator;
    }

    /**
     * @throws \Exception
     */
    private function validateCreator(Contractor $creator)
    {
        if (is_null($creator)) {
            throw new \Exception('Creator not found!', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function getExpert(): Contractor
    {
        $expert = Employee::getById($this->request->expertId);
        $this->validateExpert($expert);
        return $expert;
    }

    /**
     * @throws \Exception
     */
    private function validateExpert(Contractor $expert)
    {
        if (is_null($expert)) {
            throw new \Exception('Expert not found!', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function getDifferences(): string
    {
        $this->validateDataDifferences();
        if ($this->request->notificationType === OperationTypes::TYPE_NEW) {
            return __('NewPositionAdded', null, $this->request->resellerId);
        }
        $differences = [
            'FROM' => Status::getName($this->request->differences->from),
            'TO' => Status::getName($this->request->differences->to),
        ];
        return __('PositionStatusHasChanged', $differences, $this->request->resellerId);
    }

    /**
     * @throws \Exception
     */
    private function validateDataDifferences()
    {
        if ($this->request->notificationType === OperationTypes::TYPE_NEW) {
            return;
        }
        if (is_null($this->request->differences)) {
            throw new \Exception('notification type not valid with empty differences');
        }
        if ($this->request->notificationType !== OperationTypes::TYPE_CHANGE) {
            throw new \Exception('notification type not valid for differences');
        }
    }
}
