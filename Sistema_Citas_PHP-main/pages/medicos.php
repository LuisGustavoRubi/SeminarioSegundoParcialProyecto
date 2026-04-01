<?php
require_once '../includes/config.php';
require_once '../controllers/MedicosController.php';

$esJefe = ($_SESSION['rol'] ?? '') === 'jefe';

// Empleados no pueden crear, editar ni eliminar médicos
if (!$esJefe && (
    $_SERVER['REQUEST_METHOD'] === 'POST' ||
    (isset($_GET['action']) && in_array($_GET['action'], ['new', 'edit', 'delete']))
)) {
    $_SESSION['error'] = 'No tiene permiso para realizar esta acción.';
    header('Location: medicos.php');
    exit();
}

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

// Recuperar datos de formulario si hubo error de validación
$form_data = null;
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

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
                    <form method="POST" onsubmit="return validarFormularioMedico()" novalidate>
                        <input type="hidden" name="action" value="<?php echo $medico ? 'update' : 'create'; ?>">
                        <?php if ($medico): ?>
                            <input type="hidden" name="id" value="<?php echo $medico['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                       value="<?php echo htmlspecialchars($form_data['nombre'] ?? ($medico['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="12" required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido *</label>
                                <input type="text" class="form-control" id="apellido" name="apellido"
                                       value="<?php echo htmlspecialchars($form_data['apellido'] ?? ($medico['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       maxlength="12" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Especialidad *</label>
                            <?php $espActual = $form_data['especialidad'] ?? ($medico['especialidad'] ?? ''); ?>
                            <input type="hidden" id="especialidad" name="especialidad"
                                   value="<?php echo htmlspecialchars($espActual, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                        id="dropEspecialidad"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="true"
                                        aria-expanded="false"
                                        style="background:#fff;">
                                    <?php echo $espActual ? htmlspecialchars($espActual, ENT_QUOTES, 'UTF-8') : '-- Seleccione una especialidad --'; ?>
                                </button>
                                <ul class="dropdown-menu w-100"
                                    aria-labelledby="dropEspecialidad"
                                    style="max-height:220px; overflow-y:auto;">
                                    <?php foreach (MedicoController::getEspecialidades() as $esp): ?>
                                        <li>
                                            <a class="dropdown-item<?php echo ($espActual === $esp) ? ' active' : ''; ?>"
                                               href="#"
                                               data-value="<?php echo htmlspecialchars($esp, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($esp, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                       value="<?php echo htmlspecialchars($form_data['telefono'] ?? ($medico['telefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="+504 9999-9999"
                                       pattern="\+504 \d{4}-\d{4}"
                                       maxlength="14"
                                       required>
                                <div class="form-text">Formato: +504 9999-9999</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ($medico['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="doctor@hospital.com"
                                       required>
                                <div class="form-text">Dominio requerido: @hospital.com</div>
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

    <script>
        const TELEFONO_MEDICO_REGEX = /^\+504 \d{4}-\d{4}$/;

        function validarFormularioMedico() {
            const campos = [
                { id: 'nombre',       label: 'Nombre' },
                { id: 'apellido',     label: 'Apellido' },
                { id: 'telefono',     label: 'Teléfono' },
                { id: 'email',        label: 'Email' }
            ];

            // Verificar el select de especialidad por separado
            if (!document.getElementById('especialidad').value) {
                Swal.fire({
                    icon:  'error',
                    title: 'Especialidad requerida',
                    text:  'Por favor seleccione una especialidad de la lista.'
                });
                return false;
            }

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

            const telefono = document.getElementById('telefono').value.trim();
            if (!TELEFONO_MEDICO_REGEX.test(telefono)) {
                Swal.fire({
                    icon:  'error',
                    title: 'Teléfono inválido',
                    html:  'El formato de teléfono no es válido.<br>Use el formato: <strong>+504 9999-9999</strong>'
                });
                return false;
            }

            const email = document.getElementById('email').value.trim().toLowerCase();
            if (!email.endsWith('@hospital.com')) {
                Swal.fire({
                    icon:  'error',
                    title: 'Dominio de correo no permitido',
                    html:  'El correo debe pertenecer al dominio institucional.<br>Ejemplo: <strong>doctor@hospital.com</strong>'
                });
                return false;
            }

            return true;
        }

        // Dropdown de especialidad
        document.querySelectorAll('#dropEspecialidad ~ .dropdown-menu .dropdown-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                const val = this.dataset.value;
                document.getElementById('especialidad').value = val;
                document.getElementById('dropEspecialidad').textContent = val;
                // Marcar activo visualmente
                document.querySelectorAll('#dropEspecialidad ~ .dropdown-menu .dropdown-item')
                    .forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Auto-formato de teléfono al escribir: +504 9999-9999
        document.getElementById('telefono').addEventListener('input', function () {
            let local = this.value.replace(/^\+504\s*/, '');
            let val   = local.replace(/\D/g, '');
            if (val.length > 8) val = val.slice(0, 8);
            if (val.length > 4)
                this.value = '+504 ' + val.slice(0, 4) + '-' + val.slice(4);
            else if (val.length > 0)
                this.value = '+504 ' + val;
            else
                this.value = '';
        });
    </script>

<?php else: ?>
    <?php if ($esJefe): ?>
    <div class="mb-3">
        <a href="?action=new" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo Médico
        </a>
    </div>
    <?php endif; ?>

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
                                <td><strong>Dr. <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($m['especialidad'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo $m['telefono'] ?: '-'; ?></td>
                                <td><?php echo $m['email'] ?: '-'; ?></td>
                                <?php if ($esJefe): ?>
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
                                <?php else: ?>
                                <td><span class="text-muted small">Solo lectura</span></td>
                                <?php endif; ?>
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
