<?php
require_once "WEB-INF/conf/session.php";

session_destroy();
$cp=session_get_cookie_params();
setcookie(urlencode(session_name()),"",0,$cp["path"],$cp["domain"],$cp["secure"],$cp["httponly"]);
header("Location: index.php");
exit(0);