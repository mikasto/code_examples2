### Тестовое задание для разработчика на PHP
Мы ожидаем, что Вы:
* найдете и исправите все возможные ошибки (синтаксические, проектирования, безопасности и т.д.);
* отрефакторите код файла `ReturnOperation.php` в лучший по вашему мнению вид;
* напишите в комментарии краткое резюме по коду: назначение кода и его качество.

### Выполненная работа
Анализ: Класс ReturnOperation.php (TsReturnOperation.php) анализирует входящие данные из глобального массива $_REQUEST, валидирует их, выясняет тип операции, дополняет данные для уведомлений и выполняет уведомления. Результат уведомлений фиксируется в заданной структуре и возвращается в основном рабочем методе doOperation(). Обслуживается событие в Event Sourcing архитектуре

## Рефакторинг:
 * Меняю название файла по имени класса
 * Меняю тип возвращаемых данных для function doOperation() согласно абстрактному классу
 * Не найдены свойства email, mobile в классе Contractor - нужно исправить, но это за пределами
 * класса для рефакторинга.
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
 * Исключения перехватываются с отправляются клиенту в виде ошибки, операция всегда возвращает результат
 * Добавлен конструктор с $data_array для передачи поля data из $_REQUEST
 * Класс имеет слишком много ответственности и разделен на несколько классов
 * Выносим конфигурацию всех классов на более высокий уровень абстракции через DI
 * Извлек интерфейсы для конструкторов
 
## TODO:
 * выполнять получение и возврат данных только посредством DTO (исключить массивы)
 * перенести оставшиеся строковые значения в константы на базе использующих их классов
 * максимально детерминировать формат обмена данными с классами NotificationManager, MessagesClient
 * изменить принцип именования классов на более говорящий и единый с абстрактным классом
 * нормализовать формат неймспейсов согласно PSR12
 * изменить названия методов и переменных к более говорящим о их сути
 * уменьшить размер новых классов за счет выноса методов или классов по ответственности
 * расширить описание и источник ошибок в исключениях
 * фиксировать ошибку исключений в системный лог
 * модульные тесты
 * выяснить поведение MessagesClient при ошибке и обработать
 * функция __() осталась загадкой
   
