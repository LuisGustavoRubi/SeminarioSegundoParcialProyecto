<?php
/**
 * controllers/pacientes_controller.php
 * Controlador para el módulo de Pacientes.
 * Maneja toda la lógica de negocio (CRUD) y redirige a la vista.
 */

session_start();
require_once '../includes/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$id     = isset($_GET['id'])  ? (int) $_GET['id']  : (isset($_POST['id']) ? (int) $_POST['id'] : null);

// ─────────────────────────────────────────────
// POST: Crear o Actualizar
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre           = $conn->real_escape_string(trim($_POST['nombre']           ?? ''));
    $apellido         = $conn->real_escape_string(trim($_POST['apellido']         ?? ''));
    $cedula           = $conn->real_escape_string(trim($_POST['cedula']           ?? ''));
    $telefono         = $conn->real_escape_string(trim($_POST['telefono']         ?? ''));
    $email            = $conn->real_escape_string(trim($_POST['email']            ?? ''));
    $fecha_nacimiento = $conn->real_escape_string(trim($_POST['fecha_nacimiento'] ?? ''));

    switch ($action) {

        case 'create':
            $check = $conn->query("SELECT id FROM pacientes WHERE cedula = '$cedula'");
            if ($check->num_rows > 0) {
                $_SESSION['error'] = "Ya existe un paciente con esta cédula.";
            } else {
                $sql = "INSERT INTO pacientes (nombre, apellido, cedula, telefono, email, fecha_nacimiento)
                        VALUES ('$nombre', '$apellido', '$cedula', '$telefono', '$email', '$fecha_nacimiento')";
                if ($conn->query($sql)) {
                    $_SESSION['success'] = "Paciente registrado exitosamente.";
                } else {
                    $_SESSION['error'] = "Error al registrar paciente: " . $conn->error;
                }
            }
            header("Location: ../controllers/pacientes_controller.php");
            exit();

        case 'update':
            if (!$id) {
                $_SESSION['error'] = "ID de paciente no válido.";
                header("Location: ../controllers/pacientes_controller.php");
                exit();
            }
            $check = $conn->query("SELECT id FROM pacientes WHERE cedula = '$cedula' AND id != $id");
            if ($check->num_rows > 0) {
                $_SESSION['error'] = "Ya existe otro paciente con esta cédula.";
            } else {
                $sql = "UPDATE pacientes
                        SET nombre='$nombre', apellido='$apellido', cedula='$cedula',
                            telefono='$telefono', email='$email', fecha_nacimiento='$fecha_nacimiento'
                        WHERE id=$id";
                if ($conn->query($sql)) {
                    $_SESSION['success'] = "Paciente actualizado exitosamente.";
                } else {
                    $_SESSION['error'] = "Error al actualizar paciente: " . $conn->error;
                }
            }
            header("Location: ../controllers/pacientes_controller.php");
            exit();

        default:
            header("Location: ../controllers/pacientes_controller.php");
            exit();
    }
}

// ─────────────────────────────────────────────
// GET: Eliminar
// ─────────────────────────────────────────────
if ($action === 'delete') {
    if (!$id) {
        $_SESSION['error'] = "ID de paciente no válido.";
        header("Location: ../controllers/pacientes_controller.php");
        exit();
    }

    if ($conn->query("DELETE FROM pacientes WHERE id=$id")) {
        $_SESSION['success'] = "Paciente eliminado exitosamente.";
    } else {
        $_SESSION['error'] = "Error al eliminar paciente: " . $conn->error;
    }
    header("Location: ../controllers/pacientes_controller.php");
    exit();
}

// ─────────────────────────────────────────────
// GET: Cargar datos para la vista
// ─────────────────────────────────────────────

/** @var array|null $paciente  Registro a editar (null si es formulario nuevo) */
$paciente = null;

if ($action === 'edit' && $id) {
    $result   = $conn->query("SELECT * FROM pacientes WHERE id=$id");
    $paciente = $result ? $result->fetch_assoc() : null;

    if (!$paciente) {
        $_SESSION['error'] = "Paciente no encontrado.";
        header("Location: ../controllers/pacientes_controller.php");
        exit();
    }
}

/** @var bool $mostrar_formulario */
$mostrar_formulario = in_array($action, ['new', 'edit']);

/** @var string $page_title */
$page_title = match(true) {
    $mostrar_formulario && $paciente !== null => "Editar Paciente",
    $mostrar_formulario                       => "Nuevo Paciente",
    default                                   => "Gestión de Pacientes",
};

/** @var mysqli_result|null $pacientes */
$pacientes = null;
if (!$mostrar_formulario) {
    $pacientes = $conn->query("SELECT * FROM pacientes ORDER BY apellido, nombre");
}

// Cede el control a la vista
include '../pages/pacientes.php';