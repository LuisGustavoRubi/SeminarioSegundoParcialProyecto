<?php
/**
 * pages/pacientes.php  –  Vista del módulo de Pacientes.
 *
 * Espera que el controlador (controllers/pacientes_controller.php) haya definido:
 *   @var string             $page_title
 *   @var bool               $mostrar_formulario
 *   @var array|null         $paciente            (null = formulario vacío)
 *   @var mysqli_result|null $pacientes            (listado)
 */

if (!isset($mostrar_formulario)) {
    header("Location: ../controllers/pacientesController.php");
    exit();
}

include '../includes/header.php';
?>

<?php if ($mostrar_formulario): ?>
    <!-- ─── FORMULARIO CREAR / EDITAR ─────────────────────────────────── -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person-plus"></i>
                    <?php echo $paciente ? 'Editar' : 'Registrar'; ?> Paciente
                </div>
                <div class="card-body">
                    <form method="POST" action="../controllers/pacientesController.php" onsubmit="return validarFormulario()">
                        <input type="hidden" name="action" value="<?php echo $paciente ? 'update' : 'create'; ?>">
                        <?php if ($paciente): ?>
                            <input type="hidden" name="id" value="<?php echo (int) $paciente['id']; ?>">
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                       value="<?php echo htmlspecialchars($paciente['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="apellido" class="form-label">Apellido *</label>
                                <input type="text" class="form-control" id="apellido" name="apellido"
                                       value="<?php echo htmlspecialchars($paciente['apellido'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cedula" class="form-label">Cédula *</label>
                                <input type="text" class="form-control" id="cedula" name="cedula"
                                       value="<?php echo htmlspecialchars($paciente['cedula'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       required>
                                <div class="form-text">Debe ser única</div>
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                       value="<?php echo htmlspecialchars($paciente['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                       value="<?php echo htmlspecialchars($paciente['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="+504 1234-5678">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($paciente['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="ejemplo@correo.com">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="../controllers/pacientesController.php" class="btn btn-secondary">
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
        function validarFormulario() {
            const nombre   = document.getElementById('nombre').value.trim();
            const apellido = document.getElementById('apellido').value.trim();
            const cedula   = document.getElementById('cedula').value.trim();

            if (!nombre || !apellido || !cedula) {
                Swal.fire({
                    icon:  'error',
                    title: 'Campos requeridos',
                    text:  'Por favor complete todos los campos obligatorios'
                });
                return false;
            }
            return true;
        }
    </script>

<?php else: ?>
    <!-- ─── LISTADO ────────────────────────────────────────────────────── -->
    <div class="mb-3">
        <a href="../controllers/pacientesController.php?action=new" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Nuevo Paciente
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-people"></i>
            Lista de Pacientes (<?php echo $pacientes->num_rows; ?>)
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
                                <td>
                                    <?php echo htmlspecialchars(
                                        normalizar_texto($p['nombre']) . ' ' . normalizar_texto($p['apellido']),
                                        ENT_QUOTES, 'UTF-8'
                                    ); ?>
                                </td>
                                <td><?php echo $p['telefono'] ? htmlspecialchars($p['telefono'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <td><?php echo $p['email']    ? htmlspecialchars($p['email'],    ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                                <td><?php echo $p['fecha_nacimiento'] ? date('d/m/Y', strtotime($p['fecha_nacimiento'])) : '-'; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="../controllers/pacientes_controller.php?action=edit&id=<?php echo (int) $p['id']; ?>"
                                           class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmarEliminacion(
                                                    <?php echo (int) $p['id']; ?>,
                                                    '<?php echo htmlspecialchars(
                                                        normalizar_texto($p['nombre']) . ' ' . normalizar_texto($p['apellido']),
                                                        ENT_QUOTES, 'UTF-8'
                                                    ); ?>',
                                                    'al paciente',
                                                    '../controllers/pacientes_controller.php'
                                                )"
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
                    <a href="../controllers/pacientes_controller.php?action=new" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Registrar Primer Paciente
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>