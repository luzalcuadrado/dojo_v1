<?php
// maestro/avisos_api.php — Opción 1: usa announcements.school_id y announcement_grades
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function out($a){ echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function fail($msg, $http=200){ http_response_code($http); out(['ok'=>false,'msg'=>$msg]); }

try{
  if (empty($_SESSION['uid'])) fail('No autenticado', 401);
  $uid = (int)$_SESSION['uid'];

  // Solo maestros
  $st = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
  $st->bind_param('i', $uid);
  $st->execute();
  $st->bind_result($role);
  $st->fetch();
  $st->close();
  if($role !== 'maestro') fail('Solo maestros', 403);

  $action = $_REQUEST['action'] ?? 'list';

  if ($action === 'list') {
    $q = trim($_GET['q'] ?? '');
    $sql = "SELECT id, title, school_id, starts_at, ends_at, status FROM announcements";
    $params = []; $types='';
    if($q!==''){ $sql .= " WHERE title LIKE ?"; $params[]='%'.$q.'%'; $types.='s'; }
    $sql .= " ORDER BY id DESC LIMIT 200";
    $st = $conn->prepare($sql);
    if($params){ $st->bind_param($types, ...$params); }
    $st->execute();
    $res = $st->get_result();
    $items = [];
    while($row = $res->fetch_assoc()){
      // determinar scope_label
      $scope_label = 'Público';
      if(!is_null($row['school_id'])){
        // ¿tiene grado asociado?
        $st2 = $conn->prepare("SELECT COUNT(*) FROM announcement_grades WHERE announcement_id=?");
        $st2->bind_param('i', $row['id']);
        $st2->execute();
        $st2->bind_result($cnt);
        $st2->fetch();
        $st2->close();
        $scope_label = $cnt>0 ? 'Curso+Grado' : 'Curso';
      }
      $active_now = ((int)$row['status']===1);
      if($row['starts_at'] && $active_now){ $active_now = $active_now && (strtotime($row['starts_at']) <= time()); }
      if($row['ends_at'] && $active_now){ $active_now = $active_now && (strtotime($row['ends_at']) >= time()); }
      $items[] = [
        'id'=>(int)$row['id'],
        'title'=>$row['title'],
        'scope_label'=>$scope_label,
        'active_now'=>$active_now
      ];
    }
    $st->close();
    out(['ok'=>true,'items'=>$items]);
  }

  if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $st = $conn->prepare("SELECT id, title, body, school_id, starts_at, ends_at, status FROM announcements WHERE id=? LIMIT 1");
    $st->bind_param('i', $id);
    $st->execute();
    $res = $st->get_result();
    $it = $res->fetch_assoc();
    $st->close();
    if(!$it) fail('No encontrado', 404);

    // si tiene grade asociado, tomar uno (según regla: solo un grado)
    $grade_id = null;
    $stg = $conn->prepare("SELECT grade_id FROM announcement_grades WHERE announcement_id=? LIMIT 1");
    $stg->bind_param('i', $id);
    $stg->execute();
    $stg->bind_result($g);
    if($stg->fetch()){ $grade_id = (int)$g; }
    $stg->close();

    // scope inference:
    $scope_type = 'public';
    if(!is_null($it['school_id'])){
      $scope_type = $grade_id ? 'grade' : 'course';
    }

    $payload = [
      'id'=>(int)$it['id'],
      'title'=>$it['title'],
      'body'=>$it['body'],
      'scope_type'=>$scope_type,
      'course_id'=> $it['school_id'] ? (int)$it['school_id'] : null,
      'grade_id'=> $grade_id,
      'starts_at_input'=> $it['starts_at'] ? date('Y-m-d\TH:i', strtotime($it['starts_at'])) : '',
      'ends_at_input'=> $it['ends_at'] ? date('Y-m-d\TH:i', strtotime($it['ends_at'])) : '',
      'status'=> (int)$it['status']
    ];
    out(['ok'=>true,'item'=>$payload]);
  }

  if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $body  = $_POST['body'] ?? '';
    $scope_type = $_POST['scope_type'] ?? 'public';
    $course_id = isset($_POST['course_id']) && $_POST['course_id']!=='' ? (int)$_POST['course_id'] : null;
    $grade_id  = isset($_POST['grade_id'])  && $_POST['grade_id']!==''  ? (int)$_POST['grade_id']  : null;
    $starts_at = $_POST['starts_at'] ?? null;
    $ends_at   = $_POST['ends_at'] ?? null;
    $status    = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    if($title==='') fail('El título es obligatorio.');
    if(trim(strip_tags($body))==='') fail('El contenido es obligatorio.');
    if($scope_type==='course' && !$course_id) fail('Selecciona un curso.');
    if($scope_type==='grade'  && (!$course_id || !$grade_id)) fail('Selecciona curso y grado.');
    if($starts_at && $ends_at && strtotime($starts_at) > strtotime($ends_at)) fail('La fecha de inicio no puede ser posterior a la de fin.');

    if($scope_type==='public'){ $course_id = null; $grade_id = null; }

    if($id>0){
      $st = $conn->prepare("UPDATE announcements SET title=?, body=?, school_id=?, starts_at=?, ends_at=?, status=? WHERE id=?");
      $st->bind_param('ssissii', $title, $body, $course_id, $starts_at, $ends_at, $status, $id);
      $st->execute();
      $st->close();

      // actualizar grado
      $conn->query("DELETE FROM announcement_grades WHERE announcement_id=".$id);
      if($scope_type==='grade' && $grade_id){
        $stg = $conn->prepare("INSERT INTO announcement_grades (announcement_id, grade_id) VALUES (?,?)");
        $stg->bind_param('ii', $id, $grade_id);
        $stg->execute();
        $stg->close();
      }
    }else{
      $st = $conn->prepare("INSERT INTO announcements (title, body, school_id, starts_at, ends_at, status) VALUES (?,?,?,?,?,?)");
      $st->bind_param('ssissi', $title, $body, $course_id, $starts_at, $ends_at, $status);
      $st->execute();
      $id = $st->insert_id;
      $st->close();

      if($scope_type==='grade' && $grade_id){
        $stg = $conn->prepare("INSERT INTO announcement_grades (announcement_id, grade_id) VALUES (?,?)");
        $stg->bind_param('ii', $id, $grade_id);
        $stg->execute();
        $stg->close();
      }
    }

    out(['ok'=>true,'id'=>$id]);
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if(!$id) fail('ID requerido.');
    $st = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $st->bind_param('i', $id);
    $st->execute();
    $st->close();
    out(['ok'=>true]);
  }

  fail('Acción no válida.');
}catch(Throwable $e){
  fail('SQL: '.$e->getMessage(), 500);
}
