<?php defined('SYSPATH') OR die('No direct access allowed.');
/*

*/

class Model_monitor extends Model
{
	public 	$id_event=0;
	public function getReport1($id_org){// Статистика
		
			$month=20;
			$ttime=time()-60*60*24*30*$month;
			$timeFrom=date('Y-m-d', mktime(0, 0, 0, date('m', $ttime), 1, date('Y', $ttime)));
			$sql='SELECT EXTRACT(year from p.time_stamp) as yearFrom, EXTRACT(month from p.time_stamp) as montFrom, count(*) FROM people p
			join organization_getchild(1, '.Arr::get(Auth::instance()->get_user(), 'ID_ORGCTRL').') og on og.id_org=p.id_org
			where p.time_stamp>\''.$timeFrom.'\'
				GROUP BY 1, 2
				order by 1,2';
			//echo Debug::vars('21', $sql);exit;
			$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();
			//заменяю номер месяца на его название
			$monthes = array('NullMonth', 'Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь', 'Декабрь');
			foreach ($query as $key=>$value)
			{
				$query[$key]['MONTFROM']=Arr::get($monthes, $value['MONTFROM']).' ('.$value['MONTFROM'].')';
			}
		
			$titleArray=array('YEARFROM', 'MONTFROM', 'COUNT' );
			return array('title'=>$titleArray, 'data'=>$query);
	}

	public function getReport2(){
		
	}
	
}

