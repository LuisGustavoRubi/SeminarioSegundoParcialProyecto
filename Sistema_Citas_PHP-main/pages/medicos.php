<?php
require_once '../includes/config.php';
require_once '../controllers/MedicosController.php';

$controller = new MedicoController($conn);
$controller->handleRequest();

$medico = null;
$mostrar_formulario = false;

// Verificar acciones
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $medico = $controller->obtenerPorId($_GET['id']);
    $mostrar_formulario = true;
}

if (isset($_GET['action']) && $_GET['action'] == 'new') {
    $mostrar_formulario = true;
}

if (!$mostrar_formulario) {
    $medicos = $controller->obtenerTodos();
}

// AHORA sí puedes usarla
$current_page = 'medicos';
$page_title = $mostrar_formulario 
    ? ($medico ? "Editar Médico" : "Nuevo Médico")
    : "Gestión de Médicos";

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
                                <td><strong>Dr. <?php echo normalizar_texto($m['nombre']) . ' ' . normalizar_texto($m['apellido']); ?></strong></td>
                                <td><span class="badge bg-info text-dark"><?php echo normalizar_texto($m['especialidad']); ?></span></td>
                                <td><?php echo $m['telefono'] ?: '-'; ?></td>
                                <td><?php echo $m['email'] ?: '-'; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?action=edit&id=<?php echo $m['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="confirmarEliminacion(<?php echo $m['id']; ?>, 'Dr. <?php echo htmlspecialchars(normalizar_texto($m['nombre']) . ' ' . normalizar_texto($m['apellido']), ENT_QUOTES, 'UTF-8'); ?>', 'al médico')" 
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
