## AJAX-режим {#ajax}

### Настройки {#ajax-settings}

Клиентские настройки (в `localStorage` браузера), управляются через
диалог **⚙️ Настройки** на странице монитора:

- **`interval`** — в `localStorage.monitorSettings`, по умолчанию 5 сек.
- **`photoEnabled`** — в `localStorage.monitorSettings`, по умолчанию вкл.
- **`autoScroll`** — в `localStorage.monitorSettings`, по умолчанию вкл.
- **`rowCount`** — в `localStorage.monitorSettings`, по умолчанию 20.

### Схема потока {#ajax-flow}

    Браузер                    Kohana                   Firebird
       │                          │                        │
       │  setInterval(showUser)   │                        │
       │                          │                        │
       │  GET /monitors/getEvent  │                        │
       │  ?photo=1&group=2&t=...  │                        │
       │─────────────────────────►│                        │
       │                          │  Cookie::get('id')     │
       │                          │                        │
       │                          │  SELECT ... FROM events│
       │                          │───────────────────────►│
       │                          │◄───────────────────────│
       │                          │                        │
       │                          │  renderEventRow()      │
       │                          │  Cookie::set('id', N)  │
       │                          │                        │
       │   HTML <tr>...</tr>      │                        │
       │◄─────────────────────────│                        │
       │                          │                        │
       │  handleNewEvents()       │                        │
       │  table.insertAdjacent... │                        │

### Пошагово {#ajax-steps}

1. **Инициализация** (`list.php`, `$(function(){...})`):
   если `wsEnabled === false`, вызывается `startAjaxInterval()`.

2. **`startAjaxInterval()`**:

        ajaxIntervalId = setInterval(showUser, timeUpdate * 1000);

   Период `timeUpdate` берётся из `localStorage.monitorSettings.interval`.

3. **`showUser()`** формирует GET-запрос:

        monitors/getEvent?photo=1&group=2&t=<timestamp>

   - `photo` — включён ли показ фото;
   - `group` — выбранная группа устройств;
   - `t` — метка времени для обхода кэша браузера.

4. **`Controller_monitors::action_getEvent()`**:
   - читает cookie `id` (последний полученный ID события);
   - если cookie нет — берёт `getNextId() - 20`;
   - вызывает `Model_MonitorM::getEvents($id, $photo, 30, $groupId)`;
   - рендерит каждое событие через `renderEventRow()`;
   - обновляет cookie новым максимальным ID;
   - возвращает склеенный HTML.

5. **`Model_MonitorM::getEvents()`** выполняет SQL:

        SELECT FIRST 30 e.id_event, ...
        FROM device d
        JOIN events e ON e.id_dev = d.id_dev
        JOIN eventtype et ON et.id_eventtype = e.id_eventtype
        LEFT JOIN people p ON p.id_pep = e.ess1
        LEFT JOIN organization o ON o.id_org = e.ess2
        [JOIN DEVGROUP_GETCHILD(1, :group) dg ON e.id_dev = dg.id_dev]
        WHERE e.id_event > :id
        ORDER BY e.id_event DESC

6. **`handleNewEvents()`** в браузере вставляет HTML в начало `<tbody>`,
   удаляет лишние строки по `selectsSize`, создаёт модальные окна
   с фото (`createModal`), обновляет счётчики.

### Точки настройки AJAX {#ajax-config-points}

- **Включить/выключить режим** — `config/monitors.php` → `websocket.enabled = false`.
- **Интервал опроса** — `localStorage.monitorSettings.interval` (диалог ⚙️).
- **Лимит событий за запрос** — `action_getEvent()`, третий аргумент `getEvents($id, $photo, 30, …)`.
- **Имя cookie** — `config/monitors.php` → `events.cookie_name`.
- **Количество строк на экране** — `localStorage.monitorSettings.rowCount`.
- **Фотографии** — чекбокс «Фотографии» → `localStorage.monitorSettings.photoEnabled`.

---