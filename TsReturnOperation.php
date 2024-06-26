<?php
/**
 * Класс анализирует входящие данные из глобального массива $_REQUEST,
 * валидирует их, выясняет тип операции, дополняет данные для уведомлений
 * и выполняет уведомления. Результат уведомлений фиксируется в заданной структуре
 * и возвращается в основном рабочем методе doOperation()
 *
 * Рефакторинг:
 * Меняю название файла по имени класса
 * Меняю тип возвращаемых данных для function doOperation() согласно абстрактному классу
 * Не найдены свойства email, mobile в классе Contractor - нужно исправить, но это за пределами класса для рефакторинга.
 * Считаю что задача по классу Contractor переходит ответственному лицу
 * Декомпозирую крупные методы под SRP (SOLID)
 * Уменьшаю цикломатическую сложность кода до 1-2 уровней
 * Добавляю базовый DTO класс для возможности рекурсивного копирования массива в свойства объекта
 * Любые параметры более 2х на метод транспортирую через DTO
 * Валидация типов происходит при заполнении DTO. Исключения необходимо будет поймать выше по стеку.
 * Уникальные для данного класса текстовые поля перевожу в константы
 * Разделяю операции преобразования и валидацию на отдельные методы для удобочитаемости и SRP
 * Расставляю доступы к методам и свойствам для реализации принципа открытости/закрытости (SOLID)
 * Преобразую условные выражения в простейшие и удобочитаемые
 * Располагаю методы класса в порядке использования
 * Удаляю очевидные комментарии
 * Переименование переменных в полные слова согласно их сути и текущему контексту
 * Удалены избыточные условия
 * Заменены ненадежные условия
 * Добавляю PhpDoc для методов с исключениями
 * Форматирование максимально близко к PSR12
 * Внешние данные для вывода в уведомлениях фильтруются по HTML тегам
 *
 * TODO:
 * вынести интерфейсы,
 * создать инверсию зависимости через конструктор вместо обращения к глобальному массиву
 * выполнять получение и возврат данных только посредством DTO (исключить массивы)
 * вынести общие методы в абстрактный класс
 * перенести оставшиеся строковые значения в константы на базе использующих их классов
 * максимально детерминировать формат обмена данными с классами уведомлений
 * изменить принцип именования классов на более говорящий и единый с абстрактным классом
 * нормализовать формат неймспейсов согласно PSR12
 * изменить названия методов и переменных к более говорящим о их сути
 * уменьшить размер класса за счет выноса методов или классов
 */

namespace NW\WebService\References\Operations\Notification;

final class TsReturnOperation extends ReferencesOperation
{
    public const EVENT = 'tsGoodsReturn';
    public const TYPE_NEW = 1;
    public const TYPE_CHANGE = 2;
    private array $data_array;
    private OperationData $operationData;
    private array $result = [
        'notificationEmployeeByEmail' => false,
        'notificationClientByEmail' => false,
        'notificationClientBySms' => [
            'isSent' => false,
            'message' => '',
        ],
    ];
    private Contractor $client;
    private Contractor $creator;
    private Contractor $expert;
    private string $clientFullName;
    private string $differences;
    private array $notifyTemplateData;
    private string $emailFrom;

    /**
     * @throws \Exception
     */
    public function doOperation(): array
    {
        $this->loadOperationData();
        $this->loadContractors();
        $this->loadDifferences();
        $this->loadNotifyData();
        $this->notifyEmployeeAndSetResult();
        $this->notifyClientAndSetResult();

        return $this->result;
    }

    /**
     * @throws \Exception
     */
    public function loadOperationData()
    {
        $this->data_array = $this->getRequest('data');
        $this->validateDataArray();
        $this->fillDtoByArray(obj: $this->operationData, values: $this->data_array);
        $this->validateOperationData();
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function validateDataArray(): void
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
            $this->result['notificationClientBySms']['message'] = 'Empty resellerId';
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

    /**
     * @throws \Exception
     */
    private function loadContractors()
    {
        $this->loadClient();
        $this->loadCreator();
        $this->loadExpert();
    }

    /**
     * @throws \Exception
     */
    private function loadClient()
    {
        $this->client = Contractor::getById($this->operationData->clientId);
        $this->validateClient();
        $this->loadClientFullName();
    }

    /**
     * @throws \Exception
     */
    private function validateClient()
    {
        if (is_null($this->client)) {
            throw new \Exception('client not found!', 400);
        }
        if ($this->client->type !== Contractor::TYPE_CUSTOMER) {
            throw new \Exception('bad client type', 400);
        }
        if ($this->client->Seller->id !== $this->operationData->resellerId) {
            throw new \Exception('client and reseller can not be equals', 400);
        }
    }

    private function loadClientFullName()
    {
        $this->clientFullName = $this->client->getFullName();
        if (empty($this->clientFullName)) {
            $this->clientFullName = $this->client->name;
        }
    }

    /**
     * @throws \Exception
     */
    private function loadCreator()
    {
        $this->creator = Employee::getById($this->operationData->creatorId);
        $this->validateCreator();
    }

    /**
     * @throws \Exception
     */
    private function validateCreator()
    {
        if (is_null($this->creator)) {
            throw new \Exception('Creator not found!', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function loadExpert()
    {
        $this->expert = Employee::getById($this->operationData->expertId);
        $this->validateExpert();
    }

    /**
     * @throws \Exception
     */
    private function validateExpert()
    {
        if (is_null($this->expert)) {
            throw new \Exception('Expert not found!', 400);
        }
    }

    /**
     * @throws \Exception
     */
    private function loadDifferences()
    {
        $this->validateDifferences();
        if ($this->operationData->notificationType === self::TYPE_NEW) {
            $this->differences = __('NewPositionAdded', null, $this->operationData->resellerId);
        } else {
            $differences = [
                'FROM' => Status::getName($this->operationData->differences->from),
                'TO' => Status::getName($this->operationData->differences->to),
            ];
            $this->differences = __('PositionStatusHasChanged', $differences, $this->operationData->resellerId);
        }
    }

    /**
     * @throws \Exception
     */
    private function validateDifferences()
    {
        if ($this->operationData->notificationType === self::TYPE_NEW) {
            return;
        }
        if (is_null($this->operationData->differences)) {
            throw new \Exception('notification type not valid with empty differences');
        }
        if ($this->operationData->notificationType !== self::TYPE_CHANGE) {
            throw new \Exception('notification type not valid for differences');
        }
    }

    /**
     * @throws \Exception
     */
    private function loadNotifyData()
    {
        $this->notifyTemplateData = [
            'COMPLAINT_ID' => $this->operationData->complaintId,
            'COMPLAINT_NUMBER' => $this->operationData->complaintNumber,
            'CREATOR_ID' => $this->operationData->creatorId,
            'CREATOR_NAME' => $this->creator->getFullName(),
            'EXPERT_ID' => $this->operationData->expertId,
            'EXPERT_NAME' => $this->expert->getFullName(),
            'CLIENT_ID' => $this->operationData->clientId,
            'CLIENT_NAME' => $this->clientFullName,
            'CONSUMPTION_ID' => $this->operationData->consumptionId,
            'CONSUMPTION_NUMBER' => $this->operationData->consumptionNumber,
            'AGREEMENT_NUMBER' => $this->operationData->agreementNumber,
            'DATE' => $this->operationData->date,
            'DIFFERENCES' => $this->differences,
        ];
        $this->validateNotifyTemplateData();
        $this->filterNotifyTemplateDataByHtmlTags();
        $this->emailFrom = getResellerEmailFrom($this->operationData->resellerId);
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

    private function notifyEmployeeAndSetResult()
    {
        $emails = getEmailsByPermit($this->operationData->resellerId, self::EVENT);
        if (empty($this->emailFrom) || !count($emails)) {
            return;
        }
        $resellerId = $this->operationData->resellerId;
        foreach ($emails as $email) {
            $message = [ // MessageTypes::EMAIL
                'emailFrom' => $this->emailFrom,
                'emailTo' => $email,
                'subject' => __('complaintEmployeeEmailSubject', $this->notifyTemplateData, $resellerId),
                'message' => __('complaintEmployeeEmailBody', $this->notifyTemplateData, $resellerId),
            ];
            MessagesClient::sendMessage(
                [$message],
                $resellerId,
                NotificationEvents::CHANGE_RETURN_STATUS
            );
        }
        $this->result['notificationEmployeeByEmail'] = true;
    }

    private function notifyClientAndSetResult()
    {
        if (($this->operationData->notificationType !== self::TYPE_CHANGE)
            || empty($this->operationData->differences)
            || empty($this->operationData->differences->to)
        ) {
            return;
        }
        if (!empty($this->emailFrom) && !empty($this->client->email)) {
            $this->sendEmailToClientAndSetResult();
        }
        if (!empty($this->client->mobile)) {
            $this->sendMessageToClientAndSetResult();
        }
    }

    private function sendEmailToClientAndSetResult()
    {
        $resellerId = $this->operationData->resellerId;
        $message = [ // MessageTypes::EMAIL
            'emailFrom' => $this->emailFrom,
            'emailTo' => $this->client->email,
            'subject' => __('complaintClientEmailSubject', $this->notifyTemplateData, $resellerId),
            'message' => __('complaintClientEmailBody', $this->notifyTemplateData, $resellerId),
        ];
        MessagesClient::sendMessage(
            [$message],
            $resellerId,
            $this->client->id,
            NotificationEvents::CHANGE_RETURN_STATUS,
            $this->operationData->differences->to
        );
        $this->result['notificationClientByEmail'] = true;
    }

    private function sendMessageToClientAndSetResult()
    {
        $error = '';
        $notifyResult = NotificationManager::send(
            $this->operationData->resellerId,
            $this->client->id,
            NotificationEvents::CHANGE_RETURN_STATUS,
            $this->operationData->differences->to,
            $this->notifyTemplateData,
            $error
        );
        if ($notifyResult) {
            $this->result['notificationClientBySms']['isSent'] = true;
        } elseif (!empty($error)) {
            $this->result['notificationClientBySms']['message'] = $error;
        }
    }
}