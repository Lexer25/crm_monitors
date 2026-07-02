<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Модель монитора событий
 * Уникальное имя для избежания конфликтов с другими модулями
 */
class Model_MonitorM extends Model
{
    /**
     * Получение ID следующего события из генератора
     * @return int
     */
    public function getNextId()
    {
        $sql = 'SELECT GEN_ID(gen_event_id, 0) FROM RDB$DATABASE';
        $query = DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->current();
        return (int)$query['GEN_ID'];
    }
    
    /**
     * Получение событий с ID больше указанного
     * @param int $id - ID события, начиная с которого выбирать
     * @param bool $withPhoto - нужно ли загружать фото
     * @param int $limit - количество записей
     * @return array
     */
    public function getEvents($id, $withPhoto = false, $limit = 30)
    {
        $sqlphoto = $withPhoto ? 'p.photo,' : '';
        
        $sql = 'SELECT FIRST ' . (int)$limit . ' 
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
        WHERE e.id_event > '.$id.'
        ORDER BY e.id_event DESC';
		
 //Kohana::$log->add(Log::DEBUG, '52 id='.$id);  
 //Kohana::$log->add(Log::DEBUG, '53 sql '.$sql);  
 
        $query = DB::query(Database::SELECT, $sql)
            ->parameters(array(':id' => (int)$id))
            ->execute(Database::instance('fb'))
            ->as_array();
        
        return $query;
    }
    
    /**
     * Получение одного события по ID
     * @param int $eventId
     * @param bool $withPhoto
     * @return array|null
     */
    public function getEventById($eventId, $withPhoto = true)
    {
        $sqlphoto = $withPhoto ? 'p.photo,' : '';
        
        $sql = 'SELECT 
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
        WHERE e.id_event = :id';
        
        $query = DB::query(Database::SELECT, $sql)
            ->parameters(array(':id' => (int)$eventId))
            ->execute(Database::instance('fb'))
            ->as_array();
        
        return isset($query[0]) ? $query[0] : null;
    }
    
    /**
     * Добавление нового события (для тестирования)
     * @param array $data
     * @return int|null ID добавленного события
     */
    public function addEvent($data)
    {
        $defaults = array(
            'id_db' => 1,
            'id_eventtype' => 50,
            'id_dev' => 574,
            'id_plan' => null,
            'datetime' => 'current_timestamp',
            'id_card' => null,
            'note' => null,
            'id_video' => null,
            'id_pep' => null,
            'ess1' => null,
            'ess2' => null,
        );
        
        $data = array_merge($defaults, $data);
        
        // Экранирование
        $note = !empty($data['note']) ? addslashes($data['note']) : 'NULL';
        $id_card = $data['id_card'] !== null ? "'" . addslashes($data['id_card']) . "'" : 'NULL';
        $id_plan = $data['id_plan'] !== null ? (int)$data['id_plan'] : 'NULL';
        $id_video = $data['id_video'] !== null ? (int)$data['id_video'] : 'NULL';
        $id_pep = $data['id_pep'] !== null ? (int)$data['id_pep'] : 'NULL';
        $ess1 = $data['ess1'] !== null ? (int)$data['ess1'] : 'NULL';
        $ess2 = $data['ess2'] !== null ? (int)$data['ess2'] : 'NULL';
        
        $datetime = $data['datetime'] === 'current_timestamp' 
            ? 'current_timestamp' 
            : "'" . $data['datetime'] . "'";
        
        $sql = "INSERT INTO events (
            ID_DB, ID_EVENTTYPE, ID_DEV, ID_PLAN, DATETIME, 
            ID_CARD, NOTE, ID_VIDEO, ID_PEP, ESS1, ESS2
        ) VALUES (
            {$data['id_db']}, {$data['id_eventtype']}, {$data['id_dev']}, {$id_plan}, {$datetime},
            {$id_card}, '{$note}', {$id_video}, {$id_pep}, {$ess1}, {$ess2}
        ) RETURNING ID_EVENT";
        
        $result = DB::query(Database::INSERT, $sql)
            ->execute(Database::instance('fb'));
        
        return isset($result[0]) ? (int)$result[0] : null;
    }
    
    /**
     * Формирование HTML строки для таблицы
     * @param array $event
     * @param bool $withPhoto
     * @return string
     */
    public function renderEventRow($event, $withPhoto = false)
    {
        try {
            $color = isset($event['COLOR']) ? dechex($event['COLOR']) : 'FFFFFF';
            $style = 'color: black; background-color: #' . str_pad($color, 6, '0', STR_PAD_LEFT) . ';';
            
            // Конвертация кодировок
            $post = isset($event['POST']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['POST']) : '';
            $eventtype_name = isset($event['EVENTTYPE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['EVENTTYPE_NAME']) : '';
            $device_name = isset($event['DEVICE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['DEVICE_NAME']) : '';
            $people_name = isset($event['PEOPLE_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['PEOPLE_NAME']) : '';
            $organization_name = isset($event['ORGANIZATION_NAME']) ? iconv('CP1251', 'UTF-8//IGNORE', $event['ORGANIZATION_NAME']) : '';
            
            // Фото
            $bodyphoto = '';
            if ($withPhoto && !empty($event['PHOTO'])) {
                try {
                    $photoData = base64_encode(pack("H*", str_replace("\0", "", $event['PHOTO'])));
                    $bodyphoto = '<td id="photo" style="' . $style . 'display:none;">' . $photoData . '</td>';
                } catch (Exception $e) {
                    // Игнорируем ошибки фото
                }
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
            Log::instance()->add(Log::ERROR, 'Model_MonitorM::renderEventRow: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Отправить событие в WebSocket
     * @param int $eventId
     * @param bool $withPhoto
     * @return bool
     */
    public function sendToWebSocket($eventId, $withPhoto = true)
    {
        $event = $this->getEventById($eventId, $withPhoto);
        
        if (!$event) {
            Log::instance()->add(Log::DEBUG, 'Model_MonitorM::sendToWebSocket: Событие ' . $eventId . ' не найдено');
            return false;
        }
        
        $html = $this->renderEventRow($event, $withPhoto);
        
        if ($html) {
            // Используем хелпер WebSocket
            if (class_exists('Helper_WebSocket')) {
                return Helper_WebSocket::send($html);
            }
            return false;
        }
        
        return false;
    }
}
