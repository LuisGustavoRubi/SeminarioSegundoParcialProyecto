<?php
require_once 'includes/config.php';

$total_pacientes = $conn->query("SELECT COUNT(*) as total FROM pacientes")->fetch_assoc()['total'];
$total_medicos = $conn->query("SELECT COUNT(*) as total FROM medicos")->fetch_assoc()['total'];
$total_citas = $conn->query("SELECT COUNT(*) as total FROM citas")->fetch_assoc()['total'];

$hoy = date('Y-m-d');
$sql_citas_hoy = "SELECT c.*, 
                  CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
                  CONCAT(m.nombre, ' ', m.apellido) as medico_nombre
                  FROM citas c
                  INNER JOIN pacientes p ON c.paciente_id = p.id
                  INNER JOIN medicos m ON c.medico_id = m.id
                  WHERE c.fecha = '$hoy'
                  ORDER BY c.hora ASC";
$citas_hoy = $conn->query($sql_citas_hoy);

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

<!-- Citas de hoy -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calendar-day"></i> Citas de Hoy
            </div>
            <div class="card-body">
                <?php if ($citas_hoy->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Paciente</th>
                                    <th>Médico</th>
                                    <th>Motivo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($cita = $citas_hoy->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo date('H:i', strtotime($cita['hora'])); ?></strong></td>
                                    <td><?php echo normalizar_texto($cita['paciente_nombre']); ?></td>
                                    <td>Dr. <?php echo normalizar_texto($cita['medico_nombre']); ?></td>
                                    <td><?php echo substr($cita['motivo'], 0, 50); ?><?php echo strlen($cita['motivo']) > 50 ? '...' : ''; ?></td>
                                    <td>
                                        <?php if ($cita['estado'] == 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php elseif ($cita['estado'] == 'completada'): ?>
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
                        <p class="mt-2">No hay citas programadas para hoy</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
