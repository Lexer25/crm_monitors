# Настройка

## Конфигурационный файл {#config-file}

Файл: `modules/monitors/config/monitors.php`

Модуль читает настройки через `Kohana::$config->load('monitors')`.
Файл возвращает обычный PHP-массив с двумя секциями: `websocket`
и `events`.

    return array(
        'websocket' => array(
            'host'    => '127.0.0.1',  // адрес WebSocket-сервера
            'port'    => 8082,         // порт WebSocket-сервера
            'timeout' => 5,            // таймаут подключения, сек
            'enabled' => false,        // false = AJAX, true = WebSocket
        ),
        'events' => array(
            'limit'       => 30,       // сколько событий за один запрос
            'cookie_name' => 'id',     // имя cookie с последним ID
        ),
    );

---

## Секция `websocket` {#websocket}

Отвечает за доставку событий через WebSocket. Если `enabled = false`,
эта секция фактически не используется — монитор работает в AJAX-режиме.

### Параметры websocet {#websocket-params}

- **`host`** (строка, по умолчанию `127.0.0.1`).
  Адрес WebSocket-сервера. Используется в `Helper_WebSocket::send()`
  и `Helper_WebSocket::isAvailable()`.
  Если сайт работает на другом сервере — укажите его IP или домен.
  Для локальной разработки оставьте `127.0.0.1`.

- **`port`** (целое, по умолчанию `8082`).
  Порт, который слушает WebSocket-сервер. Должен совпадать с портом
  в `websocket_server.php` и с портом на клиенте (`wsUrl`
  в `views/list.php`).

- **`timeout`** (целое, секунды, по умолчанию `5`).
  Таймаут `stream_socket_client()` при отправке данных в WS-сервер.
  Если WS-сервер не отвечает дольше — `send()` вернёт `false`,
  а событие не будет доставлено.

- **`enabled`** (bool, по умолчанию `false`).
  Главный переключатель режима:
  - `false` — AJAX-режим. Браузер опрашивает `/monitors/getEvent`
    каждые `interval` секунд (см. ниже). WebSocket не используется.
  - `true` — WebSocket-режим. Клиент подключается к `ws://host:port`,
    а сервер пушит события через `Helper_WebSocket::send()`.
    Если WS-сервер недоступен — клиент через 10 неудачных попыток
    автоматически переключится на AJAX.

### Когда включать WebSocket {#when-websocket}

- Много событий в секунду (задержка AJAX слишком велика).
- Больше 5–10 одновременно открытых мониторов.
- Есть возможность держать отдельный сервис `websocket_server.php`.

### Когда оставить AJAX {#when-ajax}

- Единичные мониторы.
- Нет возможности запускать дополнительный сервис.
- Порт 8082 закрыт файрволом.

---

## Секция `events` {#events}

Отвечает за логику выборки событий из БД.

### Параметры events {#events-params}

- **`limit`** (целое, по умолчанию `30`).
  Сколько событий возвращать за один запрос. Используется в
  `Controller_monitors::action_getEvent()` при вызове
  `Model_MonitorM::getEvents($id, $photo, 30, $groupId)`.
  Увеличение лимита повышает нагрузку на БД и трафик;
  уменьшение — снижает, но события будут появляться «порциями».

- **`cookie_name`** (строка, по умолчанию `id`).
  Имя cookie, в котором хранится ID последнего полученного события.
  Используется в `action_getEvent()`:
  - `Cookie::get('id')` — чтение при запросе;
  - `Cookie::set('id', $newId)` — запись после успешной выборки.

  Менять значение нужно, только если в проекте уже есть cookie `id`
  с другим смыслом. При смене — обновите также `getLastEventId()`
  в `views/list.php` (там зашито имя `id`).

---

## Клиентские настройки (localStorage) {#localstorage}

Часть параметров пользователь задаёт сам через диалог **⚙️ Настройки**
на странице монитора. Они хранятся в браузере, а не в конфиге модуля:

- **`interval`** — интервал AJAX-опроса, секунды. Хранится в
  `localStorage.monitorSettings.interval`. Диапазон 1–60,
  по умолчанию 5. В WebSocket-режиме игнорируется.
- **`rowCount`** — сколько строк показывать на экране. Хранится в
  `localStorage.monitorSettings.rowCount`. Значения: 10, 20, 30,
  50, 100. По умолчанию 20.
- **`windowsCount`** — максимум всплывающих окон с фото.
  Хранится в `localStorage.monitorSettings.windowsCount`.
  Диапазон 1–20, по умолчанию 5.
- **`photoEnabled`** — показывать ли фотографии. Хранится в
  `localStorage.monitorSettings.photoEnabled`. По умолчанию `true`.
- **`autoScroll`** — автоматическая прокрутка к новым событиям.
  Хранится в `localStorage.monitorSettings.autoScroll`.
  По умолчанию `true`.

Эти параметры не требуют правки конфига — их меняет пользователь
в браузере, и они сохраняются между сессиями.

---

## Маршруты {#routes}

Модуль регистрирует маршруты в `init.php`:

- **`/monitors`** — главная страница монитора.
- **`/monitors/getEvent`** — API получения событий (AJAX).
- **`/monitors/setGroup`** — API сохранения выбранной группы.
- **`/monitors/guide`** — справка модуля (если подключён контроллер
  `Guide`).

Маршруты объявляются в `modules/monitors/init.php` через `Route::set()`
и имеют приоритет выше общего `default`.

---

## Пользовательские настройки в БД {#db}

Отдельной таблицы настроек у модуля нет. Всё, что не вынесено
в `config/monitors.php` и `localStorage`, задаётся в других модулях:

- **Группы устройств** — таблица `DEVGROUP` в БД СКУД.
  Монитор читает только верхний уровень (`ID_PARENT = 1`).
- **Типы событий и цвета** — таблица `eventtype`
  (колонки `COLOR`, `NAME`).
- **Сотрудники, фото, организации** — таблицы `people`,
  `organization`.

---

## Пример типовых конфигураций {#examples}

### Только AJAX, один монитор на слабой машине {#example-ajax}

    return array(
        'websocket' => array(
            'enabled' => false,
        ),
        'events' => array(
            'limit'       => 20,
            'cookie_name' => 'id',
        ),
    );

### WebSocket в локальной сети {#example-websocket}

    return array(
        'websocket' => array(
            'host'    => '192.168.0.10',
            'port'    => 8082,
            'timeout' => 5,
            'enabled' => true,
        ),
        'events' => array(
            'limit'       => 30,
            'cookie_name' => 'id',
        ),
    );

### Отладка на локальной машине {#example-debug}

    return array(
        'websocket' => array(
            'host'    => '127.0.0.1',
            'port'    => 8082,
            'timeout' => 1,
            'enabled' => false,  // пока не запустим websocket_server.php
        ),
        'events' => array(
            'limit'       => 100,  // удобно видеть больше событий
            'cookie_name' => 'id',
        ),
    );

---

## После изменения конфига {#after-change}

Kohana кэширует конфиги. После правки `config/monitors.php`:

1. Очистите кэш:

        rm -rf application/cache/*

   Windows:

        del /Q /S application\cache\*

2. Перезагрузите страницу монитора (Ctrl+F5).

3. Убедитесь, что новый конфиг применился — например, измените
   `enabled` на `true` и проверьте, что индикатор статуса в правом
   верхнем углу показывает WebSocket, а не AJAX.

---

## Смотрите также {#see-also}

- [Установка](install)
- [WebSocket](websocket)
- [Решение проблем](troubleshooting)