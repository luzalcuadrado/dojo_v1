<?php
// alumno/avisos.php — Visualiza avisos según announcements.school_id y announcement_grades
include __DIR__ . '/header.php';

$uid = $_SESSION['uid'] ?? null;
if(!$uid){ header('Location: ../login.php'); exit; }

// Últimas inscripciones por curso del alumno (para conocer course_id y grade_id actuales)
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
$courses = [];
$gradesByCourse = [];
while($r = $res->fetch_assoc()){
  $cid = (int)$r['course_id']; $gid = (int)$r['grade_id'];
  $courses[] = $cid;
  $gradesByCourse[$cid] = $gid;
}
$st->close();

// Obtener avisos activos por regla:
// - Público: school_id IS NULL
// - Curso: school_id IN (cursos del alumno)
// - Curso+Grado: school_id = curso del alumno y existe registro anuncio-grado igual al kyu del alumno
$items = [];

// públicos
$q = "SELECT id, title, body, NULL AS course_id, NULL AS grade_id, 'public' as scope_type
      FROM announcements
      WHERE status=1 AND school_id IS NULL
        AND (starts_at IS NULL OR starts_at<=NOW())
        AND (ends_at IS NULL OR ends_at>=NOW())
      ORDER BY id DESC LIMIT 100";
$r = $conn->query($q);
while($a = $r->fetch_assoc()){ $items[] = $a; }

if(!empty($courses)){
  // por curso (sin grado)
  $in = implode(',', array_map('intval', $courses));
  $q2 = "SELECT id, title, body, school_id as course_id, NULL as grade_id, 'course' as scope_type
         FROM announcements
         WHERE status=1 AND school_id IN ($in)
           AND (starts_at IS NULL OR starts_at<=NOW())
           AND (ends_at IS NULL OR ends_at>=NOW())
           AND id NOT IN (SELECT announcement_id FROM announcement_grades)
         ORDER BY id DESC LIMIT 200";
  $r2 = $conn->query($q2);
  while($a = $r2->fetch_assoc()){ $items[] = $a; }

  // por curso+grado
  $q3 = "SELECT a.id, a.title, a.body, a.school_id as course_id, ag.grade_id, 'grade' as scope_type
         FROM announcements a
         JOIN announcement_grades ag ON ag.announcement_id = a.id
         WHERE a.status=1 AND a.school_id IN ($in)
           AND (a.starts_at IS NULL OR a.starts_at<=NOW())
           AND (a.ends_at IS NULL OR a.ends_at>=NOW())";
  $r3 = $conn->query($q3);
  while($a = $r3->fetch_assoc()){
    $cid = (int)$a['course_id']; $gid = (int)$a['grade_id'];
    if(isset($gradesByCourse[$cid]) && $gradesByCourse[$cid] === $gid){
      $items[] = $a;
    }
  }
}
?>
<div class="container py-3">
  <div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0">Avisos</h1>
  </div>
  <?php if(empty($items)): ?>
    <div class="alert alert-info">No hay avisos por ahora.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach($items as $it): ?>
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($it['title']) ?></h5>
              <div class="card-text"><?= $it['body'] ?></div>
            </div>
            <div class="card-footer small text-muted">
              <?php if($it['scope_type']==='public'): ?>
                Público
              <?php elseif($it['scope_type']==='course'): ?>
                Curso
              <?php else: ?>
                Curso + grado
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
