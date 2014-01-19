<?php
class Facebook_User extends User
{

	public static function get($id)
	{
		$user = new Facebook_User();
		// считаем конструктор из базы
		$query="SELECT * FROM users WHERE id='".mysql_real_escape_string($id)."';";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		while($row=mysql_fetch_assoc($res) ){
			$user->id=$row["id"];
			$user->name=$row["name"];
			$user->username=$row["username"];
			$user->email=$row["email"];
			$user->meta=unserialize($row["me"]);
		}
		//print_r($row);exit;
		return $user;
	}

	public function add($id,$meta)
	{
		$query = "INSERT INTO users 
					(id,username,name,email,me) 
					VALUES(
						'".mysql_real_escape_string($id)."',
						'".mysql_real_escape_string(isset($meta["username"])?$meta["username"]:$id)."',
						'".mysql_real_escape_string(isset($meta["name"])?$meta["name"]:"Аноним")."',
						'".mysql_real_escape_string(isset($meta["email"])?$meta["email"]:"")."',
						'".mysql_real_escape_string(serialize($meta))."');";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		return TRUE;
	}
	
	public function del($id)
	{
		$query="DELETE FROM users WHERE id='".mysql_real_escape_string($id)."';";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		return TRUE;
	}
	
	public function upd($id,$meta)
	{
		$query = "UPDATE users SET
					username='".mysql_real_escape_string(isset($meta["username"])?$meta["username"]:$id)."',
					name='".mysql_real_escape_string(isset($meta["name"])?$meta["name"]:"Аноним")."',
					email='".mysql_real_escape_string(isset($meta["email"])?$meta["email"]:"")."',
					me='".mysql_real_escape_string(serialize($meta))."' 
					WHERE id='".mysql_real_escape_string($id)."';";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		return TRUE;
	}
}