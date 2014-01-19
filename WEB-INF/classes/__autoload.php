<?php
/**
 *
 * загрузчик классов.
 *
 *  @author dab@ilb.ru
 */

function __autoload( $c ){
	$f=dirname(__FILE__)."/".str_replace("_","/",$c).".php";
	// проверяем сами - чтоб отловить место где произошла ошибка (стандартное сообщение неинформатвно)
	if(!is_readable($f)){ // файл класса не существует или недоступен
		// из __autoload нельзя выбросить исключение, поэтому зовем обработчик ошибок явно
		UncaughtFatalErrorExceptionHandler(new FatalErrorException("File '".$f."' not readable"));
	}
	require_once($f);
	if(!class_exists($c,FALSE)&&!interface_exists($c,FALSE)){ // проверяем загрузился ли класс
		UncaughtFatalErrorExceptionHandler(new FatalErrorException("Class '".$c."' not found in '".$f."'"));
	}
}