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
        
        // Проверяем доступность WebSocket
        $wsAvailable = false;
        if (class_exists('Helper_WebSocket')) {
            $wsAvailable = Helper_WebSocket::isAvailable();
        }
        
        $this->template->content = View::factory('list')
            ->bind('alert', $fl)
            ->bind('ws_available', $wsAvailable);
    }
    
    /**
     * API получения событий в реальном времени
     * URL: /monitors/getEvent?photo=true
     */
    public function action_getEvent()
    {
        // Отключаем шаблон для AJAX-запросов
        $this->auto_render = false;
        
        // Устанавливаем правильный заголовок
        $this->response->headers('Content-Type', 'text/html; charset=utf-8');
        
        try {
            $photo = filter_var($this->request->query('photo'), FILTER_VALIDATE_BOOLEAN);
            
            // Используем модель MonitorM
            $model = new Model_MonitorM();
            
            // Получаем ID из куки
            $id = Cookie::get('id');
            
            if ($id === null) {
                $id = $model->getNextId() - 20;
                Cookie::set('id', $id);
            }
            
            // Получаем события
            $events = $model->getEvents($id, $photo);
            
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
            Cookie::set('id', $events[0]['ID_EVENT']);
            
            $this->response->body($body);
            
        } catch (Exception $e) {
            Log::instance()->add(Log::ERROR, 'Controller_monitors::action_getEvent: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->response->body('');
        }
    }
}