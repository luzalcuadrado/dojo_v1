<?php require_once __DIR__ . '/../auth.php'; require_role('admin'); require_once __DIR__ . '/../config.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['id'] ?? 0); $name=trim($_POST['name'] ?? ''); $status=(int)($_POST['status'] ?? 1);
  if($id>0){ $st=$conn->prepare("UPDATE courses SET name=?, status=? WHERE id=?"); $st->bind_param("sii",$name,$status,$id); $st->execute(); }
  else { $st=$conn->prepare("INSERT INTO courses (school_id,name,status,created_by) VALUES (1,?,1,1)"); $st->bind_param("s",$name); $st->execute(); }
  header("Location: cursos.php"); exit;
}
if(isset($_GET['del'])){ $id=(int)$_GET['del']; $conn->query("DELETE FROM courses WHERE id=$id"); header("Location: cursos.php"); exit; }
$edit=null; if(isset($_GET['id'])){ $id=(int)$_GET['id']; $edit=$conn->query("SELECT id,name,status FROM courses WHERE id=$id")->fetch_assoc(); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cursos</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>
<div class="container py-4"><a href="./" class="btn btn-link">&larr; Volver</a>
<h1 class="h5">ABC Cursos</h1>
<div class="card mb-3"><div class="card-body"><form method="post">
<input type="hidden" name="id" value="<?php echo htmlspecialchars($edit['id'] ?? 0); ?>">
<div class="row g-2"><div class="col-md-6"><input class="form-control" name="name" placeholder="Nombre" required value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>"></div>
<div class="col-md-2"><select class="form-select" name="status"><?php $s=(int)($edit['status'] ?? 1); ?>
<option value="1" <?php echo $s===1?'selected':''; ?>>Activo</option><option value="0" <?php echo $s===0?'selected':''; ?>>Inactivo</option></select></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Guardar</button></div></div>
</form></div></div>
<div class="card"><div class="card-body table-responsive"><table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Estatus</th><th></th></tr></thead><tbody>
<?php $rs=$conn->query("SELECT id,name,status FROM courses ORDER BY id DESC"); while($r=$rs->fetch_assoc()): ?>
<tr><td><?php echo $r['id']; ?></td><td><?php echo htmlspecialchars($r['name']); ?></td><td><?php echo $r['status']?'Activo':'Inactivo'; ?></td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="cursos.php?id=<?php echo $r['id']; ?>">Editar</a>
<a class="btn btn-sm btn-outline-danger" href="cursos.php?del=<?php echo $r['id']; ?>" onclick="return confirm('¿Eliminar curso?')">Borrar</a></td></tr>
<?php endwhile; ?></tbody></table></div></div>
</div></body></html>