<?php
require_once("WEB-INF/conf/session.php");
require_once("WEB-INF/conf/conf.php");

getWord();

/**
 * Подбирает новое слово для изучения
 * фиксирует результат перевода предыдущего слова
 */
function getWord() {

	global $contr;
	global $next;
	global $trans_serv;
	global $sound;
	global $phonetic;

	if(!Config::$USER)
	{
		header("Location: index.php");
		exit;
	}

	$u = User::createUser(Config::$USER,'facebook');

	//$query="SELECT * FROM dictionary_en_ru";
	//$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
	//$counter = 0;
	//while($row=mysql_fetch_array($res) )
	//{
	//	$counter++;
	//	print $counter.".".$row["word"]."-".$row["translatedText"]."<br/>";
	//}
	//exit;
	//читаем данные и HTTP-запроса, строим из них XML по схеме
	$req=new HTTP_Request2Xml("schemas/lexicon/getwordrequest.xsd");

	if(!$req->isEmpty()) {
		//проверяем на соответствие схеме
		try {
			$req->validate();
		}catch(Exception $e) {
			var_dump($req->getAsXML());
			throw new Exception("invalid params\nОшибка проверки входных данных: ".$e->getMessage(),450);
		}
	}

	// Создаем экземпляр контроллера
	$contr = Lexicon_UserController::create(Config::$USER);
	// Если есть входящие данные то фиксируем результат
	if(!$req->isEmpty()) {
		$word=$req->getElementByTagName("word");
		$result=$req->getElementByTagName("result");
		$drop=$req->getElementByTagName("drop");
		$contr->fixResult($word,$result,$drop);
		header("Location:lexicon.php");
		exit;
	}
	// Загружаем в его информацию о процессе обучения
	$contr->build();
	//print "<pre>";
	//print_r( $contr );
	//exit;
	// Выбираем новое слово для изучения
	$next = $contr->getNext();
	$contr->update();
	
	
	// если в базе сохранены данные ответов до достаем их
	$sql = "SELECT * FROM `dictionary_en_ru` WHERE `word`='".$next."' and (`transServ`!='' OR `dictServ`!='');";
	if( $res = mysql_query( $sql ) )
	{
		if( mysql_num_rows( $res ) > 0 )
		{
			while( $row = mysql_fetch_assoc( $res ) )
			{
				if( $row["transServ"] ) $trans_serv = unserialize( $row["transServ"] );
				if( $row["dictServ"] ) $dict_serv = unserialize( $row["dictServ"] );
			}
		}
	}
	if( !is_object( $trans_serv ) )
	{
		// новый костыль дает только перевод без фонетики и ссылок на произношение
		$url = "http://translate.google.ru/translate_a/t?client=x&text=".$next."&hl=en&sl=en&tl=ru";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		//$header = array( "Accept-Charset: UTF-8" );
		//curl_setopt($ch, CURLOPT_HEADER, true);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.battleship.ru");
		$body = curl_exec($ch);
		// приходит в кривой кодировке
		$body = iconv("KOI8-R", "UTF-8", $body);
		$m = array();
		preg_match_all( "/\{.*\}/", $body, $m );
		curl_close($ch);
		$trans_serv = json_decode( $m[0][0] );
		$err = json_last_error();
		if( $err !== 0 || !isset( $trans_serv->dict )  )
		{
			// Если у нас нет перевода соответствующего слова то будем его удалять из словаря
			$query = "DELETE FROM words WHERE word='".$next."';";
			$res = mysql_query($query);
			//throw new Exception( $next.": translation error. Reload page, please.", 450 );
			header("Location:lexicon.php");
			exit;
		}
		else
		{
			//сохраним в базу
			//Lexicon_Dictionary::updateWord( $next, null, serialize( $trans_serv ), null );
			$query = "UPDATE `dictionary_en_ru` SET `transServ`='".mysql_real_escape_string(serialize($trans_serv))."' WHERE `word`='".$next."';";
			$res = mysql_query($query);
		}
	}
	
	if( !isset( $dict_serv ) )
	{
		// следующий костыль для того чтобы забрать транскрипцию и ссылку на произношение
		//$url = "www.google.com/dictionary/json?callback=dict_api.callbacks.id100&q=".$next."&sl=en&tl=en&restrict=pr,de&client=te";
		$url = "www.google.com/dictionary/json?callback=dict_api.callbacks.id100&q=".$next."&sl=en&tl=en";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.battleship.ru");
		$body = curl_exec($ch);
		$body = str_replace("\x","",$body);
		
		$m = array();
		preg_match_all( "/\{.*\}/", $body, $m );
		curl_close($ch);
		$dict_serv = json_decode( $m[0][0] );
		$err = json_last_error();
		if( $err === 0 && isset( $dict_serv->primaries )  )
		{
		
			//сохраним в базу
			//Lexicon_Dictionary::updateWord( $next, null, null, serialize( $dict_serv ) );
			$query = "UPDATE `dictionary_en_ru` SET `dictServ`='".mysql_real_escape_string(serialize($dict_serv))."' WHERE `word`='".$next."';";
			$res = mysql_query($query);
		}
	}
	
	if( isset( $dict_serv->primaries ) )
	{
		$primary = $dict_serv->primaries[0];
		$terms = $primary->terms;
		foreach( $terms as $term )
		{
			if( $term->type == "sound" ) $sound = $term->text;
			if( $term->type == "phonetic" ) $phonetic = $term->text;
		}
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Lexicon</title>
	<link rel="stylesheet"  href="styles/jquerymobile/jquery.mobile.min.css" />
	<link rel="stylesheet"  href="styles/jplayer/jplayer.blue.monday.css" />
	<script src="scripts/jquery/jquery.js"></script>
	<script src="scripts/jquerymobile/jquery.mobile.min.js"></script>
	<script src="scripts/jplayer/jquery.jplayer.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			$("#jquery_jplayer_1").jPlayer({
				ready: function () {
					$(this).jPlayer("setMedia", {
						mp3: "<?php print $sound; ?>"
					});
				},
				solution: "flash,html",
				swfPath: "/lexicon/scripts/jplayer",
				supplied: "mp3",
				wmode: "window",
				cssSelectorAncestor: "",
				cssSelector: {
					play: "#play",
				},
				size: {
					width: "0px",
					height: "0px"
				}
			});
			$("#playBtn").click( function() {
				$("#jquery_jplayer_1").jPlayer("play");
			});
		});
	</script>
	<style type="text/css">
		@media all and (min-width: 650px){
			.content-secondary {text-align:left;float:left;width:55%}
			.content-primary {width:40%;float:right;padding-top:75px;}
		}
		@media all and (min-width: 750px){
			.content-secondary {width:60%;}
			.content-primary {width:35%;}
		}
			@media all and (min-width: 1200px){
			.content-secondary {width:65%;}
			.content-primary {width:30%;}
		}
	</style>
</head>
<body>

<div data-role="page" data-theme="d">
	<div data-role="header" data-theme="a">
		<h1><?php print $contr->status.($contr->status=="ВЫУЧЕННОЕ"?"":(" [".$contr->cum."]")); ?></h1>
		<a data-ajax="false" href="index.php" data-icon="home" class="ui-btn-right" data-iconpos="notext">Домой</a>
	</div>
	<!-- header -->
	
	<div data-role="content">
		<div class="content-secondary">
		<!--embed src="<?php print$sound;?>"  autoplay="false" loop="false"></embed-->
		<div id="jquery_jplayer_1" class="jp-jplayer"></div>
		<h1 style="font-style:italic;color:firebrick;">
			<?php print $next ?>
			&#160;<img style="margin-bottom:-6px;" src="images/sound-on.png" id="playBtn" />
			<!--
			<audio controls height="100" width="100">
				<source src="<?php print $sound; ?>" type="audio/mpeg">
				<embed height="50" width="50" src="<?php print $sound; ?>">
			</audio> 
			-->
			<div style="font-size:50%;font-style:italic;margin-top:-0px;">
				<?php print $phonetic ?>
			</div>
		</h1>
		<!--form action="#" method="POST">
			<div data-role="fieldcontain" class="ui-hide-label">
				<label for="translate">Перевод:</label>
				<input type="text" name="translate" id="translate" data-mini="true" placeholder="ввести перевод" />
			</div>
		</form-->
		<div data-role="collapsible" data-theme="b">
			<h3>Перевод</h3>
			<!--div data-role="navbar" class="ui-body-c">
				<ul>
					<li><a data-ajax="false" href="lexicon.php?word-0=<?php print $next ?>&result-0=false">Ошибка</a></li>
					<li><a data-ajax="false" href="lexicon.php?word-0=<?php print $next ?>&result-0=true">Верно</a></li>
					<li><a data-ajax="false" href="lexicon.php?word-0=<?php print $next ?>&result-0=true&drop-0=true">Выучено</a></li>
				</ul>
			</div-->
			<ul data-role="listview" data-inset="true" data-divider-theme="b">
				
				<?php
					foreach($trans_serv->dict as $dict)
					{
						print "<li data-role=\"list-divider\" >Part-Of-Speech: ".$dict->pos."</li>";
						foreach( $dict->terms as $term )
						{
							print "<li><h3>".$term."</h3><p>";
							//$rt = trim( $term );
							//if( !preg_match( '/[^а-я]/', $rt ) )
							//{
								//Запишем русское слово в русско-английский словарь
							//	$query = "INSERT INTO `dictionary_ru_en` SET `word`='".$rt."'";
							//	$res = mysql_query($query);
							//}
							foreach( $dict->entry as $entry )
							{
								if( $entry->word == $term )
								{
									if( isset( $entry->reverse_translation ) )
									{
										foreach( $entry->reverse_translation as $rt )
										{
											// Тут было бы не плохо пополнить словарь словами синонимами
											// следует только убрать словосочетания
											$rt = trim( $rt );
											if( !preg_match( '/[^a-z]/', $rt ) )
											{
												Lexicon_Dictionary::uploadWord( $rt );
											}
											print $rt."; ";
										}
									}
								}
							}
							print "</p></li>";
						}
					}
				?>
			</ul>
			<div data-role="navbar" class="ui-body-c">
				<ul>
					<li><a data-ajax="false" href="lexicon.php?word-0=<?php print $next ?>&result-0=false">Ошибка</a></li>
					<li><a data-ajax="false" href="lexicon.php?word-0=<?php print $next ?>&result-0=true">Верно</a></li>
					<li><a data-ajax="false" href="lexicon.php?word-0=<?php print $next ?>&result-0=true&drop-0=true">Выучено</a></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="content-primary">
		<p>
			Лексикон - простой и доступный сервис для изучения слов английского языка.
		</p>
		<p>
			Для изучения используется частотный словарь (в настоящее время <?php print number_format(Lexicon_Dictionary::howManyWords(),0,' ',' '); ?> слов).
			Сервис подбирает случайным образом слова для изучения и регистрирует результаты перевода.
			Оценка результата производится самим пользователем нажатием кнопок "Ошибка"(неправильный ответ) или "Верно"(правильный ответ).
		</p>
		<p>
			Для каждого слова необходимо дать правильный ответ 8 раз, чтобы оно считалось выученным.
			Можно сделать слово выученным без 8 правильных ответов, нажав на кнопку "Выучено".
			Если выбрана кнопка "Ошибка" на уже выученном ранее слове, то оно становится новым.
		</p>
		<!--div data-role="collapsible" data-collapsed="false" data-theme="b">
			<h3>Сейчас учатся</h3>
			<ul data-role="listview" data-inset="true" data-divider-theme="b">
				<?php
					//print "<li data-role=\"list-divider\" >Новые слова</li>";
					//foreach($contr->words as $w=>$wData)
					//{
					//	print "<li><h3>".$w."</h3><!--p>".("Накопленных правильных ответов ".$wData["cum"])."</p--></li>";
					//}
				?>
			</ul>
		</div-->
	</div>
	</div><!-- page -->
	
	<div data-role="footer" data-theme="a">
		<p>&#160;Выучено: <?php print $contr->getUsed() ?> | Сегодня: <?php print $contr->getUsed(date('Y-m-d'))/*print $contr->td*/ ?></p>
	</div><!-- footer -->
</div>
</body>
</html>
