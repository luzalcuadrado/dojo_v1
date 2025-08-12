<?php require_once __DIR__ . '/../auth.php'; require_role('admin'); require_once __DIR__ . '/../config.php'; ?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<nav class="navbar bg-white border-bottom"><div class="container">
  <a class="navbar-brand" href="./">Admin</a>
  <div class="ms-auto"><a class="btn btn-sm btn-outline-secondary" href="../logout.php">Salir</a></div>
</div></nav>
<div class="container py-4">
  <h1 class="h5">Resumen</h1>
  <div class="row g-3">
    <div class="col-md-3"><div class="card"><div class="card-body">
      <div class="small text-muted">Usuarios</div>
      <div class="display-6"><?php echo $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c']; ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">
      <div class="small text-muted">Cursos</div>
      <div class="display-6"><?php echo $conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c']; ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">
      <div class="small text-muted">Contenidos</div>
      <div class="display-6"><?php echo $conn->query("SELECT COUNT(*) c FROM contents")->fetch_assoc()['c']; ?></div>
    </div></div></div>
  </div>
  <hr>
  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="usuarios.php">ABC Usuarios</a>
    <a class="btn btn-outline-primary" href="cursos.php">ABC Cursos</a>
    <a class="btn btn-outline-primary" href="contenidos.php">ABC Contenidos</a>
  </div>
</div></body></html>