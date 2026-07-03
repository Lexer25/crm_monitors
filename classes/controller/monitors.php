<?php defined('SYSPATH') or die('No direct script access.');

class Controller_monitors extends Controller_Template {
    
    public $template = 'template';
    public $session;
    
    public function before()
    {
        parent::before();
        $this->session = Session::instance();
    }

    /**
     * Главная страница монитора
     * URL: /monitors
     */
    public function action_index()
    {
        $fl = $this->session->get('alert');
        $this->session->delete('alert');
        
        // Получаем конфиг как массив
        $config = Kohana::$config->load('monitors')->as_array();
        
        // Берем значение
        $wsEnabled = isset($config['websocket']['enabled']) ? (bool)$config['websocket']['enabled'] : false;
        
        if ($wsEnabled && class_exists('Helper_WebSocket')) {
            $wsAvailable = Helper_WebSocket::isAvailable();
        } else {
            $wsAvailable = false;
        }
        
        // Получаем группы устройств
        $model = new Model_MonitorM();
        $deviceGroups = $model->getDeviceGroups();
        
        // Получаем выбранную группу из сессии
        $selectedGroup = $this->session->get('selected_device_group', 0);
        
        $this->template->content = View::factory('list')
            ->bind('alert', $fl)
            ->bind('ws_available', $wsAvailable)
            ->bind('ws_enabled', $wsEnabled)
            ->bind('device_groups', $deviceGroups)
            ->bind('selected_group', $selectedGroup);
    }
    
    /**
     * API получения событий в реальном времени
     * URL: /monitors/getEvent?photo=true&group=1
     */
    public function action_getEvent()
    {
        // Отключаем шаблон для AJAX-запросов
        $this->auto_render = false;
        
        // Устанавливаем правильный заголовок
        $this->response->headers('Content-Type', 'text/html; charset=utf-8');
  
//Kohana::$log->add(Log::DEBUG, '62 ' . Debug::vars($_GET)); 

        try {
            // Получаем параметры из запроса
            $photo = filter_var($this->request->query('photo'), FILTER_VALIDATE_BOOLEAN);
        
		   $groupId = (int)$this->request->param('group', 0);
		   $groupId = (int)Arr::get($_GET, 'group', 0);
  
            // Логируем запрос
            Kohana::$log->add(Log::DEBUG, '71 action_getEvent: photo=' . ($photo ? 'true' : 'false') . ', group=' . $groupId);
            
            // Сохраняем выбранную группу в сессию
            if ($groupId > 0) {
                $this->session->set('selected_device_group', $groupId);
            } else {
                $this->session->delete('selected_device_group');
            }
            
            // Используем модель MonitorM
            $model = new Model_MonitorM();
            
            // Получаем ID из куки
            $id = Cookie::get('id');
            
           // Kohana::$log->add(Log::DEBUG, '86 action_getEvent: ID из куки = ' . ($id !== null ? $id : 'null'));
            
            if ($id === null) {
                // Если куки нет, берем последние 20 событий
                $maxId = $model->getNextId() - 1;
                $id = max(0, $maxId - 20);
                Cookie::set('id', $id);
               // Kohana::$log->add(Log::DEBUG, '93 action_getEvent: Установлен новый ID = ' . $id);
            }
            
            // Приводим ID к целому числу
            $id = (int)$id;
            
            // Получаем события с фильтрацией по группе
            $events = $model->getEvents($id, $photo, 30, $groupId > 0 ? $groupId : null);
            
           // Kohana::$log->add(Log::DEBUG, '102 action_getEvent: Найдено событий = ' . count($events));
            
            if (empty($events)) {
                $this->response->body('');
                return;
            }
            
            // Формируем HTML
            $body = '';
            foreach ($events as $event) {
                $body .= $model->renderEventRow($event, $photo);
            }
            
            // Сохраняем в куку максимальный ID
            $newId = (int)$events[0]['ID_EVENT'];
            Cookie::set('id', $newId);
            //Kohana::$log->add(Log::DEBUG, '118 action_getEvent: Обновлен ID в куки = ' . $newId);
            
            $this->response->body($body);
            
        } catch (Exception $e) {
           // Kohana::$log->add(Log::ERROR, '123Controller_monitors::action_getEvent: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->response->body('');
        }
    }
    
    /**
     * API для сохранения выбранной группы устройств
     * URL: /monitors/setGroup?group=1
     */
    public function action_setGroup()
    {
        $this->auto_render = false;
        $this->response->headers('Content-Type', 'application/json; charset=utf-8');
        
        try {
            // Получаем параметр group из запроса
            $groupId = (int)$this->request->query('group', 0);
            
            Kohana::$log->add(Log::DEBUG, 'action_setGroup: group=' . $groupId);
            
            if ($groupId > 0) {
                $this->session->set('selected_device_group', $groupId);
            } else {
                $this->session->delete('selected_device_group');
            }
            
            $this->response->body(json_encode([
                'success' => true,
                'group_id' => $groupId
            ]));
            
        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Controller_monitors::action_setGroup: ' . $e->getMessage());
            $this->response->body(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }
}
