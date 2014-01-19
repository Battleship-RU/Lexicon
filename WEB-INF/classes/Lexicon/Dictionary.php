<?php
class Lexicon_Dictionary
{

	public static function getWord( $word )
	{
		$word = mysql_real_escape_string( $word );
		$query = "SELECT * from dictionary_en_ru WHERE word='".$word."'";
		if( $res = mysql_query($query) )
		{
			 $rec = mysql_fetch_assoc($res);
			 return $rec;
		}
	}

	public static function uploadWord( $word, $transText = null, $transServ = null , $dictServ = null )
	{
		$word = mysql_real_escape_string( $word );
		$transText = mysql_real_escape_string( $transText );
		$transServ = mysql_real_escape_string( $transServ );
		$dictServ = mysql_real_escape_string( $dictServ );
		$query="INSERT INTO dictionary_en_ru SET word='".$word."', translatedText='".$transText."', transServ='".$transServ."', dictServ='".$dictServ."' ;";
		$res = mysql_query($query);
	}
	
	
	public static function updateWord( $word, $transText = null, $transServ = null , $dictServ = null )
	{
		$word = mysql_real_escape_string( $word );
		$transText = $transText ? " transText='".mysql_real_escape_string( $transText )."'," : " ";
		$transServ = $transServ ? " transServ='".mysql_real_escape_string( $transServ )."'," : " ";
		$dictServ = $dictServ ? " dictServ='".mysql_real_escape_string( $dictServ )."'," : " ";
		$query="UPDATE dictionary_en_ru SET ".substr($transText.$transServ.$dictServ,0,-1)." WHERE word='".$word."' ;";
		//print $query;exit;
		$res = mysql_query($query);
	}
	
	public static function howManyWords(){
		$query = "SELECT count(*) AS total FROM dictionary_en_ru;";
		$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
		if($row=mysql_fetch_array($res)){
			return $row["total"];
		}
	}
	
}
?>