<?php
defined('MONITORS_VERSION') OR define('MONITORS_VERSION', '1.0.3');

Kohana::$config->load('menu')
    ->set('monitors', array(
        'title' => 'Монитор',
        'url' => 'monitors',
        'icon' => 'fa-cog',
        'order' => 3,
    ));

// Маршрут для сохранения группы устройств
Route::set('monitors_set_group', 'monitors/setGroup', array())
    ->defaults(array(
        'controller' => 'monitors',
        'action' => 'setGroup',
    ));

// Маршрут для API получения событий
Route::set('monitors_api', 'monitors/getEvent', array())
    ->defaults(array(
        'controller' => 'monitors',
        'action' => 'getEvent',
    ));

// Главный маршрут
Route::set('monitors', 'monitors(/<action>)', array('action' => 'index'))
    ->defaults(array(
        'controller' => 'monitors',
        'action' => 'index',
    ));