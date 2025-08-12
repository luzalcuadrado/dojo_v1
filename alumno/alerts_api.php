<?php
// alumno/alerts_api.php — devuelve count + últimos avisos visibles para el alumno
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function out($a){ echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

if (empty($_SESSION['uid'])) out(['ok'=>false,'msg'=>'No autenticado']);
$uid = (int)$_SESSION['uid'];

// cursos+grados actuales del alumno (última inscripción por curso)
$sql = "
SELECT e.course_id, e.grade_id
FROM enrollments e
JOIN (
  SELECT user_id, course_id, MAX(enrolled_at) max_enr
  FROM enrollments
  WHERE user_id=?
  GROUP BY user_id, course_id
) last ON last.user_id=e.user_id AND last.course_id=e.course_id AND last.max_enr=e.enrolled_at
";
$st = $conn->prepare($sql);
$st->bind_param('i', $uid);
$st->execute();
$res = $st->get_result();
$scopes = [];
$courseIds = [];
while($row = $res->fetch_assoc()){
  $scopes[] = ['course_id'=>(int)$row['course_id'], 'grade_id'=>(int)$row['grade_id']];
  $courseIds[] = (int)$row['course_id'];
}
$st->close();

$items = [];
if (empty($scopes)) {
  // solo públicos
  $sql = "SELECT id, title, body, starts_at, created_at FROM announcements
          WHERE status=1 AND (starts_at IS NULL OR starts_at<=NOW())
            AND (ends_at IS NULL OR ends_at>=NOW())
            AND school_id IS NULL
          ORDER BY COALESCE(starts_at, created_at) DESC, id DESC
          LIMIT 10";
  $q = $conn->query($sql);
  while($r=$q->fetch_assoc()) $items[$r['id']] = $r;
} else {
  // públicos
  $sql = "SELECT id, title, body, starts_at, created_at FROM announcements
          WHERE status=1 AND (starts_at IS NULL OR starts_at<=NOW())
            AND (ends_at IS NULL OR ends_at>=NOW())
            AND school_id IS NULL
          ORDER BY COALESCE(starts_at, created_at) DESC, id DESC
          LIMIT 20";
  $q = $conn->query($sql);
  while($r=$q->fetch_assoc()) $items[$r['id']] = $r;

  // por curso (sin grado específico)
  if(!empty($courseIds)){
    $in = implode(',', array_map('intval',$courseIds));
    $sql = "SELECT a.id, a.title, a.body, a.starts_at, a.created_at
            FROM announcements a
            LEFT JOIN announcement_grades ag ON ag.announcement_id = a.id
            WHERE a.status=1 AND (a.starts_at IS NULL OR a.starts_at<=NOW())
              AND (a.ends_at IS NULL OR a.ends_at>=NOW())
              AND a.school_id IN ($in)
              AND ag.id IS NULL
            ORDER BY COALESCE(a.starts_at, a.created_at) DESC, a.id DESC
            LIMIT 50";
    $q = $conn->query($sql);
    while($r=$q->fetch_assoc()) $items[$r['id']] = $r;
  }

  // por curso+grado
  foreach($scopes as $s){
    $c = (int)$s['course_id']; $g = (int)$s['grade_id'];
    if(!$c || !$g) continue;
    $st = $conn->prepare("
      SELECT a.id, a.title, a.body, a.starts_at, a.created_at
      FROM announcements a
      JOIN announcement_grades ag ON ag.announcement_id = a.id AND ag.grade_id = ?
      WHERE a.status=1 AND (a.starts_at IS NULL OR a.starts_at<=NOW())
        AND (a.ends_at IS NULL OR a.ends_at>=NOW())
        AND a.school_id = ?
      ORDER BY COALESCE(a.starts_at, a.created_at) DESC, a.id DESC
      LIMIT 50
    ");
    $st->bind_param('ii', $g, $c);
    $st->execute();
    $r = $st->get_result();
    while($row=$r->fetch_assoc()) $items[$row['id']] = $row;
    $st->close();
  }
}

// ordenar y recortar
usort($items, function($a,$b){
  $da = $a['starts_at'] ? strtotime($a['starts_at']) : strtotime($a['created_at']);
  $db = $b['starts_at'] ? strtotime($b['starts_at']) : strtotime($b['created_at']);
  if($da === $db) return $b['id'] <=> $a['id'];
  return $db <=> $da;
});
$items = array_values($items);
$count = count($items);
$items = array_slice($items, 0, 10);

out(['ok'=>true,'count'=>$count, 'items'=>$items]);
