<?php defined('SYSPATH') or die('No direct script access.'); ?>

<style>
    body { font-family: Arial; margin: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .control-panel { margin-bottom: 20px; padding: 10px; background: #f0f0f0; }
    .control-group { display: inline-block; margin-right: 15px; }
    #txtHint tr:hover { background-color: #f5f5f5; cursor: pointer; }
    #status { margin-left: 15px; font-weight: bold; }
    .header span { font-size: 20px; font-weight: bold; }
</style>

<?php include Kohana::find_file('views', 'alert'); ?>

<?php if ($alert): ?>
<div class="alert_success">
    <p><img alt="success" src="images/icon_accept.png" /> <?php echo htmlspecialchars($alert); ?></p>
</div>
<?php endif; ?>

<div class="onecolumn">
    <div class="header">
        <span>Монитор событий</span>
    </div>
    
    <div class="control-panel">
        <div class="control-group">
            <label>Количество:</label>
            <select id="selectsSize">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="30">30</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        
        <div class="control-group">
            <label>
                <input type="checkbox" id="updatemonitor" /> Остановить
            </label>
        </div>
        
        <div class="control-group">
            <label>
                <input type="checkbox" id="photomonitor" checked /> Фото
            </label>
        </div>
        
        <button id="clearBtn">Очистить</button>
        <span id="status">● Работает</span>
    </div>
    
    <div style="overflow: auto; max-height: 70vh;">
        <table class="data">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>Дата/Время</th>
                    <th>Событие</th>
                    <th>Устройство</th>
                    <th>Сотрудник</th>
                    <th>Организация</th>
                </tr>
            </thead>
            <tbody id="txtHint">
                <tr><td colspan="7" style="text-align:center;">Загрузка событий...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Монитор событий
var isRunning = true;
var maxRows = 20;
var withPhoto = true;
var updateInterval = null;

function loadSettings() {
    var sizeSelect = document.getElementById('selectsSize');
    if (sizeSelect) {
        maxRows = parseInt(sizeSelect.value);
    }
    var photoCheck = document.getElementById('photomonitor');
    if (photoCheck) {
        withPhoto = photoCheck.checked;
    }
}

function clearEvents() {
    var tbody = document.getElementById('txtHint');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">Загрузка событий...</td></tr>';
    }
}

function addEventsToTable(html) {
    if (!html || html.trim() === '') return;
    
    var tbody = document.getElementById('txtHint');
    if (!tbody) return;
    
    // Удаляем сообщение "Загрузка..." если оно есть
    if (tbody.children.length === 1 && tbody.children[0].innerHTML.indexOf('Загрузка') !== -1) {
        tbody.innerHTML = '';
    }
    
    // Вставляем новые события в начало
    tbody.insertAdjacentHTML('afterbegin', html);
    
    // Ограничиваем количество строк
    while (tbody.rows.length > maxRows) {
        tbody.deleteRow(tbody.rows.length - 1);
    }
    
    // Анимация новых строк
    for (var i = 0; i < Math.min(3, tbody.rows.length); i++) {
        if (tbody.rows[i]) {
            tbody.rows[i].style.backgroundColor = '#ffffcc';
            setTimeout(function(row) {
                return function() {
                    if (row) row.style.backgroundColor = '';
                };
            }(tbody.rows[i]), 1000);
        }
    }
    
    // Создаем модальные окна для фото
    if (withPhoto && typeof createModal === 'function') {
        var limit = Math.min(tbody.rows.length, window.windowsCountsetings || 5);
        for (var i = 0; i < limit; i++) {
            var row = tbody.rows[i];
            var photoCell = row.cells.namedItem('photo');
            if (photoCell && photoCell.innerText) {
                createModal(
                    row.cells.namedItem('even_name') ? row.cells.namedItem('even_name').innerText : '',
                    photoCell.innerText,
                    row.cells.namedItem('people_name') ? row.cells.namedItem('people_name').innerText : '',
                    row.cells.namedItem('org_name') ? row.cells.namedItem('org_name').innerText : '',
                    row.cells.namedItem('people_post') ? row.cells.namedItem('people_post').innerText : '',
                    row.cells.namedItem('device_name') ? row.cells.namedItem('device_name').innerText : ''
                );
            }
        }
    }
}

function fetchEvents() {
	console.log('=== fetchEvents вызван ===');
    console.log('isRunning:', isRunning);
	
    if (!isRunning) return;
    
    var url = '/crm2/monitors/getEvent?photo=' + (withPhoto ? '1' : '0') + '&format=json&limit=' + maxRows;
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.timeout = 10000;
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                console.log('=== ОТВЕТ СЕРВЕРА ===');
                console.log('response:', response);
                console.log('response.hasNew:', response.hasNew);
                console.log('response.events:', response.events);
                
                if (response.success && response.hasNew) {
                    var html = response.html;
                    if (!html && response.events) {
                        console.log('Вызываем renderEventsToHtml');
                        html = renderEventsToHtml(response.events);
                        console.log('HTML получен, длина:', html ? html.length : 0);
                    }
                    if (html) {
                        addEventsToTable(html);
                    }
                }
            } catch (e) {
                console.error('Ошибка:', e);
            }
        }
    };
    
    xhr.send();
}

function renderEventsToHtml(events) {
    var html = '';
    for (var i = 0; i < events.length; i++) {
        var e = events[i];
        
        var colorValue = parseInt(e.color, 10);
        if (isNaN(colorValue)) colorValue = 0;
        
        var colorHex = colorValue.toString(16);
        // Дополняем до 6 символов
        while (colorHex.length < 6) colorHex = '0' + colorHex;
        
        var bgColor = '#' + colorHex;
        var textColor = getContrastColor(colorHex);
        var style = 'color: ' + textColor + '; background-color: ' + bgColor + ';';
        
        html += '<tr>';
        html += '<td style="' + style + '">' + escapeHtml(String(e.id_event || '')) + '</td>';
        html += '<td id="event_type" style="' + style + '">' + escapeHtml(String(e.id_eventtype || '')) + '</td>';
        html += '<td style="' + style + '">' + escapeHtml(String(e.datetime || '')) + '</td>';
        html += '<td id="even_name" style="' + style + '">' + escapeHtml(String(e.eventtype_name || '')) + '</td>';
        html += '<td id="device_name" style="' + style + '">' + escapeHtml(String(e.device_name || '')) + '</td>';
        html += '<td id="people_name" style="' + style + '">' + escapeHtml(String(e.people_name || '')) + '</td>';
        html += '<td id="org_name" style="' + style + '">' + escapeHtml(String(e.organization_name || '')) + '</td>';
        html += '</tr>';
    }
    return html;
}

// Функция для определения контрастного цвета текста (белый или черный)
function getContrastColor(hexColor) {
    // Преобразуем HEX в RGB
    var r = parseInt(hexColor.substr(0, 2), 16);
    var g = parseInt(hexColor.substr(2, 2), 16);
    var b = parseInt(hexColor.substr(4, 2), 16);
    
    // Формула яркости (Y = 0.299R + 0.587G + 0.114B)
    var brightness = (r * 0.299 + g * 0.587 + b * 0.114);
    
    // Если яркость выше 128, используем черный текст, иначе белый
    return brightness > 128 ? '#000000' : '#ffffff';
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function setupEventHandlers() {
    var stopCheckbox = document.getElementById('updatemonitor');
    if (stopCheckbox) {
        stopCheckbox.onchange = function() {
            isRunning = !this.checked;
            var statusSpan = document.getElementById('status');
            if (statusSpan) {
                statusSpan.innerHTML = isRunning ? '● Работает' : '○ Остановлен';
                statusSpan.style.color = isRunning ? 'green' : 'red';
            }
            if (isRunning) {
                fetchEvents();
            }
        };
    }
    
    var sizeSelect = document.getElementById('selectsSize');
    if (sizeSelect) {
        sizeSelect.onchange = function() {
            maxRows = parseInt(this.value);
            var tbody = document.getElementById('txtHint');
            if (tbody) {
                while (tbody.rows.length > maxRows) {
                    tbody.deleteRow(tbody.rows.length - 1);
                }
            }
        };
    }
    
    var photoCheckbox = document.getElementById('photomonitor');
    if (photoCheckbox) {
        photoCheckbox.onchange = function() {
            withPhoto = this.checked;
        };
    }
    
    var clearBtn = document.getElementById('clearBtn');
    if (clearBtn) {
        clearBtn.onclick = clearEvents;
    }
    
    // Пробел для остановки/запуска
    document.onkeypress = function(e) {
        if (e.keyCode === 32 || e.key === ' ') {
            if (stopCheckbox) {
                stopCheckbox.checked = !stopCheckbox.checked;
                if (stopCheckbox.onchange) {
                    stopCheckbox.onchange();
                }
            }
            e.preventDefault();
        }
    };
}

function startMonitor() {
    loadSettings();
    setupEventHandlers();
    fetchEvents();
    
    var intervalTime = (window.timeUpdate || 2) * 1000;
    if (updateInterval) {
        clearInterval(updateInterval);
    }
    updateInterval = setInterval(function() {
        if (isRunning) {
            fetchEvents();
        }
    }, intervalTime);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startMonitor);
} else {
    startMonitor();
}
</script>