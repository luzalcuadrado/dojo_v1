<?php
// maestro/asignar_toggle.php - endpoint para encender/apagar un contenido para un alumno
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function out($ok, $msg=''){ echo json_encode(['ok'=>$ok,'msg'=>$msg]); exit; }

if (empty($_SESSION['uid'])) out(false,'No auth');
$maestroId = (int)$_SESSION['uid'];

// Validar rol
$st = $conn->prepare('SELECT role FROM users WHERE id=? LIMIT 1');
$st->bind_param('i',$maestroId);
$st->execute();
$st->bind_result($role);
$st->fetch(); $st->close();
if($role !== 'maestro') out(false,'Solo maestros');

$contentId = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
$userId    = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$enabled   = isset($_POST['is_enabled']) ? (int)$_POST['is_enabled'] : 0;
if($contentId<=0 || $userId<=0) out(false,'Datos insuficientes');

// Verifica que el maestro y el alumno compartan grupo
$st = $conn->prepare("
  SELECT 1
  FROM group_members gm_m
  JOIN group_members gm_a ON gm_a.group_id = gm_m.group_id AND gm_a.role_in_group='alumno'
  WHERE gm_m.user_id=? AND gm_m.role_in_group='maestro' AND gm_a.user_id=?
  LIMIT 1
");
$st->bind_param('ii',$maestroId,$userId);
$st->execute();
$ok = $st->get_result()->num_rows > 0;
$st->close();
if(!$ok) out(false,'El alumno no pertenece a tus grupos');

// Upsert en content_assignments
if($enabled === 1){
  // insertar o actualizar a enabled
  $st = $conn->prepare('INSERT INTO content_assignments (content_id,user_id,assigned_by,is_enabled) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE is_enabled=VALUES(is_enabled), assigned_by=VALUES(assigned_by)');
  $st->bind_param('iii',$contentId,$userId,$maestroId);
  $st->execute(); $st->close();
  out(true,'habilitado');
}else{
  // dejar registro pero en 0 o eliminar (aquí lo dejamos en 0)
  $st = $conn->prepare('INSERT INTO content_assignments (content_id,user_id,assigned_by,is_enabled) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE is_enabled=0, assigned_by=VALUES(assigned_by)');
  $st->bind_param('iii',$contentId,$userId,$maestroId);
  $st->execute(); $st->close();
  out(true,'deshabilitado');
}
