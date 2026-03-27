<?php
session_start();
require_once '../includes/config.php';

class ActivacionController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function handleRequest()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario = $_POST['usuario'] ?? '';
            $contrasena = $_POST['contrasena'] ?? '';

            // Si existe usuario 
            $stmt = $this->conn->prepare("SELECT id, estado FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $_SESSION['error'] = "El usuario no existe";
                header("Location: ../pages/activarCuenta.php");
                exit();
            }

            $user = $result->fetch_assoc();
            $stmt->close();

            // Si esta activo
            if ($user['estado'] === 'activo') {
                $_SESSION['error'] = "El usuario ya está activo, no es necesaria la activación";
                header("Location: ../pages/activarCuenta.php");
                exit();
            }

            // Cambiar estado activo
            $stmt = $this->conn->prepare(" UPDATE usuarios SET contrasena = ?, estado = 'activo' WHERE id = ? ");
            $stmt->bind_param("si", $contrasena, $user['id']);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Cuenta activada";
                header("Location: ../pages/login.php");
                exit();
            } else {
                $_SESSION['error'] = "Error al activar cuenta";
                header("Location: ../pages/login.php");
            }

            $stmt->close();

            header("Location: ../pages/activarCuenta.php");
            exit();
        }
    }
}
