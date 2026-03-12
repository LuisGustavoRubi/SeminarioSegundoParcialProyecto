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
        // validar peticiones POST con token CSRF de inmediato
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
                $_SESSION['error'] = 'Token CSRF inválido. Acción repudiada.';
                header('Location: ../pages/citas.php');
                exit();
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] !== 'changeStatus') {

            $paciente_id = intval($_POST['paciente_id'] ?? 0);
            $medico_id   = intval($_POST['medico_id'] ?? 0);
            $fecha       = $_POST['fecha']        ?? '';
            $hora        = $_POST['hora']         ?? '';
            $motivo      = trim($_POST['motivo']  ?? '');
            $estado      = $_POST['estado']       ?? 'pendiente';

            // Validación de campos obligatorios
            $camposFaltantes = [];
            if ($paciente_id <= 0) $camposFaltantes[] = 'Paciente';
            if ($medico_id <= 0)   $camposFaltantes[] = 'Médico';
            if (empty($fecha))     $camposFaltantes[] = 'Fecha';
            if (empty($hora))      $camposFaltantes[] = 'Hora';
            if ($motivo === '')    $camposFaltantes[] = 'Motivo de consulta';

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

            // verificar existencia de paciente y médico
            $exists = $this->conn->prepare("SELECT COUNT(*) FROM pacientes WHERE id=?");
            $exists->bind_param("i", $paciente_id);
            $exists->execute();
            $exists->bind_result($cntPac);
            $exists->fetch();
            $exists->close();
            if ($cntPac === 0) {
                $_SESSION['error'] = 'Paciente no válido';
                $_SESSION['form_data'] = $_POST;
                header('Location: ../pages/citas.php?action=new');
                exit();
            }
            $exists = $this->conn->prepare("SELECT COUNT(*) FROM medicos WHERE id=?");
            $exists->bind_param("i", $medico_id);
            $exists->execute();
            $exists->bind_result($cntMed);
            $exists->fetch();
            $exists->close();
            if ($cntMed === 0) {
                $_SESSION['error'] = 'Médico no válido';
                $_SESSION['form_data'] = $_POST;
                header('Location: ../pages/citas.php?action=new');
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

            // sincronizar acceso a la hora para evitar concurrencia
            $this->conn->begin_transaction();
            try {
                // comprobar conflicto horario dentro de la transacción
                $intervaloMin = 30 * 60; // 30 minutos en segundos
                $checkStmt = $this->conn->prepare(
                    "SELECT id FROM citas
                     WHERE medico_id=?
                     AND fecha=?
                     AND estado != 'cancelada'
                     AND ABS(TIME_TO_SEC(TIMEDIFF(hora, ?))) < ?"
                );
                $checkStmt->bind_param("isss", $medico_id, $fecha, $hora, $intervaloMin);

                if ($_POST['action'] == 'create') {
                    $checkStmt->execute();
                    $checkStmt->store_result();
                    if ($checkStmt->num_rows > 0) {
                        $this->conn->rollback();
                        $_SESSION['error'] = "El médico ya tiene una cita dentro de los 30 minutos de ese horario. Seleccione otro horario.";
                        $_SESSION['form_data'] = $_POST;
                        header("Location: ../pages/citas.php?action=new");
                        exit();
                    }
                    $checkStmt->close();

                    $insert = $this->conn->prepare(
                        "INSERT INTO citas
                        (paciente_id, medico_id, fecha, hora, motivo, estado)
                        VALUES (?,?,?,?,?,?)"
                    );
                    $insert->bind_param("iissss", $paciente_id, $medico_id, $fecha, $hora, $motivo, $estado);
                    if ($insert->execute()) {
                        $_SESSION['success'] = "Cita agendada exitosamente";
                    } else {
                        error_log("[CITAS] insert error: " . $insert->error);
                        $_SESSION['error'] = "Error técnico al agendar cita";
                    }
                    $insert->close();
                    $this->conn->commit();
                    header("Location: ../pages/citas.php");
                    exit();
                }

                // update
                if ($_POST['action'] == 'update') {
                    $id = intval($_POST['id']);
                    $observacion = $_POST['observacion'] ?? '';

                    // comprobar existencia de la cita
                    $existC = $this->conn->prepare("SELECT estado, fecha, hora FROM citas WHERE id=? FOR UPDATE");
                    $existC->bind_param("i", $id);
                    $existC->execute();
                    $res = $existC->get_result();
                    if ($res->num_rows === 0) {
                        $this->conn->rollback();
                        $_SESSION['error'] = 'La cita que intenta modificar no existe.';
                        header("Location: ../pages/citas.php");
                        exit();
                    }
                    $citaAnterior = $res->fetch_assoc();
                    $existC->close();

                    if ($citaAnterior['estado'] === 'cancelada') {
                        $this->conn->rollback();
                        $_SESSION['error'] = 'No se puede modificar una cita cancelada. Cree una nueva cita para continuar.';
                        header("Location: ../pages/citas.php?action=edit&id=$id");
                        exit();
                    }

                    $checkStmt->bind_param("isss", $medico_id, $fecha, $hora, $intervaloMin);
                    $checkStmt->execute();
                    $checkStmt->store_result();
                    if ($checkStmt->num_rows > 0) {
                        $checkStmt->close();
                        $this->conn->rollback();
                        $_SESSION['error'] = "El médico ya tiene una cita dentro de los 30 minutos de ese horario. Seleccione otro horario.";
                        $_SESSION['form_data'] = $_POST;
                        header("Location: ../pages/citas.php?action=edit&id=$id");
                        exit();
                    }
                    $checkStmt->close();

                    // registrar en historial antes de update
                    $tipo_cambio = 'modificacion';
                    if ($estado == 'cancelada') {
                        $tipo_cambio = 'cancelacion';
                    }
                    if ($citaAnterior['fecha'] != $fecha || $citaAnterior['hora'] != $hora) {
                        $tipo_cambio = 'reprogramacion';
                    }
                    $hist = $this->conn->prepare(
                        "INSERT INTO citas_historial(
                            cita_id,tipo_cambio,observacion,
                            anterior_paciente_id,anterior_medico_id,anterior_fecha,anterior_hora,anterior_motivo,anterior_estado,
                            nuevo_paciente_id,nuevo_medico_id,nuevo_fecha,nuevo_hora,nuevo_motivo,nuevo_estado
                        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                    );
                    $hist->bind_param("issiiiiisssssss",
                        $id, $tipo_cambio, $observacion,
                        $paciente_id, $medico_id, $citaAnterior['fecha'], $citaAnterior['hora'], $motivo, $citaAnterior['estado'],
                        $paciente_id, $medico_id, $fecha, $hora, $motivo, $estado
                    );
                    $hist->execute();
                    $hist->close();

                    $update = $this->conn->prepare(
                        "UPDATE citas SET
                            paciente_id=?, medico_id=?, fecha=?, hora=?, motivo=?, estado=?
                          WHERE id=?"
                    );
                    $update->bind_param("iissssi", $paciente_id, $medico_id, $fecha, $hora, $motivo, $estado, $id);
                    if ($update->execute()) {
                        $_SESSION['success'] = "Cita actualizada exitosamente";
                    } else {
                        error_log("[CITAS] update error: " . $update->error);
                        $_SESSION['error'] = "Error técnico al actualizar cita";
                    }
                    $update->close();
                    $this->conn->commit();
                    header("Location: ../pages/citas.php");
                    exit();
                }
            } catch (\\Throwable $e) {
                $this->conn->rollback();
                error_log("[CITAS] transacción fallida: " . $e->getMessage());
                $_SESSION['error'] = 'Error técnico al procesar la cita';
                header('Location: ../pages/citas.php');
                exit();
            }
        }

        // Cancelar el estado de la cita vía POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                // leer datos anteriores y bloquear fila para evitar condiciones de carrera
                $stmt = $this->conn->prepare("SELECT * FROM citas WHERE id=? FOR UPDATE");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows === 1) {
                    $citaAnterior = $res->fetch_assoc();

                    $hist = $this->conn->prepare(
                        "INSERT INTO citas_historial(
                            cita_id,tipo_cambio,observacion,
                            anterior_paciente_id,anterior_medico_id,anterior_fecha,anterior_hora,anterior_motivo,anterior_estado,
                            nuevo_paciente_id,nuevo_medico_id,nuevo_fecha,nuevo_hora,nuevo_motivo,nuevo_estado
                        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                    );
                    $nuevoEstado = 'cancelada';
                    $hist->bind_param("issiiiiisssssss",
                        $id, 'cancelacion', 'Cita cancelada',
                        $citaAnterior['paciente_id'], $citaAnterior['medico_id'], $citaAnterior['fecha'], $citaAnterior['hora'], $citaAnterior['motivo'], $citaAnterior['estado'],
                        $citaAnterior['paciente_id'], $citaAnterior['medico_id'], $citaAnterior['fecha'], $citaAnterior['hora'], $citaAnterior['motivo'], $nuevoEstado
                    );
                    $hist->execute();
                    $hist->close();

                    $upd = $this->conn->prepare("UPDATE citas SET estado='cancelada' WHERE id=?");
                    $upd->bind_param("i", $id);
                    if ($upd->execute()) {
                        $_SESSION['success'] = "Cita cancelada exitosamente";
                    } else {
                        error_log("[CITAS] error cancelar cita id=$id: " . $upd->error);
                        $_SESSION['error'] = "Error técnico al cancelar cita";
                    }
                    $upd->close();
                }
                $stmt->close();
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
                $stmt = $this->conn->prepare("SELECT * FROM citas WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $citaAnterior = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($citaAnterior && $citaAnterior['estado'] === 'cancelada') {
                    $_SESSION['error'] = 'No se puede reactivar una cita cancelada. Cree una nueva cita para continuar.';
                    header('Location: ../pages/citas.php');
                    exit();
                }

                if ($citaAnterior && $citaAnterior['estado'] !== $nuevo_estado) {
                    $tipo_cambio = $nuevo_estado === 'cancelada' ? 'cancelacion' : 'modificacion';

                    $hist = $this->conn->prepare(
                        "INSERT INTO citas_historial(
                            cita_id, tipo_cambio, observacion,
                            anterior_paciente_id, anterior_medico_id, anterior_fecha, anterior_hora, anterior_motivo, anterior_estado,
                            nuevo_paciente_id, nuevo_medico_id, nuevo_fecha, nuevo_hora, nuevo_motivo, nuevo_estado
                        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                    );
                    $obs = 'Cambio de estado desde el listado';
                    $hist->bind_param("issiiiiisssssss",
                        $id, $tipo_cambio, $obs,
                        $citaAnterior['paciente_id'], $citaAnterior['medico_id'], $citaAnterior['fecha'], $citaAnterior['hora'], $citaAnterior['motivo'], $citaAnterior['estado'],
                        $citaAnterior['paciente_id'], $citaAnterior['medico_id'], $citaAnterior['fecha'], $citaAnterior['hora'], $citaAnterior['motivo'], $nuevo_estado
                    );
                    $hist->execute();
                    $hist->close();

                    $upd = $this->conn->prepare("UPDATE citas SET estado=? WHERE id=?");
                    $upd->bind_param("si", $nuevo_estado, $id);
                    if ($upd->execute()) {
                        $_SESSION['success'] = 'Estado actualizado exitosamente';
                    } else {
                        $_SESSION['error'] = 'Error técnico al actualizar el estado';
                    }
                    $upd->close();
                }
            }

            header('Location: ../pages/citas.php');
            exit();
        }

        // Eliminar cita vía POST (no debería usarse en versión productiva)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $this->conn->prepare("DELETE FROM citas WHERE id=?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Cita eliminada exitosamente";
                } else {
                    error_log("[CITAS] error eliminar cita id=$id: " . $stmt->error);
                    $_SESSION['error'] = "Error técnico al eliminar cita";
                }
                $stmt->close();
            }
            header("Location: ../pages/citas.php");
            exit();
        }
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM citas WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row;
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