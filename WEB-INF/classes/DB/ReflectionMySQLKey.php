<?php
/* 
 */

/**
 * Description of DB_ReflectionMySQLField
 *
 * @author slavb
 */
class DB_ReflectionMySQLKey {
	/**
	 *
	 * @var string
	 */
	
	public $Key_name;
	/**
	 *
	 * @var boolean
	 */
	public $Non_unique;
	/**
	 *
	 * @var array
	 */
	public $Column_names;
	
	public function __construct($row) {
		$this->Key_name=$row["Key_name"];
		$this->Non_unique=$row["Non_unique"];
		$this->Column_names[]=$row["Column_name"];
	}

	public function addColumn($colname){
		$this->Column_names[]=$colname;
	}
	public function hasColumn($colname){
		return in_array($colname,$this->Column_names);
	}

}
?>
