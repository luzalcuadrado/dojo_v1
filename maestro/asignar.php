<?php
// maestro/asignar.php - Módulo de asignación de contenidos (V1.1)
// Lista contenidos por curso/grado y permite activar/desactivar por alumno.

require_once __DIR__ . '/../config.php';

function require_maestro($conn){
  if (empty($_SESSION['uid'])) { header('Location: ../login.php'); exit; }
  $uid = $_SESSION['uid'];
  $st = $conn->prepare('SELECT role FROM users WHERE id=? LIMIT 1');
  $st->bind_param('i', $uid);
  $st->execute();
  $st->bind_result($role);
  if(!$st->fetch() || $role !== 'maestro'){
    http_response_code(403);
    exit('Acceso denegado');
  }
  $st->close();
}
require_maestro($conn);

$maestroId = (int)$_SESSION['uid'];
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) { http_response_code(400); exit('Falta user_id'); }

// Verifica que el maestro y el alumno compartan al menos un grupo
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
if(!$ok){ http_response_code(403); exit('Este alumno no pertenece a tus grupos.'); }

// Última inscripción del alumno para saber curso/kyu vigentes
$st = $conn->prepare("
  SELECT e.course_id, c.name AS course_name, e.grade_id, g.name AS grade_name
  FROM enrollments e
  JOIN courses c ON c.id=e.course_id
  JOIN grades g ON g.id=e.grade_id
  WHERE e.user_id=?
  ORDER BY e.enrolled_at DESC
  LIMIT 1
");
$st->bind_param('i', $userId);
$st->execute();
$st->bind_result($courseId,$courseName,$gradeId,$gradeName);
if(!$st->fetch()){ $st->close(); http_response_code(404); exit('El alumno no tiene una inscripción.'); }
$st->close();

// Listado de cursos del alumno (por si se quiere cambiar)
$cursos = [];
$q = $conn->prepare("
  SELECT DISTINCT e.course_id, c.name 
  FROM enrollments e JOIN courses c ON c.id=e.course_id
  WHERE e.user_id=?
");
$q->bind_param('i', $userId);
$q->execute();
$r = $q->get_result();
while($row = $r->fetch_assoc()){ $cursos[] = $row; }
$q->close();

// Permite cambiar de curso vía ?course_id=
if(isset($_GET['course_id'])){
  $tmp = (int)$_GET['course_id'];
  foreach($cursos as $c){ if((int)$c['course_id'] === $tmp){ $courseId = $tmp; $courseName = $c['name']; break; } }
}

// Contenidos del curso y de todos los grados (orden por grado->seq)
$sql = "
SELECT 
  c.id, c.title, c.content_type, c.url, c.seq,
  g.id AS gid, g.name AS gname,
  (SELECT ca.is_enabled FROM content_assignments ca WHERE ca.content_id=c.id AND ca.user_id=? LIMIT 1) AS asignado
FROM contents c
JOIN grades g ON g.id=c.grade_id
WHERE c.course_id=? AND c.status=1
ORDER BY g.ord DESC, c.seq ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $userId, $courseId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Agrupa por grado
$porGrado = [];
foreach($rows as $it){
  $porGrado[$it['gname']][] = $it;
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Asignar contenido</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .grade-header{background:#f8f9fa;border:1px solid #eee;padding:.5rem 1rem;border-radius:.5rem}
    .yt-thumb{width:120px;height:68px;object-fit:cover;border-radius:6px}
    .type-badge{font-size:.75rem}
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg" style="background:#0a0907;border-bottom:4px solid #c8b052;">
  <div class="container">
    <a class="navbar-brand text-white" href="index.php">Panel Maestro</a>
    <div class="ms-auto">
      <a class="btn btn-sm btn-outline-light" href="../logout.php">Salir</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0">Asignar contenido a alumno</h1>
    <a class="btn btn-link ms-auto" href="index.php">&larr; Volver</a>
  </div>

  <div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
      <div><strong>Alumno:</strong>
        <?php
          $u = $conn->prepare('SELECT name,email FROM users WHERE id=?');
          $u->bind_param('i', $userId);
          $u->execute();
          $u->bind_result($uname,$uemail);
          $u->fetch();
          $u->close();
          echo htmlspecialchars($uname).' <span class="text-muted small">('.htmlspecialchars($uemail).')</span>';
        ?>
      </div>
      <div>|</div>
      <form method="get" class="d-flex align-items-center gap-2">
        <input type="hidden" name="user_id" value="<?= (int)$userId ?>">
        <label class="small text-muted">Curso</label>
        <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach($cursos as $c): ?>
          <option value="<?= (int)$c['course_id'] ?>" <?= ((int)$c['course_id']===$courseId?'selected':'') ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </form>
      <div class="ms-auto"><span class="badge bg-secondary">Grado actual: <?= htmlspecialchars($gradeName) ?></span></div>
    </div>
  </div>

  <?php if(empty($rows)): ?>
    <div class="alert alert-warning">Este curso no tiene contenidos aún.</div>
  <?php else: ?>
    <?php foreach($porGrado as $gname=>$items): ?>
      <div class="grade-header mb-2"><strong><?= htmlspecialchars($gname) ?></strong></div>
      <div class="list-group mb-4">
        <?php foreach($items as $c): ?>
          <div class="list-group-item d-flex align-items-center gap-3">
            <?php if($c['content_type']==='youtube' && !empty($c['url'])):
              // tomar id de youtube para thumb
              $url = $c['url'];
              $ytid = '';
              if (preg_match('~(?:youtu\.be/|v=)([\w-]{11})~', $url, $m)) { $ytid = $m[1]; }
              $thumb = $ytid ? 'https://img.youtube.com/vi/'.$ytid.'/mqdefault.jpg' : '';
            ?>
              <?php if($thumb): ?><img class="yt-thumb" src="<?= htmlspecialchars($thumb) ?>" alt="thumb"><?php endif; ?>
            <?php else: ?>
              <span class="type-badge badge text-bg-light"><?= htmlspecialchars($c['content_type']) ?></span>
            <?php endif; ?>
            <div class="flex-fill">
              <div class="fw-semibold">#<?= (int)$c['seq'] ?> · <?= htmlspecialchars($c['title']) ?></div>
              <?php if(!empty($c['url'])): ?><a class="small" href="<?= htmlspecialchars($c['url']) ?>" target="_blank">ver fuente</a><?php endif; ?>
            </div>
            <div class="form-check form-switch ms-auto">
              <input class="form-check-input assign-toggle" type="checkbox"
                     data-content="<?= (int)$c['id'] ?>" <?= ((int)$c['asignado']===1?'checked':'') ?>>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
document.querySelectorAll('.assign-toggle').forEach(el => {
  el.addEventListener('change', async (ev) => {
    const checked = el.checked ? 1 : 0;
    const contentId = el.dataset.content;
    const userId = <?= (int)$userId ?>;
    const fd = new FormData();
    fd.append('content_id', contentId);
    fd.append('user_id', userId);
    fd.append('is_enabled', checked);
    try{
      const res = await fetch('asignar_toggle.php', { method: 'POST', body: fd });
      const data = await res.json();
      if(!data.ok){
        alert('No se pudo actualizar: ' + (data.msg || 'error'));
        el.checked = !checked; // revertir
      }
    }catch(e){
      alert('Error de red');
      el.checked = !checked;
    }
  });
});
</script>
</body>
</html>
