<?php

class CitasHistorialController
{
    private $conn;

    public function __construct($conexion)
    {
        $this->conn = $conexion;
    }

    private function queryBase(string $where = ''): array
    {
        $sql = "SELECT
                h.id,
                h.tipo_cambio,
                h.observacion,
                h.fecha_cambio,

                u.usuario AS usuario_responsable,
                e.nombre AS enfermedad,
                l.nombre AS localidad,

                p1.nombre AS paciente_anterior_nombre,
                p1.apellido AS paciente_anterior_apellido,
                p2.nombre AS paciente_nuevo_nombre,
                p2.apellido AS paciente_nuevo_apellido,

                m1.nombre AS medico_anterior_nombre,
                m1.apellido AS medico_anterior_apellido,
                m2.nombre AS medico_nuevo_nombre,
                m2.apellido AS medico_nuevo_apellido,

                h.anterior_fecha,
                h.anterior_hora,
                h.nuevo_fecha,
                h.nuevo_hora,
                h.anterior_estado,
                h.nuevo_estado

            FROM citas_historial h

            LEFT JOIN usuarios u ON h.usuario_id = u.id
            LEFT JOIN enfermedades e ON h.enfermedad_id = e.id
            LEFT JOIN localidades l ON h.localidad_id = l.id
            LEFT JOIN pacientes p1 ON h.anterior_paciente_id = p1.id
            LEFT JOIN pacientes p2 ON h.nuevo_paciente_id = p2.id
            LEFT JOIN medicos m1 ON h.anterior_medico_id = m1.id
            LEFT JOIN medicos m2 ON h.nuevo_medico_id = m2.id

            $where
            ORDER BY h.fecha_cambio DESC";

        $resultado = $this->conn->query($sql);
        if ($resultado === false) return [];

        $rows = $resultado->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as &$row) {
            $row['medicamentos'] = $this->obtenerMedicamentosDeHistorial($row['id']);
        }

        return $rows;
    }

    // Medicamentos del historial
    private function obtenerMedicamentosDeHistorial(int $historial_id): array
    {
        $stmt = $this->conn->prepare(
            "SELECT m.nombre FROM citas_historial_medicamentos hm
            INNER JOIN medicamentos m ON hm.medicamento_id = m.id
            WHERE hm.historial_id = ?
            ORDER BY m.nombre ASC"
        );
        $stmt->bind_param("i", $historial_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_column($result, 'nombre');
    }

    // Todo el historial
    public function obtenerHistorial(): array
    {
        $rol = $_SESSION['rol'] ?? '';
        $medicoId = intval($_SESSION['medico_id'] ?? 0);
        $where = ($rol === 'empleado' && $medicoId > 0) ? "WHERE (h.anterior_medico_id = $medicoId OR h.nuevo_medico_id = $medicoId)" : '';

        return $this->queryBase($where);
    }

    // Historial por paciente id
    public function buscarPorPaciente(int $paciente_id): array
    {
        $where = "WHERE (h.anterior_paciente_id = $paciente_id OR h.nuevo_paciente_id = $paciente_id)";
        return $this->queryBase($where);
    }

    // Historial por nombre de paciente
    public function buscarPorNombre(string $nombre): array
    {
        $rol = $_SESSION['rol'] ?? '';
        $medicoId = intval($_SESSION['medico_id'] ?? 0);
        $esMedico = ($rol === 'empleado' && $medicoId > 0);
        $andMedico = $esMedico ? "AND (h.anterior_medico_id = $medicoId OR h.nuevo_medico_id = $medicoId)" : '';

        $termino = '%' . $this->conn->real_escape_string($nombre) . '%';

        $sql = "SELECT
                h.id,
                h.tipo_cambio,
                h.observacion,
                h.fecha_cambio,

                u.usuario AS usuario_responsable,
                e.nombre AS enfermedad,
                l.nombre AS localidad,

                p1.nombre AS paciente_anterior_nombre,
                p1.apellido AS paciente_anterior_apellido,
                p2.nombre AS paciente_nuevo_nombre,
                p2.apellido AS paciente_nuevo_apellido,
                m1.nombre AS medico_anterior_nombre,
                m1.apellido AS medico_anterior_apellido,
                m2.nombre AS medico_nuevo_nombre,
                m2.apellido AS medico_nuevo_apellido,
                h.anterior_fecha,
                h.anterior_hora,
                h.nuevo_fecha,
                h.nuevo_hora,
                h.anterior_estado,
                h.nuevo_estado

            FROM citas_historial h

            LEFT JOIN usuarios u ON h.usuario_id = u.id
            LEFT JOIN enfermedades e ON h.enfermedad_id = e.id
            LEFT JOIN localidades l ON h.localidad_id = l.id
            LEFT JOIN pacientes p1 ON h.anterior_paciente_id = p1.id
            LEFT JOIN pacientes p2 ON h.nuevo_paciente_id = p2.id
            LEFT JOIN medicos m1 ON h.anterior_medico_id = m1.id
            LEFT JOIN medicos m2 ON h.nuevo_medico_id = m2.id

            WHERE (
                p2.nombre LIKE '$termino'
                OR p2.apellido LIKE '$termino'
                OR CONCAT(p2.nombre, ' ', p2.apellido) LIKE '$termino'
            )
            $andMedico
            ORDER BY h.fecha_cambio DESC";

        $resultado = $this->conn->query($sql);
        if ($resultado === false) return [];

        $rows = $resultado->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$row) {
            $row['medicamentos'] = $this->obtenerMedicamentosDeHistorial($row['id']);
        }
        return $rows;
    }

    public function obtenerPacientes()
    {
        return $this->conn->query("SELECT * FROM pacientes ORDER BY apellido, nombre");
    }
}