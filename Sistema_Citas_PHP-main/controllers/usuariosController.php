<?php
session_start();
require_once '../includes/config.php';

class UsuarioController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {

        // Eliminar registro (DEBE IR PRIMERO, antes de validaciones de campos)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
            $id = intval($_POST['id'] ?? 0);

            if ($id > 0) {
                $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Usuario eliminado exitosamente";
                } else {
                    $_SESSION['error'] = "Error al eliminar usuario";
                }
                $stmt->close();
            }

            header("Location: ../pages/usuarios.php");
            exit();
        }

        // Crear / Actualizar registro
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

            // Guardar datos del formulario para recuperación en caso de error
            $_SESSION['form_data'] = $_POST;

            $usuario   = trim($_POST['usuario']   ?? '');
            $medico_id = (!empty($_POST['medico_id']) && $_POST['medico_id'] !== '') ? intval($_POST['medico_id']) : null;

            // Validación de campos obligatorios
            $camposFaltantes = [];
            if ($usuario === '') $camposFaltantes[] = 'Usuario';

            if (!empty($camposFaltantes)) {
                $_SESSION['error'] = 'Los siguientes campos son obligatorios: ' . implode(', ', $camposFaltantes);
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/usuarios.php?action=new'
                    : '../pages/usuarios.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validar que el usuario tenga entre 4 y 20 caracteres
            if (strlen($usuario) < 4 || strlen($usuario) > 20) {
                $_SESSION['error'] = 'El nombre de usuario debe tener entre 4 y 20 caracteres';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/usuarios.php?action=new'
                    : '../pages/usuarios.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validar que el usuario solo contenga letras, números y guiones
            if (!preg_match('/^[a-zA-Z0-9_\-]{4,20}$/', $usuario)) {
                $_SESSION['error'] = 'El nombre de usuario solo puede contener letras, números, guiones y guiones bajos';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/usuarios.php?action=new'
                    : '../pages/usuarios.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validar que el médico existe si se seleccionó uno
            if ($medico_id !== null) {
                $stmtMedico = $this->conn->prepare("SELECT id FROM medicos WHERE id = ?");
                $stmtMedico->bind_param("i", $medico_id);
                $stmtMedico->execute();
                $stmtMedico->store_result();

                if ($stmtMedico->num_rows === 0) {
                    $_SESSION['error'] = 'El médico seleccionado no existe';
                    $stmtMedico->close();
                    header("Location: ../pages/usuarios.php?action=new");
                    exit();
                }
                $stmtMedico->close();
            }

            if ($_POST['action'] == 'create') {

                // Determinar rol basado en si se seleccionó médico
                // Si hay médico: rol_id = 1 (jefe/administrador)
                // Si no hay médico: rol_id = 2 (empleado)
                $rol_id = ($medico_id !== null) ? 1 : 2;

                // Verificar duplicados de usuario
                $dupCheck = $this->conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
                $dupCheck->bind_param("s", $usuario);
                $dupCheck->execute();
                $dupCheck->store_result();

                if ($dupCheck->num_rows > 0) {
                    $_SESSION['error'] = "Ya existe un usuario con el nombre '$usuario'. Verifique los datos.";
                    $dupCheck->close();
                    header("Location: ../pages/usuarios.php?action=new");
                    exit();
                }
                $dupCheck->close();

                // Contraseña predeterminada
                $contrasena = '1234';

                $stmt = $this->conn->prepare(
                    "INSERT INTO usuarios (usuario, contrasena, rol_id, medico_id, estado)
                     VALUES (?, ?, ?, ?, 'inactivo')"
                );
                $stmt->bind_param("ssii", $usuario, $contrasena, $rol_id, $medico_id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Usuario registrado exitosamente. Estado: Inactivo | Contraseña: $contrasena";
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = "Error al registrar usuario";
                }
                $stmt->close();

                header("Location: ../pages/usuarios.php");
                exit();
            }

            // Actualizar registro
            if ($_POST['action'] == 'update') {

                $id = intval($_POST['id']);

                // Verificar duplicados excluyendo el registro actual
                $dupCheck = $this->conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
                $dupCheck->bind_param("si", $usuario, $id);
                $dupCheck->execute();
                $dupCheck->store_result();

                if ($dupCheck->num_rows > 0) {
                    $_SESSION['error'] = "Ya existe otro usuario con el nombre '$usuario'.";
                    $dupCheck->close();
                    header("Location: ../pages/usuarios.php?action=edit&id=$id");
                    exit();
                }
                $dupCheck->close();

                // Determinar rol basado en si se seleccionó médico
                $rol_id = ($medico_id !== null) ? 1 : 2;

                $stmt = $this->conn->prepare(
                    "UPDATE usuarios SET usuario = ?, rol_id = ?, medico_id = ? WHERE id = ?"
                );
                $stmt->bind_param("siii", $usuario, $rol_id, $medico_id, $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Usuario actualizado exitosamente";
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = "Error al actualizar usuario";
                }
                $stmt->close();

                header("Location: ../pages/usuarios.php");
                exit();
            }
        }

        // Eliminar registro
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
            $id = intval($_POST['id'] ?? 0);

            if ($id > 0) {
                $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Usuario eliminado exitosamente";
                } else {
                    $_SESSION['error'] = "Error al eliminar usuario";
                }
                $stmt->close();
            }

            header("Location: ../pages/usuarios.php");
            exit();
        }
    }

    /**
     * Obtener todos los médicos para el combobox
     */
    public function obtenerMedicos() {
        $stmt = $this->conn->prepare(
            "SELECT id, CONCAT(nombre, ' ', apellido, ' - ', especialidad) as descripcion 
             FROM medicos 
             ORDER BY nombre ASC"
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $medicos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $medicos;
    }

    /**
     * Obtener un usuario por ID
     */
    public function obtenerPorId($id) {
        $id = intval($id);
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.usuario, u.rol_id, u.medico_id, u.estado, u.fecha_creacion
             FROM usuarios u
             WHERE u.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $usuario = $result->fetch_assoc();
        $stmt->close();
        return $usuario;
    }

    /**
     * Obtener todos los usuarios con información relacionada
     */
    public function obtenerTodos() {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.usuario, u.rol_id, r.nombre as rol, u.medico_id, 
                    CONCAT(m.nombre, ' ', m.apellido) as medico, u.estado, u.fecha_creacion
             FROM usuarios u
             JOIN roles r ON u.rol_id = r.id
             LEFT JOIN medicos m ON u.medico_id = m.id
             ORDER BY u.fecha_creacion DESC"
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $usuarios;
    }
}
?>
