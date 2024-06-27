<?php

namespace NW\WebService\References\Operations\Notification;

final class OperationNotifier implements NotifierInterface
{
    private OperationDataDTO $data;
    private array $notifyTemplateData;

    public function __construct(private readonly DataMapperInterface $dataMapper)
    {
        $this->data = $this->dataMapper->getData();
        $this->setNotifyTemplateData();
    }

    /**
     * @throws \Exception
     */
    private function setNotifyTemplateData()
    {
        $this->notifyTemplateData = [
            'COMPLAINT_ID' => $this->data->request->complaintId,
            'COMPLAINT_NUMBER' => $this->data->request->complaintNumber,
            'CREATOR_ID' => $this->data->request->creatorId,
            'CREATOR_NAME' => $this->data->contractors->creator->getFullName(),
            'EXPERT_ID' => $this->data->request->expertId,
            'EXPERT_NAME' => $this->data->contractors->expert->getFullName(),
            'CLIENT_ID' => $this->data->request->clientId,
            'CLIENT_NAME' => $this->data->contractors->client->getFullName() ?: $this->data->contractors->client->name,
            'CONSUMPTION_ID' => $this->data->request->consumptionId,
            'CONSUMPTION_NUMBER' => $this->data->request->consumptionNumber,
            'AGREEMENT_NUMBER' => $this->data->request->agreementNumber,
            'DATE' => $this->data->request->date,
            'DIFFERENCES' => $this->data->differences,
        ];
        $this->validateNotifyTemplateData();
        $this->filterNotifyTemplateDataByHtmlTags();
    }

    /**
     * @throws \Exception
     */
    private function validateNotifyTemplateData()
    {
        foreach ($this->notifyTemplateData as $key => $value) {
            if (empty($value)) {
                throw new \Exception("Notify Template Data ({$key}) is empty!", 500);
            }
        }
    }

    private function filterNotifyTemplateDataByHtmlTags()
    {
        foreach ($this->notifyTemplateData as $key => $value) {
            if (gettype($value) === 'string') {
                $this->notifyTemplateData[$key] = strip_tags($value);
            }
        }
    }

    private function canNotifyClient(): bool
    {
        if (($this->data->request->notificationType !== OperationTypes::TYPE_CHANGE)
            || empty($this->data->request->differences)
            || empty($this->data->request->differences->to)
        ) {
            return false;
        }
        return true;
    }

    public function notifyEmployeeByMail(string $event_type): bool
    {
        $resellerId = $this->data->request->resellerId;
        $emails = getEmailsByPermit($resellerId, $event_type);
        $emailFrom = getResellerEmailFrom($resellerId);
        if (empty($emailFrom) || !count($emails)) {
            return false;
        }

        foreach ($emails as $email) {
            $message = [ // MessageTypes::EMAIL
                'emailFrom' => $emailFrom,
                'emailTo' => $email,
                'subject' => __(
                    'complaintEmployeeEmailSubject',
                    $this->notifyTemplateData,
                    $resellerId
                ),
                'message' => __(
                    'complaintEmployeeEmailBody',
                    $this->notifyTemplateData,
                    $resellerId
                ),
            ];
            MessagesClient::sendMessage(
                [$message],
                $resellerId,
                NotificationEvents::CHANGE_RETURN_STATUS
            );
        }
        return true;
    }

    public function notifyClientByMail(): bool
    {
        if (!$this->canNotifyClient()) {
            return false;
        }
        if (empty($this->emailFrom) || empty($this->data->contractors->client->email)) {
            return false;
        }
        $resellerId = $this->data->request->resellerId;
        $message = [ // MessageTypes::EMAIL
            'emailFrom' => $this->emailFrom,
            'emailTo' => $this->data->contractors->client->email,
            'subject' => __('complaintClientEmailSubject', $this->notifyTemplateData, $resellerId),
            'message' => __('complaintClientEmailBody', $this->notifyTemplateData, $resellerId),
        ];
        MessagesClient::sendMessage(
            [$message],
            $resellerId,
            $this->data->contractors->client->id,
            NotificationEvents::CHANGE_RETURN_STATUS,
            $this->data->request->differences->to
        );
        return true;
    }

    public function notifyClientByMessenger(): MessengerResponce
    {
        $responce = new MessengerResponce();
        if (!$this->canNotifyClient() || empty($this->data->contractors->client->mobile)) {
            $responce->isSent = false;
            $responce->error = '';
            return $responce;
        }

        $error = '';
        $responce->isSent = NotificationManager::send(
            $this->data->request->resellerId,
            $this->data->contractors->client->id,
            NotificationEvents::CHANGE_RETURN_STATUS,
            $this->data->request->differences->to,
            $this->notifyTemplateData,
            $error
        );
        $responce->error = $error;
        return $responce;
    }
}
