<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../controllers/PacientesController.php';

$controller = new PacienteController($conn);
$controller->handleRequest();

$paciente = null;
$mostrar_formulario = false;

// Verificar acciones
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $paciente = $controller->obtenerPorId($_GET['id']);
    $mostrar_formulario = true;
}

if (isset($_GET['action']) && $_GET['action'] == 'new') {
    $mostrar_formulario = true;
}

if (!$mostrar_formulario) {
    $pacientes = $controller->obtenerTodos();
}

// Recuperar datos de formulario si hubo error de validación
$form_data = null;
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

$current_page = 'pacientes';
$page_title = $mostrar_formulario
    ? ($paciente ? "Editar Paciente" : "Nuevo Paciente")
    : "Gestión de Pacientes";

include '../includes/header.php';
?>

<?php if ($mostrar_formulario): ?>
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person-plus"></i>
                    <?php echo $paciente ? 'Editar' : 'Registrar'; ?> Paciente
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validarFormulario()" novalidate>
                        <input type="hidden" name="action" value="<?php echo $paciente ? 'update' : 'create'; ?>">
                        <?php if ($paciente): ?>
                            <input type="hidden" name="id" value="<?php echo $paciente['id']; ?>">
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                       value="<?php echo htmlspecialchars($form_data['nombre'] ?? ($paciente['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="12" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido *</label>
                                <input type="text" class="form-control" id="apellido" name="apellido"
                                       value="<?php echo htmlspecialchars($form_data['apellido'] ?? ($paciente['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="12" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cedula" class="form-label">Cédula *</label>
                                <input type="text" class="form-control" id="cedula" name="cedula"
                                       value="<?php echo htmlspecialchars($form_data['cedula'] ?? ($paciente['cedula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="0000-0000-00000"
                                       pattern="\d{4}-\d{4}-\d{5}"
                                       maxlength="15"
                                       required>
                                <div class="form-text">Formato: 0000-0000-00000 · Debe ser única</div>
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                       value="<?php echo htmlspecialchars($form_data['fecha_nacimiento'] ?? ($paciente['fecha_nacimiento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       min="1900-01-01"
                                       max="<?php echo (new DateTime('now', new DateTimeZone('America/Tegucigalpa')))->format('Y-m-d'); ?>"
                                       required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                       value="<?php echo htmlspecialchars($form_data['telefono'] ?? ($paciente['telefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="+504 9999-9999"
                                       pattern="\+504 \d{4}-\d{4}"
                                       maxlength="14"
                                       required>
                                <div class="form-text">Formato: +504 9999-9999</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ($paciente['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="ejemplo@correo.com" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="pacientes.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i>
                                <?php echo $paciente ? 'Actualizar' : 'Registrar'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CEDULA_REGEX   = /^\d{4}-\d{4}-\d{5}$/;
        const TELEFONO_REGEX = /^\+504 \d{4}-\d{4}$/;

        function validarFormulario() {
            const campos = [
                { id: 'nombre',           label: 'Nombre' },
                { id: 'apellido',         label: 'Apellido' },
                { id: 'cedula',           label: 'Cédula' },
                { id: 'telefono',         label: 'Teléfono' },
                { id: 'email',            label: 'Email' },
                { id: 'fecha_nacimiento', label: 'Fecha de Nacimiento' }
            ];

            const faltantes = campos
                .filter(c => !document.getElementById(c.id).value.trim())
                .map(c => c.label);

            if (faltantes.length > 0) {
                Swal.fire({
                    icon:  'error',
                    title: 'Campos obligatorios incompletos',
                    html:  'Por favor complete los siguientes campos:<br><strong>' + faltantes.join(', ') + '</strong>'
                });
                return false;
            }

            const fechaNacStr = document.getElementById('fecha_nacimiento').value;
            if (fechaNacStr) {
                const nacimiento = new Date(fechaNacStr + 'T00:00:00');
                const hoy        = new Date();
                hoy.setHours(0, 0, 0, 0);
                if (nacimiento > hoy) {
                    Swal.fire({
                        icon:  'error',
                        title: 'Fecha inválida',
                        html:  'La fecha de nacimiento no puede ser una <strong>fecha futura</strong>.'
                    });
                    return false;
                }
                if (nacimiento < new Date('1900-01-01T00:00:00')) {
                    Swal.fire({
                        icon:  'error',
                        title: 'Fecha inválida',
                        html:  'La fecha de nacimiento no puede ser anterior al <strong>año 1900</strong>.'
                    });
                    return false;
                }
            }

            const cedula = document.getElementById('cedula').value.trim();
            if (!CEDULA_REGEX.test(cedula)) {
                Swal.fire({
                    icon:  'error',
                    title: 'Cédula inválida',
                    html:  'El formato de cédula no es válido.<br>Use el formato: <strong>0000-0000-00000</strong><br><small class="text-muted">Solo dígitos separados por guiones</small>'
                });
                return false;
            }

            const telefono = document.getElementById('telefono').value.trim();
            if (!TELEFONO_REGEX.test(telefono)) {
                Swal.fire({
                    icon:  'error',
                    title: 'Teléfono inválido',
                    html:  'El formato de teléfono no es válido.<br>Use el formato: <strong>+504 9999-9999</strong>'
                });
                return false;
            }

            return true;
        }

        // Auto-formato de cédula al escribir
        document.getElementById('cedula').addEventListener('input', function () {
            let val = this.value.replace(/\D/g, ''); // solo dígitos
            if (val.length > 13) val = val.slice(0, 13); // máximo 13 dígitos
            if (val.length > 8)
                val = val.slice(0, 4) + '-' + val.slice(4, 8) + '-' + val.slice(8);
            else if (val.length > 4)
                val = val.slice(0, 4) + '-' + val.slice(4);
            this.value = val;
        });

        // Auto-formato de teléfono al escribir: +504 9999-9999
        document.getElementById('telefono').addEventListener('input', function () {
            // Quitar el prefijo +504 primero para no incluir sus dígitos en el número local
            let local = this.value.replace(/^\+504\s*/, '');
            let val   = local.replace(/\D/g, ''); // solo dígitos locales
            if (val.length > 8) val = val.slice(0, 8); // máximo 8 dígitos
            if (val.length > 4)
                this.value = '+504 ' + val.slice(0, 4) + '-' + val.slice(4);
            else if (val.length > 0)
                this.value = '+504 ' + val;
            else
                this.value = '';
        });
    </script>

<?php else: ?>
    <div class="mb-3">
        <a href="?action=new" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo Paciente
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-people"></i> Lista de Pacientes (<?php echo $pacientes->num_rows; ?>)
        </div>
        <div class="card-body">
            <?php if ($pacientes->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Fecha Nacimiento</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $pacientes->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($p['cedula'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($p['telefono'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($p['email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $p['fecha_nacimiento'] ? date('d/m/Y', strtotime($p['fecha_nacimiento'])) : '-'; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmarEliminacion(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido'], ENT_QUOTES, 'UTF-8'); ?>', 'al paciente')"
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
                    <p class="mt-3">No hay pacientes registrados</p>
                    <a href="?action=new" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Registrar Primer Paciente
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>