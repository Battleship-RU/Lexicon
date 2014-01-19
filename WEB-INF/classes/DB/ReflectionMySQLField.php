<?php
/* 
 */

/**
 * Description of DB_ReflectionMySQLField
 *
 * @author slavb
 */
class DB_ReflectionMySQLField {
	public $Field;
	public $Type;
	public $Null;
	public $Default;
	public $Comment;
	public function __construct($row) {
		$this->Field=$row["Field"];
		$this->Type=$row["Type"];
		$this->Null=$row["Null"]=="YES";
		$this->Default=$row["Default"];
		$this->Comment=$row["Comment"];
	}
}
?>
