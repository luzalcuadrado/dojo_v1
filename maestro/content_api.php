<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

if (empty($_SESSION['uid'])) out(['ok'=>false,'msg'=>'No autenticado']);
$uid = (int)$_SESSION['uid'];

$st = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
$st->bind_param('i', $uid);
$st->execute();
$st->bind_result($role);
$st->fetch();
$st->close();
if($role !== 'maestro') out(['ok'=>false,'msg'=>'Solo maestros']);

$action = $_REQUEST['action'] ?? 'list';

if ($action === 'list') {
  $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
  $grade_id  = isset($_GET['grade_id']) ? (int)$_GET['grade_id'] : 0;
  $q         = trim($_GET['q'] ?? '');

  if(!$course_id) out(['ok'=>true,'items'=>[]]);

  $sql = "
    SELECT c.id, c.title, c.content_type, c.url, c.seq, c.grade_id, g.name AS grade_name
    FROM contents c
    JOIN grades g ON g.id = c.grade_id
    WHERE c.course_id=? AND c.status=1
  ";
  $types = 'i';
  $params = [$course_id];

  if($grade_id){ $sql .= " AND c.grade_id=? "; $types.='i'; $params[] = $grade_id; }
  if($q!==''){ $sql .= " AND c.title LIKE ? "; $types.='s'; $params[] = '%'.$q+'%'; }

  $sql .= " ORDER BY g.ord DESC, c.seq ASC";

  $st = $conn->prepare($sql);
  $st->bind_param($types, ...$params);
  $st->execute();
  $res = $st->get_result();
  $items = [];
  while($row = $res->fetch_assoc()){ $items[] = $row; }
  $st->close();
  out(['ok'=>true,'items'=>$items]);
}

if ($action === 'get') {
  $id = (int)($_GET['id'] ?? 0);
  $st = $conn->prepare("SELECT id, course_id, grade_id, title, content_type, url, body, seq FROM contents WHERE id=? LIMIT 1");
  $st->bind_param('i', $id);
  $st->execute();
  $res = $st->get_result();
  $item = $res->fetch_assoc();
  $st->close();
  if(!$item) out(['ok'=>false,'msg'=>'No encontrado']);
  out(['ok'=>true,'item'=>$item]);
}

if ($action === 'save') {
  $id          = (int)($_POST['id'] ?? 0);
  $course_id   = (int)($_POST['course_id'] ?? 0);
  $grade_id    = (int)($_POST['grade_id'] ?? 0);
  $title       = trim($_POST['title'] ?? '');
  $content_type= trim($_POST['content_type'] ?? 'youtube');
  $url         = trim($_POST['url'] ?? '');
  $body        = $_POST['body'] ?? null;

  if(!$course_id || !$grade_id || $title==='') out(['ok'=>false,'msg'=>'Faltan datos']);

  if($id>0){
    $st = $conn->prepare("UPDATE contents SET grade_id=?, title=?, content_type=?, url=?, body=? WHERE id=?");
    $st->bind_param('issssi', $grade_id, $title, $content_type, $url, $body, $id);
    $st->execute();
    $st->close();
  }else{
    $st = $conn->prepare("SELECT COALESCE(MAX(seq),0)+1 FROM contents WHERE course_id=? AND grade_id=?");
    $st->bind_param('ii', $course_id, $grade_id);
    $st->execute();
    $st->bind_result($next_seq);
    $st->fetch();
    $st->close();

    $st = $conn->prepare("INSERT INTO contents (course_id, grade_id, title, content_type, url, body, seq, status, created_by) VALUES (?,?,?,?,?,?,?,1,?)");
    $st->bind_param('iissssii', $course_id, $grade_id, $title, $content_type, $url, $body, $next_seq, $uid);
    $st->execute();
    $id = $st->insert_id;
    $st->close();
  }

  $st = $conn->prepare("SELECT id, course_id, grade_id, title, content_type, url, body, seq FROM contents WHERE id=?");
  $st->bind_param('i', $id);
  $st->execute();
  $res = $st->get_result();
  $item = $res->fetch_assoc();
  $st->close();

  out(['ok'=>true,'item'=>$item]);
}

if ($action === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if(!$id) out(['ok'=>false,'msg'=>'ID inválido']);
  $st = $conn->prepare("DELETE FROM contents WHERE id=?");
  $st->bind_param('i', $id);
  $st->execute();
  $st->close();
  out(['ok'=>true]);
}

if ($action === 'reorder') {
  $ids = trim($_POST['ids'] ?? '');
  if($ids==='') out(['ok'=>false,'msg'=>'Sin ids']);
  $parts = array_filter(array_map('intval', explode(',', $ids)));
  $seq = 1;
  foreach($parts as $cid){
    $st = $conn->prepare("UPDATE contents SET seq=? WHERE id=?");
    $st->bind_param('ii', $seq, $cid);
    $st->execute();
    $st->close();
    $seq++;
  }
  out(['ok'=>true]);
}

out(['ok'=>false,'msg'=>'Acción no válida']);
