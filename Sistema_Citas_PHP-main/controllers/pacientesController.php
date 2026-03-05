<?php
session_start();
require_once '../includes/config.php';

class PacienteController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {

        // Crear / Actualizar registro
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

            $nombre           = $_POST['nombre'];
            $apellido         = $_POST['apellido'];
            $cedula           = $_POST['cedula'];
            $telefono         = $_POST['telefono'];
            $email            = $_POST['email'];
            $fecha_nacimiento = $_POST['fecha_nacimiento'];

            if ($_POST['action'] == 'create') {

                $check = $this->conn->query("SELECT id FROM pacientes WHERE cedula = '$cedula'");
                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "Ya existe un paciente con esta cédula";
                } else {
                    $sql = "INSERT INTO pacientes (nombre, apellido, cedula, telefono, email, fecha_nacimiento)
                            VALUES ('$nombre', '$apellido', '$cedula', '$telefono', '$email', '$fecha_nacimiento')";

                    if ($this->conn->query($sql)) {
                        $_SESSION['success'] = "Paciente registrado exitosamente";
                    } else {
                        $_SESSION['error'] = "Error al registrar paciente";
                    }
                }

                header("Location: ../pages/pacientes.php");
                exit();
            }

            // Actualizar registro
            if ($_POST['action'] == 'update') {

                $id = $_POST['id'];

                $check = $this->conn->query("SELECT id FROM pacientes WHERE cedula = '$cedula' AND id != $id");
                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "Ya existe otro paciente con esta cédula";
                } else {
                    $sql = "UPDATE pacientes
                            SET nombre='$nombre', apellido='$apellido', cedula='$cedula',
                                telefono='$telefono', email='$email', fecha_nacimiento='$fecha_nacimiento'
                            WHERE id=$id";

                    if ($this->conn->query($sql)) {
                        $_SESSION['success'] = "Paciente actualizado exitosamente";
                    } else {
                        $_SESSION['error'] = "Error al actualizar paciente";
                    }
                }

                header("Location: ../pages/pacientes.php");
                exit();
            }
        }

        // Eliminar registro
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {

            $id = $_GET['id'];

            if ($this->conn->query("DELETE FROM pacientes WHERE id=$id")) {
                $_SESSION['success'] = "Paciente eliminado exitosamente";
            } else {
                $_SESSION['error'] = "Error al eliminar paciente";
            }

            header("Location: ../pages/pacientes.php");
            exit();
        }
    }

    public function obtenerPorId($id) {
        $result = $this->conn->query("SELECT * FROM pacientes WHERE id=$id");
        return $result->fetch_assoc();
    }

    public function obtenerTodos() {
        return $this->conn->query("SELECT * FROM pacientes ORDER BY apellido, nombre");
    }
}