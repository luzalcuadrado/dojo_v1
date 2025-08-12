<?php
// maestro/avisos.php — ABC Avisos usando announcements.school_id como alcance de curso
// y tabla announcement_grades para alcance por grado.
include __DIR__ . '/header.php'; // asegura sesión y rol maestro

// Cursos donde el maestro tiene grupos asignados
$courses = [];
$stmt = $conn->prepare("
  SELECT DISTINCT co.id, co.name
  FROM courses co
  JOIN dojo_groups dg ON dg.course_id = co.id AND dg.status=1
  JOIN group_members gm ON gm.group_id = dg.id AND gm.role_in_group='maestro' AND gm.user_id = ?
  WHERE co.status=1
  ORDER BY co.name
");
$stmt->bind_param('i', $_SESSION['uid']);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $courses[] = $row;
$stmt->close();

// Grados
$grades = [];
$gq = $conn->query("SELECT id, name, ord FROM grades ORDER BY ord DESC");
while($r = $gq->fetch_assoc()) $grades[] = $r;
?>
<div class="container py-3">
  <div id="alertBox"></div>

  <div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0 text-white">Avisos / Promociones</h1>
    <div class="ms-auto d-flex gap-2">
      <input id="q" class="form-control" placeholder="Buscar por título...">
      <button id="btnNew" class="btn btn-primary" type="button">Nuevo</button>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <ul id="list" class="list-group small">
            <li class="list-group-item">Cargando…</li>
          </ul>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <form id="form" novalidate>
            <input type="hidden" id="id" name="id">
            <div class="mb-2">
              <label class="form-label">Título <span class="text-danger">*</span></label>
              <input class="form-control" id="title" name="title" required>
              <div class="invalid-feedback">El título es obligatorio.</div>
            </div>

            <div class="mb-2">
              <label class="form-label">Alcance</label>
              <div class="row g-2">
                <div class="col-md-4">
                  <select class="form-select" id="scope_type" name="scope_type">
                    <option value="public">Público (todos los cursos)</option>
                    <option value="course">Solo un curso</option>
                    <option value="grade">Curso + Grado</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <select class="form-select" id="course_id" name="course_id">
                    <option value="">— Curso —</option>
                    <?php foreach($courses as $c): ?>
                      <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Selecciona un curso para este alcance.</div>
                </div>
                <div class="col-md-4">
                  <select class="form-select" id="grade_id" name="grade_id">
                    <option value="">— Grado —</option>
                    <?php foreach($grades as $g): ?>
                      <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Selecciona un grado cuando el alcance sea Curso + Grado.</div>
                </div>
              </div>
              <div class="form-text">Implementación: Público → school_id = NULL. Curso → school_id = ID curso. Curso+Grado → school_id + registro en announcement_grades.</div>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <label class="form-label">Inicio</label>
                <input type="datetime-local" class="form-control" id="starts_at" name="starts_at">
              </div>
              <div class="col-md-6">
                <label class="form-label">Fin</label>
                <input type="datetime-local" class="form-control" id="ends_at" name="ends_at">
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label">Estado</label>
              <select class="form-select" id="status" name="status">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <div class="mb-2">
              <label class="form-label">Contenido <span class="text-danger">*</span></label>
              <textarea id="body" name="body" class="form-control" rows="8"></textarea>
              <div class="invalid-feedback">El contenido es obligatorio.</div>
            </div>

            <div class="text-end">
              <button type="button" class="btn btn-outline-danger me-2" id="btnDelete" style="display:none;">Borrar</button>
              <button class="btn btn-success" type="submit" id="btnSave">
                <span class="save-text">Guardar</span>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CKEditor -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
let CK = null;

function showAlert(type, message) {
  const html = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>`;
  $('#alertBox').html(html);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function initEditor(){
  if(CK) return;
  ClassicEditor.create(document.querySelector('#body'), {
    toolbar: ['undo','redo','|','bold','italic','underline','|','numberedList','bulletedList','|','link','insertTable','mediaEmbed']
  }).then(e => CK = e);
}

function validateForm(){
  let ok = true;
  $('#title,#body,#course_id,#grade_id').removeClass('is-invalid');
  if(!$('#title').val().trim()){ $('#title').addClass('is-invalid'); ok=false; }
  const bodyHtml = CK ? CK.getData() : $('#body').val();
  const clean = bodyHtml.replace(/<[^>]*>/g,'').trim();
  if(clean.length===0){ $('#body').addClass('is-invalid'); ok=false; }
  const scope = $('#scope_type').val();
  if(scope==='course' && !$('#course_id').val()){ $('#course_id').addClass('is-invalid'); ok=false; }
  if(scope==='grade'){
    if(!$('#course_id').val()){ $('#course_id').addClass('is-invalid'); ok=false; }
    if(!$('#grade_id').val()){ $('#grade_id').addClass('is-invalid'); ok=false; }
  }
  return ok;
}

function listItems(){
  $('#list').html('<li class="list-group-item">Cargando…</li>');
  $.get('avisos_api.php', {action:'list', q: $('#q').val()||''}, function(resp){
    if(!resp.ok){ $('#list').html('<li class="list-group-item text-danger">'+(resp.msg||'Error')+'</li>'); return; }
    if(!resp.items.length){ $('#list').html('<li class="list-group-item">Sin avisos</li>'); return; }
    const out = [];
    resp.items.forEach(it => {
      out.push('<li class="list-group-item d-flex justify-content-between align-items-center" data-id="'+it.id+'">');
      out.push('<div>');
      out.push('<div class="fw-semibold">'+it.title+'</div>');
      out.push('<div class="text-muted small">'+it.scope_label+' · '+(it.active_now ? 'Activo' : 'Fuera de fecha')+'</div>');
      out.push('</div>');
      out.push('<button class="btn btn-sm btn-outline-primary">Editar</button>');
      out.push('</li>');
    });
    $('#list').html(out.join(''));
  }, 'json');
}

function clearForm(){
  $('#id').val('');
  $('#title').val('');
  $('#scope_type').val('public');
  $('#course_id').val('');
  $('#grade_id').val('');
  $('#starts_at').val('');
  $('#ends_at').val('');
  $('#status').val('1');
  if(CK){ CK.setData(''); } else { $('#body').val(''); }
  $('#btnDelete').hide();
}

function loadItem(id){
  $.get('avisos_api.php', {action:'get', id}, function(resp){
    if(!resp.ok) { showAlert('danger', resp.msg||'No se pudo cargar el aviso'); return; }
    const it = resp.item;
    $('#id').val(it.id);
    $('#title').val(it.title);
    $('#scope_type').val(it.scope_type);
    $('#course_id').val(it.course_id||'');
    $('#grade_id').val(it.grade_id||'');
    $('#starts_at').val(it.starts_at_input||'');
    $('#ends_at').val(it.ends_at_input||'');
    $('#status').val(it.status);
    initEditor();
    if(CK){ CK.setData(it.body||''); } else { $('#body').val(it.body||''); }
    $('#btnDelete').show();
    showAlert('info','Aviso cargado listo para editar.');
  }, 'json');
}

$(document).on('click', '#btnNew', function(){ clearForm(); initEditor(); $('#title').focus(); });
$(document).on('keyup', '#q', function(){ listItems(); });
$('#list').on('click', 'li button', function(){
  const id = $(this).closest('li').data('id');
  loadItem(id);
});

$('#form').on('submit', function(e){
  e.preventDefault();
  if(!validateForm()) { showAlert('warning','Revisa los campos marcados.'); return; }
  const btn = $('#btnSave'); btn.prop('disabled', true);
  btn.find('.save-text').text('Guardando…'); btn.find('.spinner-border').removeClass('d-none');

  const payload = {
    action: 'save',
    id: $('#id').val(),
    title: $('#title').val(),
    scope_type: $('#scope_type').val(),
    course_id: $('#course_id').val(),
    grade_id: $('#grade_id').val(),
    starts_at: $('#starts_at').val(),
    ends_at: $('#ends_at').val(),
    status: $('#status').val(),
    body: CK ? CK.getData() : $('#body').val()
  };

  $.post('avisos_api.php', payload, function(resp){
    btn.prop('disabled', false);
    btn.find('.save-text').text('Guardar'); btn.find('.spinner-border').addClass('d-none');
    if(!resp.ok){ showAlert('danger', resp.msg||'No se pudo guardar'); return; }
    if(!$('#id').val() && resp.id){ $('#id').val(resp.id); $('#btnDelete').show(); }
    listItems();
    showAlert('success','¡Guardado con éxito!');
  }, 'json').fail(function(xhr){
    btn.prop('disabled', false);
    btn.find('.save-text').text('Guardar'); btn.find('.spinner-border').addClass('d-none');
    const msg = (xhr && xhr.responseText) ? xhr.responseText : 'Error de red, intenta de nuevo.';
    showAlert('danger', msg);
  });
});

$('#btnDelete').on('click', function(){
  if(!confirm('¿Borrar este aviso?')) return;
  $.post('avisos_api.php', {action:'delete', id: $('#id').val()}, function(resp){
    if(!resp.ok) { showAlert('danger', resp.msg||'No se pudo borrar'); return; }
    clearForm(); listItems();
    showAlert('success','Aviso borrado.');
  }, 'json').fail(function(xhr){
    showAlert('danger', xhr.responseText || 'Error de red al borrar.');
  });
});

// init
$(function(){ listItems(); initEditor(); });
</script>
<?php include __DIR__ . '/footer.php'; ?>
