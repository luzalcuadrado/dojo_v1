<?php
// admin/grupos.php - ABC de grupos y asignaciones (MySQL 8 friendly, rutas relativas)
require_once __DIR__.'/../config.php';
// Guardas acceso admin simple
function require_admin($conn){
  if (empty($_SESSION['uid'])) { header('Location: ../login.php'); exit; }
  $uid = $_SESSION['uid'];
  $st = $conn->prepare('SELECT role FROM users WHERE id=? LIMIT 1');
  $st->bind_param('i', $uid); $st->execute(); $st->bind_result($r);
  if(!$st->fetch() || $r!=='admin'){ http_response_code(403); exit('Acceso denegado'); }
  $st->close();
}
require_admin($conn);

// Crear/editar grupo
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_group'])) {
  $id = (int)($_POST['id'] ?? 0);
  $course_id = (int)($_POST['course_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $status = (int)($_POST['status'] ?? 1);
  if ($id>0) {
    $st = $conn->prepare('UPDATE dojo_groups SET course_id=?, name=?, status=? WHERE id=?');
    $st->bind_param('isii', $course_id, $name, $status, $id);
    $st->execute();
  } else {
    $st = $conn->prepare('INSERT INTO dojo_groups (course_id, name, status) VALUES (?,?,?)');
    $st->bind_param('isi', $course_id, $name, $status);
    $st->execute();
    $id = $conn->insert_id;
  }
  header('Location: grupos.php?gid='.$id.'&ok=1'); exit;
}

// Asignar maestro al grupo (uno por grupo recomendado)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['assign_teacher'])) {
  $group_id = (int)($_POST['group_id'] ?? 0);
  $teacher_id = (int)($_POST['teacher_id'] ?? 0);
  if ($group_id>0 && $teacher_id>0) {
    // eliminar maestros previos del grupo (opcional; si quieres múltiples, comenta esto)
    $conn->query("DELETE gm FROM group_members gm WHERE gm.group_id={$group_id} AND gm.role_in_group='maestro'");
    $st = $conn->prepare('INSERT INTO group_members (group_id,user_id,role_in_group) VALUES (?,?,"maestro")');
    $st->bind_param('ii', $group_id, $teacher_id);
    $st->execute();
  }
  header('Location: grupos.php?gid='.$group_id.'&ok=1'); exit;
}

// Asignar alumnos (múltiple)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['assign_students'])) {
  $group_id = (int)($_POST['group_id'] ?? 0);
  $students = $_POST['students'] ?? [];
  if ($group_id>0) {
    // limpiar alumnos del grupo
    $st = $conn->prepare("DELETE FROM group_members WHERE group_id=? AND role_in_group='alumno'");
    $st->bind_param('i', $group_id);
    $st->execute();
    // insertar seleccionados
    $st = $conn->prepare('INSERT INTO group_members (group_id,user_id,role_in_group) VALUES (?,?,"alumno")');
    foreach($students as $sid){
      $sid = (int)$sid;
      if ($sid>0) { $st->bind_param('ii', $group_id, $sid); $st->execute(); }
    }
  }
  header('Location: grupos.php?gid='.$group_id.'&ok=1'); exit;
}

// Cargar datos para UI
$courses = $conn->query('SELECT id,name FROM courses WHERE status=1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$teachers = $conn->query("SELECT id,name FROM users WHERE role='maestro' AND status=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$students = $conn->query("SELECT id,name FROM users WHERE role='alumno' AND status=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$gid = (int)($_GET['gid'] ?? 0);
$edit = null;
if ($gid>0) {
  $st = $conn->prepare('SELECT id,course_id,name,status FROM dojo_groups WHERE id=?');
  $st->bind_param('i', $gid);
  $st->execute();
  $edit = $st->get_result()->fetch_assoc();
}
// maestro actual del grupo
$current_teacher = null;
if ($gid>0) {
  $st = $conn->prepare("SELECT u.id,u.name FROM group_members gm JOIN users u ON u.id=gm.user_id WHERE gm.group_id=? AND gm.role_in_group='maestro' LIMIT 1");
  $st->bind_param('i', $gid); $st->execute(); $current_teacher = $st->get_result()->fetch_assoc();
}
// alumnos actuales del grupo
$current_students = [];
if ($gid>0) {
  $st = $conn->prepare("SELECT u.id FROM group_members gm JOIN users u ON u.id=gm.user_id WHERE gm.group_id=? AND gm.role_in_group='alumno'"); 
  $st->bind_param('i', $gid); $st->execute(); 
  $res = $st->get_result();
  while($r=$res->fetch_assoc()){ $current_students[] = (int)$r['id']; }
}
$all_groups = $conn->query('SELECT dg.id,dg.name,c.name course FROM dojo_groups dg JOIN courses c ON c.id=dg.course_id ORDER BY c.name,dg.name')->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin · Grupos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top" style="border-bottom:4px solid #c8b052">
  <div class="container">
    <a class="navbar-brand" href="index.php">Admin Dojo</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-sm btn-outline-primary" href="alumnos.php">Alumnos</a>
      <a class="btn btn-sm btn-outline-primary" href="maestros.php">Maestros</a>
      <a class="btn btn-sm btn-primary" href="grupos.php">Grupos</a>
      <a class="btn btn-sm btn-outline-secondary" href="../logout.php">Salir</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <?php if(isset($_GET['ok'])): ?><div class="alert alert-success">Cambios guardados</div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header">Crear / Editar grupo</div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="save_group" value="1">
            <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? 0) ?>">
            <div class="mb-2">
              <label class="form-label">Curso</label>
              <select class="form-select" name="course_id" required>
                <option value="">-- Selecciona --</option>
                <?php foreach($courses as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= isset($edit['course_id']) && (int)$edit['course_id']===(int)$c['id'] ? 'selected':'' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Nombre del grupo</label>
              <input class="form-control" name="name" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Estatus</label>
              <select class="form-select" name="status">
                <?php $st = (int)($edit['status'] ?? 1); ?>
                <option value="1" <?= $st===1?'selected':'' ?>>Activo</option>
                <option value="0" <?= $st===0?'selected':'' ?>>Inactivo</option>
              </select>
            </div>
            <button class="btn btn-primary">Guardar</button>
            <?php if(isset($edit)): ?><a class="btn btn-outline-secondary" href="grupos.php">Nuevo</a><?php endif; ?>
          </form>
        </div>
      </div>

      <?php if($gid>0): ?>
      <div class="card mt-4">
        <div class="card-header">Asignar maestro</div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="assign_teacher" value="1">
            <input type="hidden" name="group_id" value="<?= $gid ?>">
            <div class="input-group">
              <select class="form-select" name="teacher_id" required>
                <option value="">-- Selecciona maestro --</option>
                <?php foreach($teachers as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= ($current_teacher && (int)$current_teacher['id']===(int)$t['id'])?'selected':'' ?>>
                  <?= htmlspecialchars($t['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-dark">Guardar</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header">Asignar alumnos</div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="assign_students" value="1">
            <input type="hidden" name="group_id" value="<?= $gid ?>">
            <label class="form-label">Alumnos</label>
            <select class="form-select" name="students[]" multiple size="10">
              <?php foreach($students as $s): $sel = in_array((int)$s['id'], $current_students) ? 'selected':''; ?>
                <option value="<?= (int)$s['id'] ?>" <?= $sel ?>><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="mt-2">
              <button class="btn btn-primary">Guardar alumnos</button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-7">
      <div class="card">
        <div class="card-header d-flex align-items-center">
          <div>Grupos existentes</div>
          <div class="ms-auto">
            <a class="btn btn-sm btn-outline-primary" href="grupos.php">Actualizar</a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr><th>ID</th><th>Grupo</th><th>Curso</th><th>Maestro</th><th>Alumnos</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach($all_groups as $g): 
                // maestro
                $st = $conn->prepare("SELECT u.name FROM group_members gm JOIN users u ON u.id=gm.user_id WHERE gm.group_id=? AND gm.role_in_group='maestro' LIMIT 1");
                $st->bind_param('i', $g['id']); $st->execute(); $st->bind_result($mname); $st->fetch(); $st->close();
                // alumnos count
                $cnt = $conn->query('SELECT COUNT(*) c FROM group_members WHERE group_id='.(int)$g['id'].' AND role_in_group=\'alumno\'')->fetch_assoc()['c'] ?? 0;
              ?>
              <tr>
                <td><?= (int)$g['id'] ?></td>
                <td><?= htmlspecialchars($g['name']) ?></td>
                <td><?= htmlspecialchars($g['course']) ?></td>
                <td><?= htmlspecialchars($mname ?? '—') ?></td>
                <td><?= (int)$cnt ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="grupos.php?gid=<?= (int)$g['id'] ?>">Editar</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
