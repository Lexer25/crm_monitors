<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Контроллер монитора событий
 */
class Controller_monitors extends Controller_Template {
    
    public $view = 'result';
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
    public function action_index($filter = null)
    {
        $fl = $this->session->get('alert');
        $this->session->delete('alert');
        
        $this->template->content = View::factory('list')
            ->bind('alert', $fl)
            ->bind('arrAlert', $arrAlert);
    }
    
    /**
     * Получение ID следующего события из генератора
     * @return int
     */
    private function getid()
    {
        $sql = 'SELECT GEN_ID(gen_event_id, 0) FROM RDB$DATABASE';
        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->current();
        return (int)$query['GEN_ID'];
    }
    
    /**
     * Выборка событий из базы данных
     * @param int $id - ID события, начиная с которого выбирать
     * @param bool $photo - нужно ли загружать фото
     * @return array
     */
    private function selectevent($id, $photo)
    {
        $sqlphoto = $photo ? 'p.photo,' : '';
        
        $sql = 'SELECT FIRST 30 
            e.id_event, 
            e.id_eventtype, 
            e.datetime, 
            et.color, 
            et.name as eventtype_name, 
            d.name as device_name, 
            p.surname, 
            p.surname||\' \'|| p.name||\' \'|| p.patronymic as people_name,
            ' . $sqlphoto . ' 
            p.post, 
            o.name as organization_name
        FROM device d
        JOIN events e ON e.id_dev = d.id_dev
        JOIN eventtype et ON et.id_eventtype = e.id_eventtype
        LEFT JOIN people p ON p.id_pep = e.ess1
        LEFT JOIN organization o ON o.id_org = e.ess2
        WHERE e.id_event > ' . (int)$id;
        
        Log::instance()->add(Log::DEBUG, 'SQL: ' . $sql);
        
        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->as_array();
        
        return $query;
    }
    
    /**
     * API получения событий в реальном времени
     * URL: /monitors/getEvent?photo=true
     * Используется в JavaScript для long-polling
     */
    public function action_getEvent()
    {
        // Отключаем шаблон для AJAX-запросов
        $this->auto_render = false;
        
        // Устанавливаем заголовок JSON (хотя возвращаем HTML)
        $this->response->headers('Content-Type', 'text/html; charset=utf-8');
        
        $t1 = microtime(true);
        $photo = filter_var($this->request->query('photo'), FILTER_VALIDATE_BOOLEAN);
        $getPhoto = $photo ? 'true' : 'false';
        
        $this->response->body('');
        
        try {
            // Получаем последний ID из куки
            $id = Cookie::get('id');
            Log::instance()->add(Log::DEBUG, 'ID from cookie: ' . $id);
            
            // Если куки нет - получаем текущий ID из генератора и отступаем на 20
            if ($id === null) {
                $id = $this->getid() - 20;
                Cookie::set('id', $id);
                Log::instance()->add(Log::DEBUG, 'Set initial cookie ID: ' . $id);
            }
            
            // Получаем события
            $tab = $this->selectevent($id, $photo);
            
            if (count($tab) == 0) {
                Log::instance()->add(Log::DEBUG, 'No new events. From: ' . $id . ', Count: 0, Photo: ' . $getPhoto . ', Time: ' . round((microtime(true) - $t1), 3) . 's');
                $this->response->body('');
                return;
            }
            
            // Сортируем в обратном порядке (новые сверху)
            $tab = array_reverse($tab);
            
            $body = '';
            
            foreach ($tab as $row) {
                // Формируем стиль с цветом
                $color = isset($row['COLOR']) ? dechex($row['COLOR']) : 'FFFFFF';
                $style = 'color: black; background-color: #' . str_pad($color, 6, '0', STR_PAD_LEFT) . ';';
                
                // Фото
                $bodyphoto = '';
                if ($photo && !empty($row['PHOTO'])) {
                    try {
                        $photoData = base64_encode(pack("H*", str_replace("\0", "", $row['PHOTO'])));
                        $bodyphoto = '<td id="photo" style="' . $style . 'display:none;">' . $photoData . '</td>';
                    } catch (Exception $e) {
                        Log::instance()->add(Log::DEBUG, 'Photo decode error: ' . $e->getMessage());
                    }
                }
                
                // Конвертация кодировок
                $post = isset($row['POST']) ? iconv('CP1251', 'UTF-8//IGNORE', $row['POST']) : '';
                $eventtype_name = isset($row['EVENTTYPE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $row['EVENTTYPE_NAME']) : '';
                $device_name = isset($row['DEVICE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $row['DEVICE_NAME']) : '';
                $people_name = isset($row['PEOPLE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $row['PEOPLE_NAME']) : '';
                $organization_name = isset($row['ORGANIZATION_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $row['ORGANIZATION_NAME']) : '';
                
                $body .= '<tr>
                    ' . $bodyphoto . '
                    <td id="people_post" style="' . $style . 'display:none;">' . $post . '</td>
                    <td style="' . $style . '">' . $row['ID_EVENT'] . '</td>
                    <td id="event_type" style="' . $style . '">' . $row['ID_EVENTTYPE'] . '</td>
                    <td style="' . $style . '">' . $row['DATETIME'] . '</td>
                    <td id="even_name" style="' . $style . '">' . $eventtype_name . '</td>
                    <td id="device_name" style="' . $style . '">' . $device_name . '</td>
                    <td id="people_name" style="' . $style . '">' . $people_name . '</td>
                    <td id="org_name" style="' . $style . '">' . $organization_name . '</td>
                </tr>';
            }
            
            // Сохраняем в куку максимальный ID
            $maxId = $tab[0]['ID_EVENT'];
            Cookie::set('id', $maxId);
            
            Log::instance()->add(Log::DEBUG, 
                'Success. From: ' . $id . ', Count: ' . count($tab) . 
                ', Save to cookie: ' . $maxId . 
                ', Photo: ' . $getPhoto . 
                ', Time: ' . round((microtime(true) - $t1), 3) . 's'
            );
            
            $this->response->body($body);
            
        } catch (Exception $e) {
            Log::instance()->add(Log::ERROR, 'Error in action_getEvent: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->response->body('');
        }
    }
}