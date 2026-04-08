<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../controllers/citasHistorialController.php';

$controller = new CitasHistorialController($conn);

$page_title = "Historial de Citas";
$paciente_nombre = $_GET['paciente_nombre'] ?? null;
$historial = $paciente_nombre ? $controller->buscarPorNombre($paciente_nombre) : $controller->obtenerHistorial();

$current_page = 'citasHistorial';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-body">

            <!-- Búsqueda -->
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Filtrar por paciente</label>
                    <input type="text" name="paciente_nombre" class="form-control"
                        placeholder="Nombre o apellido del paciente..."
                        value="<?= htmlspecialchars($_GET['paciente_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="citasHistorial.php" class="btn btn-secondary w-100">Limpiar</a>
                </div>
            </form>

            <?php if (empty($historial)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clock-history" style="font-size:4rem"></i>
                    <p class="mt-3">No hay registros en el historial<?= $paciente_nombre ? ' para ese paciente' : '' ?>.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th>Fecha Cambio</th>
                                <th>Tipo</th>
                                <th>Responsable</th>
                                <th>Centro Médico</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Fecha Cita</th>
                                <th>Estado</th>
                                <th>Enfermedad</th>
                                <th>Medicamentos</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $h): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $dt = new DateTime($h['fecha_cambio'], new DateTimeZone('UTC'));
                                        $dt->setTimezone(new DateTimeZone('America/Tegucigalpa'));
                                        echo $dt->format('d/m/Y H:i');
                                        ?>
                                    </td>

                                    <td>
                                        <?php if ($h['tipo_cambio'] === 'modificacion'): ?>
                                            <span class="badge bg-primary">Modificación</span>
                                        <?php elseif ($h['tipo_cambio'] === 'cancelacion'): ?>
                                            <span class="badge bg-danger">Cancelación</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Reprogramación</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Usuario -->
                                    <td>
                                        <?php if (!empty($h['usuario_responsable'])): ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-person-fill"></i>
                                                <?= htmlspecialchars($h['usuario_responsable'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Localidad -->
                                    <td>
                                        <?php if (!empty($h['localidad'])): ?>
                                            <span class="badge bg-info text-dark">
                                                <i class="bi bi-hospital"></i>
                                                <?= htmlspecialchars($h['localidad'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            trim(($h['paciente_nuevo_nombre'] ?? '') . ' ' . ($h['paciente_nuevo_apellido'] ?? '')),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            trim(($h['medico_nuevo_nombre'] ?? '') . ' ' . ($h['medico_nuevo_apellido'] ?? '')),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= $h['nuevo_fecha'] ? date('d/m/Y', strtotime($h['nuevo_fecha'])) : '—' ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= $h['nuevo_hora'] ? substr($h['nuevo_hora'], 0, 5) : '' ?>
                                        </small>
                                        <?php if (
                                            $h['anterior_fecha'] && $h['nuevo_fecha'] &&
                                            ($h['anterior_fecha'] !== $h['nuevo_fecha'] || $h['anterior_hora'] !== $h['nuevo_hora'])
                                        ): ?>
                                            <br>
                                            <small class="text-muted">
                                                Antes: <?= date('d/m/Y', strtotime($h['anterior_fecha'])) ?>
                                                <?= $h['anterior_hora'] ? substr($h['anterior_hora'], 0, 5) : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($h['nuevo_estado'] === 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php elseif ($h['nuevo_estado'] === 'completada'): ?>
                                            <span class="badge bg-success">Completada</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Cancelada</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Enfermedad -->
                                    <td>
                                        <?php if (!empty($h['enfermedad'])): ?>
                                            <span class="badge bg-info text-dark">
                                                <?= htmlspecialchars($h['enfermedad'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Medicamentos -->
                                    <td>
                                        <?php if (!empty($h['medicamentos'])): ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($h['medicamentos'] as $med): ?>
                                                    <span class="badge bg-light text-dark border" style="font-size:.75rem">
                                                        <i class="bi bi-capsule"></i>
                                                        <?= htmlspecialchars($med, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="text-muted small">
                                            <?= htmlspecialchars($h['observacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>