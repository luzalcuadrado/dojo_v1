<?php
// maestro/contenidos.php — Single Page CRUD + CKEditor 5 + Upload + Drag&Drop reorder
include __DIR__ . '/header.php'; // valida rol maestro y abre <html>

// Cargar cursos donde el maestro tiene grupos asignados
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

// Curso y grado activos
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (count($courses)? (int)$courses[0]['id'] : 0);
$grade_id  = isset($_GET['grade_id']) ? (int)$_GET['grade_id']   : 0;
?>
<div class="container py-3">
  <div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0 text-white">Contenidos</h1>
    <div class="ms-auto d-flex gap-2">
      <form method="get" class="d-flex gap-2">
        <select name="course_id" class="form-select" onchange="this.form.submit()">
          <?php foreach($courses as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id']===$course_id?'selected':'') ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="grade_id" class="form-select" onchange="this.form.submit()">
          <option value="0">Todos los Grados</option>
          <?php foreach($grades as $g): ?>
            <option value="<?= (int)$g['id'] ?>" <?= ((int)$g['id']===$grade_id?'selected':'') ?>>
              <?= htmlspecialchars($g['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>

  <?php if(!$course_id): ?>
    <div class="alert alert-warning">No tienes cursos asociados a grupos. Pídele al admin que te asigne a un grupo.</div>
  <?php else: ?>
  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex mb-2">
            <input id="q" class="form-control me-2" placeholder="Buscar por título...">
            <button id="btnNew" class="btn btn-primary" type="button">Nuevo</button>
          </div>
          <ul id="contentList" class="list-group small">
            <li class="list-group-item">Cargando...</li>
          </ul>
          <div class="form-text mt-2">Arrastra para reordenar dentro del mismo grado.</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <form id="contentForm">
            <input type="hidden" name="id" id="id">
            <input type="hidden" name="course_id" id="course_id" value="<?= (int)$course_id ?>">

            <div class="row g-2">
              <div class="col-md-8">
                <label class="form-label">Título</label>
                <input class="form-control" name="title" id="title" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Grado (Kyu)</label>
                <select class="form-select" name="grade_id" id="grade_id" required>
                  <?php foreach($grades as $g): ?>
                    <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="content_type" id="content_type">
                  <option value="youtube">YouTube</option>
                  <option value="vimeo">Vimeo</option>
                  <option value="pdf">PDF</option>
                  <option value="image">Imagen</option>
                  <option value="text">Texto</option>
                  <option value="link">Link</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">URL (o deja vacío si subes archivo)</label>
                <div class="input-group">
                  <input class="form-control" name="url" id="url">
                  <button class="btn btn-outline-secondary" type="button" id="btnUpload">Subir...</button>
                </div>
                <div class="form-text">Para PDF/Imagen puedes subir el archivo. Para YouTube/Vimeo pega el enlace.</div>
              </div>
              <div class="col-12" id="bodyWrapper" style="display:none;">
                <label class="form-label">Contenido (texto)</label>
                <textarea class="form-control" name="body" id="body" rows="8"></textarea>
              </div>
              <div class="col-12 text-end mt-2">
                <button type="button" id="btnDelete" class="btn btn-outline-danger me-2" style="display:none;">Borrar</button>
                <button type="submit" class="btn btn-success">Guardar</button>
              </div>
            </div>
          </form>

          <hr>
          <div>
            <h6 class="mb-2">Vista previa</h6>
            <div id="preview" class="ratio ratio-16x9 border rounded d-flex align-items-center justify-content-center text-muted">
              Selecciona o crea un contenido…
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- CKEditor 5 (CDN) -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const COURSE_ID = <?= (int)$course_id ?>;
const GRADE_ID  = <?= (int)$grade_id ?>;
let CK = null;

function initEditor(){
  if(CK) return;
  ClassicEditor.create(document.querySelector('#body'), {
    toolbar: ['undo','redo','|','bold','italic','underline','|','numberedList','bulletedList','|','link','insertTable','mediaEmbed']
  }).then(editor => {
    CK = editor;
  }).catch(err => console.error(err));
}

function destroyEditor(){
  if(CK){
    CK.destroy().then(() => { CK = null; }).catch(()=>{});
  }
}

function showBodyByType(){
  const t = $('#content_type').val();
  if(t === 'text'){ $('#bodyWrapper').show(); initEditor(); }
  else { $('#bodyWrapper').hide(); destroyEditor(); $('#body').val(''); }
}

function renderPreview(item){
  if(!item){ $('#preview').html('Selecciona o crea un contenido…'); return; }
  const t = item.content_type;
  let html = '';
  if(t === 'youtube'){
    const m = (item.url||'').match(/(?:v=|\/embed\/|youtu\.be\/)([A-Za-z0-9_\-]{6,})/);
    const id = m? m[1] : '';
    html = '<iframe src=\"https://www.youtube.com/embed/'+id+'?rel=0\" allowfullscreen class=\"w-100 h-100\"></iframe>';
  }else if(t === 'vimeo'){
    html = '<iframe src=\"'+(item.url||'')+'\" allowfullscreen class=\"w-100 h-100\"></iframe>';
  }else if(t === 'pdf'){
    html = '<iframe src=\"'+(item.url||'')+'\" class=\"w-100\" style=\"height:60vh\"></iframe>';
  }else if(t === 'image'){
    html = '<img src=\"'+(item.url||'')+'\" class=\"img-fluid\" />';
  }else if(t === 'text'){
    html = '<div class=\"p-2\">'+(item.body||'')+'</div>';
  }else{
    html = '<a target=\"_blank\" href=\"'+(item.url||'#')+'\">Abrir recurso</a>';
  }
  $('#preview').removeClass('ratio ratio-16x9').html(html);
}

function listItems(){
  $('#contentList').html('<li class="list-group-item">Cargando…</li>');
  $.get('content_api.php', {action:'list', course_id: COURSE_ID, grade_id: GRADE_ID, q: $('#q').val()||''}, function(resp){
    if(!resp.ok){ $('#contentList').html('<li class="list-group-item text-danger">'+resp.msg+'</li>'); return; }
    const out = [];
    resp.items.forEach(function(it){
      out.push('<li class="list-group-item d-flex align-items-center" data-id="'+it.id+'" draggable="true">');
      out.push('<span class="me-2 text-muted">☰</span>');
      out.push('<div class="flex-fill">');
      out.push('<div class="fw-semibold">'+it.title+'</div>');
      out.push('<div class="text-muted small">'+it.grade_name+' · '+it.content_type+' · #'+it.seq+'</div>');
      out.push('</div></li>');
    });
    $('#contentList').html(out.join(''));
  }, 'json');
}

function clearForm(){
  $('#id').val('');
  $('#title').val('');
  $('#content_type').val('youtube');
  $('#url').val('');
  $('#grade_id').val(GRADE_ID || '');
  $('#btnDelete').hide();
  destroyEditor();
  $('#body').val('');
  showBodyByType();
  renderPreview(null);
}

function loadItem(id){
  $.get('content_api.php', {action:'get', id:id}, function(resp){
    if(!resp.ok) return alert(resp.msg||'Error');
    const it = resp.item;
    $('#id').val(it.id);
    $('#title').val(it.title);
    $('#content_type').val(it.content_type);
    $('#url').val(it.url);
    $('#grade_id').val(it.grade_id);
    if(it.content_type==='text'){
      showBodyByType();
      if(CK) CK.setData(it.body||'');
    }else{
      destroyEditor();
      $('#body').val(it.body||'');
    }
    $('#btnDelete').show();
    renderPreview(it);
  }, 'json');
}

$(document).on('click', '#btnNew', function(){
  clearForm();
  $('#title').focus();
});

$(document).on('keyup', '#q', function(){ listItems(); });

$(document).on('change', '#content_type', showBodyByType);

$('#contentForm').on('submit', function(e){
  e.preventDefault();
  const payload = $(this).serializeArray();
  if(CK){ payload.push({name:'body', value: CK.getData()}); }
  payload.push({name:'action', value:'save'});
  $.post('content_api.php', payload, function(resp){
    if(!resp.ok) return alert(resp.msg||'Error');
    listItems();
    if(resp.item){ $('#id').val(resp.item.id); renderPreview(resp.item); $('#btnDelete').show(); }
  }, 'json');
});

$('#contentList').on('click', 'li', function(){
  const id = $(this).data('id');
  loadItem(id);
});

// Drag & Drop reordenar
let dragId = null;
$(document).on('dragstart', '#contentList li', function(e){ dragId = $(this).data('id'); });
$(document).on('dragover', '#contentList li', function(e){ e.preventDefault(); });
$(document).on('drop', '#contentList li', function(e){
  e.preventDefault();
  const target = $(this);
  const dragged = $('#contentList li[data-id="'+dragId+'"]');
  if(dragged[0] === target[0]) return;
  if(dragged.index() < target.index()) target.after(dragged); else target.before(dragged);
  const ids = $('#contentList li').map(function(){ return $(this).data('id'); }).get();
  $.post('content_api.php', {action:'reorder', ids: ids.join(','), course_id: COURSE_ID, grade_id: $('#grade_id').val()}, function(resp){
    if(!resp.ok) alert(resp.msg||'Error al reordenar');
    else listItems();
  }, 'json');
});

// Upload (pdf/image)
$('#btnUpload').on('click', function(){
  const t = $('#content_type').val();
  if(t!=='pdf' && t!=='image'){ alert('El botón Subir es solo para PDF o Imagen.'); return; }
  const inp = $('<input type="file" accept="'+(t==='pdf'?'.pdf':'image/*')+'">');
  inp.on('change', function(){
    const f = this.files[0]; if(!f) return;
    const fd = new FormData();
    fd.append('file', f);
    fd.append('type', t);
    $.ajax({
      url: 'upload.php',
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      success: function(resp){
        try{ resp = JSON.parse(resp); }catch(e){ alert('Respuesta inválida'); return; }
        if(!resp.ok) return alert(resp.msg||'Error');
        $('#url').val(resp.url);
        renderPreview({content_type:t, url: resp.url});
      },
      error: function(){ alert('Error de red al subir'); }
    });
  });
  inp.click();
});

// Inicial
$(function(){
  listItems();
  showBodyByType();
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
