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

    private function validarContrasena(string $pass): ?string
    {
        if (strlen($pass) < 8)
            return 'La contraseña debe tener mínimo 8 caracteres';
        if (!preg_match('/[A-Z]/', $pass))
            return 'La contraseña debe contener al menos una mayúscula';
        if (!preg_match('/[a-z]/', $pass))
            return 'La contraseña debe contener al menos una minúscula';
        if (!preg_match('/[0-9]/', $pass))
            return 'La contraseña debe contener al menos un número';
        if (!preg_match('/[@$!%*?&]/', $pass))
            return 'La contraseña debe contener al menos un carácter especial (@$!%*?&)';
        return null;
    }

    public function handleRequest()
    {
        // Verificar token de sesión — solo se llega aquí desde el flujo de login
        if (!isset($_SESSION['activacion_id'], $_SESSION['activacion_usuario'])) {
            $_SESSION['error'] = "Debes iniciar sesión con tu contraseña temporal para activar tu cuenta";
            header("Location: ../pages/login.php");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id_usuario = (int) $_SESSION['activacion_id'];
            $contrasena = $_POST['contrasena'] ?? '';

            // Re-verificar en BD que el usuario sigue inactivo
            $stmt = $this->conn->prepare("SELECT id, estado FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                unset($_SESSION['activacion_id'], $_SESSION['activacion_usuario']);
                $_SESSION['error'] = "Usuario no encontrado";
                header("Location: ../pages/login.php");
                exit();
            }

            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user['estado'] === 'activo') {
                unset($_SESSION['activacion_id'], $_SESSION['activacion_usuario']);
                $_SESSION['error'] = "La cuenta ya está activa";
                header("Location: ../pages/login.php");
                exit();
            }

            // Validar formato de contraseña
            $errorPass = $this->validarContrasena($contrasena);
            if ($errorPass !== null) {
                $_SESSION['error'] = $errorPass;
                header("Location: ../pages/activarCuenta.php");
                exit();
            }

            // Hashear contraseña con BCrypt
            $hash = password_hash($contrasena, PASSWORD_BCRYPT);

            // Cambiar estado a activo y guardar hash
            $stmt = $this->conn->prepare("UPDATE usuarios SET contrasena = ?, estado = 'activo' WHERE id = ?");
            $stmt->bind_param("si", $hash, $user['id']);

            if ($stmt->execute()) {
                unset($_SESSION['activacion_id'], $_SESSION['activacion_usuario']);
                $_SESSION['success'] = "Cuenta activada exitosamente";
                header("Location: ../pages/login.php");
                exit();
            } else {
                $_SESSION['error'] = "Error al activar la cuenta";
                header("Location: ../pages/activarCuenta.php");
            }

            $stmt->close();
            exit();
        }
    }
}
