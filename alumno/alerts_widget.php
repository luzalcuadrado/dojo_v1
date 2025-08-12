<?php
// alumno/alerts_widget.php — Botón campana con badge + dropdown
// Requiere Bootstrap 5 y jQuery (ya presentes en tu layout).
// Incluir este archivo dentro del <nav> del header del alumno, en la sección derecha.

$bellId = 'alertsBell';
$dropId = 'alertsDropdown';
$apiUrl = $basePath . '/alumno/alerts_api.php';
?>
<li class="nav-item dropdown">
  <a class="nav-link position-relative" href="#" id="<?= $bellId ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <!-- SVG campana -->
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16">
      <path d="M8 16a2 2 0 0 0 1.985-1.75H6.015A2 2 0 0 0 8 16m.104-14a1 1 0 0 0-2.208 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 2.26-1.528 3.47-.27.32-.392.73-.343 1.14C1.2 11.56 1.67 12 2.2 12h11.6c.53 0 1-.44 1.071-1.39.05-.41-.073-.82-.343-1.14C13.5 8.26 13 7.098 13 6a5.002 5.002 0 0 0-2.896-4"/>
    </svg>
    <span id="alertsBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
  </a>
  <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="<?= $bellId ?>" style="min-width: 320px; max-width: 360px;">
    <li><h6 class="dropdown-header">Avisos</h6></li>
    <li id="alertsList">
      <div class="px-3 py-2 small text-muted">Cargando…</div>
    </li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-center small" href="<?= $basePath ?>/alumno/avisos.php">Ver todos</a></li>
  </ul>
</li>

<script>
(function(){
  const API = <?= json_encode($apiUrl) ?>;
  const $badge = $('#alertsBadge');
  const $list = $('#alertsList');

  function loadAlerts(){
    $.getJSON(API, function(resp){
      if(!resp || !resp.ok){ renderError(); return; }
      const count = resp.count || 0;
      if(count > 0){ $badge.removeClass('d-none').text(count > 99 ? '99+' : count); }
      else { $badge.addClass('d-none').text('0'); }

      if(!resp.items || resp.items.length === 0){
        $list.html('<div class="px-3 py-2 small text-muted">Sin avisos.</div>');
        return;
      }
      const out = [];
      resp.items.forEach(function(it){
        const dateStr = (it.starts_at ?? it.created_at ?? '').replace('T',' ').substring(0,16);
        out.push('<a class="dropdown-item" href="<?= $basePath ?>/alumno/avisos.php">');
        out.push('<div class="d-flex">');
        out.push('<div class="me-2 flex-shrink-0"><span class="badge bg-primary rounded-circle p-2">!</span></div>');
        out.push('<div class="flex-grow-1">');
        out.push('<div class="small text-muted">'+dateStr+'</div>');
        out.push('<div class="fw-semibold">'+escapeHtml(it.title||'Aviso')+'</div>');
        out.push('</div></div></a>');
      });
      $list.html(out.join(''));
    }).fail(renderError);
  }

  function renderError(){
    $badge.addClass('d-none').text('0');
    $list.html('<div class="px-3 py-2 small text-danger">No se pudieron cargar los avisos.</div>');
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]); });
  }

  // carga inicial y polling cada 60s
  loadAlerts();
  setInterval(loadAlerts, 60000);
})();
</script>
