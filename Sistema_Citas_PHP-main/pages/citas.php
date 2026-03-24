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

// Recuperar datos de formulario si hubo error de validación
$form_data = null;
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

$current_page = 'citas';
$page_title = $mostrar_formulario
    ? ($cita ? "Editar Cita" : "Nueva Cita")
    : "Gestión de Citas";

include '../includes/header.php';
?>

<?php if ($mostrar_formulario): ?>
    <?php $citaBloqueada = $cita && in_array($cita['estado'], ['cancelada', 'completada']); ?>
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <?php echo $cita ? 'Editar' : 'Agendar'; ?> Cita
                </div>
                <div class="card-body">
                    <form method="POST" id="formCita" onsubmit="return validarFormularioCita()" novalidate>
                        <input type="hidden" name="action" value="<?php echo $cita ? 'update' : 'create'; ?>">
                        <?php if ($cita): ?>
                            <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="paciente_id" class="form-label">Paciente *</label>
                                <select class="form-select" id="paciente_id" name="paciente_id"
                                    <?php echo $citaBloqueada ? 'disabled' : 'required'; ?>>
                                    <option value="">Seleccionar paciente...</option>
                                    <?php
                                    $selectedPaciente = $form_data['paciente_id'] ?? ($cita['paciente_id'] ?? '');
                                    while ($p = $pacientes->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>"
                                            <?php echo ($selectedPaciente == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo normalizar_texto($p['nombre']) . ' ' . normalizar_texto($p['apellido']) . ' - ' . $p['cedula']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="medico_id" class="form-label">Médico *</label>
                                <select class="form-select" id="medico_id" name="medico_id"
                                    <?php echo $citaBloqueada ? 'disabled' : 'required'; ?>>
                                    <option value="">Seleccionar médico...</option>
                                    <?php
                                    $selectedMedico = $form_data['medico_id'] ?? ($cita['medico_id'] ?? '');
                                    while ($m = $medicos->fetch_assoc()): ?>
                                        <option value="<?php echo $m['id']; ?>"
                                            <?php echo ($selectedMedico == $m['id']) ? 'selected' : ''; ?>>
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
                                    min="<?php echo (new DateTime('now', new DateTimeZone('America/Tegucigalpa')))->format('Y-m-d'); ?>"
                                    value="<?php echo htmlspecialchars($form_data['fecha'] ?? ($cita['fecha'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $citaBloqueada ? 'disabled' : 'required'; ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hora *</label>
                                <?php
                                $selectedHora = substr($form_data['hora'] ?? ($cita['hora'] ?? ''), 0, 5);
                                $selectedH = $selectedHora ? substr($selectedHora, 0, 2) : '07';
                                $selectedM = $selectedHora ? substr($selectedHora, 3, 2) : '00';
                                ?>
                                <div class="input-group">
                                    <select class="form-select" id="hora_h"
                                        <?php echo $citaBloqueada ? 'disabled' : ''; ?>
                                        onchange="actualizarHora()">
                                        <?php for ($h = 0; $h <= 23; $h++): $hStr = sprintf('%02d', $h); ?>
                                            <option value="<?= $hStr ?>" <?= $selectedH === $hStr ? 'selected' : '' ?>><?= $hStr ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="input-group-text fw-bold">:</span>
                                    <select class="form-select" id="hora_m"
                                        <?php echo $citaBloqueada ? 'disabled' : ''; ?>
                                        onchange="actualizarHora()">
                                        <option value="00" <?= $selectedM === '00' ? 'selected' : '' ?>>00</option>
                                        <option value="30" <?= $selectedM === '30' ? 'selected' : '' ?>>30</option>
                                    </select>
                                </div>
                                <input type="hidden" id="hora" name="hora">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo de la Consulta *</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3"
                                placeholder="Describa brevemente el motivo de la consulta..."
                                <?php echo $citaBloqueada ? 'disabled' : 'minlength="10" required'; ?>
                            ><?php echo htmlspecialchars($form_data['motivo'] ?? ($cita['motivo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <?php if (!$citaBloqueada): ?>
                                <div class="form-text">Mínimo 10 caracteres. Campo obligatorio.</div>
                            <?php endif; ?>
                        </div>

                        <?php if ($cita): ?>
                            <?php if ($citaBloqueada): ?>
                                <div class="alert alert-<?php echo $cita['estado'] === 'cancelada' ? 'warning' : 'info'; ?> d-flex align-items-start gap-2 mb-3">
                                    <i class="bi bi-<?php echo $cita['estado'] === 'cancelada' ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> fs-5 mt-1"></i>
                                    <div>
                                        <?php if ($cita['estado'] === 'cancelada'): ?>
                                            <strong>Esta cita fue cancelada.</strong><br>
                                            No se puede reactivar ni modificar. Si necesita continuar, <a href="?action=new">cree una nueva cita</a>.
                                        <?php else: ?>
                                            <strong>Esta cita ya fue completada.</strong><br>
                                            No se puede modificar una cita completada. Si necesita continuar, <a href="?action=new">cree una nueva cita</a>.
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" disabled style="opacity:.6">
                                        <option selected><?php echo $cita['estado'] === 'cancelada' ? 'Cancelada' : 'Completada'; ?></option>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <?php $selectedEstado = $form_data['estado'] ?? $cita['estado']; ?>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="pendiente"  <?php echo $selectedEstado == 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="completada" <?php echo $selectedEstado == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                        <option value="cancelada"  <?php echo $selectedEstado == 'cancelada'  ? 'selected' : ''; ?>>Cancelada</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación del Cambio</label>
                                    <textarea class="form-control" id="observacion" name="observacion" rows="2"
                                        placeholder="Motivo del cambio de la cita" required></textarea>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="citas.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <?php if (!$citaBloqueada): ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> <?php echo $cita ? 'Actualizar' : 'Agendar'; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

    <script>
        function actualizarHora() {
            const h = document.getElementById('hora_h').value;
            const m = document.getElementById('hora_m').value;
            document.getElementById('hora').value = h + ':' + m;
        }
        actualizarHora();

        function validarFormularioCita() {
            const paciente = document.getElementById('paciente_id').value;
            const medico   = document.getElementById('medico_id').value;
            const fecha    = document.getElementById('fecha').value.trim();
            const hora     = document.getElementById('hora').value.trim();
            const motivo   = document.getElementById('motivo').value.trim();

            const faltantes = [];
            if (!paciente) faltantes.push('Paciente');
            if (!medico)   faltantes.push('Médico');
            if (!fecha)    faltantes.push('Fecha');
            if (!hora)     faltantes.push('Hora');
            if (!motivo)   faltantes.push('Motivo de consulta');

            if (faltantes.length > 0) {
                Swal.fire({
                    icon:  'error',
                    title: 'Campos obligatorios incompletos',
                    html:  'Por favor complete los siguientes campos:<br><strong>' + faltantes.join(', ') + '</strong>'
                });
                return false;
            }

            if (motivo.length < 10) {
                Swal.fire({
                    icon:  'error',
                    title: 'Motivo muy corto',
                    html:  'El motivo de consulta debe tener al menos <strong>10 caracteres</strong>.'
                });
                return false;
            }

            const hoy = new Date();
            const hoyStr = hoy.getFullYear() + '-' +
                String(hoy.getMonth() + 1).padStart(2, '0') + '-' +
                String(hoy.getDate()).padStart(2, '0');
            if (fecha < hoyStr) {
                Swal.fire({
                    icon:  'error',
                    title: 'Fecha inválida',
                    html:  'No se pueden agendar citas en fechas pasadas.<br>Seleccione <strong>hoy o una fecha futura</strong>.'
                });
                return false;
            }

            if (fecha === hoyStr) {
                const ahoraStr = String(hoy.getHours()).padStart(2, '0') + ':' +
                    String(hoy.getMinutes()).padStart(2, '0');
                if (hora < ahoraStr) {
                    Swal.fire({
                        icon:  'error',
                        title: 'Hora inválida',
                        html:  'Para citas de hoy, la hora debe ser <strong>posterior a la hora actual</strong>.'
                    });
                    return false;
                }
            }

            return true;
        }
    </script>

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
                                        <?php if (in_array($c['estado'], ['cancelada', 'completada'])): ?>
                                            <?php
                                            $labelFinal = $c['estado'] === 'cancelada' ? 'Cancelada' : 'Completada';
                                            $titleFinal = $c['estado'] === 'cancelada'
                                                ? 'Cita cancelada. Cree una nueva cita para continuar.'
                                                : 'Cita completada. No se puede modificar el estado.';
                                            $colorFinal = $c['estado'] === 'cancelada' ? 'danger' : 'success';
                                            ?>
                                            <select class="form-select form-select-sm border-<?php echo $colorFinal; ?>" disabled
                                                style="width:auto;min-width:120px;opacity:.65;cursor:not-allowed"
                                                title="<?php echo $titleFinal; ?>">
                                                <option selected><?php echo $labelFinal; ?></option>
                                            </select>
                                        <?php else: ?>
                                            <form method="POST" action="citas.php" class="d-flex align-items-center">
                                                <input type="hidden" name="action" value="changeStatus">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                <select name="estado"
                                                    class="form-select form-select-sm border-warning"
                                                    onchange="this.form.submit()"
                                                    style="width:auto;min-width:120px">
                                                    <option value="pendiente"  <?php echo $c['estado'] == 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                                                    <option value="completada" <?php echo $c['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                                    <option value="cancelada">Cancelada</option>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-outline-primary" title="Ver detalle">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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