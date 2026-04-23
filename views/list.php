<script type="text/javascript" src="js/modal-window.js"></script>
<script type="text/javascript" src="js/modal-photo.js"></script>
<script type="text/javascript">
	
  	$(function(){		
  		$("#tablesorter").tablesorter({ headers: { 7:{sorter: false}},  widgets: ['zebra']});
  	});	
	//пробел остановка событий
	$(document).bind('keypress', function(e){
		if (e.keyCode == 32) document.getElementById("updatemonitor").checked=!document.getElementById("updatemonitor").checked;
	});
	setInterval(
	//функция обновления монитора событий
	function showUser() {
	//проверка кнопки остановки событий
	if(document.getElementById("updatemonitor").checked) return;
	// формирование get запроса
	var xmlhttp=new XMLHttpRequest();
	var photoneed=document.getElementById("photomonitor").checked;
	// обработка get запроса
	xmlhttp.onreadystatechange=function() {
		//проверка на наличие событий
		if(this.responseText=='') return;
		//поиск элементов
		var table=document.getElementById("txtHint");
		var select=document.getElementById("selectsSize");
		// добавить строки к таблице
		table.insertAdjacentHTML('afterbegin',this.responseText);
		// удалить строки из таблицы
		while(table.rows.length>select.value) table.deleteRow(table.rows.length-1);
		// формирование карточки
		if(photoneed) for (let i = 0; i < table.rows.length && i<windowsCountsetings; i++){		
			var photo=table.rows[i].cells.namedItem('photo');
			if(photo)
			createModal(table.rows[i].cells.namedItem('even_name').innerText, photo.innerText,
			table.rows[i].cells.namedItem('people_name').innerText,
			table.rows[i].cells.namedItem('org_name').innerText,
			table.rows[i].cells.namedItem('people_post').innerText,
			table.rows[i].cells.namedItem('device_name').innerText,
			);
		}
	}
	//get запрос
	xmlhttp.open("GET","/crm2/events/getEvent?photo="+photoneed,false);
	// отправка запроса
	xmlhttp.send();
	// время обнавления монитора событий
	},timeUpdate*1000)
</script>
<style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh; /* Занимаем 100% высоты окна браузера */
        }

        #myTableContainer {
            flex: 1; /* Занимаем оставшееся пространство, необходимое для таблицы */
            overflow: auto; /* Добавляем полосы прокрутки, если содержимое больше экрана */
            margin-bottom: 40px; 
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #dddddd;
            padding: 8px;
            text-align: left;
            position: relative;
            transition: background-color 0.3s; /* Анимация перехода цвета */
        }

        th {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f5f5f5; /* Цвет подсветки при наведении */
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: max-content;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -75px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        td:hover .tooltip .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Новые стили для строки с дополнительной информацией */
        #additionalInfo {
            padding: 10px;
            position: fixed;
            bottom: 0;
            right: 0;
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            gap: 20px;
        }

        #currentTime, #eventCounter, #visibleEventCount {
            font-size: 14px;
            color: #333; /* Цвет текста */
        }
        
        .modal {
            display: none;
            position: absolute;
            background-color: #fff;
            border: 1px solid #ccc;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            cursor: move;
            padding: 10px;
            background-color: #f1f1f1;
            display: flex;
            justify-content: space-between;
        }

        .close-button {
            width: 15px;
            height: 15px;
            cursor: pointer;
            margin-right: 5px;
            background-color: #ff6f6f;
            border: none;
            color: #fff;
            font-weight: bold;
            border-radius: 10px;
        }

        .tabs {
            display: flex;
            margin-top: 10px;
        }

        .tab-button {
            margin-right: 2px;
            padding: 8px 12px;
            border: none;
            background-color: #eee;
            cursor: pointer;
            font-size: 14px;
        }

        .tab-button:hover {
            background-color: #ddd;
        }

        .tab-button.active {
            background-color: #ccc;
        }
</style>
<?php 
//https://webformyself.com/sortirovka-tablic-pri-pomoshhi-plagina-tablesorter-js/?ysclid=lrgdz4nrzp693511651
// список идентификаторов
//echo Debug::vars('2', $cards); //exit;
//echo Debug::vars('2-2', $cardsList); //exit;
//echo Debug::vars('16', array_diff($cards, $cardsList));//exit;
//echo Debug::vars('12', $cardsList); //exit;
//echo Debug::vars('2', $catdTypelist); //exit;
//echo Debug::vars('3', $alert); //exit;
//echo Debug::vars('4', $filter); //exit;
//echo Debug::vars('5', $pagination); //exit;
define ('_notAllowed', "HTML::image('images/text_lock.png', array('title' => __('tip.notAllowed'), 'width'=>'32'))");
include Kohana::find_file('views','alert');
if ($alert) { ?>
<div class="alert_success">
	<p>
		<img class="mid_align" alt="success" src="images/icon_accept.png" />
		<?php echo $alert; ?>
	</p>
</div>
<?php } ?>
<div class="onecolumn">
	<div class="header">
		<div id="search"<?php if (isset($hidesearch)) echo ' style="display: none;"'; ?>>
			<form action="cards/search_any" method="post">
			
				<input type="text" class="search noshadow" title="<?php echo __('search'); ?>" name="q" id="q" value="<?php if (isset($filter)) echo $filter; ?>" />
			</form>
		</div>
		<span><?php 
		switch(Session::instance()->get('identifier')){
			case 1:
				echo __('cards.titleRFID'); 
			break;
			case 1:
				echo __('cards.titleGRZ'); 
			break;
			default:
		break;
		}			
		
	?></span>
	</div>
	<br class="clear"/>
	<div class="content">
	Количество Событий: 
		<select id="selectsSize">
				<option value=10>10</option>
				<option value=20 selected="selected">20</option>
				<option value=30 >30</option>
		</select>
	<button onclick="createSettingsModal2()">Настройки</button>
	Остановить:
	<input type="checkbox" id="updatemonitor"/>
	Фотографии:
	<input type="checkbox" id="photomonitor" checked />
		<form id="form_data" name="form_data" action="" method="post">
			<table class="data tablesorter-blue" width="100%" cellpadding="0" cellspacing="0"  >
			<thead>
					<tr>
						<th>ID_EVENT</th>
						<th>ID_EVENTTYPE</th>
						<th>DATETIME</th>
						<th>EVENTTYPE_NAME</th>
						<th>DEVICE_NAME</th>
						<th>PEOPLE_NAME</th>
						<th>ORGANIZATION_NAME</th>
					</tr>
			</thead>		
			<tbody id="txtHint"/>
			</table>

			<div id="chart_wrapper" class="chart_wrapper"></div>
		
                <div id="additionalInfo">
    <div id="currentTime"></div>
    <div id="eventCounter"></div>
    <div id="visibleEventCount"></div>
</div>
	</div>
</div>

