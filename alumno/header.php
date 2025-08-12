<?php
require_once __DIR__ . '/../config.php';
$basePath = $basePath ?? ''; 
//include __DIR__ . '/alerts_widget.php'; 

if (empty($_SESSION['uid'])) { header('Location: ../login.php'); exit; }
$uid = $_SESSION['uid'];
$stmt = $conn->prepare('SELECT name, role FROM users WHERE id=? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->bind_result($username, $userrole);
if(!$stmt->fetch()){ http_response_code(401); exit('Sesión inválida'); }
$stmt->close();
if ($userrole !== 'alumno') { http_response_code(403); exit('Acceso denegado'); }

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
  <title>Panel Alumno</title>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
  <link href='../assets/css/custom.css' rel='stylesheet'>
<script>
async function loadAlerts(){
  const badge = document.getElementById('alertsBadge');
  const list = document.getElementById('alertsList');
  try{
    const res = await fetch('alerts_api.php');
    const data = await res.json();
    if(data.ok && data.items.length){
      badge.textContent = data.items.length > 99 ? '99+' : data.items.length;
      badge.classList.remove('d-none');
      list.innerHTML = data.items.slice(0,5).map(a=>`
        <li>
          <a class="dropdown-item" href="avisos.php">
            <small class="text-muted">${a.starts_at}</small><br>
            ${a.title}
          </a>
        </li>
      `).join('');
    } else {
      badge.classList.add('d-none');
      list.innerHTML = `<li class="px-3 py-2 text-muted">No hay avisos</li>`;
    }
  } catch(e){
    badge.classList.add('d-none');
    list.innerHTML = `<li class="px-3 py-2 text-danger">Error al cargar</li>`;
  }
}
document.addEventListener('DOMContentLoaded', loadAlerts);
</script>

</head>
<body>
  <nav class='navbar navbar-expand-lg' style='background:#ae0304;border-bottom:4px solid #ffffff;'>
    <div class='container'>
      <a class='navbar-brand text-white' href='index.php'>
        <img class='logo-img' src='https://via.placeholder.com/120x40?text=LOGO' alt='Logo'>
        Panel Alumno
      </a>
      <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navalumno' aria-controls='navalumno' aria-expanded='false' aria-label='Toggle navigation'>
        <span class='navbar-toggler-icon'></span>
      </button>
      <div class='collapse navbar-collapse' id='navalumno'>
        <ul class='navbar-nav ms-auto'>

        <li class="nav-item"><a class="nav-link text-white" href="index.php">Mis Contenidos</a></li>
        
        
          <li class="nav-item dropdown">
            <button type="button"
                    class="btn  position-relative"
                    id="alertsDropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    title="Avisos">
              <!-- Icono campana -->
              <svg style="fill: #fff;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16">
  <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
</svg>
              <!-- Badge contador -->
              <span id="alertsBadge"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                0
                <span class="visually-hidden">nuevos avisos</span>
              </span>
            </button>

            <!-- Dropdown avisos -->
            <ul class="dropdown-menu dropdown-menu-end shadow"
                aria-labelledby="alertsDropdown"
                style="min-width: 320px;">
              <li><h6 class="dropdown-header">Avisos</h6></li>
              <div id="alertsList">
                <li class="px-3 py-2 text-muted">Cargando…</li>
              </div>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-center" href="avisos.php">Ver todos</a></li>
            </ul>
          </li>
                    
          <li class='nav-item'><a href="" class='nav-link text-white'>👤 <?php echo htmlspecialchars($username); ?></a></li>
          
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
