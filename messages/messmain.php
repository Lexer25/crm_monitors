<?php
 
return array(
    'howDeletePeople' => 'Что делать при удалении сотрудника? 0 - пометить как неактивный, 1 - удалить из базы данных',
    'iphost' => 'IP адрес сервера СКУД. Этот параметр вписывается в шаблон HTML. Если использовать адрес 127.0.0.1, то доступ с других компьютеров сети будет запрещен.',
    'lic' => 'Номер лицензии объекта.',
    'odbcname' => 'Название используемого ODBC подключения к базе данных. На текущий момент не используется; параметры подключения к базе данных находятся в файле \citycrm\application\config\database.php',
    'orgname' => 'Название объекта. Это название выводится в закладке окна браузера',
    'sysVer' => 'Версия CityCRM. На текущий момент не используется.',
    'baseFormatRfid' => 'Формат хранения идентификатора RFID в базе данных. ГРЗ всегда хранится в формате 4',
    'screenFormatRFID ' => 'Формат вывода номера RFID на экран.',
    'regFormatRfid' => 'Формат номера RFID, получаемого от регистрационного считывателя. Этот параметр необходимо для преобразования номера RFID от регистрационного комплекта к baseFormatRfid',
	'formatViewAll' => 'Разрешает вывод на экран всех вариантов отображения номера RFID (0,1,2)',
	'viewFromatForEdit_d' => 'Формат вывода на экран номера RFID при выводе его свойств',
	'screenFormatRFID ' => 'Формат вывода на экран номера RFID  на веб-страницах',
	
	
	'formatDescription'=>'0 - HEX 8 byte <b>00124CD8</b><br>1-001A 10 byte <b>262F8F001A</b><br>2-DEC 10 digit <b>0001493650</b><br>4-ГРЗ <b>A123BC45</b>',
	
	'descBaseFormatRfid.0'=>'descBaseFormatRfid_0',
	'descBaseFormatRfid.1'=>'descBaseFormatRfid_1',
	'descBaseFormatRfid.2'=>'descBaseFormatRfid_2',
	'descBaseFormatRfid.4'=>'descBaseFormatRfid_4',
    
);