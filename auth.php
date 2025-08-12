<?php
// auth.php — helpers de autenticación
require_once __DIR__ . "/config.php";

function login(string $email, string $password): bool {
  global $conn;
  $stmt = $conn->prepare("SELECT id, name, email, password_hash, role, status, school_id FROM users WHERE email=? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->bind_result($id, $name, $em, $hash, $role, $status, $school_id);
  if ($stmt->fetch() && (int)$status === 1 && password_verify($password, $hash)) {
    // Guardar sesión completa
    $_SESSION['uid']       = (int)$id;
    $_SESSION['name']      = $name;
    $_SESSION['email']     = $em;
    $_SESSION['role']      = $role;       // 'admin' | 'maestro' | 'alumno'
    $_SESSION['school_id'] = $school_id ? (int)$school_id : null;
    return true;
  }
  return false;
}

function logout(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"] ?? '', $params["secure"], $params["httponly"]);
  }
  session_destroy();
}

function current_user_id(){ return $_SESSION['uid'] ?? null; }
function current_role(){ return $_SESSION['role'] ?? null; }

function require_login(): void {
  if (!current_user_id()) {
    header("Location: " . rtrim(($GLOBALS['basePath'] ?? ''), '/') . "/login.php?e=1");
    exit;
  }
}
?>