<?php

require_once( "WEB-INF/classes/__autoload.php" );
//require_once( "WEB-INF/lib/utils.php" );
//require_once( "WEB-INF/lib/dbutils.php" );

//устанавливаем глобальный конфиг приложения, мапим суперглобальные настройки в него и пр...
Config::$BASE = realpath( getcwd()."/../" );
Config::$CONTENT_LANGSET = array( "RU-RU"=>"RU", "RU"=>"RU", "EN"=>"EN", "EN-US"=>"EN" );
Config::$DEFAULT_LANG = "RU";
Config::$DB1=new DB_MySQL("localhost","lexicon","lexicon",$_SERVER["MYSQLUSER_LX_PASSWORD"]);
Config::$fb_ca_chain_bundle = "../../certs/fb_ca_chain_bundle.crt";
//вызаваем метод который содержит логику инициализации
Config::init();

//устанавливаем конфиг LEXICON
Lexicon_Config::$CONTROLLER_SIZE=30;
Lexicon_Config::$CONTROLLER_NEXTRULE=8;
//вызаваем метод который содержит логику инициализации
Lexicon_Config::init();

?>