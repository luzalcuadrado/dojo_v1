<?php
require_once __DIR__ . "/config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingreso</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center"><div class="col-md-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h5 mb-3">Entrar</h1>
      <?php if(isset($_GET['e'])): ?>
        <div class="alert alert-danger">Credenciales inválidas</div>
      <?php endif; ?>
      <form method="post" action="login_check.php">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input class="form-control" type="password" name="password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Entrar</button>
      </form>
    </div>
  </div>
  </div></div>
</div>
</body>
</html>
