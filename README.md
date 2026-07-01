"# crm_monitors" 
monitors/
├── classes/
│   ├── controller/
│   │   └── monitors.php              # Контроллер
│   ├── Model/
│   │   └── MonitorM.php              # Модель (уникальное имя!)
│   ├── Helper/
│   │   └── WebSocket.php             # Хелпер
│   └── Task/
│       └── eventsInsert.php          # Для тестирования
├── config/
│   └── monitors.php                  # Конфигурация
├── views/
│   └── list.php                      # Шаблон
└── init.php

cd C:\xampp\htdocs\city
php websocket_server.php

Очистка кэша Kohana (если что-то не обновляется):
rm -rf application/cache/*