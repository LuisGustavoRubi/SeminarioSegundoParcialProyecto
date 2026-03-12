<?php
require_once '../includes/config.php';

class PacienteController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {

        // Crear / Actualizar registro
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

            // Validar token CSRF obligatorio
            if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
                $_SESSION['error'] = 'Token CSRF inválido. Por favor recargue la página e intente de nuevo.';
                header('Location: ../pages/pacientes.php');
                exit();
            }

            // Guardar datos del formulario para recuperación en caso de error
            $_SESSION['form_data'] = $_POST;

            $nombre           = trim($_POST['nombre']           ?? '');
            $apellido         = trim($_POST['apellido']         ?? '');
            $cedula           = trim($_POST['cedula']           ?? '');
            $telefono         = trim($_POST['telefono']         ?? '');
            $email            = trim($_POST['email']            ?? '');
            $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');

            // Validación de campos obligatorios
            $camposFaltantes = [];
            if ($nombre           === '') $camposFaltantes[] = 'Nombre';
            if ($apellido         === '') $camposFaltantes[] = 'Apellido';
            if ($cedula           === '') $camposFaltantes[] = 'Cédula';
            if ($telefono         === '') $camposFaltantes[] = 'Teléfono';
            if ($email            === '') $camposFaltantes[] = 'Email';
            if ($fecha_nacimiento === '') $camposFaltantes[] = 'Fecha de Nacimiento';

            if (!empty($camposFaltantes)) {
                $_SESSION['error'] = 'Los siguientes campos son obligatorios: ' . implode(', ', $camposFaltantes);
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/pacientes.php?action=new'
                    : '../pages/pacientes.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validación de fecha de nacimiento
            $fechaNac = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
            $hoyHN    = new DateTime('now', new DateTimeZone('America/Tegucigalpa'));
            $redirect_fn = ($_POST['action'] === 'create')
                ? '../pages/pacientes.php?action=new'
                : '../pages/pacientes.php?action=edit&id=' . intval($_POST['id'] ?? 0);

            if (!$fechaNac || $fechaNac->format('Y-m-d') !== $fecha_nacimiento) {
                $_SESSION['error'] = 'La fecha de nacimiento no es válida.';
                header("Location: $redirect_fn");
                exit();
            }
            if ($fechaNac > $hoyHN) {
                $_SESSION['error'] = 'La fecha de nacimiento no puede ser una fecha futura.';
                header("Location: $redirect_fn");
                exit();
            }
            if ((int)$fechaNac->format('Y') < 1900) {
                $_SESSION['error'] = 'La fecha de nacimiento no puede ser anterior al año 1900.';
                header("Location: $redirect_fn");
                exit();
            }

            // Normalización del teléfono: quitar todo excepto dígitos, luego formatear
            $telefonoDigitos = preg_replace('/\D/', '', $telefono);
            if (strlen($telefonoDigitos) === 11 && str_starts_with($telefonoDigitos, '504')) {
                // caso: 50412345678 → +504 1234-5678
                $local = substr($telefonoDigitos, 3);
                $telefono = '+504 ' . substr($local, 0, 4) . '-' . substr($local, 4);
            } elseif (strlen($telefonoDigitos) === 8) {
                // caso: 12345678 → +504 1234-5678
                $telefono = '+504 ' . substr($telefonoDigitos, 0, 4) . '-' . substr($telefonoDigitos, 4);
            }

            // Validación de formato de teléfono: +504 9999-9999
            if (!preg_match('/^\+504 \d{4}-\d{4}$/', $telefono)) {
                $_SESSION['error'] = 'Formato de teléfono inválido. Use el formato: +504 9999-9999';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/pacientes.php?action=new'
                    : '../pages/pacientes.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validación de formato de email
            $email = strtolower($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'El email ingresado no tiene un formato válido.';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/pacientes.php?action=new'
                    : '../pages/pacientes.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validación de formato de cédula: 0000-0000-00000
            if (!preg_match('/^\d{4}-\d{4}-\d{5}$/', $cedula)) {
                $_SESSION['error'] = 'Formato de cédula inválido. Use el formato: 0000-0000-00000 (solo dígitos separados por guiones)';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/pacientes.php?action=new'
                    : '../pages/pacientes.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            if ($_POST['action'] == 'create') {

                $check = $this->conn->prepare("SELECT id FROM pacientes WHERE cedula = ?");
                $check->bind_param("s", $cedula);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "La cédula $cedula ya está registrada. Verifique los datos o busque al paciente existente.";
                    $check->close();
                    header("Location: ../pages/pacientes.php?action=new");
                    exit();
                }
                $check->close();

                // Verificar duplicados de teléfono y email
                $dupCheck = $this->conn->prepare("SELECT id FROM pacientes WHERE telefono = ? OR email = ?");
                $dupCheck->bind_param("ss", $telefono, $email);
                $dupCheck->execute();
                $dupCheck->store_result();

                if ($dupCheck->num_rows > 0) {
                    $dupTel = $this->conn->prepare("SELECT id FROM pacientes WHERE telefono = ?");
                    $dupTel->bind_param("s", $telefono);
                    $dupTel->execute();
                    $dupTel->store_result();

                    $dupEmail = $this->conn->prepare("SELECT id FROM pacientes WHERE email = ?");
                    $dupEmail->bind_param("s", $email);
                    $dupEmail->execute();
                    $dupEmail->store_result();

                    $msgs = [];
                    if ($dupTel->num_rows > 0)   $msgs[] = "el teléfono $telefono";
                    if ($dupEmail->num_rows > 0)  $msgs[] = "el email $email";
                    $_SESSION['error'] = 'Ya existe un paciente con ' . implode(' y ', $msgs) . '. Verifique los datos.';
                    $dupTel->close();
                    $dupEmail->close();
                    $dupCheck->close();
                    header("Location: ../pages/pacientes.php?action=new");
                    exit();
                }
                $dupCheck->close();

                $stmt = $this->conn->prepare(
                    "INSERT INTO pacientes (nombre, apellido, cedula, telefono, email, fecha_nacimiento)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("ssssss", $nombre, $apellido, $cedula, $telefono, $email, $fecha_nacimiento);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Paciente registrado exitosamente";
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = "Error al registrar paciente";
                }
                $stmt->close();

                header("Location: ../pages/pacientes.php");
                exit();
            }

            // Actualizar registro
            if ($_POST['action'] == 'update') {

                $id = intval($_POST['id']);

                // verificar existencia del paciente antes de proseguir
                $exist = $this->conn->prepare("SELECT COUNT(*) FROM pacientes WHERE id=?");
                $exist->bind_param("i", $id);
                $exist->execute();
                $exist->bind_result($cnt);
                $exist->fetch();
                $exist->close();
                if ($cnt === 0) {
                    $_SESSION['error'] = 'Paciente no encontrado para actualizar.';
                    header("Location: ../pages/pacientes.php");
                    exit();
                }

                if ($check->num_rows > 0) {
                    $_SESSION['error'] = "La cédula $cedula ya está registrada para otro paciente.";
                    $check->close();
                    header("Location: ../pages/pacientes.php?action=edit&id=$id");
                    exit();
                }
                $check->close();

                // Verificar duplicados de teléfono y email excluyendo el registro actual
                $dupCheck = $this->conn->prepare("SELECT id FROM pacientes WHERE (telefono = ? OR email = ?) AND id != ?");
                $dupCheck->bind_param("ssi", $telefono, $email, $id);
                $dupCheck->execute();
                $dupCheck->store_result();

                if ($dupCheck->num_rows > 0) {
                    $dupTel = $this->conn->prepare("SELECT id FROM pacientes WHERE telefono = ? AND id != ?");
                    $dupTel->bind_param("si", $telefono, $id);
                    $dupTel->execute();
                    $dupTel->store_result();

                    $dupEmail = $this->conn->prepare("SELECT id FROM pacientes WHERE email = ? AND id != ?");
                    $dupEmail->bind_param("si", $email, $id);
                    $dupEmail->execute();
                    $dupEmail->store_result();

                    $msgs = [];
                    if ($dupTel->num_rows > 0)   $msgs[] = "el teléfono $telefono";
                    if ($dupEmail->num_rows > 0)  $msgs[] = "el email $email";
                    $_SESSION['error'] = 'Ya existe otro paciente con ' . implode(' y ', $msgs) . '.';
                    $dupTel->close();
                    $dupEmail->close();
                    $dupCheck->close();
                    header("Location: ../pages/pacientes.php?action=edit&id=$id");
                    exit();
                }
                $dupCheck->close();

                $stmt = $this->conn->prepare(
                    "UPDATE pacientes
                     SET nombre=?, apellido=?, cedula=?, telefono=?, email=?, fecha_nacimiento=?
                     WHERE id=?"
                );
                $stmt->bind_param("ssssssi", $nombre, $apellido, $cedula, $telefono, $email, $fecha_nacimiento, $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Paciente actualizado exitosamente";
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = "Error al actualizar paciente";
                }
                $stmt->close();

                header("Location: ../pages/pacientes.php");
                exit();
            }
        }

        // Eliminar registro (ahora vía POST en lugar de GET)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
            if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
                $_SESSION['error'] = 'Token CSRF inválido. No se puede eliminar el paciente.';
                header('Location: ../pages/pacientes.php');
                exit();
            }

            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $_SESSION['error'] = 'ID de paciente no válido.';
                header('Location: ../pages/pacientes.php');
                exit();
            }

            // verificar citas asociadas
            $chk = $this->conn->prepare("SELECT COUNT(*) FROM citas WHERE paciente_id=?");
            $chk->bind_param("i", $id);
            $chk->execute();
            $chk->bind_result($count);
            $chk->fetch();
            $chk->close();

            if ($count > 0) {
                $_SESSION['error'] = 'No se puede eliminar el paciente porque tiene citas asociadas.';
                header('Location: ../pages/pacientes.php');
                exit();
            }

            $stmt = $this->conn->prepare("DELETE FROM pacientes WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Paciente eliminado exitosamente";
            } else {
                error_log("[PACIENTES] Error al eliminar paciente id=$id: " . $stmt->error);
                $_SESSION['error'] = "Error técnico al eliminar paciente";
            }
            $stmt->close();

            header("Location: ../pages/pacientes.php");
            exit();
        }
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM pacientes WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }

    public function obtenerTodos() {
        return $this->conn->query("SELECT * FROM pacientes ORDER BY apellido, nombre");
    }
}