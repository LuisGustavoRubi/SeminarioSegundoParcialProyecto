<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

    private function usuarioId(): int
    {
        return intval($_SESSION['user_id'] ?? 0);
    }

    // Redirige con error y conserva form_data en sesion — reemplaza el bloque duplicado que aparecia 4+ veces
    private function redirectWithError(string $mensaje, string $action, int $id = 0): void
    {
        $_SESSION['error']     = $mensaje;
        $_SESSION['form_data'] = $_POST;
        $url = $action === 'create'
            ? '../pages/citas.php?action=new'
            : '../pages/citas.php?action=edit&id=' . $id;
        header("Location: $url");
        exit();
    }

    // Tipo de cambio
    private function determinarTipoCambio(array $anterior, array $nuevo): string
    {
        if ($nuevo['estado'] === 'cancelada') {
            return 'cancelacion';
        }
        if ($anterior['fecha'] !== $nuevo['fecha'] || $anterior['hora'] !== $nuevo['hora']) {
            return 'reprogramacion';
        }
        return 'modificacion';
    }

    // Insertar en el historial — usa prepared statement para evitar inyeccion SQL
    private function insertarHistorial(array $datos): int
    {
        $usuario_id    = $this->usuarioId();
        $enfermedad_id = intval($datos['enfermedad_id'] ?? 0);
        $localidad_id  = intval($datos['localidad_id']  ?? 0);

        $stmt = $this->conn->prepare(
            "INSERT INTO citas_historial (
                cita_id, usuario_id, tipo_cambio, observacion, enfermedad_id, localidad_id,
                anterior_paciente_id, anterior_medico_id, anterior_fecha, anterior_hora, anterior_motivo, anterior_estado,
                nuevo_paciente_id,   nuevo_medico_id,    nuevo_fecha,    nuevo_hora,    nuevo_motivo,    nuevo_estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $enfNullable = $enfermedad_id > 0 ? $enfermedad_id : null;
        
        $stmt->bind_param(
            'iissiiiissssiissss',
            $datos['cita_id'],
            $usuario_id,
            $datos['tipo_cambio'],
            $datos['observacion'],
            $enfNullable,
            $localidad_id,
            $datos['ant_paciente'],
            $datos['ant_medico'],
            $datos['ant_fecha'],
            $datos['ant_hora'],
            $datos['ant_motivo'],
            $datos['ant_estado'],
            $datos['nvo_paciente'],
            $datos['nvo_medico'],
            $datos['nvo_fecha'],
            $datos['nvo_hora'],
            $datos['nvo_motivo'],
            $datos['nvo_estado']
        );

        $stmt->execute();
        return $this->conn->insert_id;
    }

    // Medicamentos en el historial — prepared statement
    private function insertarMedicamentosHistorial(int $historial_id, array $med_ids): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO citas_historial_medicamentos (historial_id, medicamento_id) VALUES (?, ?)"
        );
        foreach ($med_ids as $med_id) {
            $med_id = intval($med_id);
            if ($med_id > 0) {
                $stmt->bind_param('ii', $historial_id, $med_id);
                $stmt->execute();
            }
        }
    }

    // Restar del stock el medicamento usado — prepared statement
    private function descontarStock(int $localidad_id, array $med_ids): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE localidad_medicamentos
             SET stock = GREATEST(0, stock - 1)
             WHERE localidad_id = ? AND medicamento_id = ?"
        );
        foreach ($med_ids as $med_id) {
            $med_id = intval($med_id);
            if ($med_id > 0) {
                $stmt->bind_param('ii', $localidad_id, $med_id);
                $stmt->execute();
            }
        }
    }

    public function handleRequest()
    {
        // Medicamentos disponibles por localidad (endpoint JSON)
        if (isset($_GET['action']) && $_GET['action'] === 'getMedicamentos' && isset($_GET['localidad_id'])) {
            $meds   = $this->obtenerMedicamentosPorLocalidad($_GET['localidad_id']);
            $result = [];
            while ($row = $meds->fetch_assoc()) {
                $result[] = $row;
            }
            header('Content-Type: application/json');
            echo json_encode($result);
            exit();
        }

        // CREATE Y UPDATE
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] !== 'changeStatus') {

            $paciente_id  = $_POST['paciente_id']  ?? '';
            $medico_id    = $_POST['medico_id']     ?? '';
            $localidad_id = $_POST['localidad_id']  ?? '';
            $fecha        = $_POST['fecha']          ?? '';
            $hora         = $_POST['hora']           ?? '';
            $motivo       = trim($_POST['motivo']    ?? '');
            $estado       = $_POST['estado']         ?? 'pendiente';
            $action       = $_POST['action'];
            $id           = intval($_POST['id']      ?? 0);

            if ($this->esMedico() && $this->miMedicoId() > 0) {
                $medico_id = $this->miMedicoId();
            }

            // Validacion campos obligatorios
            $camposFaltantes = [];
            if (empty($paciente_id))  $camposFaltantes[] = 'Paciente';
            if (empty($medico_id))    $camposFaltantes[] = 'Médico';
            if (empty($localidad_id)) $camposFaltantes[] = 'Localidad';
            if (empty($fecha))        $camposFaltantes[] = 'Fecha';
            if (empty($hora))         $camposFaltantes[] = 'Hora';
            if ($motivo === '')        $camposFaltantes[] = 'Motivo de consulta';

            if (!empty($camposFaltantes)) {
                $this->redirectWithError(
                    'Los siguientes campos son obligatorios: ' . implode(', ', $camposFaltantes),
                    $action,
                    $id
                );
            }

            if (mb_strlen($motivo) < 10) {
                $this->redirectWithError(
                    'El motivo de consulta debe tener al menos 10 caracteres.',
                    $action,
                    $id
                );
            }

            $ahoraHN = new DateTime('now', new DateTimeZone('America/Tegucigalpa'));

            if ($fecha < $ahoraHN->format('Y-m-d')) {
                $this->redirectWithError('No se pueden agendar citas en fechas pasadas.', $action, $id);
            }

            if ($fecha === $ahoraHN->format('Y-m-d') && $hora < $ahoraHN->format('H:i')) {
                $this->redirectWithError('No se pueden agendar citas para horas que ya pasaron.', $action, $id);
            }

            // CREATE
            if ($action === 'create') {
                $intervaloSeg = 30 * 60;
                $stmt = $this->conn->prepare(
                    "SELECT id FROM citas
                     WHERE medico_id = ? AND fecha = ? AND estado != 'cancelada' AND ABS(TIME_TO_SEC(TIMEDIFF(hora, ?))) < ?"
                );
                $stmt->bind_param('issi', $medico_id, $fecha, $hora, $intervaloSeg);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $this->redirectWithError(
                        'El médico ya tiene una cita dentro de los 30 minutos de ese horario.',
                        'create'
                    );
                }

                // INSERT con prepared statement — elimina riesgo de inyeccion SQL
                $stmt = $this->conn->prepare(
                    "INSERT INTO citas (paciente_id, medico_id, localidad_id, fecha, hora, motivo, estado)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('iiissss', $paciente_id, $medico_id, $localidad_id, $fecha, $hora, $motivo, $estado);

                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Cita agendada exitosamente';
                } else {
                    $_SESSION['error'] = 'Error al agendar cita';
                }

                header('Location: ../pages/citas.php');
                exit();
            }

            // UPDATE — bloque unificado: valida, actualiza cita y guarda medicamentos en un solo flujo
            if ($action === 'update') {

                if ($this->esMedico()) {
                    $chk = $this->conn->prepare("SELECT medico_id FROM citas WHERE id = ?");
                    $chk->bind_param('i', $id);
                    $chk->execute();
                    $ownCheck = $chk->get_result()->fetch_assoc();
                    if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                        $_SESSION['error'] = 'No tiene permiso para modificar esta cita.';
                        header("Location: ../pages/citas.php?action=edit&id=$id");
                        exit();
                    }
                }

                $stmtCita = $this->conn->prepare("SELECT * FROM citas WHERE id = ?");
                $stmtCita->bind_param('i', $id);
                $stmtCita->execute();
                $citaAnterior = $stmtCita->get_result()->fetch_assoc();

                if ($citaAnterior && in_array($citaAnterior['estado'], ['cancelada', 'completada'])) {
                    $_SESSION['error'] = 'No se puede modificar una cita ' . $citaAnterior['estado'] . '.';
                    header("Location: ../pages/citas.php?action=edit&id=$id");
                    exit();
                }

                $intervaloSeg = 30 * 60;
                $chkHorario = $this->conn->prepare(
                    "SELECT id FROM citas
                     WHERE medico_id = ? AND fecha = ? AND estado != 'cancelada' AND ABS(TIME_TO_SEC(TIMEDIFF(hora, ?))) < ? AND id != ?"
                );
                $chkHorario->bind_param('issii', $medico_id, $fecha, $hora, $intervaloSeg, $id);
                $chkHorario->execute();
                if ($chkHorario->get_result()->num_rows > 0) {
                    $this->redirectWithError(
                        'El médico ya tiene una cita dentro de los 30 minutos de ese horario.',
                        'update',
                        $id
                    );
                }

                $tipo_cambio = $this->determinarTipoCambio(
                    $citaAnterior,
                    ['fecha' => $fecha, 'hora' => $hora, 'estado' => $estado]
                );

                $enfermedad_id = intval($_POST['enfermedad_id'] ?? 0);
                $med_ids = !empty($_POST['medicamentos'])
                    ? array_filter(array_map('intval', explode(',', $_POST['medicamentos'])))
                    : [];
                $observacion = $_POST['observacion'] ?? '';

                $historial_id = $this->insertarHistorial([
                    'cita_id'      => $id,
                    'tipo_cambio'  => $tipo_cambio,
                    'observacion'  => $observacion,
                    'enfermedad_id' => $enfermedad_id,
                    'localidad_id' => $localidad_id,
                    'ant_paciente' => $citaAnterior['paciente_id'],
                    'ant_medico'   => $citaAnterior['medico_id'],
                    'ant_fecha'    => $citaAnterior['fecha'],
                    'ant_hora'     => $citaAnterior['hora'],
                    'ant_motivo'   => $citaAnterior['motivo'],
                    'ant_estado'   => $citaAnterior['estado'],
                    'nvo_paciente' => $paciente_id,
                    'nvo_medico'   => $medico_id,
                    'nvo_fecha'    => $fecha,
                    'nvo_hora'     => $hora,
                    'nvo_motivo'   => $motivo,
                    'nvo_estado'   => $estado,
                ]);

                // Guardar medicamentos dentro del mismo bloque update — antes era codigo inalcanzable
                if ($estado === 'completada' && !empty($med_ids)) {
                    $this->insertarMedicamentosHistorial($historial_id, $med_ids);
                    $this->descontarStock(intval($localidad_id), $med_ids);
                }

                // Incluir enfermedad_id en el UPDATE cuando el estado pasa a completada
                if ($estado === 'completada' && $enfermedad_id > 0) {
                    $stmtUpd = $this->conn->prepare("UPDATE citas SET paciente_id = ?, medico_id = ?, localidad_id = ?, fecha = ?, hora = ?, motivo = ?, estado = ?, enfermedad_id = ? WHERE id = ?");
                    $stmtUpd->bind_param('iiissssii', $paciente_id, $medico_id, $localidad_id, $fecha, $hora, $motivo, $estado, $enfermedad_id, $id);
                } else {
                    $stmtUpd = $this->conn->prepare("UPDATE citas SET paciente_id = ?, medico_id = ?, localidad_id = ?, fecha = ?, hora = ?, motivo = ?, estado = ? WHERE id = ?");
                    $stmtUpd->bind_param('iiissssi', $paciente_id, $medico_id, $localidad_id, $fecha, $hora, $motivo, $estado, $id);
                }

                if ($stmtUpd->execute()) {
                    $_SESSION['success'] = 'Cita actualizada exitosamente';
                } else {
                    $_SESSION['error'] = 'Error al actualizar cita';
                }

                header('Location: ../pages/citas.php');
                exit();
            }
        }

        // CANCEL
        if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
            $id = intval($_GET['id']);

            if ($this->esMedico()) {
                $chk = $this->conn->prepare("SELECT medico_id FROM citas WHERE id = ?");
                $chk->bind_param('i', $id);
                $chk->execute();
                $ownCheck = $chk->get_result()->fetch_assoc();
                if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                    $_SESSION['error'] = 'No tiene permiso para cancelar esta cita.';
                    header('Location: ../pages/citas.php');
                    exit();
                }
            }

            $stmtCita = $this->conn->prepare("SELECT * FROM citas WHERE id = ?");
            $stmtCita->bind_param('i', $id);
            $stmtCita->execute();
            $cita = $stmtCita->get_result()->fetch_assoc();

            $this->insertarHistorial([
                'cita_id'      => $id,
                'tipo_cambio'  => 'cancelacion',
                'observacion'  => 'Cita cancelada',
                'enfermedad_id' => 0,
                'localidad_id' => $cita['localidad_id'],
                'ant_paciente' => $cita['paciente_id'],
                'ant_medico'   => $cita['medico_id'],
                'ant_fecha'    => $cita['fecha'],
                'ant_hora'     => $cita['hora'],
                'ant_motivo'   => $cita['motivo'],
                'ant_estado'   => $cita['estado'],
                'nvo_paciente' => $cita['paciente_id'],
                'nvo_medico'   => $cita['medico_id'],
                'nvo_fecha'    => $cita['fecha'],
                'nvo_hora'     => $cita['hora'],
                'nvo_motivo'   => $cita['motivo'],
                'nvo_estado'   => 'cancelada',
            ]);

            $stmt = $this->conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Cita cancelada exitosamente';
            } else {
                $_SESSION['error'] = 'Error al cancelar cita';
            }

            header('Location: ../pages/citas.php');
            exit();
        }

        // CHANGE STATUS
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'changeStatus') {
            $id            = intval($_POST['id']           ?? 0);
            $nuevo_estado  = $_POST['estado']              ?? '';
            $enfermedad_id = intval($_POST['enfermedad_id'] ?? 0);
            $med_ids = !empty($_POST['medicamentos'])
                ? array_filter(array_map('intval', explode(',', $_POST['medicamentos'])))
                : [];
            $estados_validos = ['pendiente', 'completada', 'cancelada'];

            if ($id > 0 && in_array($nuevo_estado, $estados_validos)) {

                if ($this->esMedico()) {
                    $chk = $this->conn->prepare("SELECT medico_id FROM citas WHERE id = ?");
                    $chk->bind_param('i', $id);
                    $chk->execute();
                    $ownCheck = $chk->get_result()->fetch_assoc();
                    if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                        $_SESSION['error'] = 'No tiene permiso para cambiar el estado de esta cita.';
                        header('Location: ../pages/citas.php');
                        exit();
                    }
                }

                $stmtCita = $this->conn->prepare("SELECT * FROM citas WHERE id = ?");
                $stmtCita->bind_param('i', $id);
                $stmtCita->execute();
                $citaAnterior = $stmtCita->get_result()->fetch_assoc();

                if ($citaAnterior && in_array($citaAnterior['estado'], ['cancelada', 'completada'])) {
                    $_SESSION['error'] = 'No se puede cambiar el estado de una cita ' . $citaAnterior['estado'] . '.';
                    header('Location: ../pages/citas.php');
                    exit();
                }

                if ($nuevo_estado === 'completada' && $enfermedad_id <= 0) {
                    $_SESSION['error'] = 'Debe seleccionar una enfermedad para completar la cita.';
                    header('Location: ../pages/citas.php');
                    exit();
                }

                if ($citaAnterior && $citaAnterior['estado'] !== $nuevo_estado) {
                    $historial_id = $this->insertarHistorial([
                        'cita_id'      => $id,
                        'tipo_cambio'  => ($nuevo_estado === 'cancelada' ? 'cancelacion' : 'modificacion'),
                        'observacion'  => "Cambio de estado a $nuevo_estado",
                        'enfermedad_id' => $enfermedad_id,
                        'localidad_id' => $citaAnterior['localidad_id'],
                        'ant_paciente' => $citaAnterior['paciente_id'],
                        'ant_medico'   => $citaAnterior['medico_id'],
                        'ant_fecha'    => $citaAnterior['fecha'],
                        'ant_hora'     => $citaAnterior['hora'],
                        'ant_motivo'   => $citaAnterior['motivo'],
                        'ant_estado'   => $citaAnterior['estado'],
                        'nvo_paciente' => $citaAnterior['paciente_id'],
                        'nvo_medico'   => $citaAnterior['medico_id'],
                        'nvo_fecha'    => $citaAnterior['fecha'],
                        'nvo_hora'     => $citaAnterior['hora'],
                        'nvo_motivo'   => $citaAnterior['motivo'],
                        'nvo_estado'   => $nuevo_estado,
                    ]);

                    if ($nuevo_estado === 'completada' && !empty($med_ids)) {
                        $this->insertarMedicamentosHistorial($historial_id, $med_ids);
                        $this->descontarStock(intval($citaAnterior['localidad_id']), $med_ids);
                    }

                    // Incluir enfermedad_id en el UPDATE cuando el estado pasa a completada
                    if ($nuevo_estado === 'completada') {
                        $stmt = $this->conn->prepare("UPDATE citas SET estado = ?, enfermedad_id = ? WHERE id = ?");
                        $stmt->bind_param('sii', $nuevo_estado, $enfermedad_id, $id);
                    } else {
                        $stmt = $this->conn->prepare("UPDATE citas SET estado = ? WHERE id = ?");
                        $stmt->bind_param('si', $nuevo_estado, $id);
                    }

                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Estado actualizado a $nuevo_estado";
                    } else {
                        $_SESSION['error'] = 'Error al actualizar estado';
                    }
                }
            }

            header('Location: ../pages/citas.php');
            exit();
        }

        // DELETE
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $id = intval($_GET['id']);

            if ($this->esMedico()) {
                $chk = $this->conn->prepare("SELECT medico_id FROM citas WHERE id = ?");
                $chk->bind_param('i', $id);
                $chk->execute();
                $ownCheck = $chk->get_result()->fetch_assoc();
                if (!$ownCheck || $ownCheck['medico_id'] != $this->miMedicoId()) {
                    $_SESSION['error'] = 'No tiene permiso para eliminar esta cita.';
                    header('Location: ../pages/citas.php');
                    exit();
                }
            }

            $stmt = $this->conn->prepare("DELETE FROM citas WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Cita eliminada exitosamente';
            } else {
                $_SESSION['error'] = 'Error al eliminar cita';
            }

            header('Location: ../pages/citas.php');
            exit();
        }
    }

    // ── Queries de lectura ──────────────────────────────────────────────────

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

    // Devuelve array PHP en lugar de MySQLi result — elimina la necesidad de data_seek(0)
    public function obtenerEnfermedades(): array
    {
        $result = $this->conn->query("SELECT * FROM enfermedades ORDER BY nombre");
        $filas  = [];
        while ($fila = $result->fetch_assoc()) {
            $filas[] = $fila;
        }
        return $filas;
    }

    public function obtenerLocalidades()
    {
        return $this->conn->query("SELECT * FROM localidades ORDER BY nombre");
    }

    public function obtenerMedicamentos()
    {
        return $this->conn->query("SELECT id, nombre FROM medicamentos ORDER BY nombre ASC");
    }

    public function obtenerMedicamentosPorLocalidad($localidad_id)
    {
        $localidad_id = intval($localidad_id);
        return $this->conn->query(
            "SELECT m.id, m.nombre, lm.stock
             FROM medicamentos m
             INNER JOIN localidad_medicamentos lm ON lm.medicamento_id = m.id
             WHERE lm.localidad_id = $localidad_id AND lm.stock > 0
             ORDER BY m.nombre ASC"
        );
    }

    public function obtenerTodas()
    {
        $where = ($this->esMedico() && $this->miMedicoId() > 0)
            ? 'WHERE c.medico_id = ' . $this->miMedicoId()
            : '';
        $sql = "SELECT c.*,
                CONCAT(p.nombre, ' ', p.apellido) AS paciente_nombre,
                CONCAT(m.nombre, ' ', m.apellido) AS medico_nombre,
                m.especialidad,
                l.nombre AS localidad
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                INNER JOIN medicos   m ON c.medico_id   = m.id
                INNER JOIN localidades l ON c.localidad_id = l.id
                $where
                ORDER BY c.fecha DESC, c.hora DESC";
        return $this->conn->query($sql);
    }
}