<?php defined('SYSPATH') or die('No direct script access.');

//тестирование монтори онлай
//C:\xampp\htdocs\city\modules\monitors\classes\Task>c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion --task=eventsInsert --id_dev=797
//если id_dev не указан, то берется первая активная точка прохода



class Task_EventsInsert extends Minion_Task {

    protected $_options = array(
        'count'  => 10,                    // количество циклов
        'delay'  => 2,                     // интервал в секундах между вставками
        'type'   => 50,                    // ID типа события
        'people' => 22877,                 // ID человека (ESS1)
        'note'   => '17 building test',    // текст заметки
        'id_dev' => NULL,                  // ID устройства; NULL = авто-выбор
    );

    protected function _execute(array $params)
    {
        // ---- Параметры с значениями по умолчанию (PHP 5.6) ----
        $count  = isset($params['count'])  ? (int) $params['count']  : 10;
        $delay  = isset($params['delay'])  ? (int) $params['delay']  : 2;
        $type   = isset($params['type'])   ? (int) $params['type']   : 50;
        $people = isset($params['people']) ? (int) $params['people'] : 22877;
        $note   = isset($params['note'])   ? (string) $params['note'] : '17 building test';

        // ID устройства: если передан — используем, иначе авто-выбор
        $id_dev = !empty($params['id_dev'])
            ? (int) $params['id_dev']
            : $this->getIdDev();

        if (empty($id_dev)) {
            echo "ОШИБКА: не удалось определить id_dev.\n";
            Kohana::$log->add(Log::ERROR, 'Task_EventsInsert: getIdDev() вернул пусто');
            return;
        }

        // Экранируем note
        $note_sql = addslashes($note);

        echo 'Начинаем вставку ' . $count . ' событий с интервалом ' . $delay . ' сек.' . "\n";
        echo 'id_dev=' . $id_dev . ', type=' . $type . ', people=' . $people . "\n";
        Kohana::$log->add(Log::INFO,
            'Запуск Task_EventsInsert: count=' . $count
            . ', delay=' . $delay
            . ', type=' . $type
            . ', people=' . $people
            . ', id_dev=' . $id_dev);

        $successCount = 0;
        $errorCount   = 0;

        for ($i = 1; $i <= $count; $i++) {
            try {
                // Новый ID из генератора
                $newId = DB::query(Database::SELECT,
                        'SELECT GEN_ID(GEN_EVENT_ID, 1) AS gen FROM RDB$DATABASE')
                    ->execute(Database::instance('fb'))
                    ->get('GEN');

                // Текст заметки для этого цикла
                $name_sql = addslashes($note . ' #' . $i);

                $sql = "INSERT INTO events (
                    ID_EVENT, ID_DB, ID_EVENTTYPE, ID_DEV, ID_PLAN,
                    DATETIME, ID_CARD, NOTE, ID_VIDEO, ID_PEP, ESS1, ESS2
                ) VALUES (
                    {$newId}, 1, {$type}, {$id_dev}, NULL,
                    current_timestamp, '1484F8001A', '{$name_sql}', NULL, NULL, {$people}, 1
                )";

                DB::query(Database::INSERT, $sql)
                    ->execute(Database::instance('fb'));

                $successCount++;
                echo '[' . $i . '/' . $count . '] Событие добавлено. ID: ' . $newId . "\n";
                Kohana::$log->add(Log::DEBUG,
                    'Событие добавлено. ID: ' . $newId . ', цикл: ' . $i);

                if ($i < $count && $delay > 0) {
                    sleep($delay);
                }

            } catch (Database_Exception $e) {
                $errorCount++;
                echo '[' . $i . '/' . $count . '] ОШИБКА: ' . $e->getMessage() . "\n";
                Kohana::$log->add(Log::ERROR,
                    'Ошибка вставки события (цикл ' . $i . '): ' . $e->getMessage());

                if ($i < $count && $delay > 0) {
                    sleep($delay);
                }
            }
        }

        echo '=====================================' . "\n";
        echo 'Вставка завершена!' . "\n";
        echo 'Успешно: ' . $successCount . ' событий' . "\n";
        echo 'Ошибок: ' . $errorCount . ' событий' . "\n";
        echo '=====================================' . "\n";

        Kohana::$log->add(Log::INFO,
            'Task_EventsInsert завершен. Успешно: ' . $successCount
            . ', Ошибок: ' . $errorCount);
    }

    /**
     * Авто-выбор устройства, если --id_dev не передан.
     */
    private function getIdDev()
    {
        $sql = 'SELECT FIRST 1 d.id_dev
                FROM device d
                JOIN device d2 ON d2.id_ctrl = d.id_ctrl
                              AND d2.id_reader IS NULL
                              AND d2."ACTIVE" > 0
                WHERE d.id_reader = 0
                  AND d."ACTIVE" > 0';

        return DB::query(Database::SELECT, $sql)
            ->execute(Database::instance('fb'))
            ->get('ID_DEV');
    }
}