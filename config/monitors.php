<?php defined('SYSPATH') OR die('No direct access allowed.');

return array(
    'websocket' => array(
        'host' => '127.0.0.1',
        'port' => 8082,
        'timeout' => 5,
        'enabled' => false, // ← false = AJAX, true = WebSocket
    ),
    'events' => array(
        'limit' => 30,
        'cookie_name' => 'id',
    ),
);