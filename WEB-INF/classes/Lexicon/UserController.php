<?php
class Lexicon_UserController
{
	public $user;
	public $dtStart;
	public $max;
	public $fl=TRUE;
	public $repeatcounter=0;
	public $nextrule;
	public $repeatrule;
	public $words=array();
	public $lasts=array();
	public $total;
	public $true;
	public $false;
	public $cum;
	public $status;
	public $lastmodified;
	public $achivments=array();
	
	public function __construct($user){
		$this->user=$user;
		$this->dtStart=date("c");
	}
	
	public static function create($user){
		
		// считаем конструктор из базы
		$query="SELECT * FROM controllers WHERE user='".mysql_real_escape_string($user)."'";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		while($row=mysql_fetch_array($res) ){
			$controller = unserialize($row["controller"]);
			$controller->user=$user;
			// Для старых контроллеров
			if(!isset($controller->repeatcounter)) $controller->repeatcounter=0;
			if(!isset($controller->nextrule)) $controller->nextrule = Lexicon_Config::$CONTROLLER_NEXTRULE;
			if(!isset($controller->repeatrule)) $controller->repeatrule = 1;
			if(!isset($controller->lasts)) $controller->lasts=array();
		}
		if(!isset($controller)){
			// если контроллера у пользователя нет
			// то сздаем новый, сохраняем его в базу
			$controller = new Lexicon_UserController($user);
			$controller->nextrule = Lexicon_Config::$CONTROLLER_NEXTRULE;
			$controller->repeatrule = 1;
			$controller->add();
		}
		return $controller;
	}
	
	public function add(){
		$query="INSERT INTO controllers SET user='".mysql_real_escape_string($this->user)."', dtStart='".mysql_real_escape_string($this->dtStart)."', controller='".mysql_real_escape_string(serialize($this))."';";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
	}
	
	public function update(){
		$query="UPDATE controllers SET controller='".mysql_real_escape_string(serialize($this))."' where user='".mysql_real_escape_string($this->user)."';";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
	}
	
	public function build(){
		// получить номер последнего слова которое использовалось для обучения
		// для этого просто берем число слов котоыре в перенесены в персональную 
		// базу пользователя
		$query = "SELECT recordId FROM words WHERE user='".mysql_real_escape_string($this->user)."'";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		$this->max = mysql_num_rows($res);
		// получить список слов, которые находятся на изучении
		// список ограничиваем по размеру сортируя его по дате добавления
		$query="SELECT * FROM words WHERE user='".$this->user."' AND status='new' ORDER BY dtAdd LIMIT 0,".Lexicon_Config::$CONTROLLER_SIZE.";";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		$this->words=array();// обнуляем массив изучаемых слов
		while($row=mysql_fetch_assoc($res) ){
			$this->words[$row["word"]] = $row;
		}
		// если слов меньше чем установленный Lexicon_Config::$CONTROLLER_SIZE размер
		// то необходимо догрузить новые слова для обучения
		$subtotal = count($this->words);
		if($subtotal<Lexicon_Config::$CONTROLLER_SIZE){
			$adds = Lexicon_Config::$CONTROLLER_SIZE - $subtotal;
			$query = "SELECT * FROM dictionary_en_ru ORDER BY wordId LIMIT ".$this->max.",".$adds.";";
			$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
			while($row=mysql_fetch_assoc($res)){
				// добавим в контроллер изучаемые слова
				$this->words[$row["word"]]["user"] = $this->user;
				$this->words[$row["word"]]["wordId"] = $row["wordId"];
				$this->words[$row["word"]]["word"] = $row["word"];
				$this->words[$row["word"]]["cum"] = 0;
				$this->words[$row["word"]]["true"] = 0;
				$this->words[$row["word"]]["false"] = 0;
				$this->words[$row["word"]]["status"] = "new";
				// теперь запишем новые слова в базу
				$query = "INSERT INTO words SET
					user='".mysql_real_escape_string($this->user)."',
					wordId='".mysql_real_escape_string($row["wordId"])."',
					word='".mysql_real_escape_string($row["word"])."',
					dtAdd='".date("c")."';";
				$res1=mysql_query($query) or trigger_error($query."\n".mysql_error());
				$this->max++;
			}
			//$this->max += $adds;
		}
		// получить информацию о числе слов проверенных сегодня
		$query="SELECT count(wordId) AS td FROM words WHERE user='".$this->user."' AND LEFT(lastmodified,10)=CURDATE();";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		while($row=mysql_fetch_assoc($res) ){
			$this->td = $row["td"];
		}
	}
	
	public function fixResult($word,$result,$drop){
		// достать слово установить увеличить счетчик true/false
		// изменить счетчик cum
		// изменить статус если число правильных ответов превысило Lexicon_Config::$CONTROLLER_NEXTRULE
		$query = "SELECT * FROM words WHERE word='".mysql_real_escape_string($word)."' AND user='".mysql_real_escape_string($this->user)."';";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		while($row=mysql_fetch_array($res) ){
			if($result=="true"){
				$row["true"]++;
				$row["cum"]++;
			}else{
				// если допустил ошибку то счетчик правильных ответов обнуляется
				// слово вньвь признается новым
				$row["false"]++;
				$row["cum"]=0;
				$row["status"]="new";
			}
			if($row["cum"]>=Lexicon_Config::$CONTROLLER_NEXTRULE) $row["status"] = "used";
			break;
		}
		//print "<pre>";
		//print_r($row);
		//exit;
		if(!isset($row)) return;
		// если указана переменная drop то делаем слово выученным вне зависимости от количества правильных ответов
		if($drop=="true"){
			$row["cum"]=8;
			$row["status"]="used";
		}
		$query = "UPDATE words SET
			`true`='".mysql_real_escape_string($row["true"])."',
			`false`='".mysql_real_escape_string($row["false"])."',
			`cum`='".mysql_real_escape_string($row["cum"])."',
			`status`='".mysql_real_escape_string($row["status"])."'
			WHERE
			user='".mysql_real_escape_string($this->user)."' AND word='".mysql_real_escape_string($word)."';";
		//print $query;exit;
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		// запишем слово в массив последних слов
		// слово не должно повторятся если чаще чем каждые Lexicon_Config::$CONTROLLER_SIZE слов
		array_push($this->lasts,$word);
		while(count($this->lasts)>Lexicon_Config::$CONTROLLER_SIZE){
			array_shift($this->lasts);
		}
		
		
		// Facebook
		// Create our Application instance (replace this with your appId and secret).
		$facebook = new Facebook_Facebook(array(
			'appId'  => Config::$FB_APP_ID,
			'secret' => Config::$FB_SECRET
		));
		$app_access_token = $facebook->getAccessToken();
		$attachment = array( "score" => $this->getUsed(), "access_token" => $app_access_token );
		$postscore = $facebook->api("/".$facebook->getUser()."/scores","POST", $attachment);
		// Если за день выучил 10,20,30,40,50 слов, то публикуем сообщение об этом
		$tu = $this->getUsed(date("Y-m-d"));
		switch($tu)
		{
			case 10:
			case 20:
			case 30:
			case 40:
			case 50:
				// если такого достижения за сегодня нет, то публикуем сообщение
				if(!isset($this->achivments["day"][$tu])||$this->achivments["day"][$tu]!=date("Y-m-d"))
				{
					$msg = "Сегодня выучил ".$tu." новых английских слов. Всего выучено ".$this->getUsed()." слов.";
					$this->achivments["day"][$tu] = date("Y-m-d");
				}
				break;
		}
		if(isset($msg))
		{
			$attachment = array( "message" => $msg, "access_token" => $app_access_token );
			$postscore = $facebook->api("/".$facebook->getUser()."/feed","POST", $attachment);
		}
		// Если за день сделал 100 правильных ответов, то публикуем сообщение об этом
		
		// сохраним контроллер
		$this->update();
		
		//print_r($postscore);exit();
	}
	
	public function getNext(){
		// на каждое новое слово из списка
		// вспоминаем выученное слово
		if($this->max - count($this->words) <= Lexicon_Config::$CONTROLLER_SIZE * 2){
			// если выученных слов меньше Lexicon_Config::$CONTROLLER_SIZE * 2, 
			// то пока их не повторяем выученные слова
			$this->fl=TRUE;
		}
		if( count($this->words) == 0 ){
			// если новых слов для изучения нет то посторяет старые слова
			$this->fl=FALSE;
		}
		if($this->fl==TRUE){
			// если флаг TRUE то учим новые слова
			// берем массив слов на обучении
			$keys = array_keys($this->words);
			//print "<pre>";
			//print_r($keys);
			//print_r($this->lasts);
			//print_r($this->max);
			//print_r(Lexicon_Config::$CONTROLLER_SIZE);
			//exit;
			
			// убираем те которые недавно изучали
			// если выученных слов больше Lexicon_Config::$CONTROLLER_SIZE * 2, 
			// если массив изучаемых слов целиком входит в lasts то убирать недавно изученные не следует
			if($this->max - count($this->words) > Lexicon_Config::$CONTROLLER_SIZE * 2 && count($this->words) > Lexicon_Config::$CONTROLLER_SIZE / 2){
				$keys = array_diff($keys,$this->lasts);
				$keys = array_values($keys);
			}
			//случайным образом достаем из остатка слово для изучения
			$rand = rand(0,count($keys)-1);
			// устанавливаем флаг в FALSE чтобы при следующем обращении 
			// проверить знание изученного слова
			$this->fl=FALSE;
			
			$this->true = $this->words[$keys[$rand]]["true"];
			$this->false = $this->words[$keys[$rand]]["false"];
			$this->total = $this->true + $this->false;
			$this->cum = $this->words[$keys[$rand]]["cum"];
			$this->status = "НОВОЕ";
			$this->lastmodified = NULL;
			return $keys[$rand];
		}else{
			// если флаг FALSE то проверяем старые
			// выбираем Lexicon_Config::$CONTROLLER_SIZE * 2 с самым старым значением lastmodified
			// среди них выбираем случайное
			// построим строку условия последних изученных слов
			$rand = rand(0,Lexicon_Config::$CONTROLLER_SIZE*2-1);
			$query = "SELECT `word`, `cum`, `true`,`false`,`lastmodified` FROM words WHERE user='".mysql_real_escape_string($this->user)."' AND status='used' ORDER BY lastmodified LIMIT ".$rand.",1;";
			$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
			while($row=mysql_fetch_array($res)){
				$this->fl=TRUE;
				$this->true = $row["true"];
				$this->false = $row["false"];
				$this->total = $this->true + $this->false;
				$this->cum = $row["cum"];
				$this->status = "ВЫУЧЕННОЕ";
				$this->lastmodified = $row["lastmodified"];
				return $row["word"];
			}
		}
	}
	function getUsed($dt=FALSE){
		$dtWhere = $dt ? " AND cum = '8' AND LEFT(lastmodified,10)='".$dt."'" : "";
		$query = "SELECT count(`word`) AS used FROM words WHERE user='".mysql_real_escape_string($this->user)."' ".$dtWhere." AND status!='new'";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		if($row=mysql_fetch_array($res)){
			return $row["used"];
		}else{
			return 0;
		}
		//return $this->max - count($this->words);
	}
}
?>