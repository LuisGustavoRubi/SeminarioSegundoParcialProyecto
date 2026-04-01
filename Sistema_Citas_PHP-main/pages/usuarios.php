<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (($_SESSION['rol'] ?? '') !== 'jefe') {
    $_SESSION['error'] = 'No tiene permiso para acceder a la gestión de usuarios.';
    header('Location: ../index.php');
    exit();
}

require_once '../includes/config.php';
require_once '../controllers/usuariosController.php';

$controller = new UsuarioController($conn);
$controller->handleRequest();

$usuario = null;
$mostrar_formulario = false;

// Verificar acciones
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $usuario = $controller->obtenerPorId($_GET['id']);
    $mostrar_formulario = true;
}

if (isset($_GET['action']) && $_GET['action'] == 'new') {
    $mostrar_formulario = true;
}

if (!$mostrar_formulario) {
    $usuarios = $controller->obtenerTodos();
}

// Obtener médicos para el combobox
$medicos = $controller->obtenerMedicos();

// Recuperar datos de formulario si hubo error de validación
$form_data = null;
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

$current_page = 'usuarios';
$page_title = $mostrar_formulario 
    ? ($usuario ? "Editar Usuario" : "Nuevo Usuario")
    : "Gestión de Usuarios";

include '../includes/header.php';
?>

<?php if ($mostrar_formulario): ?>
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <?php echo $usuario ? 'Editar' : 'Registrar'; ?> Usuario
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validarFormularioUsuario()" novalidate>
                        <input type="hidden" name="action" value="<?php echo $usuario ? 'update' : 'create'; ?>">
                        <?php if ($usuario): ?>
                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" id="usuario" name="usuario"
                                   value="<?php echo htmlspecialchars($form_data['usuario'] ?? ($usuario['usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="Ej: dr_rodriguez"
                                   minlength="4"
                                   maxlength="20"
                                   pattern="[a-zA-Z0-9_\-]+"
                                   required>
                            <div class="form-text">De 4 a 20 caracteres (letras, números, guiones y guiones bajos)</div>
                        </div>

                        <div class="mb-3">
                            <label for="rol_id" class="form-label">Rol *</label>
                            <?php $rolActual = $form_data['rol_id'] ?? ($usuario['rol_id'] ?? 2); ?>
                            <select class="form-select" id="rol_id" name="rol_id" required>
                                <option value="2" <?php echo $rolActual == 2 ? 'selected' : ''; ?>>Empleado (Médico)</option>
                                <option value="1" <?php echo $rolActual == 1 ? 'selected' : ''; ?>>Jefe (Administrador)</option>
                            </select>
                            <div class="form-text">Empleado: solo ve sus propias citas · Jefe: acceso completo</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Médico a Asignar (Requerido para empleados)</label>
                            <?php $medicoActual = $form_data['medico_id'] ?? ($usuario['medico_id'] ?? ''); ?>
                            <input type="hidden" id="medico_id" name="medico_id"
                                   value="<?php echo htmlspecialchars($medicoActual, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                        id="dropMedico"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="true"
                                        aria-expanded="false"
                                        style="background:#fff;">
                                    <?php
                                    if ($medicoActual) {
                                        $medico_sel = array_filter($medicos, fn($m) => $m['id'] == $medicoActual);
                                        if (!empty($medico_sel)) {
                                            echo htmlspecialchars(reset($medico_sel)['descripcion'], ENT_QUOTES, 'UTF-8');
                                        } else {
                                            echo '-- Seleccione un médico --';
                                        }
                                    } else {
                                        echo '-- Seleccione un médico (opcional) --';
                                    }
                                    ?>
                                </button>
                                <ul class="dropdown-menu w-100"
                                    aria-labelledby="dropMedico"
                                    style="max-height:300px; overflow-y:auto;">
                                    <li>
                                        <a class="dropdown-item" href="#" data-value="">
                                            -- Ninguno (Empleado) --
                                        </a>
                                    </li>
                                    <?php foreach ($medicos as $medico): ?>
                                        <li>
                                            <a class="dropdown-item<?php echo ($medicoActual == $medico['id']) ? ' active' : ''; ?>"
                                               href="#"
                                               data-value="<?php echo htmlspecialchars($medico['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($medico['descripcion'], ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="form-text">Selecciona el médico que corresponde a este usuario empleado</div>
                        </div>

                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i>
                            <strong>Nota:</strong> Este usuario se creará con contraseña predeterminada <strong>1234</strong> 
                            y estado <strong>INACTIVO</strong> (no podrá iniciar sesión).
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="usuarios.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> <?php echo $usuario ? 'Actualizar' : 'Registrar'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const USUARIO_REGEX = /^[a-zA-Z0-9_\-]{4,20}$/;

        function validarFormularioUsuario() {
            const usuario = document.getElementById('usuario').value.trim();

            if (!usuario) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El nombre de usuario es requerido'
                });
                return false;
            }

            if (!USUARIO_REGEX.test(usuario)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Formato inválido',
                    text: 'El nombre de usuario debe tener 4-20 caracteres (letras, números, guiones y guiones bajos)'
                });
                return false;
            }

            return true;
        }

        // Manejar dropdown de médicos
        document.querySelectorAll('#dropMedico ~ .dropdown-menu .dropdown-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const value = this.getAttribute('data-value');
                const text = this.textContent.trim();
                document.getElementById('medico_id').value = value;
                document.getElementById('dropMedico').textContent = text || '-- Seleccione un médico (opcional) --';
            });
        });
    </script>

<?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Gestión de Usuarios</h3>
                <a href="usuarios.php?action=new" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Nuevo Usuario
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Médico Asignado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['usuario'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($user['rol_id'] == 1) ? 'warning' : 'info'; ?>">
                                            <?php echo htmlspecialchars($user['rol'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo !empty($user['medico']) ? htmlspecialchars($user['medico'], ENT_QUOTES, 'UTF-8') : '<em class="text-muted">N/A</em>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo ($user['estado'] == 'activo') ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($user['estado'], ENT_QUOTES, 'UTF-8')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="usuarios.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No hay usuarios creados. <a href="usuarios.php?action=new">Crear uno ahora</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>
