<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRol(1);
require_once '../controllers/medicamentosController.php';

$esJefe = true;

$controller = new MedicamentosController($conn);
$controller->handleRequest();

$registro = null;
$mostrar_formulario = false;

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $registro = $controller->obtenerPorId($_GET['id']);
    if ($registro) {
        $mostrar_formulario = true;
    } else {
        $_SESSION['error'] = 'Registro no encontrado.';
        header('Location: medicamentos.php');
        exit();
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'new') {
    $mostrar_formulario = true;
}

if ($mostrar_formulario) {
    $medicamentos_existentes = $controller->obtenerMedicamentos();
    $localidades = $controller->obtenerLocalidades();
} else {
    $stock_lista = $controller->obtenerStockCompleto();
}

$form_data = null;
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

$current_page = 'medicamentos';
$page_title = $mostrar_formulario
    ? ($registro ? 'Editar Registro' : 'Registrar Medicamento')
    : 'Gestión de Medicamentos por Localidad';

include '../includes/header.php';
?>

<?php if ($mostrar_formulario): ?>
<div class="row">
    <div class="col-md-7 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-capsule"></i>
                <?= $registro ? 'Editar Registro de Medicamento' : 'Registrar Medicamento en Localidad' ?>
            </div>
            <div class="card-body">
                <form method="POST" id="formMedicamento" onsubmit="return validarFormulario()" novalidate>
                    <input type="hidden" name="action" value="<?= $registro ? 'update' : 'create' ?>">
                    <?php if ($registro): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?>">
                    <?php endif; ?>

                    <?php if (!$registro): ?>
                        <!-- MODO CREACION  -->

                        <!-- Localidad -->
                        <div class="mb-3">
                            <label for="localidad_id" class="form-label">Localidad *</label>
                            <?php $selLoc = $form_data['localidad_id'] ?? ''; ?>
                            <select class="form-select" id="localidad_id" name="localidad_id" required>
                                <option value="">-- Seleccione una localidad --</option>
                                <?php while ($l = $localidades->fetch_assoc()): ?>
                                    <option value="<?= $l['id'] ?>" <?= ($selLoc == $l['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($l['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Checkbox modo -->
                        <?php $modoActual = $form_data['modo'] ?? 'nuevo'; ?>
                        <input type="hidden" name="modo" id="inputModo" value="<?= $modoActual ?>">

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="chkActualizarStock"
                                <?= ($modoActual === 'stock') ? 'checked' : '' ?>
                                onchange="toggleModo()">
                            <label class="form-check-label fw-semibold" for="chkActualizarStock">
                                Actualizar stock de un medicamento ya existente
                            </label>
                        </div>

                        <!-- Nuevo medicamento -->
                        <div id="panelNuevo" <?= ($modoActual === 'stock') ? 'style="display:none"' : '' ?>>
                            <div class="alert alert-light border mb-3 py-2">
                                <i class="bi bi-info-circle text-primary"></i>
                                <small>Complete los datos del nuevo medicamento a registrar en la localidad seleccionada.</small>
                            </div>

                            <div class="mb-3">
                                <label for="nombre_medicamento" class="form-label">Nombre del Medicamento *</label>
                                <input type="text" class="form-control" id="nombre_medicamento" name="nombre_medicamento"
                                    maxlength="150"
                                    placeholder="Ej: Paracetamol 500mg"
                                    value="<?= htmlspecialchars($form_data['nombre_medicamento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="precio" class="form-label">Precio (L.) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input type="number" class="form-control" id="precio" name="precio"
                                            min="0" step="0.01" max="99999"
                                            placeholder="0.00"
                                            value="<?= htmlspecialchars($form_data['precio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="stock_nuevo" class="form-label">Stock inicial *</label>
                                    <input type="number" class="form-control" id="stock_nuevo" name="stock_nuevo"
                                        min="0" max="9999"
                                        placeholder="Ej: 50"
                                        value="<?= htmlspecialchars($form_data['stock_nuevo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Actualizar stiok existente -->
                        <div id="panelStock" <?= ($modoActual !== 'stock') ? 'style="display:none"' : '' ?>>
                            <div class="alert alert-light border mb-3 py-2">
                                <i class="bi bi-info-circle text-primary"></i>
                                <small>Seleccione el medicamento y la cantidad a <strong>sumar</strong> al stock actual en esa localidad.</small>
                            </div>

                            <div class="mb-3">
                                <label for="medicamento_id" class="form-label">Medicamento existente *</label>
                                <?php
                                $selMed = $form_data['medicamento_id'] ?? '';
                                $medicamentos_existentes->data_seek(0);
                                ?>
                                <select class="form-select" id="medicamento_id" name="medicamento_id">
                                    <option value="">-- Seleccione un medicamento --</option>
                                    <?php while ($m = $medicamentos_existentes->fetch_assoc()): ?>
                                        <option value="<?= $m['id'] ?>" <?= ($selMed == $m['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($m['nombre'] . ' — L. ' . number_format($m['precio'], 2), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="stock_sumar" class="form-label">Cantidad a sumar al stock *</label>
                                <input type="number" class="form-control" id="stock_sumar" name="stock_sumar"
                                    min="1" max="9999"
                                    placeholder="Ej: 30"
                                    value="<?= htmlspecialchars($form_data['stock_sumar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-text">Este valor se <strong>sumará</strong> al stock actual del medicamento en esa localidad.</div>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- MODO EDICIÓN -->

                        <div class="mb-3">
                            <label for="nombre_medicamento" class="form-label">Nombre del Medicamento *</label>
                            <input type="text" class="form-control" id="nombre_medicamento" name="nombre_medicamento"
                                maxlength="150"
                                value="<?= htmlspecialchars($form_data['nombre_medicamento'] ?? $registro['medicamento_nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="precio" class="form-label">Precio (L.) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">L.</span>
                                    <input type="number" class="form-control" id="precio" name="precio"
                                        min="0" step="0.01" max="99999"
                                        value="<?= htmlspecialchars($form_data['precio'] ?? $registro['precio'], ENT_QUOTES, 'UTF-8') ?>"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="stock" class="form-label">
                                    Stock *
                                    <span class="text-muted fw-normal small">(actual: <strong><?= $registro['stock'] ?></strong> uds)</span>
                                </label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                    min="0" max="9999"
                                    value="<?= htmlspecialchars($form_data['stock'] ?? $registro['stock'], ENT_QUOTES, 'UTF-8') ?>"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="localidad_id" class="form-label">Localidad *</label>
                            <?php $selLoc = $form_data['localidad_id'] ?? $registro['localidad_id']; ?>
                            <select class="form-select" id="localidad_id" name="localidad_id" required>
                                <option value="">-- Seleccione una localidad --</option>
                                <?php while ($l = $localidades->fetch_assoc()): ?>
                                    <option value="<?= $l['id'] ?>" <?= ($selLoc == $l['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($l['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                    <?php endif; ?>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="medicamentos.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> <?= $registro ? 'Guardar Cambios' : 'Registrar' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleModo() {
        const chk = document.getElementById('chkActualizarStock');
        const panelNuevo = document.getElementById('panelNuevo');
        const panelStock = document.getElementById('panelStock');
        const inputModo = document.getElementById('inputModo');

        if (chk.checked) {
            panelNuevo.style.display = 'none';
            panelStock.style.display = '';
            inputModo.value = 'stock';
        } else {
            panelNuevo.style.display = '';
            panelStock.style.display = 'none';
            inputModo.value = 'nuevo';
        }
    }

    function validarFormulario() {
        const esEdicion = <?= $registro ? 'true' : 'false' ?>;
        const loc = document.getElementById('localidad_id')?.value;

        if (!loc) {
            Swal.fire({ icon: 'error', title: 'Localidad requerida', text: 'Seleccione una localidad.' });
            return false;
        }

        if (esEdicion) {
            const nombre = document.getElementById('nombre_medicamento').value.trim();
            const precio = document.getElementById('precio').value.trim();
            const stock = document.getElementById('stock').value.trim();
            const faltantes = [];
            if (!nombre) faltantes.push('Nombre del medicamento');
            if (!precio) faltantes.push('Precio');
            if (stock === '') faltantes.push('Stock');
            if (faltantes.length > 0) {
                Swal.fire({ icon: 'error', title: 'Campos incompletos', html: 'Complete: <strong>' + faltantes.join(', ') + '</strong>' });
                return false;
            }
            if (parseFloat(precio) < 0) {
                Swal.fire({ icon: 'error', title: 'Precio inválido', text: 'El precio no puede ser negativo.' });
                return false;
            }
            if (parseInt(stock) < 0) {
                Swal.fire({ icon: 'error', title: 'Stock inválido', text: 'El stock no puede ser negativo.' });
                return false;
            }
            return true;
        }

        // Creación
        const modo = document.getElementById('inputModo').value;

        if (modo === 'nuevo') {
            const nombre = document.getElementById('nombre_medicamento').value.trim();
            const precio = document.getElementById('precio').value.trim();
            const stockNuevo = document.getElementById('stock_nuevo').value.trim();
            const faltantes = [];
            if (!nombre) faltantes.push('Nombre del medicamento');
            if (!precio) faltantes.push('Precio');
            if (stockNuevo === '') faltantes.push('Stock inicial');
            if (faltantes.length > 0) {
                Swal.fire({ icon: 'error', title: 'Campos incompletos', html: 'Complete: <strong>' + faltantes.join(', ') + '</strong>' });
                return false;
            }
            if (parseFloat(precio) < 0) {
                Swal.fire({ icon: 'error', title: 'Precio inválido', text: 'El precio no puede ser negativo.' });
                return false;
            }
            if (parseInt(stockNuevo) < 0) {
                Swal.fire({ icon: 'error', title: 'Stock inválido', text: 'El stock inicial no puede ser negativo.' });
                return false;
            }
        } else {
            const medId = document.getElementById('medicamento_id').value;
            const stockSumar = document.getElementById('stock_sumar').value.trim();
            const faltantes = [];
            if (!medId) faltantes.push('Medicamento');
            if (stockSumar === '') faltantes.push('Cantidad a sumar');
            if (faltantes.length > 0) {
                Swal.fire({ icon: 'error', title: 'Campos incompletos', html: 'Complete: <strong>' + faltantes.join(', ') + '</strong>' });
                return false;
            }
            if (parseInt(stockSumar) <= 0) {
                Swal.fire({ icon: 'error', title: 'Cantidad inválida', text: 'La cantidad a sumar debe ser mayor a 0.' });
                return false;
            }
        }

        return true;
    }
</script>

<?php else: ?>

    <div class="mb-3">
        <a href="?action=new" class="btn btn-primary">
            <i class="bi bi-capsule-pill"></i> Registrar Medicamento
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-capsule"></i> Stock de Medicamentos por Localidad
            (<?= $stock_lista->num_rows ?>)
        </div>
        <div class="card-body">
            <?php if ($stock_lista->num_rows > 0): ?>
                <div class="d-flex gap-3 mb-3 flex-wrap align-items-center">
                    <small class="text-muted fw-semibold">Niveles:</small>
                    <span class="badge bg-success">Alto (&gt; 20)</span>
                    <span class="badge bg-warning text-dark">Medio (11 – 20)</span>
                    <span class="badge bg-danger">Bajo (≤ 10)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Precio</th>
                                <th>Localidad</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $stock_lista->fetch_assoc()):
                                if ($s['stock'] <= 10) {
                                    $stockColor = 'danger';
                                    $stockLabel = 'Bajo';
                                } elseif ($s['stock'] <= 20) {
                                    $stockColor = 'warning';
                                    $stockLabel = 'Medio';
                                } else {
                                    $stockColor = 'success';
                                    $stockLabel = 'Alto';
                                }
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($s['medicamento'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td>L. <?= number_format($s['precio'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?= htmlspecialchars($s['localidad'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $stockColor ?>" style="font-size:.85rem; padding:.4em .75em;">
                                            <?= $s['stock'] ?> uds — <?= $stockLabel ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmarEliminacion(<?= $s['id'] ?>, '<?= htmlspecialchars($s['medicamento'] . ' en ' . $s['localidad'], ENT_QUOTES, 'UTF-8') ?>', 'el registro')"
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
                    <p class="mt-3">No hay medicamentos registrados en ninguna localidad</p>
                    <a href="?action=new" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Registrar Primero
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>