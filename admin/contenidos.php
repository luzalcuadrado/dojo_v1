<?php require_once __DIR__ . '/../auth.php'; require_role('admin'); require_once __DIR__ . '/../config.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['id'] ?? 0); $course_id=(int)($_POST['course_id'] ?? 1); $grade_id=(int)($_POST['grade_id'] ?? 0);
  $title=trim($_POST['title'] ?? ''); $type=$_POST['type'] ?? 'youtube'; $url=trim($_POST['url'] ?? ''); $seq=(int)($_POST['seq'] ?? 1); $status=(int)($_POST['status'] ?? 1);
  if($id>0){ $st=$conn->prepare("UPDATE contents SET course_id=?,grade_id=?,title=?,content_type=?,url=?,seq=?,status=? WHERE id=?");
    $st->bind_param("iisssiii",$course_id,$grade_id,$title,$type,$url,$seq,$status,$id); $st->execute();
  } else { $st=$conn->prepare("INSERT INTO contents (course_id,grade_id,title,content_type,url,seq,status,created_by) VALUES (?,?,?,?,?,?,?,1)");
    $st->bind_param("iisssii",$course_id,$grade_id,$title,$type,$url,$seq,$status); $st->execute();
  }
  header("Location: contenidos.php"); exit;
}
if(isset($_GET['del'])){ $id=(int)$_GET['del']; $conn->query("DELETE FROM contents WHERE id=$id"); header("Location: contenidos.php"); exit; }
$edit=null; if(isset($_GET['id'])){ $id=(int)$_GET['id']; $edit=$conn->query("SELECT * FROM contents WHERE id=$id")->fetch_assoc(); }
$courses = $conn->query("SELECT id,name FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$grades  = $conn->query("SELECT id,name,ord FROM grades ORDER BY ord DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contenidos</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>
<div class="container py-4"><a href="./" class="btn btn-link">&larr; Volver</a>
<h1 class="h5">ABC Contenidos</h1>
<div class="card mb-3"><div class="card-body"><form method="post">
<input type="hidden" name="id" value="<?php echo htmlspecialchars($edit['id'] ?? 0); ?>">
<div class="row g-2">
  <div class="col-md-3">
    <label class="form-label">Curso</label>
    <select class="form-select" name="course_id"><?php foreach($courses as $c): $sel=(isset($edit['course_id']) && $edit['course_id']==$c['id'])?'selected':''; ?>
      <option value="<?php echo $c['id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Grado</label>
    <select class="form-select" name="grade_id"><?php foreach($grades as $g): $sel=(isset($edit['grade_id']) && $edit['grade_id']==$g['id'])?'selected':''; ?>
      <option value="<?php echo $g['id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($g['name']); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Título</label>
    <input class="form-control" name="title" required value="<?php echo htmlspecialchars($edit['title'] ?? ''); ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Tipo</label>
    <select class="form-select" name="type"><?php $t=$edit['content_type'] ?? 'youtube'; foreach(['youtube','vimeo','pdf','image','text','link'] as $opt){ $sel=$t===$opt?'selected':''; echo "<option value='$opt' $sel>$opt</option>"; } ?></select>
  </div>
  <div class="col-md-5">
    <label class="form-label">URL / Recurso</label>
    <input class="form-control" name="url" value="<?php echo htmlspecialchars($edit['url'] ?? ''); ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">Orden</label>
    <input class="form-control" type="number" name="seq" value="<?php echo htmlspecialchars($edit['seq'] ?? 1); ?>">
  </div>
  <div class="col-md-2">
    <label class="form-label">Estatus</label>
    <select class="form-select" name="status"><?php $s=(int)($edit['status'] ?? 1); ?>
      <option value="1" <?php echo $s===1?'selected':''; ?>>Activo</option>
      <option value="0" <?php echo $s===0?'selected':''; ?>>Inactivo</option>
    </select>
  </div>
</div>
<div class="mt-2"><button class="btn btn-primary">Guardar</button></div>
</form></div></div>

<div class="card"><div class="card-body table-responsive">
<table class="table"><thead><tr><th>ID</th><th>Curso</th><th>Grado</th><th>#</th><th>Título</th><th>Tipo</th><th></th></tr></thead><tbody>
<?php
$rs = $conn->query("SELECT c.id,co.name cname,g.name gname,c.seq,c.title,c.content_type FROM contents c JOIN courses co ON co.id=c.course_id JOIN grades g ON g.id=c.grade_id ORDER BY co.name,g.ord DESC,c.seq");
while($r=$rs->fetch_assoc()):
?>
<tr><td><?php echo $r['id']; ?></td><td><?php echo htmlspecialchars($r['cname']); ?></td><td><?php echo htmlspecialchars($r['gname']); ?></td><td><?php echo $r['seq']; ?></td><td><?php echo htmlspecialchars($r['title']); ?></td><td><?php echo $r['content_type']; ?></td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="contenidos.php?id=<?php echo $r['id']; ?>">Editar</a>
<a class="btn btn-sm btn-outline-danger" href="contenidos.php?del=<?php echo $r['id']; ?>" onclick="return confirm('¿Eliminar contenido?')">Borrar</a></td></tr>
<?php endwhile; ?>
</tbody></table>
</div></div>
</div></body></html>