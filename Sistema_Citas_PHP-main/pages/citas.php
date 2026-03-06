<?php
require_once '../includes/config.php';
require_once '../controllers/CitasController.php';

$controller = new CitasController($conn);
$controller->handleRequest();

$cita = null;
$mostrar_formulario = false;

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $cita = $controller->obtenerPorId($_GET['id']);
    $mostrar_formulario = true;
}

if (isset($_GET['action']) && $_GET['action'] == 'new') {
    $mostrar_formulario = true;
}

if ($mostrar_formulario) {
    $pacientes = $controller->obtenerPacientes();
    $medicos = $controller->obtenerMedicos();
} else {
    $citas = $controller->obtenerTodas();
}

$current_page = 'citas';
$page_title = $mostrar_formulario
    ? ($cita ? "Editar Cita" : "Nueva Cita")
    : "Gestión de Citas";

include '../includes/header.php';
?>

<?php if ($mostrar_formulario): ?>
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <?php echo $cita ? 'Editar' : 'Agendar'; ?> Cita
                </div>
                <div class="card-body">
                    <form method="POST" id="formCita">
                        <input type="hidden" name="action" value="<?php echo $cita ? 'update' : 'create'; ?>">
                        <?php if ($cita): ?>
                            <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="paciente_id" class="form-label">Paciente *</label>
                                <select class="form-select" id="paciente_id" name="paciente_id" required>
                                    <option value="">Seleccionar paciente...</option>
                                    <?php while ($p = $pacientes->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>"
                                            <?php echo ($cita && $cita['paciente_id'] == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo normalizar_texto($p['nombre']) . ' ' . normalizar_texto($p['apellido']) . ' - ' . $p['cedula']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="medico_id" class="form-label">Médico *</label>
                                <select class="form-select" id="medico_id" name="medico_id" required>
                                    <option value="">Seleccionar médico...</option>
                                    <?php while ($m = $medicos->fetch_assoc()): ?>
                                        <option value="<?php echo $m['id']; ?>"
                                            <?php echo ($cita && $cita['medico_id'] == $m['id']) ? 'selected' : ''; ?>>
                                            Dr. <?php echo normalizar_texto($m['nombre']) . ' ' . normalizar_texto($m['apellido']) . ' - ' . normalizar_texto($m['especialidad']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fecha" class="form-label">Fecha *</label>
                                <input type="date" class="form-control" id="fecha" name="fecha"
                                    value="<?php echo $cita ? $cita['fecha'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="hora" class="form-label">Hora *</label>
                                <input type="time" class="form-control" id="hora" name="hora"
                                    value="<?php echo $cita ? $cita['hora'] : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo de la Consulta</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3"
                                placeholder="Describa brevemente el motivo de la consulta..."><?php echo $cita ? $cita['motivo'] : ''; ?></textarea>
                        </div>

                        <?php if ($cita): ?>
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado *</label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="pendiente" <?php echo $cita['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="completada" <?php echo $cita['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                    <option value="cancelada" <?php echo $cita['estado'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($cita): ?>
                            <div class="mb-3">
                                <label for="observacion" class="form-label">
                                    Observación del Cambio
                                </label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="2" 
                                    placeholder="Motivo del cambio de la cita" required></textarea>
                            </div>
                        <?php endif; ?>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="citas.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> <?php echo $cita ? 'Actualizar' : 'Agendar'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle text-primary"></i> Información</h6>
                    <ul class="mb-0">
                        <li>El sistema validará que no exista otra cita para el mismo médico en la misma fecha y hora</li>
                        <li>Las citas se pueden editar o cancelar posteriormente</li>
                        <li>Asegúrese de verificar la disponibilidad del médico antes de agendar</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="mb-3">
        <a href="?action=new" class="btn btn-primary">
            <i class="bi bi-calendar-plus"></i> Nueva Cita
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-calendar-check"></i> Lista de Citas (<?php echo $citas->num_rows; ?>)
        </div>
        <div class="card-body">
            <?php if ($citas->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $citas->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo date('d/m/Y', strtotime($c['fecha'])); ?></strong></td>
                                    <td><?php echo date('H:i', strtotime($c['hora'])); ?></td>
                                    <td><?php echo normalizar_texto($c['paciente_nombre']); ?></td>
                                    <td>Dr. <?php echo normalizar_texto($c['medico_nombre']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo normalizar_texto($c['especialidad']); ?></span></td>
                                    <td><?php echo substr($c['motivo'], 0, 40); ?><?php echo strlen($c['motivo']) > 40 ? '...' : ''; ?></td>
                                    <td>
                                        <?php if ($c['estado'] == 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php elseif ($c['estado'] == 'completada'): ?>
                                            <span class="badge bg-success">Completada</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cancelada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($c['estado'] == 'pendiente'): ?>
                                                <button type="button" class="btn btn-outline-warning"
                                                    onclick="confirmarCancelacion(<?php echo $c['id']; ?>)" title="Cancelar">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmarEliminacion(<?php echo $c['id']; ?>, 'Cita del <?php echo date('d/m/Y', strtotime($c['fecha'])); ?>', 'la cita')"
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
                    <p class="mt-3">No hay citas registradas</p>
                    <a href="?action=new" class="btn btn-primary">
                        <i class="bi bi-calendar-plus"></i> Agendar Primera Cita
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>