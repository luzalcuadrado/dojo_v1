<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['uid'])) { http_response_code(401); echo json_encode(['ok'=>false, 'msg'=>'No auth']); exit; }

$type = $_POST['type'] ?? '';
if ($type!=='pdf' && $type!=='image') { echo json_encode(['ok'=>false, 'msg':'Tipo inválido']); exit; }

if (!isset($_FILES['file'])) { echo json_encode(['ok'=>false,'msg':'Archivo faltante']); exit; }

$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) { echo json_encode(['ok'=>false,'msg':'Error de subida']); exit; }

$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$allowed = $type==='pdf' ? ['pdf'] : ['jpg','jpeg','png','gif','webp'];
if (!in_array($ext, $allowed)) { echo json_encode(['ok'=>false,'msg':'Extensión no permitida']); exit; }

$baseDir = realpath(__DIR__ . '/../uploads');
if(!$baseDir){ mkdir(__DIR__ . '/../uploads', 0775, true); $baseDir = realpath(__DIR__ . '/../uploads'); }

$fname = date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
$dest  = $baseDir . DIRECTORY_SEPARATOR . $fname;

if (!move_uploaded_file($f['tmp_name'], $dest)) { echo json_encode(['ok'=>false,'msg':'No se pudo mover el archivo']); exit; }

$url = '../uploads/' . $fname;
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true, 'url'=>$url]);
