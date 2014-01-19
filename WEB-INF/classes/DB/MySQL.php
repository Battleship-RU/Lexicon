<?php
/* 
 */

/**
 * конфигурация дб
 * отдельным классом
 * общий для всех типов и экземпляров коннектов
 */
class DB_MySQL {
/**
 * @var string параметр хост
 */
	public $host=NULL;
	/**
	 * @var string параметр база
	 */
	public $base=NULL;
	/**
	 * @var string параметр пользователь
	 */
	public $user=NULL;
	/**
	 * @var string параметр пароль
	 */
	public $pass=NULL;
	/**
	 * @var object коннект
	 */
	public $con=NULL;

	public function __construct($host,$base,$user,$pass) {
		$this->host=$host; $this->base=$base;
		$this->user=$user; $this->pass=$pass;
		$this->con=mysql_connect($this->host,$this->user,$this->pass);
		if ($this->con===FALSE) trigger_error(mysql_error());
		$res=mysql_select_db($this->base);
		if ($res===FALSE) trigger_error(mysql_error());
	}
	function lastMod($table=NULL) {
		$result=array();
		$query="SHOW TABLE STATUS";
		if ($table!==NULL) $query.=" LIKE '$table'";
		$res=mysql_query($query,$this->con);
		$cnt=mysql_num_rows($res);
		for ($i=0;$i<$cnt;$i++) {
			$row=mysql_fetch_assoc($res);
			$result[$row["Name"]]=date_format(date_create($row["Update_time"]),"U");
		}
		return $result;
	}
}
?>
