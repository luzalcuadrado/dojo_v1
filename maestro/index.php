<?php include __DIR__.'/header.php'; ?>
<body>
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
</div>
<?php include __DIR__.'/footer.php'; ?>
