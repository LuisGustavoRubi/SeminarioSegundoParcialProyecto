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

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] !== 'changeStatus') {

            $paciente_id = $_POST['paciente_id'] ?? '';
            $medico_id   = $_POST['medico_id']   ?? '';
            $fecha       = $_POST['fecha']        ?? '';
            $hora        = $_POST['hora']         ?? '';
            $motivo      = trim($_POST['motivo']  ?? '');
            $estado      = $_POST['estado']       ?? 'pendiente';

            // Validación de campos obligatorios
            $camposFaltantes = [];
            if (empty($paciente_id)) $camposFaltantes[] = 'Paciente';
            if (empty($medico_id))   $camposFaltantes[] = 'Médico';
            if (empty($fecha))       $camposFaltantes[] = 'Fecha';
            if (empty($hora))        $camposFaltantes[] = 'Hora';
            if ($motivo === '')      $camposFaltantes[] = 'Motivo de consulta';

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

            // Crear registro
            if ($_POST['action'] == 'create') {

                $intervaloMin = 30 * 60; // 30 minutos en segundos
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
                    (paciente_id, medico_id, fecha, hora, motivo, estado)
                    VALUES ($paciente_id, $medico_id, '$fecha', '$hora', '$motivo', '$estado')";

                if ($this->conn->query($sql)) {
                    $_SESSION['success'] = "Cita agendada exitosamente";
                } else {
                    $_SESSION['error'] = "Error al agendar cita";
                }

                header("Location: ../pages/citas.php");
                exit();
            }

            // Actualizar registro
            if ($_POST['action'] == 'update') {
                $id = intval($_POST['id']);
                $observacion = $_POST['observacion'] ?? '';

                // Estado anterior
                $citaAnterior = $this->conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();

                if ($citaAnterior && $citaAnterior['estado'] === 'cancelada') {
                    $_SESSION['error'] = 'No se puede modificar una cita cancelada. Cree una nueva cita para continuar.';
                    header("Location: ../pages/citas.php?action=edit&id=$id");
                    exit();
                }

                $intervaloMin = 30 * 60; // 30 minutos en segundos
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
                    // Tpo de cambio
                    $tipo_cambio = "modificacion";
                    if ($estado == "cancelada") {
                        $tipo_cambio = "cancelacion";
                    }

                    if ($citaAnterior['fecha'] != $fecha || $citaAnterior['hora'] != $hora) {
                        $tipo_cambio = "reprogramacion";
                    }

                    // Guardar en la tabla de hisotorial de citas
                    $sqlHistorial = "INSERT INTO citas_historial(
                    cita_id,
                    tipo_cambio,
                    observacion,

                    anterior_paciente_id,
                    anterior_medico_id,
                    anterior_fecha,
                    anterior_hora,
                    anterior_motivo,
                    anterior_estado,

                    nuevo_paciente_id,
                    nuevo_medico_id,
                    nuevo_fecha,
                    nuevo_hora,
                    nuevo_motivo,
                    nuevo_estado
                    
                    ) VALUES (
                    $id,
                    '$tipo_cambio',
                    '$observacion',

                    {$citaAnterior['paciente_id']},
                    {$citaAnterior['medico_id']},
                    '{$citaAnterior['fecha']}',
                    '{$citaAnterior['hora']}',
                    '{$citaAnterior['motivo']}',
                    '{$citaAnterior['estado']}',

                    $paciente_id,
                    $medico_id,
                    '$fecha',
                    '$hora',
                    '$motivo',
                    '$estado'
                    )";
                    $this->conn->query($sqlHistorial);

                    // Actualizar cita
                    $sql = "UPDATE citas SET
                    paciente_id=$paciente_id,
                    medico_id=$medico_id,
                    fecha='$fecha',
                    hora='$hora',
                    motivo='$motivo',
                    estado='$estado'
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

        // Cancelar el estado de la cita
        if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
            $id = $_GET['id'];

            $citaAnterior = $this->conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();
            $sqlHistorial = "INSERT INTO citas_historial(
            cita_id,
            tipo_cambio,
            observacion,

            anterior_paciente_id,
            anterior_medico_id,
            anterior_fecha,
            anterior_hora,
            anterior_motivo,
            anterior_estado,

            nuevo_paciente_id,
            nuevo_medico_id,
            nuevo_fecha,
            nuevo_hora,
            nuevo_motivo,
            nuevo_estado

            ) VALUES (
            $id,
            'cancelacion',
            'Cita cancelada',

            {$citaAnterior['paciente_id']},
            {$citaAnterior['medico_id']},
            '{$citaAnterior['fecha']}',
            '{$citaAnterior['hora']}',
            '{$citaAnterior['motivo']}',
            '{$citaAnterior['estado']}',

            {$citaAnterior['paciente_id']},
            {$citaAnterior['medico_id']},
            '{$citaAnterior['fecha']}',
            '{$citaAnterior['hora']}',
            '{$citaAnterior['motivo']}',
            'cancelada'
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
            $id = intval($_POST['id'] ?? 0);
            $nuevo_estado = $_POST['estado'] ?? '';
            $estados_validos = ['pendiente', 'completada', 'cancelada'];

            if ($id > 0 && in_array($nuevo_estado, $estados_validos)) {
                $citaAnterior = $this->conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();

                if ($citaAnterior && $citaAnterior['estado'] === 'cancelada') {
                    $_SESSION['error'] = 'No se puede reactivar una cita cancelada. Cree una nueva cita para continuar.';
                    header('Location: ../pages/citas.php');
                    exit();
                }

                if ($citaAnterior && $citaAnterior['estado'] !== $nuevo_estado) {
                    $tipo_cambio = $nuevo_estado === 'cancelada' ? 'cancelacion' : 'modificacion';
                    $motivo_esc = $this->conn->real_escape_string($citaAnterior['motivo']);

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

                    if ($this->conn->query("UPDATE citas SET estado='$nuevo_estado' WHERE id=$id")) {
                        $_SESSION['success'] = 'Estado actualizado exitosamente';
                    } else {
                        $_SESSION['error'] = 'Error al actualizar el estado';
                    }
                }
            }

            header('Location: ../pages/citas.php');
            exit();
        }

        // Eliminar la cita, este no deberia de quedar
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {

            $id = $_GET['id'];

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
        $result = $this->conn->query("SELECT * FROM citas WHERE id=$id");
        return $result->fetch_assoc();
    }

    public function obtenerPacientes()
    {
        return $this->conn->query("SELECT * FROM pacientes ORDER BY apellido, nombre");
    }

    public function obtenerMedicos()
    {
        return $this->conn->query("SELECT * FROM medicos ORDER BY apellido, nombre");
    }

    public function obtenerTodas()
    {
        $sql = "SELECT c.*, 
                CONCAT(p.nombre, ' ', p.apellido) as paciente_nombre,
                CONCAT(m.nombre, ' ', m.apellido) as medico_nombre,
                m.especialidad
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                INNER JOIN medicos m ON c.medico_id = m.id
                ORDER BY c.fecha DESC, c.hora DESC";

        return $this->conn->query($sql);
    }
}