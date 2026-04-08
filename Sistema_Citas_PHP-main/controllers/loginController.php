<?php
session_start();
require_once '../includes/config.php';

class LoginController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function login()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario = trim($_POST['usuario'] ?? '');
            $password = trim($_POST['password'] ?? '');

            // Si usuario existe
            $stmt = $this->conn->prepare("
                SELECT u.id, u.usuario, u.contrasena, u.estado, u.medico_id, u.rol_id, r.nombre AS rol FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                WHERE u.usuario = ?
            ");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            // No existe usuaruo
            if ($result->num_rows === 0) {
                $_SESSION['error'] = "El usuario no existe";
                header("Location: ../pages/login.php");
                exit();
            }

            $user = $result->fetch_assoc();

            // Usuario inactivo: validar contraseña default antes de permitir activación
            if ($user['estado'] !== 'activo') {
                if ($password !== $user['contrasena']) {
                    $_SESSION['error'] = "Credenciales incorrectas";
                    header("Location: ../pages/login.php");
                    exit();
                }
                // Credenciales default correctas → iniciar flujo de activación
                $_SESSION['activacion_id']      = $user['id'];
                $_SESSION['activacion_usuario'] = $user['usuario'];
                header("Location: ../pages/activarCuenta.php");
                exit();
            }

            // Cuenta activa: validar con hash BCrypt
            if (!password_verify($password, $user['contrasena'])) {
                $_SESSION['error'] = "Credenciales incorrectas";
                header("Location: ../pages/login.php");
                exit();
            }

            //Esto es solo para probar
            // if ($password !== $user['contrasena']) {
            //     $_SESSION['error'] = "Credenciales incorrectas";
            //     header("Location: ../pages/login.php");
            //     exit();
            // }

            // Guardar datos de usuario logeado
            $_SESSION['usuario']   = $user['usuario'];
            $_SESSION['rol']       = $user['rol'];
            $_SESSION['rol_id']    = (int) $user['rol_id'];
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['medico_id'] = $user['medico_id']; // null si es jefe

            header("Location: ../index.php");
            exit();
        }
    }
}
