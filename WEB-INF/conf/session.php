<?php
/*
создаем пользовательскую сессию с привязкой по кукам браузера
сразу устанавливаем уникальные параметры куки чтобы не мешать другим приложениям и не путаться с ними данными из сессии
также сразу кладем в сессию идентификатор пользователя чтоб по файлу определить принадлежность пользователю
*/
session_set_cookie_params ( 0, "/" );
session_name( "PHPSESSID_" );
session_start();
//$_SESSION[session_name()] = $_SERVER["REMOTE_USER"];
