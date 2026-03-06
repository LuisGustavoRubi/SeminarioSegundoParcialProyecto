<?php
require_once '../includes/config.php';
require_once '../controllers/citasHistorialController.php';

$controller = new CitasHistorialController($conn);

$historial = $controller->obtenerHistorial();

$current_page = 'historial';
$page_title = "Historial de Citas";

// Busqueda

$paciente_nombre = $_GET['paciente_nombre'] ?? null;

if ($paciente_nombre) {
    $historial = $controller->buscarPorNombre($paciente_nombre);
} else {
    $historial = $controller->obtenerHistorial();
}

$current_page = 'citasHistorial';

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-body">

         
            <!-- campo de texto de busqueda -->
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Filtrar por paciente</label>
                   <input 
                    type="text" 
                    name="paciente_nombre" 
                    class="form-control" 
                    placeholder="Nombre o apellido del paciente..."
                    value="<?= htmlspecialchars($_GET['paciente_nombre'] ?? '') ?>"
                    id="inputBusqueda"
                >
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="citasHistorial.php" class="btn btn-secondary w-100">
                        Limpiar
                    </a>
                </div>
            </form>


            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead> <!-- <thead class="table-dark"> para hacerlo negro sin la clase del table row -->
                        <tr class="bg-primary text-white">
                            <th>Fecha Cambio</th>
                            <th>Tipo</th>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Fecha Cita</th>
                            <th>Estado</th>
                            <th>Observación</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($historial as $h): ?>
                            <tr>
                                <td>
                                    <?= date("d/m/Y H:i", strtotime($h['fecha_cambio'])) ?>
                                </td>
                                <td>
                                    <?php if ($h['tipo_cambio'] == 'modificacion'): ?>
                                        <span class="badge bg-primary">Modificación</span>
                                    <?php elseif ($h['tipo_cambio'] == 'cancelacion'): ?>
                                        <span class="badge bg-danger">Cancelación</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Reprogramación</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $h['paciente_nuevo_nombre'] . " " . $h['paciente_nuevo_apellido'] ?>
                                </td>
                                <td>
                                    <?= $h['medico_nuevo_nombre'] . " " . $h['medico_nuevo_apellido'] ?>
                                </td>
                                <td>
                                    <?= date("d/m/Y", strtotime($h['nuevo_fecha'])) ?>
                                    <br>
                                    <small><?= $h['nuevo_hora'] ?></small>
                                </td>
                                <td>
                                    <?php if ($h['nuevo_estado'] == 'pendiente'): ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php elseif ($h['nuevo_estado'] == 'completada'): ?>
                                        <span class="badge bg-success">Completada</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cancelada</span>
                                    <?php endif; ?>

                                    <!-- es prueba aun puede no ser necesario -->
                                    <?php if (
                                        $h['anterior_fecha'] != $h['nuevo_fecha'] ||
                                        $h['anterior_hora'] != $h['nuevo_hora']
                                    ): ?>
                                        <br>
                                        <small class="text-muted">
                                            Fecha anterior:
                                            <?= date("d/m/Y", strtotime($h['anterior_fecha'])) ?>
                                            <?= $h['anterior_hora'] ?>

                                        </small>
                                    <?php endif; ?>

                                </td>
                                <td>
                                    <?= $h['observacion'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>