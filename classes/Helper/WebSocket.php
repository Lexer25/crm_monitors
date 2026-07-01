<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Хелпер для отправки данных в WebSocket-сервер
 */
class Helper_WebSocket
{
    /**
     * Отправить данные в WebSocket-сервер
     * 
     * @param string $data - данные для отправки
     * @return bool
     */
public static function send($data)
{
    try {
        $config = Kohana::$config->load('monitors');
        $host = $config->get('websocket.host', '127.0.0.1');
        $port = $config->get('websocket.port', 8082);
        $timeout = $config->get('websocket.timeout', 5);
        
        echo "        Connecting to {$host}:{$port}...\n";
        
        $context = stream_context_create();
        $socket = @stream_socket_client(
            'tcp://' . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            echo "        ERROR: {$errstr} ({$errno})\n";
            Log::instance()->add(Log::ERROR, 'Helper_WebSocket: ' . $errstr);
            return false;
        }
        
        echo "        Connected, sending data...\n";
        fwrite($socket, $data);
        fclose($socket);
        
        echo "        Data sent successfully\n";
        return true;
        
    } catch (Exception $e) {
        echo "        EXCEPTION: " . $e->getMessage() . "\n";
        Log::instance()->add(Log::ERROR, 'Helper_WebSocket: ' . $e->getMessage());
        return false;
    }
}
    
    /**
     * Отправить JSON-сообщение
     */
    public static function sendJson($data)
    {
        return self::send(json_encode($data));
    }
    
    /**
     * Проверить доступность WebSocket-сервера
     */
    public static function isAvailable()
    {
        try {
            $config = Kohana::$config->load('monitors');
            $host = $config->get('websocket.host', '127.0.0.1');
            $port = $config->get('websocket.port', 8082);
            
            $fp = @fsockopen($host, $port, $errno, $errstr, 1);
            
            if ($fp) {
                fclose($fp);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
