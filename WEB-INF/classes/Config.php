<?php
class Config {
	public static $USER;/** авторизированый пользователь */
	public static $BASE;/** рутовый каталог */
	public static $CONTENT_LANG;/** Текущая локализация */
	public static $CONTENT_LANGSET;/** Набор вариантов локализации */
	public static $DEFAULT_LANG;/** дефолтная локализация */
	public static $DB1;/** база lexicon */
	public static $fb_ca_chain_bundle; /** сертификат */
	public static $FB_APP_ID;
	public static $FB_SECRET;
	public static $destroy_path;

	public static function init() {
		
		Config::$destroy_path = "http://".$_SERVER["HTTP_HOST"]."/destroy.php";
		//Config::$USER = isset($_SERVER["REMOTE_USER"])?$_SERVER["REMOTE_USER"]:(isset($_SERVER["REDIRECT_REMOTE_USER"])?$_SERVER["REDIRECT_REMOTE_USER"]:NULL);
		Config::$USER = isset($_SESSION["user_id"])?$_SESSION["user_id"]:NULL;
		Config::$CONTENT_LANG = Config::$DEFAULT_LANG;
		if( isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) )
		{
			$langs = preg_split( '/,/', $_SERVER["HTTP_ACCEPT_LANGUAGE"], -1 );
			for( $i = 0; $i < count( $langs ); $i++ )
			{
				$lang = preg_split( '/;/', $langs[$i], -1 );
				if( isset( Config::$CONTENT_LANGSET[strtoupper( $lang[0] )] ) )
				{
					Config::$CONTENT_LANG = Config::$CONTENT_LANGSET[strtoupper( $lang[0] )];
					break;
				}
			}
		}
		// Create our Application instance (replace this with your appId and secret).
		Config::$FB_APP_ID = '628691480496313';
		Config::$FB_SECRET = '7731e8497fdfb63744381665848a1989';
	}
}