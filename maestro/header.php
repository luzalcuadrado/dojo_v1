<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['uid'])) { header('Location: ../login.php'); exit; }
$uid = $_SESSION['uid'];
$stmt = $conn->prepare('SELECT name, role FROM users WHERE id=? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($username, $userrole);
if(!$stmt->fetch()){ http_response_code(401); exit('Sesión inválida'); }
$stmt->close();
if ($userrole !== 'maestro') { http_response_code(403); exit('Acceso denegado'); }

// Breadcrumb
$path = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$breadcrumbs = [];
$accum = '';
foreach ($path as $p) {
  $accum .= '/' . $p;
  $breadcrumbs[] = ['name' => ucfirst(basename($p)), 'url' => $accum];
}
?>
<!doctype html>
<html lang='es'>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>Panel Maestro</title>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
  <link href='../assets/css/custom.css' rel='stylesheet'>
</head>
<body>
  <nav class='navbar navbar-expand-lg' style='background:#0a0907;border-bottom:4px solid #c8b052;'>
    <div class='container'>
      <a class='navbar-brand text-white' href='index.php'>
        <img class='logo-img' src='https://elcaminodelaespada.com/wp-content/uploads/2024/11/elcaminodelaespada-clases-de-kenjutsu.png' alt='Logo'>
        Panel Maestro
      </a>
      <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navmaestro' aria-controls='navmaestro' aria-expanded='false' aria-label='Toggle navigation'>
        <span class='navbar-toggler-icon'></span>
      </button>
      <div class='collapse navbar-collapse' id='navmaestro'>
        <ul class='navbar-nav ms-auto'>

        <li class="nav-item"><a class="nav-link text-white" href="index.php">Alumnos</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="contenidos.php">Contenidos</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="asignar.php">Asignar</a></li>
        <li class="nav-item">
          <a class="nav-link text-white" href="<?= $basePath ?? '.' ?>/maestro/cursos.php">
            <i class="bi bi-journal-bookmark"></i> Cursos
          </a>
        </li>

        
        
          <li class='nav-item'><span class='navbar-text text-white me-2'>👤 <?php echo htmlspecialchars($username); ?></span></li>
          <li class='nav-item'><a class='nav-link text-white' href='../logout.php'>Salir</a></li>
        </ul>
      </div>
    </div>
  </nav>
  <div class='container mt-3'>
    <nav aria-label='breadcrumb'>
      <ol class='breadcrumb'>
      <?php foreach($breadcrumbs as $bc): ?>
        <li class='breadcrumb-item'><a href='<?php echo $bc['url']; ?>'><?php echo $bc['name']; ?></a></li>
      <?php endforeach; ?>
      </ol>
    </nav>
