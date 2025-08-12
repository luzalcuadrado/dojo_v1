<?php
require_once '../config.php';
require_once '../auth.php';
if (!in_array($_SESSION['role'], ['admin','maestro'])) {
    die('No autenticado o sin permisos.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Cursos</title>
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.min.js"></script>
</head>
<body class="p-3">
<h3>Gestión de Cursos</h3>
<button id="btnNuevo" class="btn btn-primary mb-3">Nuevo</button>
<table id="tablaCursos" class="display">
<thead>
<tr>
<th>Curso</th>
<th>Escuela</th>
<th>Inicio</th>
<th>Status</th>
<th>Acciones</th>
</tr>
</thead>
</table>

<!-- Modal -->
<div class="modal fade" id="modalCurso" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Curso</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<form id="formCurso">
<input type="hidden" name="id" id="curso_id">
<div class="mb-3">
<label>Escuela *</label>
<select name="school_id" id="school_id" class="form-select" required></select>
</div>
<div class="mb-3">
<label>Nombre del curso *</label>
<input type="text" name="name" id="name" class="form-control" required>
</div>
<div class="mb-3">
<label>Inicio *</label>
<input type="date" name="start_date" id="start_date" class="form-control" required>
</div>
<div class="mb-3">
<label>Status</label>
<select name="status" id="status" class="form-select">
<option value="1">Activo</option>
<option value="0">Inactivo</option>
</select>
</div>
<div class="mb-3">
<label>Descripción</label>
<textarea name="description" id="description" class="form-control"></textarea>
</div>
</form>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
<button type="button" id="btnGuardar" class="btn btn-success">Guardar</button>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let tabla;
$(document).ready(function(){
    cargarEscuelas();
    tabla = $('#tablaCursos').DataTable({
        ajax: 'cursos_api.php?action=list',
        columns: [
            { data: 'name' },
            { data: 'school_name' },
            { data: 'start_date' },
            { data: 'status_label' },
            { data: 'acciones' }
        ]
    });

    $('#btnNuevo').click(function(){
        $('#formCurso')[0].reset();
        $('#curso_id').val('');
        $('#modalCurso').modal('show');
    });

    $('#btnGuardar').click(function(){
        $.post('cursos_api.php?action=save', $('#formCurso').serialize(), function(resp){
            alert(resp.msg);
            if(resp.ok){
                $('#modalCurso').modal('hide');
                tabla.ajax.reload();
            }
        }, 'json');
    });

    $('#tablaCursos').on('click', '.btnEditar', function(){
        let data = tabla.row($(this).parents('tr')).data();
        $('#curso_id').val(data.id);
        $('#school_id').val(data.school_id);
        $('#name').val(data.name);
        $('#start_date').val(data.start_date);
        $('#status').val(data.status);
        $('#description').val(data.description);
        $('#modalCurso').modal('show');
    });

    $('#tablaCursos').on('click', '.btnBorrar', function(){
        if(confirm('¿Borrar curso?')){
            let id = $(this).data('id');
            $.post('cursos_api.php?action=delete', {id:id}, function(resp){
                alert(resp.msg);
                if(resp.ok) tabla.ajax.reload();
            }, 'json');
        }
    });
});

function cargarEscuelas(){
    $.get('cursos_api.php?action=schools', function(resp){
        if(resp.ok){
            $('#school_id').html(resp.options);
        }
    }, 'json');
}
</script>
</body>
</html>
