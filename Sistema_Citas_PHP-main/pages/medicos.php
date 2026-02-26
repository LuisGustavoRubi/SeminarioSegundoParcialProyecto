<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $especialidad = $_POST['especialidad'];
        $telefono = $_POST['telefono'];
        $email = $_POST['email'];
        
        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO medicos (nombre, apellido, especialidad, telefono, email) 
                    VALUES ('$nombre', '$apellido', '$especialidad', '$telefono', '$email')";
            if ($conn->query($sql)) {
                $_SESSION['success'] = "Médico registrado exitosamente";
                header("Location: medicos.php");
                exit();
            } else {
                $_SESSION['error'] = "Error al registrar médico";
            }
        } elseif ($_POST['action'] == 'update') {
            $id = $_POST['id'];
            $sql = "UPDATE medicos SET nombre='$nombre', apellido='$apellido', especialidad='$especialidad', 
                    telefono='$telefono', email='$email' WHERE id=$id";
            if ($conn->query($sql)) {
                $_SESSION['success'] = "Médico actualizado exitosamente";
                header("Location: medicos.php");
                exit();
            } else {
                $_SESSION['error'] = "Error al actualizar médico";
            }
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($conn->query("DELETE FROM medicos WHERE id=$id")) {
        $_SESSION['success'] = "Médico eliminado exitosamente";
    } else {
        $_SESSION['error'] = "Error al eliminar médico";
    }
    header("Location: medicos.php");
    exit();
}

$medico = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM medicos WHERE id=$id");
    $medico = $result->fetch_assoc();
}

$mostrar_formulario = isset($_GET['action']) && ($_GET['action'] == 'new' || $_GET['action'] == 'edit');

if ($mostrar_formulario) {
    $page_title = $medico ? "Editar Médico" : "Nuevo Médico";
} else {
    $page_title = "Gestión de Médicos";
    $medicos = $conn->query("SELECT * FROM medicos ORDER BY apellido, nombre");
}

include '../includes/header.php';
?>

<?php if ($mostrar_formulario): ?>
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <?php echo $medico ? 'Editar' : 'Registrar'; ?> Médico
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $medico ? 'update' : 'create'; ?>">
                        <?php if ($medico): ?>
                            <input type="hidden" name="id" value="<?php echo $medico['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo $medico ? $medico['nombre'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido *</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" 
                                       value="<?php echo $medico ? $medico['apellido'] : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="especialidad" class="form-label">Especialidad *</label>
                            <input type="text" class="form-control" id="especialidad" name="especialidad" 
                                   value="<?php echo $medico ? $medico['especialidad'] : ''; ?>"
                                   placeholder="Ej: Medicina General, Pediatría, Cardiología..." required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo $medico ? $medico['telefono'] : ''; ?>"
                                       placeholder="+504 1234-5678">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo $medico ? $medico['email'] : ''; ?>"
                                       placeholder="doctor@hospital.com">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="medicos.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> <?php echo $medico ? 'Actualizar' : 'Registrar'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="mb-3">
        <a href="?action=new" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo Médico
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-person-badge"></i> Lista de Médicos (<?php echo $medicos->num_rows; ?>)
        </div>
        <div class="card-body">
            <?php if ($medicos->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Especialidad</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($m = $medicos->fetch_assoc()): ?>
                            <tr>
                                <td><strong>Dr. <?php echo $m['nombre'] . ' ' . $m['apellido']; ?></strong></td>
                                <td><span class="badge bg-info text-dark"><?php echo $m['especialidad']; ?></span></td>
                                <td><?php echo $m['telefono'] ?: '-'; ?></td>
                                <td><?php echo $m['email'] ?: '-'; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?action=edit&id=<?php echo $m['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmarEliminacion(<?php echo $m['id']; ?>, 'Dr. <?php echo $m['nombre'] . ' ' . $m['apellido']; ?>', 'al médico')" 
                                                title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                    <p class="mt-3">No hay médicos registrados</p>
                    <a href="?action=new" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Registrar Primer Médico
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>