<?php

namespace NW\WebService\References\Operations\Notification;

final readonly class OperationRequestDTO extends AbstractDTO
{
        public int $resellerId;
        public int $notificationType;
        public int $clientId;
        public int $creatorId;
        public int $expertId;
        public int $complaintId;
        public string $complaintNumber;
        public int $consumptionId;
        public string $consumptionNumber;
        public string $agreementNumber;
        public string $date;
        public OperationDifferencesDTO $differences;
}
