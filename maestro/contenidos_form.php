<?php
// maestro/contenidos_form.php - Crear/editar contenido (TinyMCE + uploads PDF/IMG)
require_once __DIR__ . '/../config.php';

function require_maestro($conn){
  if (empty($_SESSION['uid'])) { header('Location: ../login.php'); exit; }
  $uid = $_SESSION['uid'];
  $st = $conn->prepare('SELECT role FROM users WHERE id=? LIMIT 1');
  $st->bind_param('i', $uid); $st->execute(); $st->bind_result($role);
  if (!$st->fetch() || $role!=='maestro'){ http_response_code(403); exit('Acceso denegado'); }
  $st->close();
}
require_maestro($conn);
$uid = $_SESSION['uid'];

// cursos del maestro
$cs = $conn->prepare("
  SELECT DISTINCT c.id, c.name 
  FROM courses c
  JOIN dojo_groups dg ON dg.course_id=c.id
  JOIN group_members gm ON gm.group_id=dg.id AND gm.role_in_group='maestro'
  WHERE gm.user_id=? AND c.status=1
  ORDER BY c.name
");
$cs->bind_param('i', $uid); $cs->execute(); $courses = $cs->get_result()->fetch_all(MYSQLI_ASSOC);

// grados
$grades = $conn->query('SELECT id,name,ord FROM grades ORDER BY ord DESC')->fetch_all(MYSQLI_ASSOC);

$id = (int)($_GET['id'] ?? 0);
$edit = null;
if ($id>0) {
  $st = $conn->prepare('SELECT id,course_id,grade_id,title,content_type,url,body,seq,status FROM contents WHERE id=?');
  $st->bind_param('i',$id); $st->execute(); $edit = $st->get_result()->fetch_assoc();
}

// guardar
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id = (int)($_POST['id'] ?? 0);
  $course_id = (int)($_POST['course_id'] ?? 0);
  $grade_id = (int)($_POST['grade_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $content_type = $_POST['content_type'] ?? 'text';
  $url = trim($_POST['url'] ?? '');
  $body = $_POST['body'] ?? null;
  $seq = (int)($_POST['seq'] ?? 1);
  $status = (int)($_POST['status'] ?? 1);

  // si hay archivo subido (pdf/image), lo guardamos en /uploads
  if (!empty($_FILES['file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf','png','jpg','jpeg','webp','gif'];
    if (in_array($ext, $allowed)) {
      @mkdir(__DIR__.'/../uploads', 0775, true);
      $fname = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/','_', $_FILES['file']['name']);
      if (move_uploaded_file($_FILES['file']['tmp_name'], __DIR__.'/../uploads/'.$fname)) {
        $url = 'uploads/'.$fname;
        // set type if not chosen
        if ($content_type=='text') {
          $content_type = ($ext=='pdf')?'pdf':'image';
        }
      }
    }
  }

  if ($id>0) {
    $st = $conn->prepare('UPDATE contents SET course_id=?, grade_id=?, title=?, content_type=?, url=?, body=?, seq=?, status=? WHERE id=?');
    $st->bind_param('iissssiii', $course_id, $grade_id, $title, $content_type, $url, $body, $seq, $status, $id);
    $st->execute();
  } else {
    $st = $conn->prepare('INSERT INTO contents (course_id,grade_id,title,content_type,url,body,seq,status,created_by) VALUES (?,?,?,?,?,?,?,?,?)');
    $st->bind_param('iisssssii', $course_id, $grade_id, $title, $content_type, $url, $body, $seq, $status, $uid);
    $st->execute();
    $id = $conn->insert_id;
  }
  header('Location: contenidos.php?course_id='.$course_id.'&ok=1'); exit;
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Maestro · Nuevo contenido</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  tinymce.init({
    selector:'#body',
    height: 300,
    menubar: false,
    plugins: 'link lists code',
    toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link | code',
    default_link_target: '_blank'
  });
});
</script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg" style="background:#0a0907;border-bottom:4px solid #c8b052;">
  <div class="container">
    <a class="navbar-brand text-white" href="index.php">Panel Maestro</a>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-sm btn-outline-light" href="contenidos.php">Volver</a>
      <a class="btn btn-sm btn-outline-light" href="../logout.php">Salir</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="card">
    <div class="card-header"><?= $edit?'Editar':'Nuevo' ?> contenido</div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? 0) ?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Curso</label>
            <select class="form-select" name="course_id" required>
              <option value="">-- Selecciona --</option>
              <?php foreach($courses as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= isset($edit['course_id']) && (int)$edit['course_id']===(int)$c['id']?'selected':'' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Grado (Kyu)</label>
            <select class="form-select" name="grade_id" required>
              <option value="">-- Selecciona --</option>
              <?php foreach($grades as $g): ?>
              <option value="<?= (int)$g['id'] ?>" <?= isset($edit['grade_id']) && (int)$edit['grade_id']===(int)$g['id']?'selected':'' ?>>
                <?= htmlspecialchars($g['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Orden (#)</label>
            <input class="form-control" type="number" name="seq" value="<?= htmlspecialchars($edit['seq'] ?? 1) ?>" min="1" step="1">
          </div>

          <div class="col-12">
            <label class="form-label">Título</label>
            <input class="form-control" name="title" required value="<?= htmlspecialchars($edit['title'] ?? '') ?>">
          </div>

          <div class="col-md-4">
            <label class="form-label">Tipo de contenido</label>
            <select class="form-select" name="content_type">
              <?php $ct = $edit['content_type'] ?? 'text'; ?>
              <?php foreach(['youtube','vimeo','pdf','image','text','link'] as $t): ?>
                <option value="<?= $t ?>" <?= $ct===$t?'selected':'' ?>><?= strtoupper($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">URL (YouTube/Vimeo/Link) o se llenará al subir archivo</label>
            <input class="form-control" name="url" value="<?= htmlspecialchars($edit['url'] ?? '') ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Texto (opcional)</label>
            <textarea id="body" name="body" class="form-control"><?= htmlspecialchars($edit['body'] ?? '') ?></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label">Subir PDF/Imagen</label>
            <input class="form-control" type="file" name="file" accept=".pdf,image/*">
            <div class="form-text">Si subes un archivo, la URL se llenará automáticamente.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Estatus</label>
            <select class="form-select" name="status">
              <?php $st = (int)($edit['status'] ?? 1); ?>
              <option value="1" <?= $st===1?'selected':'' ?>>Activo</option>
              <option value="0" <?= $st===0?'selected':'' ?>>Inactivo</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100">Guardar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
