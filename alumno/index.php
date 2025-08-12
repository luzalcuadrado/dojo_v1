<?php
// alumno/index.php — FIX SQL alias (co.name) + header/footer includes

include __DIR__ . '/header.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) { header('Location: ../login.php'); exit; }

// Cursos donde el alumno tiene contenidos asignados ON
$sqlCourses = "
SELECT co.id, co.name
FROM contents c
JOIN content_assignments ca ON ca.content_id = c.id AND ca.is_enabled = 1
JOIN courses co ON co.id = c.course_id
WHERE ca.user_id = ?
  AND c.status = 1
GROUP BY co.id, co.name
ORDER BY co.name, co.id;

";
$stmt = $conn->prepare($sqlCourses);
$stmt->bind_param('i', $uid);
$stmt->execute();
$coursesRes = $stmt->get_result();
$courses = $coursesRes->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Elegir curso activo (param o primero)
$activeCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($activeCourseId <= 0 && !empty($courses)) {
  $activeCourseId = (int)$courses[0]['id'];
}

$contents = [];
if ($activeCourseId > 0) {
  // Cargar contenidos asignados por grado, ordenados por seq
  $sql = "
  SELECT c.id, c.title, c.content_type, c.url, c.body, c.seq, g.name AS grade_name, g.ord AS grade_ord
  FROM contents c
  JOIN content_assignments ca ON ca.content_id = c.id AND ca.is_enabled = 1 AND ca.user_id = ?
  JOIN grades g ON g.id = c.grade_id
  WHERE c.course_id = ? AND c.status = 1
  ORDER BY g.ord DESC, c.seq ASC
  ";
  $st = $conn->prepare($sql);
  $st->bind_param('ii', $uid, $activeCourseId);
  $st->execute();
  $r = $st->get_result();
  while($row = $r->fetch_assoc()){ $contents[] = $row; }
  $st->close();
}

// Agrupar por grado
$byGrade = [];
foreach($contents as $it){
  $key = $it['grade_name'];
  if(!isset($byGrade[$key])) $byGrade[$key] = [];
  $byGrade[$key][] = $it;
}

// Primer elemento para el player inicial
$first = $contents[0] ?? null;

?>
<div class="container py-3">
  <div class="d-flex align-items-center mb-3">
    <h1 class="h4 mb-0">Mis contenidos</h1>
    <form class="ms-auto" method="get">
      <div class="input-group" style="max-width:320px;">
        <label class="input-group-text">Curso</label>
        <select class="form-select" name="course_id" onchange="this.form.submit()">
          <?php foreach($courses as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id']===$activeCourseId?'selected':'') ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>

  <?php if(empty($courses)): ?>
    <div class="alert alert-warning">Aún no tienes contenidos asignados.</div>
  <?php else: ?>
    <div class="row g-3">
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
          <div class="card-body">
            <div id="playerArea">
              <?php if($first): ?>
                <div class="small text-muted mb-1" id="playerGrade"><?= htmlspecialchars($first['grade_name']) ?></div>
                <h2 class="h5 mb-3" id="playerTitle"><?= htmlspecialchars($first['title']) ?></h2>
                <div id="playerSlot" class="ratio ratio-16x9">
                  <?php if($first['content_type']==='youtube'): 
                    // Extraer id si es URL completa
                    $yt = $first['url'];
                    $ytid = preg_replace('~^.*(?:v=|/embed/|youtu\.be/)([A-Za-z0-9_\-]{6,})\b.*$~','$1',$yt);
                  ?>
                    <iframe id="playerFrame" src="https://www.youtube.com/embed/<?= htmlspecialchars($ytid) ?>?rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                  <?php elseif($first['content_type']==='vimeo'): ?>
                    <iframe id="playerFrame" src="<?= htmlspecialchars($first['url']) ?>" allowfullscreen></iframe>
                  <?php elseif($first['content_type']==='image'): ?>
                    <img id="playerImage" src="<?= htmlspecialchars($first['url']) ?>" class="img-fluid rounded" alt="">
                  <?php elseif($first['content_type']==='pdf'): ?>
                    <iframe id="playerPdf" src="<?= htmlspecialchars($first['url']) ?>" class="w-100" style="height:60vh;"></iframe>
                  <?php elseif($first['content_type']==='text'): ?>
                    <div id="playerText"><?= $first['body'] ?></div>
                  <?php else: ?>
                    <a id="playerLink" href="<?= htmlspecialchars($first['url']) ?>" target="_blank" rel="noopener">Abrir recurso</a>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="alert alert-info mb-0">No hay contenidos para este curso aún.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <?php foreach($byGrade as $gname=>$items): ?>
              <h3 class="h6 mt-2 mb-2"><?= htmlspecialchars($gname) ?></h3>
              <div class="list-group mb-3">
                <?php foreach($items as $it): 
                  $isYt = ($it['content_type']==='youtube');
                  $thumb = $isYt ? ('https://img.youtube.com/vi/'.preg_replace('~^.*(?:v=|/embed/|youtu\.be/)([A-Za-z0-9_\-]{6,})\b.*$~','$1',$it['url']).'/mqdefault.jpg') : null;
                ?>
                  <a href="#" class="list-group-item list-group-item-action d-flex gap-2 align-items-center content-item"
                     data-title="<?= htmlspecialchars($it['title']) ?>"
                     data-grade="<?= htmlspecialchars($it['grade_name']) ?>"
                     data-type="<?= htmlspecialchars($it['content_type']) ?>"
                     data-url="<?= htmlspecialchars($it['url']) ?>"
                     data-body='<?= json_encode($it['body'] ?? "", JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>'>
                    <?php if($thumb): ?>
                      <img src="<?= htmlspecialchars($thumb) ?>" alt="" style="width:72px;height:40px;object-fit:cover;border-radius:6px;">
                    <?php else: ?>
                      <span class="badge bg-secondary me-2 text-uppercase"><?= htmlspecialchars($it['content_type']) ?></span>
                    <?php endif; ?>
                    <div class="flex-fill">
                      <div class="small text-muted">#<?= (int)$it['seq'] ?> · <?= htmlspecialchars($it['grade_name']) ?></div>
                      <div class="fw-semibold"><?= htmlspecialchars($it['title']) ?></div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function(){
  $('.content-item').on('click', function(e){
    e.preventDefault();
    const title = $(this).data('title');
    const grade = $(this).data('grade');
    const type  = $(this).data('type');
    const url   = $(this).data('url');
    const body  = $(this).data('body');

    $('#playerTitle').text(title);
    $('#playerGrade').text(grade);

    const slot = $('#playerSlot');
    slot.empty();

    if(type === 'youtube'){
      const m = url.match(/(?:v=|\/embed\/|youtu\.be\/)([A-Za-z0-9_\-]{6,})/);
      const id = m ? m[1] : url;
      slot.addClass('ratio ratio-16x9');
      slot.html('<iframe src="https://www.youtube.com/embed/'+id+'?rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>');
    } else if(type === 'vimeo'){
      slot.addClass('ratio ratio-16x9');
      slot.html('<iframe src="'+url+'" allowfullscreen></iframe>');
    } else if(type === 'image'){
      slot.removeClass('ratio ratio-16x9');
      slot.html('<img src="'+url+'" class="img-fluid rounded" alt="">');
    } else if(type === 'pdf'){
      slot.removeClass('ratio ratio-16x9');
      slot.html('<iframe src="'+url+'" class="w-100" style="height:60vh;"></iframe>');
    } else if(type === 'text'){
      slot.removeClass('ratio ratio-16x9');
      slot.html(body || '');
    } else {
      slot.removeClass('ratio ratio-16x9');
      slot.html('<a href="'+url+'" target="_blank" rel="noopener">Abrir recurso</a>');
    }
  });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
