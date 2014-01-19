<?php
/* 
 */

/**
 * Информация об ошибке MySQL
 */
class DB_MySQLError extends Exception{
	/**
	 * @var string таблица
	 */
	protected $tableName;
	
	/**
	 * @var string код ошибки
	 */
	protected $mysqlErrno;

	/**
	 * @var string сообщение об ошибке
	 */
	protected $mysqlError;

	public function __construct($tableName,$code=550){
		$this->tableName=$tableName;
		$this->mysqlError=mysql_error();
		$this->mysqlErrno=mysql_errno();
		$message=$this->mysqlError;
		if($this->mysqlErrno==1062){
			$code=459;
			$keyNum=+strrchr($this->mysqlError," ");
			$tbr=new DB_ReflectionMySQLTable($tableName);
			$key=$tbr->getKeyByNum($keyNum);
			if($key){
				$message="Обнаружен дубликат по полю ";
				foreach($key->Column_names as $colname){
					$message.="\"".$tbr->getFieldByName($colname)->Comment."\",";
				}
				$message=substr($message,0,-1);
			}
		}
		parent::__construct($message,$code);
	}
	/*
	public function __toString() {
		return "HTTP".$this->code." ".$this->message;
	}*/

}
?>
