<?php
// alumno/ver.php — restringe acceso SOLO si está asignado y habilitado
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['uid'])) { header('Location: ../login.php'); exit; }
$uid = (int)$_SESSION['uid'];
$cid = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verifica rol alumno
$st = $conn->prepare('SELECT role FROM users WHERE id=? LIMIT 1');
$st->bind_param('i', $uid);
$st->execute();
$st->bind_result($role);
if(!$st->fetch() || $role !== 'alumno'){ http_response_code(403); exit('Acceso denegado'); }
$st->close();

// Verifica asignación encendida
$st = $conn->prepare("
  SELECT ct.title, ct.content_type, ct.url, c.name AS course_name
  FROM contents ct
  JOIN courses c ON c.id=ct.course_id
  JOIN content_assignments ca ON ca.content_id = ct.id AND ca.user_id=? AND ca.is_enabled=1
  WHERE ct.id=? AND ct.status=1
");
$st->bind_param('ii', $uid, $cid);
$st->execute();
$st->bind_result($title, $ctype, $url, $course);
if(!$st->fetch()){ http_response_code(403); exit('Este contenido no está disponible para ti.'); }
$st->close();

$yt = '';
if (strpos($url,'youtu') !== false) {
  if (preg_match('~(?:youtu\.be/|v=)([A-Za-z0-9_\-]+)~', $url, $m)) { $yt = $m[1]; }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($course.' · '.$title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg" style="background:#ae0304;border-bottom:4px solid #c8b052;">
  <div class="container">
    <a class="navbar-brand text-white" href="index.php">Alumno</a>
    <div class="ms-auto"><a class="btn btn-sm btn-outline-light" href="../logout.php">Salir</a></div>
  </div>
</nav>
<div class="container py-4">
  <a href="index.php" class="btn btn-link">&larr; Volver</a>
  <h1 class="h5 mb-3"><?= htmlspecialchars($title) ?></h1>
  <div class="ratio ratio-16x9">
    <?php if($yt): ?>
      <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($yt) ?>?rel=0" allowfullscreen></iframe>
    <?php else: ?>
      <div class="d-flex align-items-center justify-content-center bg-white border rounded">Este tipo de contenido aún no tiene visor.</div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
