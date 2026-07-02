<script type="text/javascript" src="js/modal-window.js"></script>
<script type="text/javascript" src="js/modal-photo.js"></script>
<script type="text/javascript">
    // ==========================================
    // Глобальные настройки
    // ==========================================
    var timeUpdate = 5; // Интервал обновления в секундах (запасной, если WebSocket не работает)
    var windowsCountsetings = 5; // Количество окон с фото
    var API_BASE_URL = 'monitors/getEvent'; // Fallback URL для AJAX
    var wsEnabled = <?php echo $ws_enabled ? 'true' : 'false'; ?>;
    // ==========================================
    // WebSocket настройки
    // ==========================================
    var ws = null;
    var wsConnected = false;
    var wsReconnectAttempts = 0;
    var wsMaxReconnectAttempts = 10;
    var wsReconnectDelay = 1000; // Начинаем с 1 секунды
    var wsLastEventId = 0; // Последний полученный ID события
    
    // ==========================================
    // Подключение к WebSocket
    // ==========================================
    function connectWebSocket() {
        try {
            // Определяем URL для WebSocket
            var wsUrl = 'ws://localhost:8082';
            
            // Если сайт на HTTPS, используем wss://
            if (window.location.protocol === 'https:') {
                wsUrl = 'wss://localhost:8082';
            }
            
            console.log('🔄 Подключение к WebSocket: ' + wsUrl);
            
            ws = new WebSocket(wsUrl);
            
            ws.onopen = function() {
                console.log('✅ WebSocket подключен!');
                wsConnected = true;
                wsReconnectAttempts = 0;
                wsReconnectDelay = 1000;
                
                // Запрашиваем последние события при подключении
                var lastId = getLastEventId();
                ws.send(JSON.stringify({
                    action: 'getEvents',
                    lastId: lastId,
                    photo: document.getElementById('photomonitor') ? document.getElementById('photomonitor').checked : false
                }));
            };
            
            ws.onmessage = function(event) {
                // Получены данные от сервера
                if (event.data && event.data !== '') {
                    // Проверяем, не пришло ли JSON-сообщение с ошибкой
                    try {
                        var jsonData = JSON.parse(event.data);
                        if (jsonData.error) {
                            console.error('❌ Ошибка от сервера:', jsonData.error);
                            return;
                        }
                    } catch(e) {
                        // Это не JSON, значит это HTML-строки для таблицы
                        handleNewEvents(event.data);
                    }
                }
            };
            
            ws.onclose = function() {
                console.log('⚠️ WebSocket отключен');
                wsConnected = false;
                ws = null;
                
                // Автоматическое переподключение с экспоненциальной задержкой
                if (wsReconnectAttempts < wsMaxReconnectAttempts) {
                    var delay = Math.min(30000, wsReconnectDelay * Math.pow(2, wsReconnectAttempts));
                    // Добавляем случайность (джиттер)
                    delay = delay * (0.8 + 0.4 * Math.random());
                    
                    wsReconnectAttempts++;
                    console.log('🔄 Переподключение через ' + Math.round(delay/1000) + ' сек. (попытка ' + wsReconnectAttempts + '/' + wsMaxReconnectAttempts + ')');
                    
                    setTimeout(function() {
                        connectWebSocket();
                    }, delay);
                } else {
                    console.error('❌ Достигнут лимит попыток переподключения. Переключаемся на AJAX...');
                    // Переключаемся на AJAX как запасной вариант
                    switchToAjaxMode();
                }
            };
            
            ws.onerror = function(error) {
                console.error('❌ Ошибка WebSocket:', error);
                // ws.onclose вызовется автоматически
            };
            
        } catch(e) {
            console.error('❌ Ошибка создания WebSocket:', e);
            switchToAjaxMode();
        }
    }
    
    // ==========================================
    // Получение последнего ID события из куки
    // ==========================================
    function getLastEventId() {
        var cookies = document.cookie.split(';');
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].trim();
            if (cookie.indexOf('id=') === 0) {
                return parseInt(cookie.substring(3)) || 0;
            }
        }
        return 0;
    }
    
    // ==========================================
    // Обработка новых событий
    // ==========================================
    function handleNewEvents(html) {
        if (!html || html === '') return;
        
        // Поиск элементов
        var table = document.getElementById('txtHint');
        var select = document.getElementById('selectsSize');
        if (!table || !select) return;
        
        // Добавить строки к таблице
        table.insertAdjacentHTML('afterbegin', html);
        
        // Удалить лишние строки
        while (table.rows.length > parseInt(select.value)) {
            table.deleteRow(table.rows.length - 1);
        }
        
        // Формирование карточки с фото
        var photoneed = document.getElementById('photomonitor') ? document.getElementById('photomonitor').checked : false;
        if (photoneed) {
            for (var i = 0; i < table.rows.length && i < windowsCountsetings; i++) {
                try {
                    var photo = table.rows[i].cells.namedItem('photo');
                    var even_name = table.rows[i].cells.namedItem('even_name');
                    var people_name = table.rows[i].cells.namedItem('people_name');
                    var org_name = table.rows[i].cells.namedItem('org_name');
                    var people_post = table.rows[i].cells.namedItem('people_post');
                    var device_name = table.rows[i].cells.namedItem('device_name');
                    
                    if (photo && typeof createModal === 'function') {
                        createModal(
                            even_name ? even_name.innerText : '',
                            photo.innerText,
                            people_name ? people_name.innerText : '',
                            org_name ? org_name.innerText : '',
                            people_post ? people_post.innerText : '',
                            device_name ? device_name.innerText : ''
                        );
                    }
                } catch(e) {
                    // Игнорируем ошибки при создании модальных окон
                }
            }
        }
        
        // Обновляем счетчики
        updateCounters();
        
        // Автоскролл к новым событиям
        var settings = localStorage.getItem('monitorSettings');
        if (settings) {
            try {
                var parsed = JSON.parse(settings);
                if (parsed.autoScroll !== false) {
                    var container = document.getElementById('myTableContainer');
                    if (container) {
                        container.scrollTop = 0;
                    }
                }
            } catch(e) {}
        }
        
        // Обновляем последний ID события
        if (table.rows.length > 0) {
            var firstRow = table.rows[0];
            var idCell = firstRow.cells[0];
            if (idCell) {
                wsLastEventId = parseInt(idCell.innerText) || 0;
            }
        }
    }
    
    // ==========================================
    // Переключение на AJAX (fallback)
    // ==========================================
    function switchToAjaxMode() {
        console.log('🔄 Переключение на AJAX-режим');
        if (window.updateInterval) {
            clearInterval(window.updateInterval);
        }
        window.updateInterval = setInterval(showUser, timeUpdate * 1000);
        document.getElementById('websocketStatus').innerHTML = '<?php echo __('monitor.ajax'); ?>';
        document.getElementById('websocketStatus').style.color = '#ff9800';
    }
    
    // ==========================================
    // Отправка события через WebSocket
    // ==========================================
    function sendWebSocketMessage(data) {
        if (ws && wsConnected && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(data));
            return true;
        }
        return false;
    }
    
    // ==========================================
    // Функция обновления монитора (AJAX-режим)
    // ==========================================
    function showUser() {
        // Проверка кнопки остановки событий
        var stopCheck = document.getElementById("updatemonitor");
        if (stopCheck && stopCheck.checked) return;
        
        // Если WebSocket работает, не используем AJAX
        if (wsConnected && ws && ws.readyState === WebSocket.OPEN) {
            return;
        }
        
        // Формирование GET запроса
        var xmlhttp = new XMLHttpRequest();
        var photoneed = document.getElementById("photomonitor") ? document.getElementById("photomonitor").checked : false;
        
        // Обработка GET запроса
        xmlhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                handleNewEvents(this.responseText);
            }
        };
        
        var url = API_BASE_URL + '?photo=' + photoneed;
        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    }
    
    // ==========================================
    // Остальные функции (без изменений)
    // ==========================================
    
    // Функция для создания модального окна настроек
    function createSettingsModal2() {
        if (document.getElementById('settingsModal')) {
            document.getElementById('settingsModal').style.display = 'block';
            document.getElementById('settingsOverlay').style.display = 'block';
            return;
        }
        
        var currentSelect = document.getElementById('selectsSize');
        var currentSelectValue = currentSelect ? currentSelect.value : 20;
        var currentTimeUpdate = window.timeUpdate || 5;
        var currentPhotoCheck = document.getElementById('photomonitor') ? document.getElementById('photomonitor').checked : true;
        
        var overlay = document.createElement('div');
        overlay.id = 'settingsOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        `;
        overlay.onclick = function() {
            closeSettingsModal();
        };
        
        var modal = document.createElement('div');
        modal.id = 'settingsModal';
        modal.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border: 2px solid #4a90d9;
            border-radius: 10px;
            z-index: 1000;
            min-width: 350px;
            max-width: 500px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            font-family: Arial, sans-serif;
        `;
        
        modal.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #4a90d9; padding-bottom: 10px;">
                <h3 style="margin: 0; color: #333; font-size: 18px;">⚙️ <?php echo __('monitor.settings_title'); ?></h3>
                <button onclick="closeSettingsModal()" style="
                    background: #ff4444;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 30px;
                    height: 30px;
                    cursor: pointer;
                    font-size: 18px;
                    font-weight: bold;
                    transition: all 0.3s;
                " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">×</button>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">
                    <?php echo __('monitor.update_interval'); ?>:
                </label>
                <input type="number" id="settingsUpdateInterval" 
                    value="${currentTimeUpdate}" 
                    min="1" max="60" 
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <small style="color: #888;"><?php echo __('monitor.recommended'); ?></small>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">
                    <?php echo __('monitor.rows_count'); ?>:
                </label>
                <select id="settingsRowCount" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="10" ${currentSelectValue == 10 ? 'selected' : ''}>10</option>
                    <option value="20" ${currentSelectValue == 20 ? 'selected' : ''}>20</option>
                    <option value="30" ${currentSelectValue == 30 ? 'selected' : ''}>30</option>
                    <option value="50" ${currentSelectValue == 50 ? 'selected' : ''}>50</option>
                    <option value="100" ${currentSelectValue == 100 ? 'selected' : ''}>100</option>
                </select>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">
                    <?php echo __('monitor.windows_count'); ?>:
                </label>
                <input type="number" id="settingsWindowsCount" 
                    value="${windowsCountsetings}" 
                    min="1" max="20" 
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <small style="color: #888;"><?php echo __('monitor.max_windows'); ?></small>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: flex; align-items: center; color: #555; cursor: pointer;">
                    <input type="checkbox" id="settingsPhotoEnabled" ${currentPhotoCheck ? 'checked' : ''} style="margin-right: 10px;">
                    <?php echo __('monitor.show_photos'); ?>
                </label>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: flex; align-items: center; color: #555; cursor: pointer;">
                    <input type="checkbox" id="settingsAutoScroll" checked style="margin-right: 10px;">
                    <?php echo __('monitor.auto_scroll'); ?>
                </label>
            </div>
            
            <div style="margin-top: 25px; text-align: right; border-top: 1px solid #eee; padding-top: 15px;">
                <button onclick="saveSettings()" style="
                    background: #4CAF50;
                    color: white;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: bold;
                    transition: all 0.3s;
                    margin-right: 10px;
                " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    💾 <?php echo __('monitor.save'); ?>
                </button>
                <button onclick="closeSettingsModal()" style="
                    background: #ccc;
                    color: #333;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                    transition: all 0.3s;
                " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    ❌ <?php echo __('monitor.cancel'); ?>
                </button>
            </div>
        `;
        
        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSettingsModal();
            }
        });
    }
    
    function closeSettingsModal() {
        var modal = document.getElementById('settingsModal');
        var overlay = document.getElementById('settingsOverlay');
        if (modal) modal.remove();
        if (overlay) overlay.remove();
    }
    
    function saveSettings() {
        try {
            var interval = parseInt(document.getElementById('settingsUpdateInterval').value);
            var rowCount = parseInt(document.getElementById('settingsRowCount').value);
            var windowsCount = parseInt(document.getElementById('settingsWindowsCount').value);
            var photoEnabled = document.getElementById('settingsPhotoEnabled').checked;
            var autoScroll = document.getElementById('settingsAutoScroll').checked;
            
            if (interval < 1 || interval > 60) {
                alert('<?php echo __('monitor.error_interval'); ?>');
                return;
            }
            if (windowsCount < 1 || windowsCount > 20) {
                alert('<?php echo __('monitor.error_windows'); ?>');
                return;
            }
            
            var settings = {
                interval: interval,
                rowCount: rowCount,
                windowsCount: windowsCount,
                photoEnabled: photoEnabled,
                autoScroll: autoScroll
            };
            localStorage.setItem('monitorSettings', JSON.stringify(settings));
            
            window.timeUpdate = interval;
            window.windowsCountsetings = windowsCount;
            
            var select = document.getElementById('selectsSize');
            if (select) {
                select.value = rowCount;
            }
            
            var photoCheck = document.getElementById('photomonitor');
            if (photoCheck) {
                photoCheck.checked = photoEnabled;
            }
            
            if (window.updateInterval) {
                clearInterval(window.updateInterval);
                window.updateInterval = setInterval(showUser, timeUpdate * 1000);
            }
            
            showNotification('<?php echo __('monitor.settings_saved'); ?>');
            closeSettingsModal();
            
        } catch(e) {
            alert('Ошибка при сохранении настроек: ' + e.message);
        }
    }
    
    function showNotification(message, type) {
        var notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'error' ? '#ff4444' : '#4CAF50'};
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            z-index: 2000;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-family: Arial, sans-serif;
            animation: slideIn 0.5s ease;
            max-width: 400px;
        `;
        notification.textContent = message;
        
        var style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(notification);
        
        setTimeout(function() {
            notification.style.animation = 'slideOut 0.5s ease';
            setTimeout(function() {
                notification.remove();
                style.remove();
            }, 500);
        }, 3000);
    }
    
    function loadSettings() {
        try {
            var saved = localStorage.getItem('monitorSettings');
            if (saved) {
                var settings = JSON.parse(saved);
                if (settings.interval) {
                    window.timeUpdate = settings.interval;
                }
                if (settings.rowCount) {
                    var select = document.getElementById('selectsSize');
                    if (select) {
                        select.value = settings.rowCount;
                    }
                }
                if (settings.windowsCount) {
                    window.windowsCountsetings = settings.windowsCount;
                }
                if (settings.photoEnabled !== undefined) {
                    var photoCheck = document.getElementById('photomonitor');
                    if (photoCheck) {
                        photoCheck.checked = settings.photoEnabled;
                    }
                }
            }
        } catch(e) {}
    }
    
    function updateCounters() {
        var table = document.getElementById("txtHint");
        if (!table) return;
        
        var totalEvents = table.rows.length;
        
        var counterElement = document.getElementById("eventCounter");
        if (counterElement) {
            counterElement.textContent = '<?php echo __('monitor.event_counter'); ?>: ' + totalEvents;
        }
        
        var visibleElement = document.getElementById("visibleEventCount");
        if (visibleElement) {
            var select = document.getElementById("selectsSize");
            if (select) {
                visibleElement.textContent = '<?php echo __('monitor.visible_count'); ?>: ' + Math.min(totalEvents, parseInt(select.value));
            }
        }
    }
    
    function updateTime() {
        var now = new Date();
        var timeString = now.toLocaleTimeString('ru-RU', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        var dateString = now.toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        var timeElement = document.getElementById("currentTime");
        if (timeElement) {
            timeElement.textContent = '🕐 ' + dateString + ' ' + timeString;
        }
    }
    
    // ==========================================
    // Инициализация
    // ==========================================
    $(function() {
        // Инициализация tablesorter
        try {
            $("#tablesorter").tablesorter({ 
                headers: { 7: {sorter: false}},  
                widgets: ['zebra']
            });
        } catch(e) {}
        
        // Загрузка сохраненных настроек
        loadSettings();
        
        // Обновление времени
        updateTime();
        setInterval(updateTime, 1000);
        
        // Обновление счетчиков
        setTimeout(updateCounters, 1000);
        
        // Подключение к WebSocket
         if (wsEnabled) {
				// WebSocket включен
				connectWebSocket();
			} else {
				// Используем только AJAX
				document.getElementById('websocketStatus').innerHTML = '📡 AJAX';
				document.getElementById('websocketStatus').style.color = '#ff9800';
			}
        
     // AJAX интервал всегда работает (как fallback)
    if (window.updateInterval) {
        clearInterval(window.updateInterval);
    }
    window.updateInterval = setInterval(showUser, timeUpdate * 1000);
        // Пробел - остановка событий
        $(document).bind('keypress', function(e) {
            if (e.keyCode == 32) {
                var check = document.getElementById("updatemonitor");
                if (check) {
                    check.checked = !check.checked;
                    e.preventDefault();
                }
            }
        });
        
        // Отслеживание изменения количества строк
        $('#selectsSize').change(function() {
            var table = document.getElementById("txtHint");
            if (table) {
                while (table.rows.length > parseInt(this.value)) {
                    table.deleteRow(table.rows.length - 1);
                }
                updateCounters();
            }
        });
        
        // Отслеживание изменения чекбокса фото
        $('#photomonitor').change(function() {
            try {
                var saved = localStorage.getItem('monitorSettings');
                if (saved) {
                    var settings = JSON.parse(saved);
                    settings.photoEnabled = this.checked;
                    localStorage.setItem('monitorSettings', JSON.stringify(settings));
                }
            } catch(e) {}
        });
    });
</script>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    #myTableContainer {
        flex: 1;
        overflow: auto;
        margin-bottom: 40px; 
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        border: 1px solid #dddddd;
        padding: 8px;
        text-align: left;
        position: relative;
        transition: background-color 0.3s;
    }

    th {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #f5f5f5;
    }

    .tooltip .tooltiptext {
        visibility: hidden;
        width: max-content;
        background-color: #555;
        color: #fff;
        text-align: center;
        border-radius: 5px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -75px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    td:hover .tooltip .tooltiptext {
        visibility: visible;
        opacity: 1;
    }

    #additionalInfo {
        padding: 10px;
        position: fixed;
        bottom: 0;
        right: 0;
        display: flex;
        flex-direction: row-reverse;
        align-items: center;
        gap: 20px;
        background: rgba(255,255,255,0.95);
        border-top: 1px solid #ddd;
        border-left: 1px solid #ddd;
        border-radius: 5px 0 0 0;
        padding: 10px 20px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        z-index: 100;
    }

    #currentTime, #eventCounter, #visibleEventCount {
        font-size: 14px;
        color: #333;
        white-space: nowrap;
    }
    
    .modal {
        display: none;
        position: absolute;
        background-color: #fff;
        border: 1px solid #ccc;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    }

    .modal-header {
        cursor: move;
        padding: 10px;
        background-color: #f1f1f1;
        display: flex;
        justify-content: space-between;
    }

    .close-button {
        width: 15px;
        height: 15px;
        cursor: pointer;
        margin-right: 5px;
        background-color: #ff6f6f;
        border: none;
        color: #fff;
        font-weight: bold;
        border-radius: 10px;
    }

    .tabs {
        display: flex;
        margin-top: 10px;
    }

    .tab-button {
        margin-right: 2px;
        padding: 8px 12px;
        border: none;
        background-color: #eee;
        cursor: pointer;
        font-size: 14px;
    }

    .tab-button:hover {
        background-color: #ddd;
    }

    .tab-button.active {
        background-color: #ccc;
    }
    
    .content {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    #txtHint {
        position: relative;
    }
    
    @keyframes highlightRow {
        0% { background-color: #ffff99; }
        100% { background-color: transparent; }
    }
    
    #txtHint tr:first-child {
        animation: highlightRow 2s ease;
    }

    /* Статус WebSocket */
    #websocketStatus {
        font-size: 12px;
        color: #4CAF50;
        padding: 2px 10px;
        border-radius: 10px;
        background: #e8f5e9;
        border: 1px solid #4CAF50;
        display: inline-block;
    }
</style>

<?php 
if ($alert) { ?>
<div class="alert_success">
    <p>
        <img class="mid_align" alt="success" src="images/icon_accept.png" />
        <?php echo $alert; ?>
    </p>
</div>
<?php } ?>

<div class="onecolumn">
    <div class="header">
        <div id="search"<?php if (isset($hidesearch)) echo ' style="display: none;"'; ?>>
            <form action="cards/search_any" method="post">
                <input type="text" class="search noshadow" title="<?php echo __('search'); ?>" name="q" id="q" value="<?php if (isset($filter)) echo $filter; ?>" />
            </form>
        </div>
        <span><?php 
        switch(Session::instance()->get('identifier')){
            case 1:
                echo __('cards.titleRFID'); 
            break;
            case 1:
                echo __('cards.titleGRZ'); 
            break;
            default:
        break;
        }           
        ?></span>
    </div>
    <br class="clear"/>
    <div class="content">
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap; padding: 5px 0;">
            <span><?php echo __('monitor.rows_count'); ?>:</span>
            <select id="selectsSize">
                <option value=10>10</option>
                <option value=20 selected="selected">20</option>
                <option value=30>30</option>
                <option value=50>50</option>
                <option value=100>100</option>
            </select>
            
            <button onclick="createSettingsModal2()" style="
                background: #4a90d9;
                color: white;
                border: none;
                padding: 5px 15px;
                border-radius: 4px;
                cursor: pointer;
                font-weight: bold;
                transition: background 0.3s;
            " onmouseover="this.style.background='#357abd'" onmouseout="this.style.background='#4a90d9'">
                ⚙️ <?php echo __('monitor.settings'); ?>
            </button>
            
            <span><?php echo __('monitor.stop'); ?>:</span>
            <input type="checkbox" id="updatemonitor" title="<?php echo __('monitor.stop'); ?>"/>
            
            <span><?php echo __('monitor.photos'); ?>:</span>
            <input type="checkbox" id="photomonitor" checked />

            <span style="margin-left: auto;">
                <?php echo __('monitor.status'); ?>: <span id="websocketStatus"><?php echo __('monitor.connecting'); ?></span>
            </span>
        </div>
        
        <form id="form_data" name="form_data" action="" method="post">
            <table class="data tablesorter-blue" width="100%" cellpadding="0" cellspacing="0" id="tablesorter">
                <thead>
                    <tr>
                        <th><?php echo __('monitor.id_event'); ?></th>
                        <th><?php echo __('monitor.id_eventtype'); ?></th>
                        <th><?php echo __('monitor.datetime'); ?></th>
                        <th><?php echo __('monitor.eventtype_name'); ?></th>
                        <th><?php echo __('monitor.device_name'); ?></th>
                        <th><?php echo __('monitor.people_name'); ?></th>
                        <th><?php echo __('monitor.organization_name'); ?></th>
                    </tr>
                </thead>     
                <tbody id="txtHint"></tbody>
            </table>

            <div id="chart_wrapper" class="chart_wrapper"></div>
        
            <div id="additionalInfo">
                <div id="currentTime"></div>
                <div id="eventCounter"></div>
                <div id="visibleEventCount"></div>
            </div>
        </form>
    </div>
</div>
