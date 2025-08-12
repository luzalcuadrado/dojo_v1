<?php
require_once '../config.php';
require_once '../auth.php';
header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['admin','maestro'])) {
    echo json_encode(['ok'=>false,'msg'=>'Sin permisos']); exit;
}

$action = $_GET['action'] ?? '';

if ($action == 'list') {
    $sql = "SELECT c.*, s.name as school_name,
            IF(c.status=1,'Activo','Inactivo') as status_label,
            CONCAT('<button class=\'btn btn-sm btn-warning btnEditar\'>Editar</button> ',
                   '<button class=\'btn btn-sm btn-danger btnBorrar\' data-id=\'',c.id,'\'>Borrar</button>') as acciones
            FROM courses c
            JOIN schools s ON s.id = c.school_id";
    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()){ $data[] = $row; }
    echo json_encode(['data'=>$data]);
}
elseif ($action == 'schools') {
    $res = $conn->query("SELECT id,name FROM schools WHERE status=1");
    $opts = "<option value=''>-- Selecciona --</option>";
    while($row = $res->fetch_assoc()){
        $opts .= "<option value='{$row['id']}'>{$row['name']}</option>";
    }
    echo json_encode(['ok'=>true,'options'=>$opts]);
}
elseif ($action == 'save') {
    $id = $_POST['id'] ?? '';
    $school_id = intval($_POST['school_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $status = intval($_POST['status'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $created_by = $_SESSION['uid'];

    if(!$school_id || !$name || !$start_date){
        echo json_encode(['ok'=>false,'msg'=>'Faltan campos obligatorios']); exit;
    }

    if($id){
        $stmt = $conn->prepare("UPDATE courses SET school_id=?, name=?, start_date=?, status=?, description=? WHERE id=?");
        $stmt->bind_param("issisi", $school_id,$name,$start_date,$status,$description,$id);
        $ok = $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO courses (school_id,name,start_date,status,description,created_by) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("issisi", $school_id,$name,$start_date,$status,$description,$created_by);
        $ok = $stmt->execute();
    }
    echo json_encode(['ok'=>$ok,'msg'=>$ok?'Guardado con éxito':'Error al guardar']);
}
elseif ($action == 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if(!$id){ echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }
    $ok = $conn->query("DELETE FROM courses WHERE id=$id");
    echo json_encode(['ok'=>$ok,'msg'=>$ok?'Eliminado':'Error al eliminar']);
}
?>
