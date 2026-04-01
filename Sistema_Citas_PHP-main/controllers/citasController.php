<?php
session_start();
require_once '../includes/config.php';

class CitasController
{

    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function esMedico(): bool
    {
        return ($_SESSION['rol'] ?? '') === 'empleado';
    }

    private function miMedicoId(): int
    {
        return intval($_SESSION['medico_id'] ?? 0);
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] !== 'changeStatus') {

            $paciente_id = $_POST['paciente_id'] ?? '';
            $medico_id   = $_POST['medico_id']   ?? '';
            $localidad_id= $_POST['localidad_id'] ?? '';
            $fecha       = $_POST['fecha']        ?? '';
            $hora        = $_POST['hora']         ?? '';
            $motivo      = trim($_POST['motivo']  ?? '');
            $estado      = $_POST['estado']       ?? 'pendiente';

            // Empleados solo pueden asignar citas a su propio médico
            if ($this->esMedico() && $this->miMedicoId() > 0) {
                $medico_id = $this->miMedicoId();
            }

            $camposFaltantes = [];
            if (empty($paciente_id))   $camposFaltantes[] = 'Paciente';
            if (empty($medico_id))     $camposFaltantes[] = 'Médico';
            if (empty($localidad_id))  $camposFaltantes[] = 'Localidad';
            if (empty($fecha))         $camposFaltantes[] = 'Fecha';
            if (empty($hora))          $camposFaltantes[] = 'Hora';
            if ($motivo === '')        $camposFaltantes[] = 'Motivo de consulta';

            if (!empty($camposFaltantes)) {
                $_SESSION['error'] = 'Los siguientes campos son obligatorios: ' . implode(', ', $camposFaltantes);
                $_SESSION['form_data'] = $_POST;
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/citas.php?action=new'
                    : '../pages/citas.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            if (mb_strlen($motivo) < 10) {
                $_SESSION['error'] = 'El motivo de consulta debe tener al menos 10 caracteres.';
                $_SESSION['form_data'] = $_POST;
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/citas.php?action=new'
                    : '../pages/citas.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            $ahoraHN = new DateTime('now', new DateTimeZone('America/Tegucigalpa'));
            if ($fecha < $ahoraHN->format('Y-m-d')) {
                $_SESSION['error'] = 'No se pueden agendar citas en fechas pasadas. Seleccione hoy o una fecha futura.';
                $_SESSION['form_data'] = $_POST;
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/citas.php?action=new'
                    : '../pages/citas.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            if ($fecha === $ahoraHN->format('Y-m-d') && $hora < $ahoraHN->format('H:i')) {
                $_SESSION['error'] = 'No se pueden agendar citas para horas que ya pasaron. Seleccione una hora futura.';
                $_SESSION['form_data'] = $_POST;
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/citas.php?action=new'
                    : '../pages/citas.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            if ($_POST['action'] == 'create') {

                $intervaloMin = 30 * 60;
                $check = $this->conn->query("SELECT id FROM citas
                    WHERE medico_id=$medico_id
                    AND fecha='$fecha'
                    AND estado != 'cancelada'
                    AND ABS(TIME_TO_SEC(TIMEDIFF(hora, '$hora'))) < $intervaloMin");

                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "El médico ya tiene una cita dentro de los 30 minutos de ese horario. Seleccione otro horario.";
                    $_SESSION['form_data'] = $_POST;
                    header("Location: ../pages/citas.php?action=new");
                    exit();
                }

                $sql = "INSERT INTO citas
                    (paciente_id, medico_id, localidad_id, fecha, hora, motivo, estado)
                    VALUES ($paciente_id, $medico_id, $localidad_id, '$fecha', '$hora', '$motivo', '$estado')";

                if ($this->conn->query($sql)) {
                    $_SESSION['success'] = "Cita agendada exitosamente";
                } else {
                    $_SESSION['error'] = "Error al agendar cita";
                }

                header("Location: ../pages/citas.php");
                exit();
            }

            if ($_POST['action'] == 'update') {
                $id = intval($_POST['id']);

                // Empleados solo pueden editar sus propias citas
                if ($this->esMedico()) {
                    $ownCheck = $this->conn->query("SELECT medico_id FROM citas WHERE id=$id")->fetch_assoc();
                    if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                        $_SESSION['error'] = 'No tiene permiso para modificar esta cita.';
                        header('Location: ../pages/citas.php');
                        exit();
                    }
                }

                $observacion = $_POST['observacion'] ?? '';

                $citaAnterior = $this->conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();

                if ($citaAnterior && in_array($citaAnterior['estado'], ['cancelada', 'completada'])) {
                    $_SESSION['error'] = 'No se puede modificar una cita ' . $citaAnterior['estado'] . '. Cree una nueva cita para continuar.';
                    header("Location: ../pages/citas.php?action=edit&id=$id");
                    exit();
                }

                $intervaloMin = 30 * 60;
                $check = $this->conn->query("SELECT id FROM citas
                WHERE medico_id=$medico_id
                AND fecha='$fecha'
                AND estado != 'cancelada'
                AND ABS(TIME_TO_SEC(TIMEDIFF(hora, '$hora'))) < $intervaloMin
                AND id != $id");

                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "El médico ya tiene una cita dentro de los 30 minutos de ese horario. Seleccione otro horario.";
                    $_SESSION['form_data'] = $_POST;
                    header("Location: ../pages/citas.php?action=edit&id=$id");
                    exit();
                } else {
                    $tipo_cambio = "modificacion";
                    if ($estado == "cancelada") {
                        $tipo_cambio = "cancelacion";
                    }
                    if ($citaAnterior['fecha'] != $fecha || $citaAnterior['hora'] != $hora) {
                        $tipo_cambio = "reprogramacion";
                    }

                    $sqlHistorial = "INSERT INTO citas_historial(
                    cita_id, tipo_cambio, observacion,
                    anterior_paciente_id, anterior_medico_id, anterior_fecha, anterior_hora, anterior_motivo, anterior_estado,
                    nuevo_paciente_id, nuevo_medico_id, nuevo_fecha, nuevo_hora, nuevo_motivo, nuevo_estado
                    ) VALUES (
                    $id, '$tipo_cambio', '$observacion',
                    {$citaAnterior['paciente_id']}, {$citaAnterior['medico_id']}, '{$citaAnterior['fecha']}', '{$citaAnterior['hora']}', '{$citaAnterior['motivo']}', '{$citaAnterior['estado']}',
                    $paciente_id, $medico_id, '$fecha', '$hora', '$motivo', '$estado'
                    )";
                    $this->conn->query($sqlHistorial);

                    // Si se está completando desde el formulario de edición, guardar enfermedad
                    $enfermedad_id = intval($_POST['enfermedad_id'] ?? 0);
                    $enfermedadSql = ($estado === 'completada' && $enfermedad_id > 0) ? ", enfermedad_id=$enfermedad_id" : '';

                    $sql = "UPDATE citas SET
                    paciente_id=$paciente_id,
                    medico_id=$medico_id,
                    localidad_id=$localidad_id,
                    fecha='$fecha',
                    hora='$hora',
                    motivo='$motivo',
                    estado='$estado'
                    $enfermedadSql
                    WHERE id=$id";

                    if ($this->conn->query($sql)) {
                        $_SESSION['success'] = "Cita actualizada exitosamente";
                    } else {
                        $_SESSION['error'] = "Error al actualizar cita";
                    }
                }
                header("Location: ../pages/citas.php");
                exit();
            }
        }

        if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
            $id = intval($_GET['id']);

            // Empleados solo pueden cancelar sus propias citas
            if ($this->esMedico()) {
                $ownCheck = $this->conn->query("SELECT medico_id FROM citas WHERE id=$id")->fetch_assoc();
                if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                    $_SESSION['error'] = 'No tiene permiso para cancelar esta cita.';
                    header('Location: ../pages/citas.php');
                    exit();
                }
            }

            $citaAnterior = $this->conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();
            $sqlHistorial = "INSERT INTO citas_historial(
            cita_id, tipo_cambio, observacion,
            anterior_paciente_id, anterior_medico_id, anterior_fecha, anterior_hora, anterior_motivo, anterior_estado,
            nuevo_paciente_id, nuevo_medico_id, nuevo_fecha, nuevo_hora, nuevo_motivo, nuevo_estado
            ) VALUES (
            $id, 'cancelacion', 'Cita cancelada',
            {$citaAnterior['paciente_id']}, {$citaAnterior['medico_id']}, '{$citaAnterior['fecha']}', '{$citaAnterior['hora']}', '{$citaAnterior['motivo']}', '{$citaAnterior['estado']}',
            {$citaAnterior['paciente_id']}, {$citaAnterior['medico_id']}, '{$citaAnterior['fecha']}', '{$citaAnterior['hora']}', '{$citaAnterior['motivo']}', 'cancelada'
            )";
            $this->conn->query($sqlHistorial);

            if ($this->conn->query("UPDATE citas SET estado='cancelada' WHERE id=$id")) {
                $_SESSION['success'] = "Cita cancelada exitosamente";
            } else {
                $_SESSION['error'] = "Error al cancelar cita";
            }

            header("Location: ../pages/citas.php");
            exit();
        }

        // Cambio rápido de estado desde el listado
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'changeStatus') {
            $id            = intval($_POST['id'] ?? 0);
            $nuevo_estado  = $_POST['estado'] ?? '';
            $enfermedad_id = intval($_POST['enfermedad_id'] ?? 0);
            $estados_validos = ['pendiente', 'completada', 'cancelada'];

            if ($id > 0 && in_array($nuevo_estado, $estados_validos)) {
                // Empleados solo pueden cambiar estado de sus propias citas
                if ($this->esMedico()) {
                    $ownCheck = $this->conn->query("SELECT medico_id FROM citas WHERE id=$id")->fetch_assoc();
                    if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                        $_SESSION['error'] = 'No tiene permiso para modificar esta cita.';
                        header('Location: ../pages/citas.php');
                        exit();
                    }
                }

                $citaAnterior = $this->conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();

                if ($citaAnterior && in_array($citaAnterior['estado'], ['cancelada', 'completada'])) {
                    $_SESSION['error'] = 'No se puede modificar una cita ' . $citaAnterior['estado'] . '. Cree una nueva cita para continuar.';
                    header('Location: ../pages/citas.php');
                    exit();
                }

                if ($nuevo_estado === 'completada' && $enfermedad_id <= 0) {
                    $_SESSION['error'] = 'Debe seleccionar una enfermedad para completar la cita.';
                    header('Location: ../pages/citas.php');
                    exit();
                }

                if ($citaAnterior && $citaAnterior['estado'] !== $nuevo_estado) {
                    $tipo_cambio = $nuevo_estado === 'cancelada' ? 'cancelacion' : 'modificacion';
                    $motivo_esc  = $this->conn->real_escape_string($citaAnterior['motivo']);

                    $sqlHistorial = "INSERT INTO citas_historial(
                        cita_id, tipo_cambio, observacion,
                        anterior_paciente_id, anterior_medico_id, anterior_fecha, anterior_hora, anterior_motivo, anterior_estado,
                        nuevo_paciente_id, nuevo_medico_id, nuevo_fecha, nuevo_hora, nuevo_motivo, nuevo_estado
                    ) VALUES (
                        $id, '$tipo_cambio', 'Cambio de estado desde el listado',
                        {$citaAnterior['paciente_id']}, {$citaAnterior['medico_id']}, '{$citaAnterior['fecha']}', '{$citaAnterior['hora']}', '$motivo_esc', '{$citaAnterior['estado']}',
                        {$citaAnterior['paciente_id']}, {$citaAnterior['medico_id']}, '{$citaAnterior['fecha']}', '{$citaAnterior['hora']}', '$motivo_esc', '$nuevo_estado'
                    )";
                    $this->conn->query($sqlHistorial);

                    $enfermedadSql = ($nuevo_estado === 'completada') ? ", enfermedad_id=$enfermedad_id" : '';

                    if ($this->conn->query("UPDATE citas SET estado='$nuevo_estado'$enfermedadSql WHERE id=$id")) {
                        $_SESSION['success'] = 'Estado actualizado exitosamente';
                    } else {
                        $_SESSION['error'] = 'Error al actualizar el estado';
                    }
                }
            }

            header('Location: ../pages/citas.php');
            exit();
        }

        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
            $id = intval($_GET['id']);

            // Empleados solo pueden eliminar sus propias citas
            if ($this->esMedico()) {
                $ownCheck = $this->conn->query("SELECT medico_id FROM citas WHERE id=$id")->fetch_assoc();
                if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                    $_SESSION['error'] = 'No tiene permiso para eliminar esta cita.';
                    header('Location: ../pages/citas.php');
                    exit();
                }
            }

            if ($this->conn->query("DELETE FROM citas WHERE id=$id")) {
                $_SESSION['success'] = "Cita eliminada exitosamente";
            } else {
                $_SESSION['error'] = "Error al eliminar cita";
            }
            header("Location: ../pages/citas.php");
            exit();
        }
    }

    public function obtenerPorId($id)
    {
        $id = intval($id);
        $andMedico = ($this->esMedico() && $this->miMedicoId() > 0)
            ? ' AND medico_id = ' . $this->miMedicoId()
            : '';
        $result = $this->conn->query("SELECT * FROM citas WHERE id=$id$andMedico");
        return $result ? $result->fetch_assoc() : null;
    }

    public function obtenerPacientes()
    {
        return $this->conn->query("SELECT * FROM pacientes ORDER BY apellido, nombre");
    }

    public function obtenerMedicos()
    {
        if ($this->esMedico() && $this->miMedicoId() > 0) {
            return $this->conn->query("SELECT * FROM medicos WHERE id = " . $this->miMedicoId());
        }
        return $this->conn->query("SELECT * FROM medicos ORDER BY apellido, nombre");
    }

    public function obtenerEnfermedades()
    {
        return $this->conn->query("SELECT * FROM enfermedades ORDER BY nombre");
    }

    public function obtenerLocalidades()
    {
        return $this->conn->query("SELECT * FROM localidades ORDER BY nombre");
    }

    public function obtenerTodas()
    {
        $where = ($this->esMedico() && $this->miMedicoId() > 0)
            ? 'WHERE c.medico_id = ' . $this->miMedicoId()
            : '';
        $sql = "SELECT c.*,
                CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
                CONCAT(m.nombre, ' ', m.apellido) as medico_nombre,
                m.especialidad,
                l.nombre AS localidad
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                INNER JOIN medicos m ON c.medico_id = m.id
                INNER JOIN localidades l ON c.localidad_id = l.id
                $where
                ORDER BY c.fecha DESC, c.hora DESC";
        return $this->conn->query($sql);
    }
}