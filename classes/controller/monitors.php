<?php defined('SYSPATH') or die('No direct script access.');

class Controller_monitors extends Controller_Template { 
    
    public $view = 'result';
    public $template = 'template';
    
    private $db;
    
    public function before()
    {
        parent::before();
        
        if ($this->request->action() === 'getEvent') {
            $this->auto_render = false;
            $this->response->headers('Content-Type', 'application/json');
        }
        
        try {
            $this->db = Database::instance('fb');
        } catch (Exception $e) {
            Log::instance()->add(Log::ERROR, 'DB connection error: ' . $e->getMessage());
        }
    }
    
    public function action_index($filter = null)
    {
        $fl = $this->session->get('alert');
        $this->session->delete('alert');
        
        $this->template->content = View::factory('list')
            ->bind('alert', $fl)
            ->bind('arrAlert', $arrAlert);
    }
    
		public function action_getEvent()
		{
			try {
				// Получаем параметры напрямую из $_GET
				$photo = isset($_GET['photo']) ? filter_var($_GET['photo'], FILTER_VALIDATE_BOOLEAN) : false;
				$format = isset($_GET['format']) ? $_GET['format'] : 'html';
				$limitParam = isset($_GET['limit']) ? (int) $_GET['limit'] : 30;
				$limit = min(100, max(1, $limitParam));
				
				// Получаем последний ID из куки
				$lastId = $this->getLastEventId();
				
				// Получаем новые события
				$events = $this->getNewEvents($lastId, $photo, $limit);
				
				// Формируем ответ
				$response = array(
					'success' => true,
					'hasNew' => !empty($events),
					'count' => count($events),
					'lastId' => $lastId
				);
				
				if (!empty($events)) {
					$maxId = $lastId;
					foreach ($events as $event) {
						if ($event['id_event'] > $maxId) {
							$maxId = $event['id_event'];
						}
					}
					if ($maxId > $lastId) {
						Cookie::set('event_last_id', $maxId, Date::WEEK);
						$response['lastId'] = $maxId;
					}
					
					if ($format === 'html') {
						$response['html'] = $this->renderEventsHtml($events, $photo);
					} else {
						$response['events'] = $events;
						// Добавьте также html для совместимости с JS
						$response['html'] = $this->renderEventsHtml($events, $photo);
					}
				}
				
				// Очищаем буфер вывода перед отправкой JSON
				if (ob_get_level()) {
					ob_clean();
				}
				
				$this->response->headers('Content-Type', 'application/json');
				$this->response->body(json_encode($response, JSON_UNESCAPED_UNICODE));
				
			} catch (Exception $e) {
				// Очищаем буфер вывода
				if (ob_get_level()) {
					ob_clean();
				}
				
				$this->response->headers('Content-Type', 'application/json');
				$this->response->body(json_encode(array(
					'success' => false,
					'error' => $e->getMessage()
				)));
			}
		}

    
    private function getLastEventId()
    {
        $lastId = Cookie::get('event_last_id');
        if ($lastId === null || !is_numeric($lastId)) {
            // Прямой SQL запрос для получения максимального ID
           $lastId = $this->getCurrentMaxEventId();
            if ($lastId > 0) {
                Cookie::set('event_last_id', $lastId, Date::WEEK);
            } else {
                $lastId = 0;
            }
        }
        
        return (int) $lastId;
    }
    
    /**
     * Получение максимального ID события прямым SQL запросом
     */
    private function getCurrentMaxEventId()
    {
        if (!$this->db) {
            return 0;
        }
        
        try {
            // Прямой SQL запрос
            //$sql = "SELECT MAX(id_event) as max_id FROM events";
            $sql = 'SELECT GEN_ID(gen_event_id, 0) AS max_id FROM RDB$DATABASE';
            $query = DB::query(Database::SELECT, $sql)
                ->execute($this->db)
                ->current();
            
            if ($query && isset($query['MAX_ID'])) {
                return (int) $query['MAX_ID'];
            }
            return 0;
        } catch (Exception $e) {
            Log::instance()->add(Log::ERROR, 'Failed to get max event ID: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Получение новых событий прямым SQL запросом (без параметров)
     */
    private function getNewEvents($lastId, $withPhoto, $limit)
    {
        if (!$this->db) {
            return array();
        }
        
        $photoField = $withPhoto ? 'p.photo, ' : '';
        
        // Безопасное экранирование
        $safeLastId = (int) ($lastId);
        $safeLimit = (int) $limit;
        
        // Прямой SQL запрос без параметров
        $sql = "SELECT FIRST {$safeLimit} 
                    e.id_event,
                    e.id_eventtype,
                    e.datetime,
                    et.color,
                    et.name as eventtype_name,
                    d.name as device_name,
                    COALESCE(p.surname || ' ' || p.name || ' ' || p.patronymic, '') as people_name,
                    {$photoField}
                    COALESCE(p.post, '') as post,
                    COALESCE(o.name, '') as organization_name
                FROM events e
                LEFT JOIN eventtype et ON et.id_eventtype = e.id_eventtype
                LEFT JOIN device d ON d.id_dev = e.id_dev
                LEFT JOIN people p ON p.id_pep = e.ess1
                LEFT JOIN organization o ON o.id_org = e.ess2
                WHERE e.id_event > {$safeLastId}
                ORDER BY e.id_event ASC";
				
    
  Log::instance()->add(Log::ERROR, $sql);
        try {
            $query = DB::query(Database::SELECT, $sql)
                ->execute($this->db)
                ->as_array();
          
            return $this->normalizeEvents($query, $withPhoto);
            
        } catch (Exception $e) {
            Log::instance()->add(Log::ERROR, 'Query error: ' . $e->getMessage());
            Log::instance()->add(Log::ERROR, 'SQL: ' . $sql);
            return array();
        }
    }
    
    private function normalizeEvents($events, $withPhoto)
    {
        $result = array();
        foreach ($events as $event) {
            $item = array(
                'id_event' => (int) (isset($event['ID_EVENT']) ? $event['ID_EVENT'] : 0),
                'id_eventtype' => (int) (isset($event['ID_EVENTTYPE']) ? $event['ID_EVENTTYPE'] : 0),
                'datetime' => isset($event['DATETIME']) ? $event['DATETIME'] : '',
                'color' => (int) (isset($event['COLOR']) ? $event['COLOR'] : 0),
                'eventtype_name' => $this->decodeCp1251(isset($event['EVENTTYPE_NAME']) ? $event['EVENTTYPE_NAME'] : ''),
                'device_name' => $this->decodeCp1251(isset($event['DEVICE_NAME']) ? $event['DEVICE_NAME'] : ''),
                'people_name' => $this->decodeCp1251(isset($event['PEOPLE_NAME']) ? $event['PEOPLE_NAME'] : ''),
                'organization_name' => $this->decodeCp1251(isset($event['ORGANIZATION_NAME']) ? $event['ORGANIZATION_NAME'] : ''),
                'post' => $this->decodeCp1251(isset($event['POST']) ? $event['POST'] : '')
            );
            
            if ($withPhoto && isset($event['PHOTO'])) {
                $item['photo'] = $event['PHOTO'];
            }
            
            $result[] = $item;
        }
        return $result;
    }
    
    private function decodeCp1251($string)
    {
        if (empty($string)) {
            return '';
        }
        $decoded = iconv('CP1251', 'UTF-8//IGNORE', $string);
        return $decoded !== false ? $decoded : $string;
    }
    
    private function renderEventsHtml($events, $withPhoto)
    {
        $html = '';
    foreach ($events as $event) {
        // Преобразуем цвет в HEX с 6 символами
        $colorHex = dechex($event['color']);
        $colorHex = str_pad($colorHex, 6, '0', STR_PAD_LEFT);  // Дополняем до 6 символов
        
        $bgColor = '#' . $colorHex;
        
        // Определяем цвет текста
        $r = hexdec(substr($colorHex, 0, 2));
        $g = hexdec(substr($colorHex, 2, 2));
        $b = hexdec(substr($colorHex, 4, 2));
        $brightness = ($r * 0.299 + $g * 0.587 + $b * 0.114);
        $textColor = $brightness > 128 ? '#000000' : '#ffffff';
        
        $style = 'color: ' . $textColor . '; background-color: ' . $bgColor . ';';
			
            
            $photoCell = '';
            if ($withPhoto && !empty($event['photo'])) {
                $cleanPhoto = str_replace("\0", "", $event['photo']);
                $photoData = base64_encode(pack("H*", $cleanPhoto));
                $photoCell = '<td id="photo" style="' . $style . 'display:none;">' . $photoData . '</td>';
            }
            
            $post = isset($event['post']) ? $event['post'] : '';
            $idEvent = isset($event['id_event']) ? $event['id_event'] : '';
            $idEventtype = isset($event['id_eventtype']) ? $event['id_eventtype'] : '';
            $datetime = isset($event['datetime']) ? $event['datetime'] : '';
            $eventtypeName = isset($event['eventtype_name']) ? $event['eventtype_name'] : '';
            $deviceName = isset($event['device_name']) ? $event['device_name'] : '';
            $peopleName = isset($event['people_name']) ? $event['people_name'] : '';
            $orgName = isset($event['organization_name']) ? $event['organization_name'] : '';
            
            $html .= '<tr>
                ' . $photoCell . '
                <td id="people_post" style="' . $style . 'display:none;">' . htmlspecialchars($post) . '</td>
                <td style="' . $style . '">' . htmlspecialchars($idEvent) . '</td>
                <td id="event_type" style="' . $style . '">' . htmlspecialchars($idEventtype) . '</td>
                <td style="' . $style . '">' . htmlspecialchars($datetime) . '</td>
                <td id="even_name" style="' . $style . '">' . htmlspecialchars($eventtypeName) . '</td>
                <td id="device_name" style="' . $style . '">' . htmlspecialchars($deviceName) . '</td>
                <td id="people_name" style="' . $style . '">' . htmlspecialchars($peopleName) . '</td>
                <td id="org_name" style="' . $style . '">' . htmlspecialchars($orgName) . '</td>
            </tr>';
        }
        return $html;
    }
}
