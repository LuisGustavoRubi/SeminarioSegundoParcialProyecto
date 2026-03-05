<?php
session_start();
require_once '../includes/config.php';

class MedicoController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {

        // Cear registro
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $especialidad = $_POST['especialidad'];
            $telefono = $_POST['telefono'];
            $email = $_POST['email'];

            if ($_POST['action'] == 'create') {

                $sql = "INSERT INTO medicos (nombre, apellido, especialidad, telefono, email) 
                        VALUES ('$nombre', '$apellido', '$especialidad', '$telefono', '$email')";

                if ($this->conn->query($sql)) {
                    $_SESSION['success'] = "Médico registrado exitosamente";
                } else {
                    $_SESSION['error'] = "Error al registrar médico";
                }

                header("Location: ../pages/medicos.php");
                exit();
            }

            // Actualizar registro
            if ($_POST['action'] == 'update') {

                $id = $_POST['id'];

                $sql = "UPDATE medicos 
                        SET nombre='$nombre', apellido='$apellido', especialidad='$especialidad',
                            telefono='$telefono', email='$email'
                        WHERE id=$id";

                if ($this->conn->query($sql)) {
                    $_SESSION['success'] = "Médico actualizado exitosamente";
                } else {
                    $_SESSION['error'] = "Error al actualizar médico";
                }

                header("Location: ../pages/medicos.php");
                exit();
            }
        }

        // Eliminar deberia no estar y pasar a cambiar/actualizar estados
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {

            $id = $_GET['id'];

            if ($this->conn->query("DELETE FROM medicos WHERE id=$id")) {
                $_SESSION['success'] = "Médico eliminado exitosamente";
            } else {
                $_SESSION['error'] = "Error al eliminar médico";
            }

            header("Location: ../pages/medicos.php");
            exit();
        }
    }

    public function obtenerPorId($id) {
        $result = $this->conn->query("SELECT * FROM medicos WHERE id=$id");
        return $result->fetch_assoc();
    }

    public function obtenerTodos() {
        return $this->conn->query("SELECT * FROM medicos ORDER BY apellido, nombre");
    }
}