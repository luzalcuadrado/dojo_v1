<?php require_once __DIR__ . '/../auth.php'; require_role('admin'); require_once __DIR__ . '/../config.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role = $_POST['role'] ?? 'alumno';
  $status = (int)($_POST['status'] ?? 1);
  if($id>0){
    $st = $conn->prepare("UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?");
    $st->bind_param("sssii",$name,$email,$role,$status,$id); $st->execute();
  }else{
    $pass = $_POST['password'] ?? '123456';
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $st = $conn->prepare("INSERT INTO users (school_id,name,email,password_hash,role,status) VALUES (1,?,?,?,?,?)");
    $st->bind_param("ssssi",$name,$email,$hash,$role,$status); $st->execute();
  }
  header("Location: usuarios.php"); exit;
}
if(isset($_GET['del'])){ $id=(int)$_GET['del']; $conn->query("DELETE FROM users WHERE id=$id"); header("Location: usuarios.php"); exit; }
$edit = null;
if(isset($_GET['id'])){ $id=(int)$_GET['id']; $edit=$conn->query("SELECT id,name,email,role,status FROM users WHERE id=$id")->fetch_assoc(); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Usuarios</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body>
<div class="container py-4">
  <a href="./" class="btn btn-link">&larr; Volver</a>
  <h1 class="h5">ABC Usuarios</h1>
  <div class="card mb-3"><div class="card-body">
    <form method="post">
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit['id'] ?? 0); ?>">
      <div class="row g-2">
        <div class="col-md-3"><input class="form-control" name="name" placeholder="Nombre" required value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>"></div>
        <div class="col-md-3"><input class="form-control" type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($edit['email'] ?? ''); ?>"></div>
        <div class="col-md-2">
          <select class="form-select" name="role">
            <?php $r=$edit['role'] ?? 'alumno'; ?>
            <option value="admin" <?php echo $r==='admin'?'selected':''; ?>>Admin</option>
            <option value="maestro" <?php echo $r==='maestro'?'selected':''; ?>>Maestro</option>
            <option value="alumno" <?php echo $r==='alumno'?'selected':''; ?>>Alumno</option>
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" name="status">
            <?php $s=(int)($edit['status'] ?? 1); ?>
            <option value="1" <?php echo $s===1?'selected':''; ?>>Activo</option>
            <option value="0" <?php echo $s===0?'selected':''; ?>>Inactivo</option>
          </select>
        </div>
        <div class="col-md-2">
          <?php if(!$edit): ?><input class="form-control" name="password" placeholder="Password inicial"><?php endif; ?>
        </div>
      </div>
      <div class="mt-2"><button class="btn btn-primary">Guardar</button>
      <?php if($edit): ?><a class="btn btn-outline-secondary" href="usuarios.php">Cancelar</a><?php endif; ?>
      </div>
    </form>
  </div></div>
  <div class="card"><div class="card-body table-responsive">
    <table class="table"><thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estatus</th><th></th></tr></thead><tbody>
    <?php $rs=$conn->query("SELECT id,name,email,role,status FROM users ORDER BY id DESC"); while($u=$rs->fetch_assoc()): ?>
      <tr><td><?php echo $u['id']; ?></td><td><?php echo htmlspecialchars($u['name']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td><td><?php echo $u['role']; ?></td><td><?php echo $u['status']?'Activo':'Inactivo'; ?></td>
      <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="usuarios.php?id=<?php echo $u['id']; ?>">Editar</a>
      <a class="btn btn-sm btn-outline-danger" href="usuarios.php?del=<?php echo $u['id']; ?>" onclick="return confirm('¿Eliminar usuario?')">Borrar</a></td></tr>
    <?php endwhile; ?></tbody></table>
  </div></div>
</div></body></html>