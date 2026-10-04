# Установка {#install}

## Размещение файлов {#install-files}

Модуль должен быть размещён в каталоге `modules/monitors/`:

    monitors/
    ├── classes/
    │   ├── controller/
    │   │   ├── monitors.php
    │   │   └── guide.php
    │   ├── Model/
    │   │   └── MonitorM.php
    │   ├── Helper/
    │   │   └── WebSocket.php
    │   └── Task/
    │       └── eventsInsert.php
    ├── config/
    │   ├── monitors.php
    │   └── userguide.php
    ├── views/
    │   └── list.php
    ├── guide/
    │   └── monitors/
    │       ├── index.md
    │       ├── install.md
    │       ├── configuration.md
    │       ├── usage.md
    │       ├── architecture.md
    │       ├── ajax.md
    │       ├── websocket.md
    │       ├── api.md
    │       ├── troubleshooting.md
    │       └── menu.md
    ├── i18n/
    │   └── ru/
    │       └── ru.php
    ├── history.txt
    ├── init.php
    └── README.md

## Подключение модуля {#install-bootstrap}

В файле `application/bootstrap.php` убедитесь, что модуль включён:

    Kohana::modules(array(
        // ...
        'monitors' => MODPATH.'monitors',
        // ...
    ));

Порядок модулей в массиве не критичен, но `monitors` должен идти
**после** `userguide` и `minion`, если вы используете их справку и
Minion-задачи.

## Очистка кэша {#install-cache}

После установки или обновления файлов модуля очистите кэш Kohana:

Linux / Mac:

    rm -rf application/cache/*

Windows:

    del /Q /S application\cache\*

Kohana кэширует список модулей, конфиги и файлы ресурсов — без
очистки изменения могут не примениться.

## Проверка установки {#install-verify}

1. Откройте `/monitors` — должна открыться страница монитора.
2. В меню появится пункт **«Монитор»**.
3. Откройте `/guide/monitors` — должна открыться справка модуля.
4. Проверьте, что на странице монитора виден выпадающий список
   групп устройств (значит, БД СКУД доступна и функция
   `DEVGROUP_GETCHILD` существует).

## Требования {#install-requirements}

- **Kohana** — 3.3.
- **PHP** — 5.6 или выше.
- **СУБД** — Firebird, подключение через ODBC Gemini InterBase.
  Имя подключения — `fb` (см. `application/config/database.php`).
- **Браузер** — любой современный с поддержкой WebSocket
  (Chrome, Firefox, Edge, Safari 12+).
- **Опционально** — PHP-расширение `sockets` для WebSocket-режима
  и внешний сервис `websocket_server.php`.

## Тестирование вставки событий {#install-test-events}

Для отладки предусмотрена Minion-задача `eventsInsert`. Она
создаёт N тестовых событий с заданным интервалом.

Запуск из корня проекта:

    php modules/minion/minion --task=eventsInsert --count=10 --delay=2

Параметры:

- `--count` — количество событий (по умолчанию 10);
- `--delay` — интервал между вставками в секундах (по умолчанию 2);
- `--type`  — ID типа события (по умолчанию 27);
- `--people`— ID человека (по умолчанию 1);
- `--note`  — текст заметки.

В Windows удобно пользоваться готовым BAT-файлом:

    monitors\classes\Task\test_insert_events.bat

Он вызывает ту же задачу без параметров (значения по умолчанию).

## Что делать при ошибке установки {#install-troubleshooting}

- **Пункт меню «Монитор» не появился** — проверьте, что модуль
  добавлен в `Kohana::modules()` и кэш очищен.
- **Страница `/monitors` открывается пустой** — проверьте лог
  `application/logs/<дата>.php`, скорее всего ошибка SQL или
  отсутствует подключение `fb`.
- **`Class 'Model_MonitorM' not found`** — файл модели должен
  лежать в `classes/model/MonitorM.php` с заглавной `M` в имени
  класса. Проверьте регистр.
- **`Helper_WebSocket` не найден** — файл должен лежать в
  `classes/Helper/WebSocket.php`, класс `Helper_WebSocket`.

Подробнее — в разделе [Решение проблем](troubleshooting).

## Смотрите также {#see-also}

- [Настройка](configuration)
- [Архитектура](architecture)
- [Решение проблем](troubleshooting)