<?php
defined('MONITORS_VERSION') OR define('MONITORS_VERSION', '1.0.1');


https://github.com/Lexer25/crm_monitors.git

Kohana::$config->load('menu')
    ->set('monitors', array(
        'title' => 'Монитор',
        'url' => 'monitors',
        'icon' => 'fa-cog',
        'order' => 3,
    ));
	
	
	
// Маршруты для монитора событий
Route::set('monitors_api', 'monitors/getEvent', array())
    ->defaults(array(
        'controller' => 'monitors',
        'action' => 'getEvent',
    ));

Route::set('monitors', 'monitors(/<action>)', array('action' => 'index'))
    ->defaults(array(
        'controller' => 'monitors',
        'action' => 'index',
    ));