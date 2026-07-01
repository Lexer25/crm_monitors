<?php defined('SYSPATH') or die('No direct script access.');

class Task_EventsInsert extends Minion_Task {
    
    protected $_options = array(
        'count'  => 10,
        'delay'  => 2,
        'type'   => 27,
        'people' => 1,
        'note'   => '17 building test',
    );
    
    protected function _execute(array $params) 
    {
        $count = isset($params['count']) ? (int)$params['count'] : 10;
        $delay = isset($params['delay']) ? (int)$params['delay'] : 2;
        
        echo 'Начинаем вставку ' . $count . ' событий...' . "\n";
        echo '------------------------------------' . "\n";
        
        $successCount = 0;
        $errorCount = 0;
        
        for ($i = 1; $i <= $count; $i++) {
            try {
                
				$new_id = DB::query(Database::SELECT, 'SELECT GEN_ID(GEN_EVENT_ID, 1) as gen FROM RDB$DATABASE')->execute(Database::instance('fb'))->get('GEN');
	//	echo Debug::vars('28', $new_id);exit;
				$name = 'test-' . $i;
                $sql = "INSERT INTO events (ID_EVENT, ID_DB, ID_EVENTTYPE, ID_DEV, ID_PLAN, DATETIME, ID_CARD, NOTE, ID_VIDEO, ID_PEP, ESS1, ESS2)
                        VALUES ({$new_id}, 1, 50, 574, NULL, current_timestamp, '1484F8001A', '{$name}', NULL, NULL, 22877, 1)";
                
                $result = DB::query(Database::INSERT, $sql)
                    ->execute(Database::instance('fb'));
                
                $eventId = isset($result[0]) ? $result[0] : null;
                $eventId = $new_id;
                
                if ($eventId) {
                    $successCount++;
                    echo '[' . $i . '/' . $count . '] Событие добавлено. ID: ' . $eventId . "\n";
                    
                    // ==========================================
                    // ОТПРАВКА В WEBSOCKET
                    // ==========================================
                    echo '    🔄 Отправка в WebSocket...' . "\n";
                    
                    try {
                        // Получаем данные события
                        $eventData = $this->getEventData($eventId);
                        
                        if ($eventData) {
                            $html = $this->renderEventRow($eventData);
                            
                            if ($html) {
                                // Прямая отправка через сокет (без библиотеки)
                                $result = $this->sendToWebSocket($html);
                                
                                if ($result) {
                                    echo '    ✅ Отправлено в WebSocket' . "\n";
                                } else {
                                    echo '    ❌ Ошибка отправки (сервер не доступен)' . "\n";
                                }
                            } else {
                                echo '    ❌ HTML не сгенерирован' . "\n";
                            }
                        } else {
                            echo '    ❌ Данные события не найдены' . "\n";
                        }
                        
                    } catch (Exception $e) {
                        echo '    ❌ Ошибка: ' . $e->getMessage() . "\n";
                    }
                    
                } else {
                    $errorCount++;
                    echo '[' . $i . '/' . $count . '] ❌ Ошибка: ID не получен' . "\n";
                }
                
                if ($i < $count && $delay > 0) {
                    sleep($delay);
                }
                
            } catch (Database_Exception $e) {
                $errorCount++;
                echo '[' . $i . '/' . $count . '] ❌ ОШИБКА: ' . $e->getMessage() . "\n";
                
                if ($i < $count && $delay > 0) {
                    sleep($delay);
                }
            }
        }
        
        echo '=====================================' . "\n";
        echo 'Вставка завершена!' . "\n";
        echo 'Успешно: ' . $successCount . ' событий' . "\n";
        echo 'Ошибок: ' . $errorCount . ' событий' . "\n";
        echo '=====================================' . "\n";
    }
    
    private function getEventData($eventId)
    {
        
		$sql = 'SELECT 
            e.id_event, 
            e.id_eventtype, 
            e.datetime, 
            et.color, 
            et.name as eventtype_name, 
            d.name as device_name, 
            p.surname, 
            p.surname||\' \'|| p.name||\' \'|| p.patronymic as people_name,
            p.photo, 
            p.post, 
            o.name as organization_name
        FROM device d
        JOIN events e ON e.id_dev = d.id_dev
        JOIN eventtype et ON et.id_eventtype = e.id_eventtype
        LEFT JOIN people p ON p.id_pep = e.ess1
        LEFT JOIN organization o ON o.id_org = e.ess2
        WHERE e.id_event = :id';
        
        $query = DB::query(Database::SELECT, $sql)
            ->parameters(array(':id' => (int)$eventId))
            ->execute(Database::instance('fb'))
            ->as_array();
        
        return isset($query[0]) ? $query[0] : null;
    }
    
    private function renderEventRow($event)
    {
        try {
            $color = isset($event['COLOR']) ? dechex($event['COLOR']) : 'FFFFFF';
            $style = 'color: black; background-color: #' . str_pad($color, 6, '0', STR_PAD_LEFT) . ';';
            
            $post = isset($event['POST']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['POST']) : '';
            $eventtype_name = isset($event['EVENTTYPE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['EVENTTYPE_NAME']) : '';
            $device_name = isset($event['DEVICE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['DEVICE_NAME']) : '';
            $people_name = isset($event['PEOPLE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['PEOPLE_NAME']) : '';
            $organization_name = isset($event['ORGANIZATION_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['ORGANIZATION_NAME']) : '';
            
            $bodyphoto = '';
            if (!empty($event['PHOTO'])) {
                try {
                    $photoData = base64_encode(pack("H*", str_replace("\0", "", $event['PHOTO'])));
                    $bodyphoto = '<td id="photo" style="' . $style . 'display:none;">' . $photoData . '</td>';
                } catch (Exception $e) {}
            }
            
            return '<tr>
                ' . $bodyphoto . '
                <td id="people_post" style="' . $style . 'display:none;">' . $post . '</td>
                <td style="' . $style . '">' . $event['ID_EVENT'] . '</td>
                <td id="event_type" style="' . $style . '">' . $event['ID_EVENTTYPE'] . '</td>
                <td style="' . $style . '">' . $event['DATETIME'] . '</td>
                <td id="even_name" style="' . $style . '">' . $eventtype_name . '</td>
                <td id="device_name" style="' . $style . '">' . $device_name . '</td>
                <td id="people_name" style="' . $style . '">' . $people_name . '</td>
                <td id="org_name" style="' . $style . '">' . $organization_name . '</td>
            </tr>';
            
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Прямая отправка в WebSocket через сокет
     */
    private function sendToWebSocket($data)
    {
        $host = '127.0.0.1';
        $port = 8082;
        $timeout = 5;
        
        // Используем fsockopen для отправки
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (!$socket) {
            return false;
        }
        
        fwrite($socket, $data);
        fclose($socket);
        
        return true;
    }
}
