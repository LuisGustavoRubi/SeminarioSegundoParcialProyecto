<?php
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

            $paciente_id = $_POST['paciente_id'];
            $medico_id = $_POST['medico_id'];
            $fecha = $_POST['fecha'];
            $hora = $_POST['hora'];
            $motivo = $_POST['motivo'];
            $estado = $_POST['estado'] ?? 'pendiente';

            // Crear registro
            if ($_POST['action'] == 'create') {

                $check = $this->conn->query("SELECT id FROM citas 
                    WHERE medico_id=$medico_id 
                    AND fecha='$fecha' 
                    AND hora='$hora' 
                    AND estado != 'cancelada'");

                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "Ya existe una cita para este médico en esa fecha y hora";
                } else {

                    $sql = "INSERT INTO citas 
                        (paciente_id, medico_id, fecha, hora, motivo, estado)
                        VALUES ($paciente_id, $medico_id, '$fecha', '$hora', '$motivo', '$estado')";

                    if ($this->conn->query($sql)) {
                        $_SESSION['success'] = "Cita agendada exitosamente";
                    } else {
                        $_SESSION['error'] = "Error al agendar cita";
                    }
                }

                header("Location: ../pages/citas.php");
                exit();
            }

            // Actualizar registro
            if ($_POST['action'] == 'update') {
                $id = $_POST['id'];
                $observacion = $_POST['observacion'] ?? '';

                // Estado anterior
                $citaAnterior = $this->conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();

                $check = $this->conn->query("SELECT id FROM citas WHERE medico_id=$medico_id 
                AND fecha='$fecha' 
                AND hora='$hora' 
                AND estado != 'cancelada'
                AND id != $id");

                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "Ya existe una cita para este médico en esa fecha y hora";
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