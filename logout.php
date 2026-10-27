<?php require __DIR__.'/../includes/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: login.php');exit;} check_csrf();
if(me())audit('logout','session');
$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();header('Location: login.php');exit;
