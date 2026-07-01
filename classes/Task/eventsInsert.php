<?php defined('SYSPATH') or die('No direct script access.');

class Task_EventsInsert extends Minion_Task {
    
    protected $_options = array(
        'count'  => 10,   // количество циклов
        'delay'  => 2,    // интервал в секундах между вставками
        'type'   => 27,   // тип события
        'people' => 1,    // ID человека
        'note'   => '17 building test', // текст заметки
    );
    
    protected function _execute(array $params) 
    {
        // Получаем параметры с значениями по умолчанию (PHP 5.6 совместимость)
        $count = isset($params['count']) ? (int)$params['count'] : 10;
        $delay = isset($params['delay']) ? (int)$params['delay'] : 2;
        $type = isset($params['type']) ? (int)$params['type'] : 27;
        $people = isset($params['people']) ? (int)$params['people'] : 1;
        $note = isset($params['note']) ? $params['note'] : '17 building test';
        
        // Экранируем note для безопасности
        $note = addslashes($note);
        
        echo 'Начинаем вставку ' . $count . ' событий с интервалом ' . $delay . ' сек.' . "\n";
        Kohana::$log->add(Log::INFO, 'Запуск Task_EventsInsert: count=' . $count . ', delay=' . $delay);
        
        $successCount = 0;
        $errorCount = 0;
        
        for ($i = 1; $i <= $count; $i++) {
            try {
                $sql = "INSERT INTO EVENTS (ID_DB, ID_EVENTTYPE, ID_DEV, ID_PLAN, DATETIME, ID_CARD, NOTE, ID_VIDEO, ID_PEP, ESS1, ESS2)
                        VALUES (1, {$type}, NULL, NULL, current_timestamp, NULL, '{$note}', NULL, {$people}, NULL, NULL)";
       $sql="INSERT INTO events (ID_DB,ID_EVENTTYPE,ID_DEV,ID_PLAN,DATETIME,ID_CARD,NOTE,ID_VIDEO,ID_PEP,ESS1,ESS2)
       VALUES (1,50,574,NULL,current_timestamp,'1484F8001A','test',NULL,NULL,22877, 1);";         
                $result = DB::query(Database::INSERT, $sql)
                    ->execute(Database::instance('fb'));
                
                $successCount++;
                echo '[' . $i . '/' . $count . '] Событие добавлено. ID: ' . $result[0] . "\n";
                Kohana::$log->add(Log::DEBUG, 'Событие добавлено. ID: ' . $result[0] . ', цикл: ' . $i);
                
                // Если это не последняя итерация и delay > 0, ждем
                if ($i < $count && $delay > 0) {
                    sleep($delay);
                }
                
            } catch (Database_Exception $e) {
                $errorCount++;
                echo '[' . $i . '/' . $count . '] ОШИБКА: ' . $e->getMessage() . "\n";
                Kohana::$log->add(Log::ERROR, 'Ошибка вставки события (цикл ' . $i . '): ' . $e->getMessage());
                
                // Если произошла ошибка, тоже ждем перед следующей попыткой
                if ($i < $count && $delay > 0) {
                    sleep($delay);
                }
            }
        }
        
        // Итоговый отчет
        echo '=====================================' . "\n";
        echo 'Вставка завершена!' . "\n";
        echo 'Успешно: ' . $successCount . ' событий' . "\n";
        echo 'Ошибок: ' . $errorCount . ' событий' . "\n";
        echo '=====================================' . "\n";
        
        Kohana::$log->add(Log::INFO, 'Task_EventsInsert завершен. Успешно: ' . $successCount . ', Ошибок: ' . $errorCount);
    }
}