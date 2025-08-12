<?php
// config.php — conexión y sesión robusta para subcarpeta
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// === Ajusta estos valores a tu entorno ===
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'dojo1';

// Si tu app vive en subcarpeta, ajústala aquí (ej. '/dojo_v1')
$basePath = $basePath ?? '/dojo_v1';

// === Sesión segura y con path correcto (para subcarpeta) ===
$cookiePath = rtrim($basePath, '/') ?: '/';
session_name('dojo_sid');
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => $cookiePath,
  'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
  'httponly' => true,
  'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) session_start();

// Conexión
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset('utf8mb4');
?>