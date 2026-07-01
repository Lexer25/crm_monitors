<script type="text/javascript" src="js/modal-window.js"></script>
<script type="text/javascript" src="js/modal-photo.js"></script>
<script type="text/javascript">
    // Глобальные настройки
    var timeUpdate = 5; // Интервал обновления в секундах
    var windowsCountsetings = 5; // Количество окон с фото
    
    // Базовый URL для API запросов
    var API_BASE_URL = '/city/events/getEvent';
    
    // Функция для создания модального окна настроек
    function createSettingsModal2() {
        // Проверяем, не открыто ли уже окно
        if (document.getElementById('settingsModal')) {
            document.getElementById('settingsModal').style.display = 'block';
            document.getElementById('settingsOverlay').style.display = 'block';
            return;
        }
        
        // Получаем текущие значения
        var currentSelect = document.getElementById('selectsSize');
        var currentSelectValue = currentSelect ? currentSelect.value : 20;
        var currentTimeUpdate = window.timeUpdate || 5;
        var currentPhotoCheck = document.getElementById('photomonitor') ? document.getElementById('photomonitor').checked : true;
        
        // Создаем затемнение фона
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
        
        // Создаем модальное окно
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
                <h3 style="margin: 0; color: #333; font-size: 18px;">⚙️ Настройки монитора</h3>
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
                    Интервал обновления (сек):
                </label>
                <input type="number" id="settingsUpdateInterval" 
                    value="${currentTimeUpdate}" 
                    min="1" max="60" 
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <small style="color: #888;">Рекомендуется: 3-10 секунд</small>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-weight: bold;">
                    Количество отображаемых строк:
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
                    Количество окон с фото:
                </label>
                <input type="number" id="settingsWindowsCount" 
                    value="${windowsCountsetings}" 
                    min="1" max="20" 
                    style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <small style="color: #888;">Максимум 20 окон</small>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: flex; align-items: center; color: #555; cursor: pointer;">
                    <input type="checkbox" id="settingsPhotoEnabled" ${currentPhotoCheck ? 'checked' : ''} style="margin-right: 10px;">
                    Показывать фотографии
                </label>
            </div>
            
            <div style="margin: 15px 0;">
                <label style="display: flex; align-items: center; color: #555; cursor: pointer;">
                    <input type="checkbox" id="settingsAutoScroll" checked style="margin-right: 10px;">
                    Автоматическая прокрутка к новым событиям
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
                    💾 Сохранить
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
                    ❌ Отмена
                </button>
            </div>
        `;
        
        document.body.appendChild(overlay);
        document.body.appendChild(modal);
        
        // Добавляем возможность закрытия по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSettingsModal();
            }
        });
    }
    
    // Функция закрытия окна настроек
    function closeSettingsModal() {
        var modal = document.getElementById('settingsModal');
        var overlay = document.getElementById('settingsOverlay');
        if (modal) modal.remove();
        if (overlay) overlay.remove();
    }
    
    // Функция сохранения настроек
    function saveSettings() {
        try {
            // Получаем значения
            var interval = parseInt(document.getElementById('settingsUpdateInterval').value);
            var rowCount = parseInt(document.getElementById('settingsRowCount').value);
            var windowsCount = parseInt(document.getElementById('settingsWindowsCount').value);
            var photoEnabled = document.getElementById('settingsPhotoEnabled').checked;
            var autoScroll = document.getElementById('settingsAutoScroll').checked;
            
            // Валидация
            if (interval < 1 || interval > 60) {
                alert('Интервал должен быть от 1 до 60 секунд!');
                return;
            }
            if (windowsCount < 1 || windowsCount > 20) {
                alert('Количество окон должно быть от 1 до 20!');
                return;
            }
            
            // Сохраняем в localStorage
            var settings = {
                interval: interval,
                rowCount: rowCount,
                windowsCount: windowsCount,
                photoEnabled: photoEnabled,
                autoScroll: autoScroll
            };
            localStorage.setItem('monitorSettings', JSON.stringify(settings));
            
            // Применяем настройки
            window.timeUpdate = interval;
            window.windowsCountsetings = windowsCount;
            
            // Обновляем select
            var select = document.getElementById('selectsSize');
            if (select) {
                select.value = rowCount;
            }
            
            // Обновляем чекбокс фото
            var photoCheck = document.getElementById('photomonitor');
            if (photoCheck) {
                photoCheck.checked = photoEnabled;
            }
            
            // Перезапускаем интервал обновления
            if (window.updateInterval) {
                clearInterval(window.updateInterval);
                window.updateInterval = setInterval(showUser, timeUpdate * 1000);
            }
            
            // Показываем уведомление
            showNotification('Настройки успешно сохранены!');
            
            // Закрываем окно
            closeSettingsModal();
            
        } catch(e) {
            alert('Ошибка при сохранении настроек: ' + e.message);
        }
    }
    
    // Функция показа уведомления
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
        
        // Добавляем стили анимации
        var style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(notification);
        
        // Автоматическое удаление через 3 секунды
        setTimeout(function() {
            notification.style.animation = 'slideOut 0.5s ease';
            setTimeout(function() {
                notification.remove();
                style.remove();
            }, 500);
        }, 3000);
    }
    
    // Загрузка сохраненных настроек
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
                // Автоскролл пока не реализован
            }
        } catch(e) {
            // Используем значения по умолчанию
        }
    }
    
    // Функция обновления монитора событий
    function showUser() {
        // Проверка кнопки остановки событий
        var stopCheck = document.getElementById("updatemonitor");
        if (stopCheck && stopCheck.checked) return;
        
        // Формирование GET запроса
        var xmlhttp = new XMLHttpRequest();
        var photoneed = document.getElementById("photomonitor") ? document.getElementById("photomonitor").checked : false;
        
        // Обработка GET запроса
        xmlhttp.onreadystatechange = function() {
            // Проверка на наличие событий
            if (this.readyState === 4 && this.status === 200) {
                if (this.responseText == '') return;
                
                // Поиск элементов
                var table = document.getElementById("txtHint");
                var select = document.getElementById("selectsSize");
                if (!table || !select) return;
                
                // Добавить строки к таблице
                table.insertAdjacentHTML('afterbegin', this.responseText);
                
                // Удалить строки из таблицы
                while (table.rows.length > parseInt(select.value)) {
                    table.deleteRow(table.rows.length - 1);
                }
                
                // Формирование карточки с фото
                if (photoneed) {
                    for (let i = 0; i < table.rows.length && i < windowsCountsetings; i++) {
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
            }
        };
        
        // Формируем URL с правильным путем
        var url = API_BASE_URL + '?photo=' + photoneed;
        
        // GET запрос
        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    }
    
    // Функция обновления счетчиков
    function updateCounters() {
        var table = document.getElementById("txtHint");
        if (!table) return;
        
        var totalEvents = table.rows.length;
        
        var counterElement = document.getElementById("eventCounter");
        if (counterElement) {
            counterElement.textContent = 'Всего событий: ' + totalEvents;
        }
        
        var visibleElement = document.getElementById("visibleEventCount");
        if (visibleElement) {
            var select = document.getElementById("selectsSize");
            if (select) {
                visibleElement.textContent = 'Отображается: ' + Math.min(totalEvents, parseInt(select.value));
            }
        }
    }
    
    // Функция обновления времени
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
    
    // Инициализация при загрузке страницы
    $(function() {
        // Инициализация tablesorter
        try {
            $("#tablesorter").tablesorter({ 
                headers: { 7: {sorter: false}},  
                widgets: ['zebra']
            });
        } catch(e) {
            // Tablesorter может быть не загружен
        }
        
        // Загрузка сохраненных настроек
        loadSettings();
        
        // Обновление времени
        updateTime();
        setInterval(updateTime, 1000);
        
        // Обновление счетчиков
        setTimeout(updateCounters, 1000);
        
        // Запуск интервала обновления
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
            // Сохраняем настройку
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
        height: 100vh; /* Занимаем 100% высоты окна браузера */
    }

    #myTableContainer {
        flex: 1; /* Занимаем оставшееся пространство, необходимое для таблицы */
        overflow: auto; /* Добавляем полосы прокрутки, если содержимое больше экрана */
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
        transition: background-color 0.3s; /* Анимация перехода цвета */
    }

    th {
        background-color: #f2f2f2;
    }

    tr:hover {
        background-color: #f5f5f5; /* Цвет подсветки при наведении */
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

    /* Новые стили для строки с дополнительной информацией */
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
        color: #333; /* Цвет текста */
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
    
    /* Дополнительные стили для улучшения интерфейса */
    .content {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    #txtHint {
        position: relative;
    }
    
    /* Анимация для новых строк */
    @keyframes highlightRow {
        0% { background-color: #ffff99; }
        100% { background-color: transparent; }
    }
    
    #txtHint tr:first-child {
        animation: highlightRow 2s ease;
    }
</style>
<?php 
//https://webformyself.com/sortirovka-tablic-pri-pomoshhi-plagina-tablesorter-js/?ysclid=lrgdz4nrzp693511651
// список идентификаторов
//echo Debug::vars('2', $cards); //exit;
//echo Debug::vars('2-2', $cardsList); //exit;
//echo Debug::vars('16', array_diff($cards, $cardsList));//exit;
//echo Debug::vars('12', $cardsList); //exit;
//echo Debug::vars('2', $catdTypelist); //exit;
//echo Debug::vars('3', $alert); //exit;
//echo Debug::vars('4', $filter); //exit;
//echo Debug::vars('5', $pagination); //exit;
//define ('_notAllowed', "HTML::image('images/text_lock.png', array('title' => __('tip.notAllowed'), 'width'=>'32'))");
//include Kohana::find_file('views','alert');
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
    Количество Событий: 
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
        ⚙️ Настройки
    </button>
    Остановить:
    <input type="checkbox" id="updatemonitor" title="Нажмите Пробел для остановки/запуска"/>
    Фотографии:
    <input type="checkbox" id="photomonitor" checked />
        <form id="form_data" name="form_data" action="" method="post">
            <table class="data tablesorter-blue" width="100%" cellpadding="0" cellspacing="0" id="tablesorter">
            <thead>
                    <tr>
                        <th>ID_EVENT</th>
                        <th>ID_EVENTTYPE</th>
                        <th>DATETIME</th>
                        <th>EVENTTYPE_NAME</th>
                        <th>DEVICE_NAME</th>
                        <th>PEOPLE_NAME</th>
                        <th>ORGANIZATION_NAME</th>
                    </tr>
            </thead>     
            <tbody id="txtHint"/>
            </table>

            <div id="chart_wrapper" class="chart_wrapper"></div>
        
                <div id="additionalInfo">
    <div id="currentTime"></div>
    <div id="eventCounter"></div>
    <div id="visibleEventCount"></div>
</div>
    </div>
</div>