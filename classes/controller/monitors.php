<?php defined('SYSPATH') or die('No direct script access.');
/**


*/
class Controller_monitors extends Controller_Template { 
		
	public $view = 'result';//view для показа результата
	public $template = 'template';
	private $session;
	
	public function before()
	{
		parent::before();
		 $this->session = Session::instance();
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
	
	
	
	
	public function action_getEvent()
	{
	
		return 'Now: '. time();
		
			
	
	}
	
	
	
	
}
