<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../controllers/citasController.php';

$controller = new CitasController($conn);
$controller->handleRequest();

$cita              = null;
$mostrar_formulario = false;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $cita = $controller->obtenerPorId($_GET['id']);
    if ($cita) {
        $mostrar_formulario = true;
    } else {
        $_SESSION['error'] = 'Cita no encontrada o no tiene permiso para acceder a ella.';
        header('Location: citas.php');
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'new') {
    $mostrar_formulario = true;
}

if ($mostrar_formulario) {
    $pacientes   = $controller->obtenerPacientes();
    $medicos     = $controller->obtenerMedicos();
    $localidades = $controller->obtenerLocalidades();
    $enfermedades = $controller->obtenerEnfermedades(); // array PHP — no necesita data_seek
} else {
    $citas        = $controller->obtenerTodas();
    $enfermedades = $controller->obtenerEnfermedades(); // array PHP — reutilizable sin workaround
}

$form_data = null;
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

$current_page = 'citas';
$page_title   = $mostrar_formulario
    ? ($cita ? 'Editar Cita' : 'Nueva Cita')
    : 'Gestión de Citas';

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
                    <form method="POST" id="formCita" onsubmit="return CitasUI.validarFormulario()" novalidate>
                        <input type="hidden" name="action" value="<?php echo $cita ? 'update' : 'create'; ?>">
                        <?php if ($cita): ?>
                            <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="paciente_id" class="form-label">Paciente *</label>
                                <select class="form-select" id="paciente_id" name="paciente_id"
                                    <?php echo $citaBloqueada ? 'disabled' : 'required'; ?>>
                                    <option value="">Seleccionar paciente...</option>
                                    <?php
                                    $selectedPaciente = $form_data['paciente_id'] ?? ($cita['paciente_id'] ?? '');
                                    // $paciente — nombre descriptivo en lugar de $p
                                    while ($paciente = $pacientes->fetch_assoc()): ?>
                                        <option value="<?php echo $paciente['id']; ?>"
                                            <?php echo ($selectedPaciente == $paciente['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido'] . ' - ' . $paciente['cedula'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="medico_id" class="form-label">Médico *</label>
                                <?php
                                $esEmpleado    = ($_SESSION['rol'] ?? '') === 'empleado';
                                $selectedMedico = $form_data['medico_id'] ?? ($cita['medico_id'] ?? ($esEmpleado ? intval($_SESSION['medico_id'] ?? 0) : ''));
                                ?>
                                <?php if ($esEmpleado): ?>
                                    <input type="hidden" name="medico_id" value="<?php echo intval($_SESSION['medico_id'] ?? 0); ?>">
                                <?php endif; ?>
                                <select class="form-select" id="medico_id"
                                    <?php if (!$esEmpleado && !$citaBloqueada): ?>name="medico_id" required<?php endif; ?>
                                    <?php echo ($esEmpleado || $citaBloqueada) ? 'disabled' : ''; ?>>
                                    <option value="">Seleccionar médico...</option>
                                    <?php
                                    // $medico — nombre descriptivo en lugar de $m
                                    while ($medico = $medicos->fetch_assoc()): ?>
                                        <option value="<?php echo $medico['id']; ?>"
                                            <?php echo ($selectedMedico == $medico['id']) ? 'selected' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($medico['nombre'] . ' ' . $medico['apellido'] . ' - ' . $medico['especialidad'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="localidad_id" class="form-label">Localidad *</label>
                                <select class="form-select" id="localidad_id" name="localidad_id"
                                    <?php echo $citaBloqueada ? 'disabled' : 'required'; ?>>
                                    <option value="">Seleccionar localidad...</option>
                                    <?php
                                    $selectedLocalidad = $form_data['localidad_id'] ?? ($cita['localidad_id'] ?? '');
                                    // $localidad — nombre descriptivo en lugar de $loc
                                    while ($localidad = $localidades->fetch_assoc()): ?>
                                        <option value="<?php echo $localidad['id']; ?>"
                                            <?php echo ($selectedLocalidad == $localidad['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($localidad['nombre'], ENT_QUOTES, 'UTF-8'); ?>
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
                                $selectedH    = $selectedHora ? substr($selectedHora, 0, 2) : '07';
                                $selectedM    = $selectedHora ? substr($selectedHora, 3, 2) : '00';
                                ?>
                                <div class="input-group">
                                    <select class="form-select" id="hora_h"
                                        <?php echo $citaBloqueada ? 'disabled' : ''; ?>
                                        onchange="CitasUI.actualizarHora()">
                                        <?php for ($h = 0; $h <= 23; $h++): $hStr = sprintf('%02d', $h); ?>
                                            <option value="<?= $hStr ?>" <?= $selectedH === $hStr ? 'selected' : '' ?>><?= $hStr ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="input-group-text fw-bold">:</span>
                                    <select class="form-select" id="hora_m"
                                        <?php echo $citaBloqueada ? 'disabled' : ''; ?>
                                        onchange="CitasUI.actualizarHora()">
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
                                    <select class="form-select" id="estado" name="estado" required
                                        onchange="CitasUI.onCambioEstadoFormulario(this.value, <?php echo $cita['id']; ?>)">
                                        <option value="pendiente"  <?php echo $selectedEstado === 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="completada" <?php echo $selectedEstado === 'completada' ? 'selected' : ''; ?>>Completada</option>
                                        <option value="cancelada"  <?php echo $selectedEstado === 'cancelada'  ? 'selected' : ''; ?>>Cancelada</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación del Cambio</label>
                                    <textarea class="form-control" id="observacion" name="observacion" rows="2"
                                        placeholder="Motivo del cambio de la cita" required></textarea>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <input type="hidden" name="enfermedad_id" id="enfermedad_id_form" value="0">

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
                                <th>Localidad</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // $cita — nombre descriptivo en lugar de $c
                            while ($cita = $citas->fetch_assoc()): ?>
                                <tr data-localidad-id="<?php echo $cita['localidad_id']; ?>">
                                    <td><strong><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></strong></td>
                                    <td><?php echo date('H:i', strtotime($cita['hora'])); ?></td>
                                    <td><?php echo htmlspecialchars($cita['paciente_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($cita['medico_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($cita['especialidad'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars($cita['localidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($cita['motivo'], 0, 40), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($cita['motivo']) > 40 ? '...' : ''; ?></td>
                                    <td>
                                        <?php if (in_array($cita['estado'], ['cancelada', 'completada'])): ?>
                                            <?php
                                            $labelFinal = $cita['estado'] === 'cancelada' ? 'Cancelada' : 'Completada';
                                            $colorFinal = $cita['estado'] === 'cancelada' ? 'danger'    : 'success';
                                            ?>
                                            <select class="form-select form-select-sm border-<?php echo $colorFinal; ?>" disabled
                                                style="width:auto;min-width:120px;opacity:.65;cursor:not-allowed">
                                                <option selected><?php echo $labelFinal; ?></option>
                                            </select>
                                        <?php else: ?>
                                            <select class="form-select form-select-sm border-warning"
                                                onchange="CitasUI.onCambioEstadoListado(this, <?php echo $cita['id']; ?>)"
                                                style="width:auto;min-width:120px"
                                                data-estado-actual="<?php echo $cita['estado']; ?>">
                                                <option value="pendiente"  <?php echo $cita['estado'] === 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="completada" <?php echo $cita['estado'] === 'completada' ? 'selected' : ''; ?>>Completada</option>
                                                <option value="cancelada">Cancelada</option>
                                            </select>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="?action=edit&id=<?php echo $cita['id']; ?>" class="btn btn-outline-primary" title="Ver detalle">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmarEliminacion(<?php echo $cita['id']; ?>, 'Cita del <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?>', 'la cita')"
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

<!-- ====== MODAL ENFERMEDAD ====== -->
<div id="modalEnfermedad" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:28px; min-width:360px; max-width:95%; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <h5 class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Completar cita</h5>
        <p class="text-muted mb-3" style="font-size:.9rem">Seleccione la enfermedad diagnosticada y medicamento para marcar la cita como completada.</p>

        <label for="modal_enfermedad_id" class="form-label fw-semibold">Enfermedad *</label>
        <select id="modal_enfermedad_id" class="form-select mb-3">
            <option value="">-- Seleccione una enfermedad --</option>
            <?php
            // foreach sobre array PHP — no necesita data_seek(0)
            // $enfermedad — nombre descriptivo en lugar de $e
            foreach ($enfermedades as $enfermedad): ?>
                <option value="<?= $enfermedad['id'] ?>"><?= htmlspecialchars($enfermedad['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="modal_medicamento_id" class="form-label fw-semibold">Medicamentos *</label>
        <select id="modal_medicamento_id" class="form-select mb-1" multiple style="min-height:120px">
            <option disabled>Seleccione primero una localidad</option>
        </select>
        <small class="text-muted d-block mb-4">Se muestran medicamentos de la localidad de la cita. Ctrl + clic para seleccionar varios.</small>

        <form id="formChangeStatus" method="POST" action="citas.php" style="display:none">
            <input type="hidden" name="action"       value="changeStatus">
            <input type="hidden" name="estado"       value="completada">
            <input type="hidden" name="id"           id="modal_cita_id">
            <input type="hidden" name="enfermedad_id"id="modal_enfermedad_hidden">
            <input type="hidden" name="medicamentos" id="modal_medicamentos_hidden">
        </form>

        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-secondary" onclick="CitasUI.cerrarModal()">Cancelar</button>
            <button type="button" class="btn btn-success"   onclick="CitasUI.submitCompletarCita()">
                <i class="bi bi-check-lg"></i> Completar cita
            </button>
        </div>
    </div>
</div>

<script>
// ── CitasUI — IIFE: todo el estado del modal queda privado, fuera del scope global ──
const CitasUI = (() => {

    // Estado privado — reemplaza las variables globales _modalOrigen y _selectOriginal
    let modalCtx = {
        citaId   : null,
        origen   : null,   // 'listado' | 'formulario'
        selectRef: null,
    };

    // abrirModalCompletar — antes: abrirModal(citaId, origen, s)
    function abrirModalCompletar(citaId, origen, selectRef = null) {
        document.getElementById('modal_cita_id').value       = citaId;
        document.getElementById('modal_enfermedad_id').value = '';
        document.getElementById('modal_medicamento_id').innerHTML = '<option disabled>Cargando...</option>';

        modalCtx = { citaId, origen, selectRef };

        let localidadId = 0;
        if (origen === 'formulario') {
            localidadId = document.getElementById('localidad_id')?.value ?? 0;
        } else if (origen === 'listado') {
            const fila  = selectRef?.closest('tr');
            localidadId = fila?.dataset.localidadId ?? 0;
        }

        if (parseInt(localidadId) > 0) {
            fetchMedicamentosPorLocalidad(localidadId);
        } else {
            document.getElementById('modal_medicamento_id').innerHTML =
                '<option disabled>No se pudo determinar la localidad</option>';
        }

        document.getElementById('modalEnfermedad').style.display = 'flex';
    }

    // fetchMedicamentosPorLocalidad — antes: cargarMedicamentosPorLocalidad(id)
    async function fetchMedicamentosPorLocalidad(localidadId) {
        try {
            const respuesta   = await fetch(`citas.php?action=getMedicamentos&localidad_id=${localidadId}`);
            const medicamentos = await respuesta.json();
            const select      = document.getElementById('modal_medicamento_id');

            if (!medicamentos.length) {
                select.innerHTML = '<option disabled>Sin medicamentos disponibles en esta localidad</option>';
            } else {
                select.innerHTML = medicamentos.map(med =>
                    `<option value="${med.id}">${med.nombre} (Stock: ${med.stock})</option>`
                ).join('');
            }
        } catch {
            document.getElementById('modal_medicamento_id').innerHTML =
                '<option disabled>Error al cargar medicamentos</option>';
        }
    }

    function cerrarModal() {
        // Restaura el select del listado a su estado anterior si el usuario cancela
        if (modalCtx.selectRef) {
            modalCtx.selectRef.value = modalCtx.selectRef.dataset.estadoActual;
        }
        if (modalCtx.origen === 'formulario') {
            const selEstado = document.getElementById('estado');
            if (selEstado) selEstado.value = 'pendiente';
        }
        document.getElementById('modalEnfermedad').style.display = 'none';
    }

    // submitCompletarCita — antes: confirmarEnfermedad()
    function submitCompletarCita() {
        const enfermedadId    = document.getElementById('modal_enfermedad_id').value;
        const selectMeds      = document.getElementById('modal_medicamento_id');
        const medicamentosIds = Array.from(selectMeds.selectedOptions).map(opt => opt.value);

        if (!enfermedadId) {
            alert('Por favor seleccione una enfermedad.');
            return;
        }
        if (medicamentosIds.length === 0) {
            alert('Por favor seleccione al menos un medicamento.');
            return;
        }

        if (modalCtx.origen === 'listado') {
            document.getElementById('modal_enfermedad_hidden').value  = enfermedadId;
            document.getElementById('modal_medicamentos_hidden').value = medicamentosIds.join(',');
            document.getElementById('formChangeStatus').submit();

        } else if (modalCtx.origen === 'formulario') {
            document.getElementById('enfermedad_id_form').value = enfermedadId;

            // Evitar duplicar el campo si el modal se abrió más de una vez
            const campoExistente = document.querySelector('#formCita input[name="medicamentos"]');
            if (campoExistente) campoExistente.remove();

            document.getElementById('formCita').insertAdjacentHTML(
                'beforeend',
                `<input type="hidden" name="medicamentos" value="${medicamentosIds.join(',')}">`
            );

            document.getElementById('modalEnfermedad').style.display = 'none';
            document.getElementById('formCita').submit();
        }
    }

    // onCambioEstadoListado — antes: manejarCambioEstado(sel, id)
    function onCambioEstadoListado(selectEl, citaId) {
        const nuevoEstado = selectEl.value;
        if (nuevoEstado === 'completada') {
            abrirModalCompletar(citaId, 'listado', selectEl);
        } else {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'citas.php';
            form.innerHTML = `
                <input type="hidden" name="action" value="changeStatus">
                <input type="hidden" name="id"     value="${citaId}">
                <input type="hidden" name="estado" value="${nuevoEstado}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // onCambioEstadoFormulario — antes: manejarCambioEstadoFormulario(e, id)
    function onCambioEstadoFormulario(nuevoEstado, citaId) {
        if (nuevoEstado === 'completada') {
            abrirModalCompletar(citaId, 'formulario');
        }
    }

    function actualizarHora() {
        const h = document.getElementById('hora_h').value;
        const m = document.getElementById('hora_m').value;
        document.getElementById('hora').value = h + ':' + m;
    }

    function validarFormulario() {
        const paciente = document.getElementById('paciente_id')?.value;
        const medico   = document.getElementById('medico_id')?.value;
        const fecha    = document.getElementById('fecha')?.value.trim();
        const hora     = document.getElementById('hora')?.value.trim();
        const motivo   = document.getElementById('motivo')?.value.trim();
        const estado   = document.getElementById('estado')?.value;

        if (estado === 'completada' &&
            (!document.getElementById('enfermedad_id_form').value ||
              document.getElementById('enfermedad_id_form').value == '0')) {
            const citaId = document.querySelector('input[name="id"]')?.value;
            abrirModalCompletar(citaId, 'formulario');
            return false;
        }

        const faltantes = [];
        if (!paciente) faltantes.push('Paciente');
        if (!medico)   faltantes.push('Médico');
        if (!fecha)    faltantes.push('Fecha');
        if (!hora)     faltantes.push('Hora');
        if (!motivo)   faltantes.push('Motivo de consulta');

        if (faltantes.length > 0) {
            Swal.fire({
                icon : 'error',
                title: 'Campos obligatorios incompletos',
                html : 'Por favor complete los siguientes campos:<br><strong>' + faltantes.join(', ') + '</strong>'
            });
            return false;
        }

        if (motivo.length < 10) {
            Swal.fire({ icon: 'error', title: 'Motivo muy corto',
                html: 'El motivo debe tener al menos <strong>10 caracteres</strong>.' });
            return false;
        }

        const hoy    = new Date();
        const hoyStr = hoy.getFullYear() + '-' +
            String(hoy.getMonth() + 1).padStart(2, '0') + '-' +
            String(hoy.getDate()).padStart(2, '0');

        if (fecha < hoyStr) {
            Swal.fire({ icon: 'error', title: 'Fecha inválida',
                html: 'No se pueden agendar citas en fechas pasadas.' });
            return false;
        }

        if (fecha === hoyStr) {
            const ahoraStr = String(hoy.getHours()).padStart(2, '0') + ':' +
                             String(hoy.getMinutes()).padStart(2, '0');
            if (hora < ahoraStr) {
                Swal.fire({ icon: 'error', title: 'Hora inválida',
                    html: 'Para citas de hoy, la hora debe ser posterior a la hora actual.' });
                return false;
            }
        }

        return true;
    }

    // Inicialización
    actualizarHora();

    document.getElementById('modalEnfermedad').addEventListener('click', function (e) {
        if (e.target === this) cerrarModal();
    });

    // API pública del módulo — solo expone lo que el HTML necesita invocar
    return {
        abrirModalCompletar,
        cerrarModal,
        submitCompletarCita,
        onCambioEstadoListado,
        onCambioEstadoFormulario,
        actualizarHora,
        validarFormulario,
    };
})();
</script>

<?php include '../includes/footer.php'; ?>