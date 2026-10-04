# Архитектура {#architecture}

## Общая схема {#scheme}

    ┌─────────────────┐    HTTP/AJAX     ┌───────────────────────┐
    │    Браузер      │◄────────────────►│  Controller_monitors  │
    │    (list.php)   │                  │   action_getEvent     │
    │                 │                  │   action_setGroup     │
    │                 │◄─── WebSocket ──►│                       │
    └─────────────────┘                  └───────────┬───────────┘
                                                     │
                                                     ▼
                                         ┌───────────────────────┐
                                         │    Model_MonitorM     │
                                         │    getEvents()        │
                                         │    renderEventRow()   │
                                         │    getDeviceGroups()  │
                                         └───────────┬───────────┘
                                                     │
                                                     ▼
                                         ┌───────────────────────┐
                                         │     Firebird (fb)     │
                                         │   events, device,     │
                                         │   people, eventtype,  │
                                         │   organization,       │
                                         │   DEVGROUP            │
                                         └───────────────────────┘

## Компоненты {#components}

### Controller_monitors {#controller-monitors}

Файл: `classes/controller/monitors.php`

- **`action_index()`** — рендер главной страницы.
- **`action_getEvent()`** — AJAX-эндпоинт, возвращает HTML-строки таблицы.
- **`action_setGroup()`** — сохранение выбранной группы в сессии.

### Model_MonitorM {#model-monitorm}

Файл: `classes/model/MonitorM.php`

- **`getNextId()`** — текущее значение генератора `GEN_EVENT_ID`.
- **`getEvents()`** — выборка событий с фильтрацией по группе.
- **`getEventById()`** — одно событие по ID.
- **`addEvent()`** — вставка события (для тестов).
- **`renderEventRow()`** — формирование HTML `<tr>`.
- **`sendToWebSocket()`** — отправка события в WebSocket.
- **`getDeviceGroups()`** — список групп верхнего уровня (`ID_PARENT = 1`).

### Helper_WebSocket {#helper-websocket}

Файл: `classes/Helper/WebSocket.php`

- **`send($data)`** — отправка строки в WebSocket-сервер.
- **`sendJson($data)`** — отправка JSON.
- **`isAvailable()`** — проверка доступности сервера.

### Task_EventsInsert {#task-eventsinsert}

Файл: `classes/Task/eventsInsert.php`

Minion-задача для генерации тестовых событий.

---

## Два способа получения событий {#two-modes}

Модуль поддерживает **два взаимозаменяемых механизма** доставки событий
в браузер. Выбор определяется параметром `enabled` в конфиге модуля.

### Сравнение {#comparison}

<table>
<thead>
<tr>
    <th>Характеристика</th>
    <th>AJAX</th>
    <th>WebSocket</th>
</tr>
</thead>
<tbody>
<tr>
    <td>Инициатор</td>
    <td>Браузер (опрос по таймеру)</td>
    <td>Сервер (push при появлении события)</td>
</tr>
<tr>
    <td>Задержка доставки</td>
    <td>до <code>interval</code> секунд</td>
    <td>мгновенная</td>
</tr>
<tr>
    <td>Нагрузка на сервер</td>
    <td>периодические HTTP-запросы</td>
    <td>одно постоянное TCP-соединение</td>
</tr>
<tr>
    <td>Требует внешнего сервиса</td>
    <td>нет</td>
    <td>да (<code>websocket_server.php</code>)</td>
</tr>
<tr>
    <td>Резервный режим</td>
    <td>—</td>
    <td>автоматический откат на AJAX</td>
</tr>
<tr>
    <td>Включается параметром</td>
    <td><code>websocket.enabled = false</code></td>
    <td><code>websocket.enabled = true</code></td>
</tr>
</tbody>
</table>

### Автопереключение WebSocket → AJAX {#auto-switch}

Если `ws.onclose` сработает 10 раз подряд (или `ws.onerror` выбросит
исключение) — вызывается `switchToAjaxMode()`, который:

- закрывает WS;
- меняет индикатор статуса на `📡 AJAX`;
- запускает `startAjaxInterval()`.

Так клиент **никогда не остаётся без обновлений**, даже если WS-сервер
упал.

---

## Общая схема БД {#db-scheme}

- **`events`** — журнал событий.
- **`eventtype`** — типы событий (цвет, название).
- **`device`** — устройства (точки доступа).
- **`people`** — сотрудники (ФИО, фото, должность).
- **`organization`** — организации.
- **`DEVGROUP`** — группы устройств (иерархия).

## Хранимые процедуры / функции {#db-functions}

- **`GEN_ID(GEN_EVENT_ID, N)`** — генератор ID событий.
- **`DEVGROUP_GETCHILD(1, group_id)`** — получение всех вложенных устройств группы.

## Смотрите также {#see-also}

- [AJAX-режим](ajax)
- [WebSocket](websocket)
- [Настройка](configuration)