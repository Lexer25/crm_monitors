<?php defined('SYSPATH') or die('No direct script access.');
/**


*/
class Controller_events extends Controller { 
		
	public $view = 'result';//view для показа результата
	public $template = 'template';
	public function before()
	{
		parent::before();
	}


	public function action_index($filter = null)
	{
		//echo Debug::vars('19');exit;
		
			$fl = $this->session->get('alert');
		$this->session->delete('alert');
		$this->template->content = View::factory('list')
			//->bind('cards', $list)
			//->bind('cardsList', $list)
			//->bind('catdTypelist', $catdTypelist)
			->bind('alert', $fl)
			->bind('arrAlert', $arrAlert)
			//->bind('filter', $filter)
			;
	}
	
	private function getid()
	{
		$sql = 'SELECT GEN_ID( gen_event_id, 0 ) FROM RDB$DATABASE';
		$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->current();
		return $query['GEN_ID'];
	}
	private function selectevent($id,$photo)
	{
		
		$sqlphoto='';
		if($photo) $sqlphoto='p.photo,';
		$sql = 'select e.id_event, e.id_eventtype, e.datetime,  et.color, et.name as eventtype_name, 
		d.name as device_name, p.surname, p.surname||\' \'|| p.name||\' \'|| p.patronymic as people_name,
		'.$sqlphoto.' p.post, o.name as organization_name from events e
 join eventtype et on et.id_eventtype=e.id_eventtype
 join device d on e.id_dev=d.id_dev
 left join people p on p.id_pep=e.ess1
 left join organization o on o.id_org=e.ess2
 where  e.id_event > '.$id.'
  order by e.id_event';
  
		$sql='select first 30 e.id_event, e.id_eventtype, e.datetime,  et.color, et.name as eventtype_name, 
        d.name as device_name, p.surname, p.surname||\' \'|| p.name||\' \'|| p.patronymic as people_name,
         '.$sqlphoto.' p.post, o.name as organization_name
from device d
join events e on e.id_dev=d.id_dev
 join eventtype et on et.id_eventtype=e.id_eventtype
  left join people p on p.id_pep=e.ess1
 left join organization o on o.id_org=e.ess2
where e.id_event >'.$id;
  
 Log::instance()->add(Log::DEBUG, 'Line 47.evenrs sql: '. $sql);
 Log::instance()->add(Log::DEBUG, 'Line 49.Запрос событий начиная с : '. $id);
		$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();
		return $query;
	}
	
	/**5.02.2025 Получить последние события.
	* http://localhost/crm2/events/getEvent?photo=
	*номер последнего событий хранится в куках.
	*/
	public function action_getEvent()
{
    $t1 = microtime(true);
    $photo = filter_var($this->request->query('photo'), FILTER_VALIDATE_BOOLEAN);
    $getPhoto = ($photo) ? 'true' : 'false';
    
    $this->response->body('');
    try {
        // Получаем последний ID из куки
        $lastId = Cookie::get('id');
        Log::instance()->add(Log::DEBUG, 'Last ID from cookie: ' . $lastId);
        
        if ($lastId == null || !is_numeric($lastId)) {
            $lastId = $this->getid();
            Cookie::set('id', $lastId);
        }
        
        // Получаем НОВЫЕ события, ID которых больше последнего сохраненного
        $tab = $this->selectevent($lastId, $photo);
        
        Log::instance()->add(Log::DEBUG, 'Found ' . count($tab) . ' new events');
        
        if (count($tab) == 0) {
            Log::instance()->add(Log::DEBUG, 'No new events. Last ID: ' . $lastId);
            return;
        }
        
        // Сортируем по ID для правильной последовательности
        usort($tab, function($a, $b) {
            return $a['ID_EVENT'] - $b['ID_EVENT'];
        });
        
        $body = '';
        $maxId = $lastId;
        
        foreach ($tab as $row) {
            // Пропускаем уже обработанные события
            if ($row['ID_EVENT'] <= $lastId) {
                Log::instance()->add(Log::DEBUG, 'Skip duplicate event: ' . $row['ID_EVENT']);
                continue;
            }
            
            //$style = 'color: black;background-color: #' . dechex($row['COLOR']) . ';';
            $style = 'color: black;background-color: #' . str_pad(dechex($row['COLOR']), 6, '0', STR_PAD_LEFT) . ';';
			
            $bodyphoto = '';
            
            if ($photo && !empty($row['PHOTO'])) {
                $bodyphoto = '<td id="photo" style="' . $style . 'display:none;">' . 
                            base64_encode(pack("H*", str_replace("\0", "", $row['PHOTO']))) . 
                            '</td>';
            }
            
            $body .= '<tr>
                ' . $bodyphoto . '
                <td id="people_post" style="' . $style . 'display:none;">' . 
                iconv('CP1251', 'UTF-8', $row['POST']) . '</td>
                <td style="' . $style . '">' . $row['ID_EVENT'] . '</td>
                <td id="event_type" style="' . $style . '">' . $row['ID_EVENTTYPE'] . '</td>
                <td style="' . $style . '">' . $row['DATETIME'] . '</td>
                <td id="even_name" style="' . $style . '">' . 
                iconv('CP1251', 'UTF-8', $row['EVENTTYPE_NAME']) . '</td>
                <td id="device_name" style="' . $style . '">' . 
                iconv('CP1251', 'UTF-8', $row['DEVICE_NAME']) . '</td>
                <td id="people_name" style="' . $style . '">' . 
                iconv('CP1251', 'UTF-8', $row['PEOPLE_NAME']) . '</td>
                <td id="org_name" style="' . $style . '">' . 
                iconv('CP1251', 'UTF-8', $row['ORGANIZATION_NAME']) . '</td>
            </tr>';
            
            // Обновляем максимальный ID
            if ($row['ID_EVENT'] > $maxId) {
                $maxId = $row['ID_EVENT'];
            }
        }
        
        // Сохраняем ТОЛЬКО максимальный ID
        if ($maxId > $lastId) {
            Cookie::set('id', $maxId);
            Log::instance()->add(Log::DEBUG, 'Update cookie from ' . $lastId . ' to ' . $maxId);
        }
        
        Log::instance()->add(Log::DEBUG, 'Processed ' . count($tab) . ' events. New max ID: ' . $maxId);
        $this->response->body($body);
        
    } catch (Exception $e) {
        Log::instance()->add(Log::DEBUG, 'Error: ' . $e->getMessage());
        return;
    }
}
}
