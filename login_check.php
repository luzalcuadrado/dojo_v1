<?php
// login_check.php — procesa login
require_once __DIR__ . "/auth.php";

$email = trim($_POST['email'] ?? '');
$pass  = trim($_POST['password'] ?? '');

if (!login($email, $pass)) {
  header("Location: " . rtrim(($basePath ?? ''), '/') . "/login.php?e=1");
  exit;
}

// Redirigir según rol
$role = $_SESSION['role'] ?? 'alumno';
$dest = 'alumno';
if ($role === 'admin')   $dest = 'admin';
if ($role === 'maestro') $dest = 'maestro';

header("Location: " . rtrim(($basePath ?? ''), '/') . "/{$dest}/");
exit;
?>