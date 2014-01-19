<?php
/* 
 */

/**
 * Description of DB_ReflectionMySQLTable
 *
 * @author slavb
 */
class DB_ReflectionMySQLTable {
	/**
	 *
	 * @var string
	 */
	public $TableName;
	/**
	 *
	 * @var string
	 */
	public $Comment;
	/**
	 * DB_ReflectionMySQLField[]
	 */
	public $Fields;
	/**
	 *
	 * @var DB_ReflectionMySQLKey[]
	 */
	public $Keys;
	
	public function __construct($tableName) {
		$this->TableName=$tableName;
		$query="SHOW TABLE STATUS LIKE '$tableName'";
		$res=mysql_query($query);
		if(!$res) trigger_error(mysql_error());
		$tableInfo=mysql_fetch_assoc($res);
		$this->Comment=$tableInfo["Comment"];
		$query="SHOW FULL COLUMNS FROM `$tableName`";
		$res=mysql_query($query);
		if(!$res) trigger_error(mysql_error());
		$cnt=mysql_num_rows($res);
		$this->Fields=array();
		for($i=0;$i<$cnt;$i++){
			$row=mysql_fetch_assoc($res);
			$f=new DB_ReflectionMySQLField($row);
			$this->Fields[$f->Field]=$f;
		}
		$query="SHOW KEYS FROM `$tableName`";
		$res=mysql_query($query) or trigger_error(mysql_error());
		$cnt=mysql_num_rows($res);
		$keyNum=0;
		for($i=0;$i<$cnt;$i++) {
			$key=mysql_fetch_assoc($res);
			if($key["Seq_in_index"]=="1") {
				$keyNum++;
				$this->Keys[$keyNum]=new DB_ReflectionMySQLKey($key);
			}else{
				$this->Keys[$keyNum]->addColumn($key["Column_name"]);
			}
		}
	}
	/**
	 * Получить поле по имени.
	 * @param string $fieldName имя поля
	 * @return DB_ReflectionMySQLField
	 */
	public function getFieldByName($fieldName){
		return isset($this->Fields[$fieldName])?$this->Fields[$fieldName]:NULL;
	}

	/**
	 * Получить ключ по номеру.
	 * @param integer $keyNum номер ключа
	 * @return DB_ReflectionMySQLKey
	 */
	public function getKeyByNum($keyNum){
		return isset($this->Keys[$keyNum])?$this->Keys[$keyNum]:NULL;
	}
}
?>
