## WebSocket-режим {#websocket}

### Настройки {#websocket-settings}

Файл: `config/monitors.php`

    return array(
        'websocket' => array(
            'host'    => '127.0.0.1',  // адрес WS-сервера
            'port'    => 8082,         // порт WS-сервера
            'timeout' => 5,            // таймаут подключения (сек)
            'enabled' => true,         // ← WebSocket-режим
        ),
        'events' => array(
            'limit' => 30,
        ),
    );

Клиентские настройки — те же, что и в AJAX-режиме
(`localStorage.monitorSettings`), плюс жёстко заданные в `list.php`:

- **`wsUrl`** — `list.php` (JS), по умолчанию `ws://localhost:8082`.
- **`wsMaxReconnectAttempts`** — `list.php` (JS), по умолчанию 10.
- **`wsReconnectDelay`** — `list.php` (JS), по умолчанию 1000 мс.

### Схема потока {#websocket-flow}

    Внешний источник          Helper_WebSocket         WS-сервер          Браузер
         │                          │                     │                  │
         │  sendToWebSocket(id)     │                     │                  │
         │─────────────────────────►│                     │                  │
         │                          │ TCP connect         │                  │
         │                          │────────────────────►│                  │
         │                          │ HTML <tr>...</tr>   │                  │
         │                          │────────────────────►│                  │
         │                          │                     │  broadcast       │
         │                          │                     │─────────────────►│
         │                          │                     │                  │
         │                          │                     │      onmessage   │
         │                          │                     │      handleNew   │
         │                          │                     │      Events()    │

### Пошагово {#websocket-steps}

1. **Инициализация** (`list.php`): если `wsEnabled === true`,
   вызывается `connectWebSocket()`. Одновременно ставится таймер 3 сек —
   если WS не подключился, запустится AJAX как fallback.

2. **`connectWebSocket()`** открывает соединение:

        ws = new WebSocket('ws://localhost:8082');

   При `onopen` отправляет запрос на получение последних событий:

        {"action":"getEvents","lastId":N,"photo":true,"groupId":2}

3. **Отправка события со стороны PHP** (из другого модуля, демона СКУД
   или Minion-задачи):

        $model = new Model_MonitorM();
        $model->sendToWebSocket($eventId, true);

   `sendToWebSocket()`:
   - читает событие через `getEventById($eventId, true)`;
   - рендерит HTML через `renderEventRow()`;
   - вызывает `Helper_WebSocket::send($html)`.

4. **`Helper_WebSocket::send()`** открывает TCP-соединение
   (`stream_socket_client('tcp://host:port')`), пишет HTML и закрывает.
   Хост и порт берутся из `config/monitors.php` → `websocket.host/port`.

5. **WS-сервер** (`websocket_server.php` в корне проекта) рассылает
   полученные строки всем подключённым клиентам.

6. **`ws.onmessage`** в браузере:
   - пытается распарсить JSON (проверка на `error` или `action:setGroup`);
   - если не JSON — вызывает `handleNewEvents(event.data)`.

7. **`handleNewEvents()`** — тот же, что и в AJAX-режиме: вставка строк,
   обрезка, создание модальных окон, счётчики.

### Точки настройки WebSocket {#websocket-config-points}

- **Включить/выключить режим** — `config/monitors.php` → `websocket.enabled = true`.
- **Адрес WS-сервера** — `config/monitors.php` → `websocket.host`.
- **Порт WS-сервера** — `config/monitors.php` → `websocket.port`.
- **Таймаут подключения** — `config/monitors.php` → `websocket.timeout`.
- **URL WS на клиенте** — `list.php` → `var wsUrl = 'ws://localhost:8082'`.
- **Лимит попыток переподключения** — `list.php` → `wsMaxReconnectAttempts`.
- **Стартовая задержка reconnect** — `list.php` → `wsReconnectDelay`.
- **Проверка доступности** — `Helper_WebSocket::isAvailable()`, вызывается в `action_index()`.

---