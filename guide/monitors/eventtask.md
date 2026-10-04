# Эмуляция событий {#eventtask}

## Назначение {#eventtask-purpose}

Модуль `monitors` содержит Minion-задачу **`eventsInsert`**, которая
создаёт в БД СКУД поток тестовых событий с заданным интервалом.
Задача нужна для:

- отладки монитора без реальных контроллеров;
- проверки WebSocket-режима (события появляются мгновенно);
- проверки AJAX-режима (события появляются по таймеру);
- нагрузочного тестирования (много событий в секунду);
- демонстрации работы фильтра по группам устройств;
- проверки отображения конкретного сотрудника (`--people`) и
  конкретного устройства (`--id_dev`).

Задача пишет события в таблицу `events` через генератор
`GEN_EVENT_ID` и использует ту же структуру полей, что и реальные
контроллеры СКУД.

## Расположение {#eventtask-file}

Класс задачи:

    modules/monitors/classes/Task/eventsInsert.php

Имя задачи для Minion: `eventsInsert`.

Дополнительно в комплекте идёт BAT-файл для запуска в Windows:

    modules/monitors/classes/Task/test_insert_events.bat

## Требования {#eventtask-requirements}

- Включённый модуль **Minion** (`modules/minion`).
- Доступ к БД СКУД через подключение `fb`.
- Существующий генератор `GEN_EVENT_ID` в БД.
- Хотя бы одно активное устройство с `ID_READER = 0` — используется
  для авто-выбора `id_dev`, если параметр `--id_dev` не передан.
- Для параметра `--people` — ID сотрудника из таблицы `people`
  (`ID_PEP`). Если такого человека нет, монитор покажет пустое ФИО,
  но событие всё равно будет создано.

## Как запустить {#eventtask-run}

Из корня проекта:

    php modules/minion/minion --task=eventsInsert

С параметрами:

    php modules/minion/minion --task=eventsInsert --count=10 --delay=2

Только через BAT-файл (Windows):

    modules\monitors\classes\Task\test_insert_events.bat

Внутри BAT-файла зашита команда:

    c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion --task=eventsInsert

Если PHP установлен в другом каталоге — поправьте путь в BAT-файле
или вызывайте Minion напрямую.

## Параметры {#eventtask-params}

Все параметры необязательные, у каждого есть значение по умолчанию.

### `--count` {#eventtask-count}

Сколько событий создать за один запуск. Целое число.

- По умолчанию: **10**.
- Пример: `--count=100`.

### `--delay` {#eventtask-delay}

Интервал между вставками в секундах. Целое число.

- По умолчанию: **2**.
- `--delay=0` — вставлять без задержки (быстро, но создаёт нагрузку
  на БД и WebSocket-сервер).
- `--delay=5` — умеренный поток, удобно наблюдать в мониторе.

### `--type` {#eventtask-type}

ID типа события (`eventtype.ID_EVENTTYPE`). Целое число.

- По умолчанию: **50**.
- Определяет цвет и название события в мониторе.
- Полный список типов — в таблице `eventtype` БД СКУД.
- Попадает в колонку события `ID_EVENTTYPE`.

### `--people` {#eventtask-people}

**ID сотрудника** (`people.ID_PEP`). Целое число.

- По умолчанию: **22877**.
- Значение вставляется в поле `ESS1` события.
- Монитор делает `LEFT JOIN people p ON p.id_pep = e.ess1` и
  показывает ФИО и должность этого сотрудника в колонке
  «Сотрудник» и во всплывающем окне с фото.
- Если такого `ID_PEP` нет в `people`, в мониторе поле «Сотрудник»
  останется пустым, но событие всё равно создастся.

Пример: `--people=1` — использовать первого сотрудника,
`--people=22877` — конкретный ID.

### `--note` {#eventtask-note}

Текст заметки события. Строка.

- По умолчанию: **17 building test**.
- К каждой вставке добавляется ` #<номер>` — например,
  `17 building test #1`, `17 building test #2`, …
- Экранируется через `addslashes()` перед вставкой в SQL.
- Попадает в колонку события `NOTE`.

### `--id_dev` {#eventtask-id-dev}

**ID устройства** (`device.ID_DEV`), для которого создаются события.
Целое число.

- **Не задан** (`NULL`) — задача сама вызывает `getIdDev()` и берёт
  первое активное устройство с `ID_READER = 0`.
- **Задан** — используется ровно это значение, авто-выбор
  пропускается.
- Если `getIdDev()` не находит ни одного устройства и `--id_dev`
  не передан — задача завершается с ошибкой
  `ОШИБКА: не удалось определить id_dev.`

Пример: `--id_dev=797`.

## Примеры запуска {#eventtask-examples}

### 1. Одиночный запуск, 10 событий с интервалом 2 секунды {#eventtask-example-1}

    php modules/minion/minion --task=eventsInsert

### 2. Сто событий, интервал 1 секунда {#eventtask-example-2}

    php modules/minion/minion --task=eventsInsert --count=100 --delay=1

### 3. Быстрый поток без задержки — для теста WebSocket {#eventtask-example-3}

    php modules/minion/minion --task=eventsInsert --count=500 --delay=0

### 4. Конкретное устройство, конкретный тип события {#eventtask-example-4}

    php modules/minion/minion --task=eventsInsert --id_dev=797 --type=27 --count=20 --delay=1

### 5. Запуск с указанием сотрудника (`id_pep`) {#eventtask-example-people}

Эмулируем проходы конкретного человека — например, `ID_PEP = 1`:

    php modules/minion/minion --task=eventsInsert --people=1 --count=5 --delay=2

Проверить другого сотрудника, `ID_PEP = 22877`:

    php modules/minion/minion --task=eventsInsert --people=22877 --count=5

**Полный пример** — конкретное устройство `797`, конкретный человек
`1`, конкретный тип события `27`, 20 вставок с интервалом 1 сек:

    php modules/minion/minion --task=eventsInsert --id_dev=797 --people=1 --type=27 --count=20 --delay=1

Что произойдёт:

1. Задача подключится к БД СКУД через `fb`.
2. Создаст 20 событий подряд в таблице `events`:
   - `ID_DEV = 797`;
   - `ESS1  = 1` — ID сотрудника;
   - `ID_EVENTTYPE = 27` — тип события.
3. Каждое событие получит `NOTE = '17 building test #N'` (по
   умолчанию) или ваш текст, если передан `--note`.
4. В `/monitors` вы увидите 20 новых строк с ФИО сотрудника
   `ID_PEP = 1` в колонке «Сотрудник».

### 6. Своя заметка и конкретный сотрудник {#eventtask-example-note}

    php modules/minion/minion --task=eventsInsert --people=1 --note="test-pass" --count=3 --delay=1

В колонке `NOTE` будет: `test-pass #1`, `test-pass #2`, `test-pass #3`.

### 7. BAT-файл с параметрами {#eventtask-example-bat}

Отредактируйте `test_insert_events.bat`:

    @echo off
    c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion ^
        --task=eventsInsert ^
        --id_dev=797 ^
        --people=1 ^
        --type=27 ^
        --count=20 ^
        --delay=1

Символ `^` в BAT-файлах — перенос строки команды.

## Как узнать `id_pep` нужного сотрудника {#eventtask-id-pep}

Параметр `--people` — это первичный ключ таблицы `people`. Узнать
его можно:

- в интерфейсе модуля `people` — откройте карточку сотрудника, ID
  виден в адресной строке (например, `people/edit/22877`);
- SQL-запросом:

        SELECT ID_PEP, SURNAME, NAME, PATRONYMIC
        FROM PEOPLE
        WHERE SURNAME LIKE 'Иванов%'

- из таблицы `CARD` по номеру карты:

        SELECT P.ID_PEP, P.SURNAME, P.NAME
        FROM PEOPLE P
        JOIN CARD C ON C.ID_PEP = P.ID_PEP
        WHERE C.ID_CARD = '1484F8001A'

Поле `ESS1` в таблице `events` хранит именно `ID_PEP`, поэтому
`--people` и `ESS1` — это одно и то же число.

## Что происходит внутри {#eventtask-internals}

Задача выполняет следующие шаги для каждого события:

1. Получает новый ID из генератора:

        SELECT GEN_ID(GEN_EVENT_ID, 1) AS gen FROM RDB$DATABASE

2. Формирует INSERT в таблицу `events`:

        INSERT INTO events (
            ID_EVENT, ID_DB, ID_EVENTTYPE, ID_DEV, ID_PLAN,
            DATETIME, ID_CARD, NOTE, ID_VIDEO, ID_PEP, ESS1, ESS2
        ) VALUES (
            :newId, 1, :type, :idDev, NULL,
            current_timestamp, '1484F8001A', :note, NULL, NULL, :people, 1
        )

3. Логирует успех в `Kohana::$log`:

        Событие добавлено. ID: 12345, цикл: 3

4. Ждёт `--delay` секунд (если это не последнее событие).

5. По завершении выводит итог:

        =====================================
        Вставка завершена!
        Успешно: 10 событий
        Ошибок: 0 событий
        =====================================

## Вывод в консоли {#eventtask-output}

Пример успешного запуска
`--id_dev=797 --people=1 --type=27 --count=3 --delay=1`:

    Начинаем вставку 3 событий с интервалом 1 сек.
    id_dev=797, type=27, people=1
    [1/3] Событие добавлено. ID: 100001
    [2/3] Событие добавлено. ID: 100002
    [3/3] Событие добавлено. ID: 100003
    =====================================
    Вставка завершена!
    Успешно: 3 событий
    Ошибок: 0 событий
    =====================================

Пример, когда `--id_dev` не найден и авто-выбор тоже не сработал:

    ОШИБКА: не удалось определить id_dev.

## Наблюдение результата в мониторе {#eventtask-monitor}

1. Откройте `/monitors` в браузере.
2. Убедитесь, что выбран режим **AJAX** (`config/monitors.php` →
   `websocket.enabled = false`) или запущен `websocket_server.php`
   (`enabled = true`).
3. Запустите задачу в консоли:

        php modules/minion/minion --task=eventsInsert --people=1 --count=20 --delay=1

4. Смотрите, как события появляются в таблице монитора каждые
   `--delay` секунд. В колонке «Сотрудник» должно быть ФИО человека
   с `ID_PEP = 1`.

Если `websocket.enabled = true` и WS-сервер запущен — события
появятся **мгновенно** после вставки, а не по таймеру.

## Что попадает в БД {#eventtask-db}

Задача пишет в таблицу `events` строку со следующими значениями:

- `ID_EVENT` — из `GEN_EVENT_ID`, уникальный;
- `ID_DB` — `1` (жёстко);
- `ID_EVENTTYPE` — из `--type` (по умолчанию `50`);
- `ID_DEV` — из `--id_dev` или `getIdDev()`;
- `ID_PLAN` — `NULL`;
- `DATETIME` — `current_timestamp`;
- `ID_CARD` — `'1484F8001A'` (заглушка);
- `NOTE` — `'<--note> #<номер цикла>'`;
- `ID_VIDEO` — `NULL`;
- `ID_PEP` — `NULL` (в задаче не заполняется);
- `ESS1` — из `--people` (по умолчанию `22877`);
- `ESS2` — `1` (жёстко).

Поле `ESS1` — это и есть ссылка на сотрудника, по которой монитор
делает `LEFT JOIN people`. Событие, у которого `ESS1` не совпадает
ни с одним `ID_PEP`, всё равно запишется, но в колонке «Сотрудник»
будет пусто.

## Типовые ошибки {#eventtask-errors}

### `Class 'Minion_Task' not found` {#eventtask-error-minion}

Не подключён модуль `minion`. Добавьте его в `Kohana::modules()` и
очистите кэш.

### `Parameter Errors: id_dev … is not a valid option for this task!` {#eventtask-error-param}

Параметр `id_dev` не зарегистрирован в `$_options`. В текущей версии
файла он уже прописан как `'id_dev' => NULL` — если ошибка всё ещё
появляется, проверьте, что файл сохранён в UTF-8 и кэш очищен.

### `Database_Exception` при вставке {#eventtask-error-db}

- Проверьте доступность БД СКУД (подключение `fb`).
- Проверьте, что генератор `GEN_EVENT_ID` существует:

        SELECT * FROM RDB$GENERATORS WHERE RDB$GENERATOR_NAME = 'GEN_EVENT_ID';

- Проверьте права пользователя БД на `INSERT` в таблицу `events`.
- Проверьте, что `ID_DEV` (переданный через `--id_dev` или
  выбранный авто) реально существует в таблице `device`.

### `ОШИБКА: не удалось определить id_dev.` {#eventtask-error-id-dev}

Ни один из вариантов не сработал:

- не передан `--id_dev`;
- `getIdDev()` вернул `null` — нет активного устройства с
  `ID_READER = 0`. Проверьте запрос:

        SELECT FIRST 1 d.id_dev
        FROM device d
        JOIN device d2 ON d2.id_ctrl = d.id_ctrl
                      AND d2.id_reader IS NULL
                      AND d2."ACTIVE" > 0
        WHERE d.id_reader = 0
          AND d."ACTIVE" > 0

**Решение**: передать `--id_dev=<ID>` вручную.

### События создаются, но не видны в мониторе {#eventtask-error-not-visible}

- Проверьте фильтр по группе устройств: возможно, устройство
  не входит в выбранную группу.
- Проверьте, что вы не «остановили» монитор (чекбокс **Остановить**
  или клавиша **Пробел**).
- Убедитесь, что cookie `id` не зафиксирована на слишком большом
  значении — нажмите **🔄 Сброс**.

### В колонке «Сотрудник» пусто {#eventtask-error-people}

- `--people` не совпадает ни с одним `ID_PEP` в таблице `people`.
  Проверьте:

        SELECT ID_PEP, SURNAME FROM PEOPLE WHERE ID_PEP = 1;

- Если запрос пуст — такого сотрудника нет, используйте реальный
  `ID_PEP`.

## Смотрите также {#see-also}

- [Установка](install)
- [Настройка](configuration)
- [WebSocket-режим](websocket)
- [Решение проблем](troubleshooting)