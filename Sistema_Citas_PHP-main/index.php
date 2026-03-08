<?php
require_once 'includes/config.php';

$total_pacientes = $conn->query("SELECT COUNT(*) as total FROM pacientes")->fetch_assoc()['total'];
$total_medicos   = $conn->query("SELECT COUNT(*) as total FROM medicos")->fetch_assoc()['total'];
$total_citas     = $conn->query("SELECT COUNT(*) as total FROM citas")->fetch_assoc()['total'];

// Fecha actual en zona Honduras (evita bug UTC del servidor)
$hoy = (new DateTime('now', new DateTimeZone('America/Tegucigalpa')))->format('Y-m-d');

// ── Parámetros de filtro / búsqueda ──────────────────────────────────────────
$fecha_filtro  = isset($_GET['fecha_filtro'])  && $_GET['fecha_filtro']  !== '' ? $_GET['fecha_filtro']  : $hoy;
$estado_filtro = isset($_GET['estado_filtro']) && in_array($_GET['estado_filtro'], ['pendiente', 'completada', 'cancelada'])
                 ? $_GET['estado_filtro'] : '';
$buscar        = trim($_GET['buscar'] ?? '');
$orden         = $_GET['orden'] ?? 'hora_asc';

// ── WHERE dinámico ───────────────────────────────────────────────────────────
$where = [];

if ($fecha_filtro !== '') {
    $fecha_esc = $conn->real_escape_string($fecha_filtro);
    $where[] = "c.fecha = '$fecha_esc'";
}

if ($estado_filtro !== '') {
    $where[] = "c.estado = '$estado_filtro'";
}

if ($buscar !== '') {
    $b = $conn->real_escape_string($buscar);
    $where[] = "(p.nombre  LIKE '%$b%'
              OR p.apellido LIKE '%$b%'
              OR m.nombre  LIKE '%$b%'
              OR m.apellido LIKE '%$b%'
              OR m.especialidad LIKE '%$b%'
              OR c.motivo  LIKE '%$b%')";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ── ORDER BY ─────────────────────────────────────────────────────────────────
$order_map = [
    'hora_asc'   => 'c.fecha ASC,  c.hora ASC',
    'hora_desc'  => 'c.fecha DESC, c.hora DESC',
    'estado'     => "FIELD(c.estado, 'pendiente', 'completada', 'cancelada'), c.fecha ASC, c.hora ASC",
    'paciente'   => 'p.apellido ASC, p.nombre ASC, c.fecha ASC',
    'medico'     => 'm.apellido ASC, m.nombre ASC, c.fecha ASC',
];
$order_sql = $order_map[$orden] ?? 'c.fecha ASC, c.hora ASC';

// ── Consulta principal ───────────────────────────────────────────────────────
$sql_citas = "SELECT c.*,
              CONCAT(p.nombre, ' ', p.apellido) AS paciente_nombre,
              CONCAT(m.nombre, ' ', m.apellido) AS medico_nombre,
              m.especialidad
              FROM citas c
              INNER JOIN pacientes p ON c.paciente_id = p.id
              INNER JOIN medicos m   ON c.medico_id   = m.id
              $where_sql
              ORDER BY $order_sql";
$citas_result = $conn->query($sql_citas);

// ── Título dinámico de la tarjeta ────────────────────────────────────────────
if ($fecha_filtro === $hoy && $estado_filtro === '' && $buscar === '') {
    $card_titulo = '<i class="bi bi-calendar-day"></i> Citas de Hoy (' . date('d/m/Y', strtotime($hoy)) . ')';
} elseif ($fecha_filtro !== '') {
    $card_titulo = '<i class="bi bi-calendar3"></i> Citas del ' . date('d/m/Y', strtotime($fecha_filtro));
} else {
    $card_titulo = '<i class="bi bi-calendar-check"></i> Todas las Citas';
}

$page_title = "Dashboard";
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card" style="border-left: 4px solid #0d6efd;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Pacientes</h6>
                        <h2 class="mb-0"><?php echo $total_pacientes; ?></h2>
                    </div>
                    <div class="text-primary" style="font-size: 3rem;">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card" style="border-left: 4px solid #198754;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Médicos</h6>
                        <h2 class="mb-0"><?php echo $total_medicos; ?></h2>
                    </div>
                    <div class="text-success" style="font-size: 3rem;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card" style="border-left: 4px solid #ffc107;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Citas</h6>
                        <h2 class="mb-0"><?php echo $total_citas; ?></h2>
                    </div>
                    <div class="text-warning" style="font-size: 3rem;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightning-charge"></i> Acciones Rápidas
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4 mb-1">
                        <a href="pages/citas.php?action=new" class="btn btn-outline-primary btn-lg w-100">
                            <i class="bi bi-calendar-plus"></i><br>
                            Agendar Cita
                        </a>
                    </div>
                    <div class="col-md-4 mb-1">
                        <a href="pages/pacientes.php?action=new" class="btn btn-outline-success btn-lg w-100">
                            <i class="bi bi-person-plus"></i><br>
                            Nuevo Paciente
                        </a>
                    </div>
                    <div class="col-md-4 mb-1">
                        <a href="pages/medicos.php?action=new" class="btn btn-outline-info btn-lg w-100">
                            <i class="bi bi-person-badge"></i><br>
                            Nuevo Médico
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Citas con filtros -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><?php echo $card_titulo; ?> (<?php echo $citas_result->num_rows; ?>)</span>
                <?php if ($fecha_filtro !== $hoy || $estado_filtro !== '' || $buscar !== ''): ?>
                    <a href="index.php" class="btn btn-sm btn-light">
                        <i class="bi bi-x-circle"></i> Ver hoy
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <!-- Barra de filtros -->
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Fecha</label>
                        <input type="date" name="fecha_filtro" class="form-control form-control-sm"
                            value="<?php echo htmlspecialchars($fecha_filtro, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Estado</label>
                        <select name="estado_filtro" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="pendiente"  <?php echo $estado_filtro === 'pendiente'  ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="completada" <?php echo $estado_filtro === 'completada' ? 'selected' : ''; ?>>Completada</option>
                            <option value="cancelada"  <?php echo $estado_filtro === 'cancelada'  ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Ordenar por</label>
                        <select name="orden" class="form-select form-select-sm">
                            <option value="hora_asc"  <?php echo $orden === 'hora_asc'  ? 'selected' : ''; ?>>Fecha/Hora ↑</option>
                            <option value="hora_desc" <?php echo $orden === 'hora_desc' ? 'selected' : ''; ?>>Fecha/Hora ↓</option>
                            <option value="estado"    <?php echo $orden === 'estado'    ? 'selected' : ''; ?>>Estado</option>
                            <option value="paciente"  <?php echo $orden === 'paciente'  ? 'selected' : ''; ?>>Paciente</option>
                            <option value="medico"    <?php echo $orden === 'medico'    ? 'selected' : ''; ?>>Médico</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Buscar</label>
                        <input type="text" name="buscar" class="form-control form-control-sm"
                            placeholder="Paciente, médico, motivo..."
                            value="<?php echo htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </form>

                <?php if ($citas_result->num_rows > 0): ?>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($cita = $citas_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                                    <td><strong><?php echo date('H:i', strtotime($cita['hora'])); ?></strong></td>
                                    <td><?php echo normalizar_texto($cita['paciente_nombre']); ?></td>
                                    <td>Dr. <?php echo normalizar_texto($cita['medico_nombre']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo normalizar_texto($cita['especialidad']); ?></span></td>
                                    <td><?php echo htmlspecialchars(substr($cita['motivo'], 0, 45), ENT_QUOTES, 'UTF-8'); ?><?php echo strlen($cita['motivo']) > 45 ? '…' : ''; ?></td>
                                    <td>
                                        <?php if ($cita['estado'] === 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php elseif ($cita['estado'] === 'completada'): ?>
                                            <span class="badge bg-success">Completada</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cancelada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x" style="font-size: 3rem;"></i>
                        <p class="mt-2">No se encontraron citas con los filtros aplicados</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
