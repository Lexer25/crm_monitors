<?php defined('SYSPATH') or die('No direct script access.');

defined('MONITORS_VERSION') OR define('MONITORS_VERSION', '1.0.5');

// ---------------------------------------------------------------------
// Меню
// ---------------------------------------------------------------------
Kohana::$config->load('menu')
    ->set('monitors', array(
        'title' => 'Монитор',
        'url'   => 'monitors',
        'icon'  => 'fa-cog',
        'order' => 3,
    ));

// ---------------------------------------------------------------------
// Маршруты
// ---------------------------------------------------------------------

// API: сохранение выбранной группы устройств
Route::set('monitors_set_group', 'monitors/setGroup', array())
    ->defaults(array(
        'controller' => 'monitors',
        'action'     => 'setGroup',
    ));

// API: получение событий (AJAX)
Route::set('monitors_api', 'monitors/getEvent', array())
    ->defaults(array(
        'controller' => 'monitors',
        'action'     => 'getEvent',
    ));

// Главный маршрут
Route::set('monitors', 'monitors(/<action>)', array(
        'action' => '[a-zA-Z0-9_]+',
    ))
    ->defaults(array(
        'controller' => 'monitors',
        'action'     => 'index',
    ));