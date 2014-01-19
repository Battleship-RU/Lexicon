<?php
class User{
	public $id;
	public $username;
	public $name;
	public $email;
	
	/** @return User */
	public static function createUser($id,$type)
	{
		switch($type)
		{
			case "facebook":
				$user = Facebook_User::get($id);
				break;
			default:
				$user = new User();
		}
		return $user;
	}
	
	public static function get($id){}
	
	public function add($id,$meta){}
	
	public function del($id){}
	
	public function upd($id,$meta){}
}