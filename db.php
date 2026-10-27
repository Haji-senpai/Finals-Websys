<?php
// XAMPP LOCAL-DEVELOPMENT defaults only. Never deploy with root/blank credentials.
// Environment variables override these values for a dedicated database account.
define('DB_HOST', getenv('GRC_DB_HOST') !== false ? getenv('GRC_DB_HOST') : 'localhost');
define('DB_NAME', getenv('GRC_DB_NAME') !== false ? getenv('GRC_DB_NAME') : 'grc_campus');
define('DB_USER', getenv('GRC_DB_USER') !== false ? getenv('GRC_DB_USER') : 'root');
define('DB_PASS', getenv('GRC_DB_PASS') !== false ? getenv('GRC_DB_PASS') : '');
date_default_timezone_set('Asia/Manila');
ini_set('session.gc_maxlifetime','7200');
ini_set('session.use_strict_mode','1');
ini_set('session.use_only_cookies','1');
$isHttps=(!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS'])!=='off') || (($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https');
session_set_cookie_params(['lifetime'=>0,'httponly'=>true,'samesite'=>'Lax','secure'=>$isHttps]);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
session_start();
try {
 $db=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 $db->exec("SET time_zone='+08:00'");
} catch(PDOException $x) {
 error_log('GRC Campus database connection failed: '.$x->getMessage());
 http_response_code(503);
 header('Content-Type: text/plain; charset=utf-8');
 die('Database connection failed. Confirm MySQL is running in XAMPP, the grc_campus database exists, and config/db.php credentials match your MySQL account. The technical error was written to the PHP error log.');
}
