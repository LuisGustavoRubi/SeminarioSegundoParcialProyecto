<?php

class CitasHistorialController
{

    private $conn;

    public function __construct($conexion)
    {
        $this->conn = $conexion;
    }
    // todo el hitorial
    public function obtenerHistorial()
    {
        $sql = "SELECT 
        h.id,
        h.tipo_cambio,
        h.observacion,
        h.fecha_cambio,

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

        LEFT JOIN pacientes p1 
        ON h.anterior_paciente_id = p1.id

        LEFT JOIN pacientes p2 
        ON h.nuevo_paciente_id = p2.id

        LEFT JOIN medicos m1 
        ON h.anterior_medico_id = m1.id

        LEFT JOIN medicos m2 
        ON h.nuevo_medico_id = m2.id

        ORDER BY h.fecha_cambio DESC";

        $resultado = $this->conn->query($sql);

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }


    // Historial por paciente
    public function buscarPorPaciente($paciente_id)
    {
        $busqueda = $this->conn->prepare("
        SELECT 
        h.id,
        h.tipo_cambio,
        h.observacion,
        h.fecha_cambio,

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

        LEFT JOIN pacientes p1 
        ON h.anterior_paciente_id = p1.id

        LEFT JOIN pacientes p2 
        ON h.nuevo_paciente_id = p2.id

        LEFT JOIN medicos m1 
        ON h.anterior_medico_id = m1.id

        LEFT JOIN medicos m2 
        ON h.nuevo_medico_id = m2.id

        WHERE h.anterior_paciente_id = ? 
        OR h.nuevo_paciente_id = ?

        ORDER BY h.fecha_cambio DESC
    ");

        $busqueda->bind_param("ii", $paciente_id, $paciente_id);
        $busqueda->execute();

        return $busqueda->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Busqueda de pacientes del combobox
    public function obtenerPacientes()
    {
        return $this->conn->query("SELECT * FROM pacientes ORDER BY apellido, nombre");
    }
    public function buscarPorNombre($nombre)
{
    $busqueda = $this->conn->prepare("
    SELECT 
    h.id,
    h.tipo_cambio,
    h.observacion,
    h.fecha_cambio,

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

    LEFT JOIN pacientes p1 ON h.anterior_paciente_id = p1.id
    LEFT JOIN pacientes p2 ON h.nuevo_paciente_id = p2.id
    LEFT JOIN medicos m1 ON h.anterior_medico_id = m1.id
    LEFT JOIN medicos m2 ON h.nuevo_medico_id = m2.id

    WHERE p2.nombre LIKE ? 
    OR p2.apellido LIKE ?
    OR CONCAT(p2.nombre, ' ', p2.apellido) LIKE ?

    ORDER BY h.fecha_cambio DESC
    ");

    $termino = "%$nombre%";
    $busqueda->bind_param("sss", $termino, $termino, $termino);
    $busqueda->execute();

    return $busqueda->get_result()->fetch_all(MYSQLI_ASSOC);
}
}