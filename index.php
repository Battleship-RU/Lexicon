<?php
/**
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require_once( "WEB-INF/conf/session.php" );
require_once( "WEB-INF/conf/conf.php" );

$query = "SELECT count(*) AS count FROM users;";
$res=mysql_query($query) or trigger_error($query."\n".mysql_error());
while($row=mysql_fetch_array($res) ){
	$count = $row["count"];
}

// Create our Application instance (replace this with your appId and secret).
$facebook = new Facebook_Facebook(array(
  'appId'  => '628691480496313',
  'secret' => '7731e8497fdfb63744381665848a1989',
));

// Get User ID
$user = $facebook->getUser();

// We may or may not have this data based on whether the user is logged in.
//
// If we have a $user id here, it means we know the user is logged into
// Facebook, but we don't know if the access token is valid. An access
// token is invalid if the user logged out of Facebook.

if ($user) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    $user_profile = $facebook->api('/me');
	// создаем пользователя, обновляем данные по нему
	$u = User::createUser($user_profile["id"],"facebook");
	if($u->id)
	{
		$u->upd($user_profile["id"],$user_profile);
	} else {
		$u->add($user_profile["id"],$user_profile);
	}
	// Сохраним код пользователя в сессии
	$_SESSION["user_id"]=$user;
	//print_r($u);exit();
    
  } catch (Facebook_ApiException $e) {
    error_log($e);
    $user = null;
  }
}

// Login or logout url will be needed depending on current user state.
if ($user) {
	$logoutUrl = $facebook->getLogoutUrl();
} else {
	$params["scope"]="email,publish_actions";
	$loginUrl = $facebook->getLoginUrl($params);
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>battleship.ru</title>
		<link rel="stylesheet"  href="styles/jquerymobile/jquery.mobile.min.css" />
		<link rel="stylesheet"  href="styles/jplayer/jplayer.blue.monday.css" />
		<script src="scripts/jquery/jquery.js"></script>
		<script src="scripts/jplayer/jquery.jplayer.min.js"></script>
		<script src="scripts/jquerymobile/jquery.mobile.min.js"></script>
		<style type="text/css">
			.footer-docs {padding: 5px 0;}
			.footer-docs p {margin-left:15px;font-weight: normal;font-size: .9em;}
			@media all and (min-width: 650px){
				.content-secondary {text-align:left;float:left;width:60%}
				.content-primary {text-align:center;width:40%;float:right;padding:30px 0 20px 0;}
			}
			@media all and (min-width: 750px){
				.content-secondary {width:65%;}
				.content-primary {width:35%;}
			}

			@media all and (min-width: 1200px){
				.content-secondary {width:70%;}
				.content-primary {width:30%;}
			}
		</style>
	</head>
	<body>
		<div id="fb-root"></div>
		<script>
			(function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
				js.src = "//connect.facebook.net/ru_RU/all.js#xfbml=1";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));
		</script>
		<div data-role="page" data-theme="d">
			<div data-role="header" data-theme="a">
				<h1><?php print ($user?$user_profile["name"]:"Добро пожаловать!") ?></h1>
				<?php if($user) print "<a data-ajax=\"false\" href=\"".$logoutUrl."\" data-icon=\"delete\" class=\"ui-btn-right\">Logout</a>"; ?>
			</div>
			<!-- header -->
			<div data-role="content">
				<div class="content-primary">
					<?php 
						if(!$user)
						{ 
							//print "<a href=\"".$loginUrl."\">Login with Facebook</a>";
							print "<a data-ajax=\"false\" href=\"".$loginUrl."\"><img src=\"images/facebook_login5.png\" alt=\"facebook_login5\"></a>";
						}
						else
						{
							print "<img src=\"https://graph.facebook.com/".$user."/picture?type=large\"><br/><br/>";
							//print "<a data-ajax=\false\" href=\"".$logoutUrl."\"><img src=\"images/logout_FB.png\"></a>";
							//print "<a data-ajax=\"false\" href=\"".$logoutUrl."\">Выйти</a>";
							//print_r(dirname($_SERVER["SCRIPT_NAME"]));
							//print $user;
							//print $logoutUrl;
						} 
					?>
				</div>
				<div class="content-secondary">
					<p>
						<?php 
							if($user) print "<a data-ajax=\"false\" href=\"lexicon.php\">Лексикон</a>";
							else print "Лексикон";
						?>
						 - простой и доступный сервис для изучения слов английского языка.
					</p>
					<p> 
						Для изучения используется частотный словарь (в настоящее время <?php print number_format(Lexicon_Dictionary::howManyWords(),0,' ',' '); ?> слов).
						Сервис подбирает случайным образом слова для изучения и регистрирует результаты перевода.
						Оценка результата производится самим пользователем нажатием кнопок "Ошибка"(неправильный ответ) или "Верно"(правильный ответ).
					</p>
					<p>
						Для каждого слова необходимо дать правильный ответ 8 раз, чтобы оно считалось выученным.
						Можно сделать слово выученным без 8 правильных ответов, нажав на кнопку "Выучено".
						Если выбрана кнопка "Ошибка" на уже выученном ранее слове, то оно становится новым.
					</p>
					<p>
						Для использования сервиса требуется регистрация через Facebook.
					</p>
					<div class="fb-like" data-href="http://www.battleship.ru" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false" data-font="verdana"></div>
				</div>
			
			</div><!-- /content -->
			<div data-role="footer" class="footer-docs" data-theme="c">
				<p>&copy; 2013 Battleship.ru <?php print $count ?> user(s).</p>
			</div>
		</div>
	</body>
</html>
