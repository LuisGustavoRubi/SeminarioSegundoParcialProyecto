<?php
require_once '../includes/config.php';

class MedicoController {
    private $conn;

    private static array $especialidades = [
        'Anestesiología',
        'Cardiología',
        'Cirugía General',
        'Dermatología',
        'Endocrinología',
        'Gastroenterología',
        'Ginecología',
        'Medicina General',
        'Medicina Interna',
        'Nefrología',
        'Neumología',
        'Neurología',
        'Oftalmología',
        'Oncología',
        'Ortopedia',
        'Pediatría',
        'Psiquiatría',
        'Radiología',
        'Reumatología',
        'Urología',
    ];

    public static function getEspecialidades(): array {
        return self::$especialidades;
    }

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {

        // Crear / Actualizar registro
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

            // validar CSRF
            if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
                $_SESSION['error'] = 'Token CSRF inválido. Por favor recargue la página.';
                header('Location: ../pages/medicos.php');
                exit();
            }
            // Guardar datos del formulario para recuperación en caso de error
            $_SESSION['form_data'] = $_POST;

            $nombre       = trim($_POST['nombre']       ?? '');
            $apellido     = trim($_POST['apellido']     ?? '');
            $especialidad = trim($_POST['especialidad'] ?? '');
            $telefono     = trim($_POST['telefono']     ?? '');
            $email        = trim($_POST['email']        ?? '');

            // Validación de campos obligatorios
            $camposFaltantes = [];
            if ($nombre       === '') $camposFaltantes[] = 'Nombre';
            if ($apellido     === '') $camposFaltantes[] = 'Apellido';
            if ($especialidad === '') $camposFaltantes[] = 'Especialidad';
            if ($telefono     === '') $camposFaltantes[] = 'Teléfono';
            if ($email        === '') $camposFaltantes[] = 'Email';

            if (!empty($camposFaltantes)) {
                $_SESSION['error'] = 'Los siguientes campos son obligatorios: ' . implode(', ', $camposFaltantes);
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/medicos.php?action=new'
                    : '../pages/medicos.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validación de especialidad contra la lista permitida
            if (!in_array($especialidad, self::$especialidades, true)) {
                $_SESSION['error'] = 'Especialidad no válida. Seleccione una opción de la lista.';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/medicos.php?action=new'
                    : '../pages/medicos.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Normalización del teléfono
            $telefonoDigitos = preg_replace('/\D/', '', $telefono);
            if (strlen($telefonoDigitos) === 11 && str_starts_with($telefonoDigitos, '504')) {
                $local    = substr($telefonoDigitos, 3);
                $telefono = '+504 ' . substr($local, 0, 4) . '-' . substr($local, 4);
            } elseif (strlen($telefonoDigitos) === 8) {
                $telefono = '+504 ' . substr($telefonoDigitos, 0, 4) . '-' . substr($telefonoDigitos, 4);
            }

            // Validación de formato de teléfono: +504 9999-9999
            if (!preg_match('/^\+504 \d{4}-\d{4}$/', $telefono)) {
                $_SESSION['error'] = 'Formato de teléfono inválido. Use el formato: +504 9999-9999';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/medicos.php?action=new'
                    : '../pages/medicos.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validación de formato de email
            $email = strtolower($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'El email ingresado no tiene un formato válido.';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/medicos.php?action=new'
                    : '../pages/medicos.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            // Validación de dominio institucional
            if (!str_ends_with($email, '@hospital.com')) {
                $_SESSION['error'] = 'El correo del médico debe pertenecer al dominio institucional: @hospital.com';
                $redirect = ($_POST['action'] === 'create')
                    ? '../pages/medicos.php?action=new'
                    : '../pages/medicos.php?action=edit&id=' . intval($_POST['id'] ?? 0);
                header("Location: $redirect");
                exit();
            }

            if ($_POST['action'] == 'create') {

                // Verificar duplicados de teléfono y email
                $dupCheck = $this->conn->prepare("SELECT id FROM medicos WHERE telefono = ? OR email = ?");
                $dupCheck->bind_param("ss", $telefono, $email);
                $dupCheck->execute();
                $dupCheck->store_result();

                if ($dupCheck->num_rows > 0) {
                    // Determinar cuál campo está duplicado para el mensaje exacto
                    $dupTel = $this->conn->prepare("SELECT id FROM medicos WHERE telefono = ?");
                    $dupTel->bind_param("s", $telefono);
                    $dupTel->execute();
                    $dupTel->store_result();

                    $dupEmail = $this->conn->prepare("SELECT id FROM medicos WHERE email = ?");
                    $dupEmail->bind_param("s", $email);
                    $dupEmail->execute();
                    $dupEmail->store_result();

                    $msgs = [];
                    if ($dupTel->num_rows > 0)   $msgs[] = "el teléfono $telefono";
                    if ($dupEmail->num_rows > 0)  $msgs[] = "el email $email";
                    $_SESSION['error'] = 'Ya existe un médico con ' . implode(' y ', $msgs) . '. Verifique los datos.';
                    $dupTel->close();
                    $dupEmail->close();
                    $dupCheck->close();
                    header("Location: ../pages/medicos.php?action=new");
                    exit();
                }
                $dupCheck->close();

                $stmt = $this->conn->prepare(
                    "INSERT INTO medicos (nombre, apellido, especialidad, telefono, email)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("sssss", $nombre, $apellido, $especialidad, $telefono, $email);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Médico registrado exitosamente";
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = "Error al registrar médico";
                }
                $stmt->close();

                header("Location: ../pages/medicos.php");
                exit();
            }

            // Actualizar registro
            if ($_POST['action'] == 'update') {

                $id = intval($_POST['id']);

                // verificar existencia antes de proceder
                $exist = $this->conn->prepare("SELECT COUNT(*) FROM medicos WHERE id=?");
                $exist->bind_param("i", $id);
                $exist->execute();
                $exist->bind_result($cnt);
                $exist->fetch();
                $exist->close();
                if ($cnt === 0) {
                    $_SESSION['error'] = 'Médico no encontrado para actualizar.';
                    header("Location: ../pages/medicos.php");
                    exit();
                }

                // Verificar duplicados excluyendo el registro actual
                $dupCheck = $this->conn->prepare("SELECT id FROM medicos WHERE (telefono = ? OR email = ?) AND id != ?");
                $dupCheck->bind_param("ssi", $telefono, $email, $id);
                $dupCheck->execute();
                $dupCheck->store_result();

                if ($dupCheck->num_rows > 0) {
                    $dupTel = $this->conn->prepare("SELECT id FROM medicos WHERE telefono = ? AND id != ?");
                    $dupTel->bind_param("si", $telefono, $id);
                    $dupTel->execute();
                    $dupTel->store_result();

                    $dupEmail = $this->conn->prepare("SELECT id FROM medicos WHERE email = ? AND id != ?");
                    $dupEmail->bind_param("si", $email, $id);
                    $dupEmail->execute();
                    $dupEmail->store_result();

                    $msgs = [];
                    if ($dupTel->num_rows > 0)   $msgs[] = "el teléfono $telefono";
                    if ($dupEmail->num_rows > 0)  $msgs[] = "el email $email";
                    $_SESSION['error'] = 'Ya existe otro médico con ' . implode(' y ', $msgs) . '.';
                    $dupTel->close();
                    $dupEmail->close();
                    $dupCheck->close();
                    header("Location: ../pages/medicos.php?action=edit&id=$id");
                    exit();
                }
                $dupCheck->close();

                $stmt = $this->conn->prepare(
                    "UPDATE medicos
                     SET nombre=?, apellido=?, especialidad=?, telefono=?, email=?
                     WHERE id=?"
                );
                $stmt->bind_param("sssssi", $nombre, $apellido, $especialidad, $telefono, $email, $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Médico actualizado exitosamente";
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = "Error al actualizar médico";
                }
                $stmt->close();

                header("Location: ../pages/medicos.php");
                exit();
            }
        }

        // Eliminar médico vía POST con verificación de integridad referencial
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
            if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
                $_SESSION['error'] = 'Token CSRF inválido. No se puede eliminar el médico.';
                header('Location: ../pages/medicos.php');
                exit();
            }
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $_SESSION['error'] = 'ID de médico no válido.';
                header('Location: ../pages/medicos.php');
                exit();
            }
            $chk = $this->conn->prepare("SELECT COUNT(*) FROM citas WHERE medico_id=?");
            $chk->bind_param("i", $id);
            $chk->execute();
            $chk->bind_result($count);
            $chk->fetch();
            $chk->close();

            if ($count > 0) {
                $_SESSION['error'] = 'No se puede eliminar el médico porque tiene citas asociadas.';
                header('Location: ../pages/medicos.php');
                exit();
            }

            $stmt = $this->conn->prepare("DELETE FROM medicos WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Médico eliminado exitosamente";
            } else {
                error_log("[MEDICOS] Error al eliminar medico id=$id: " . $stmt->error);
                $_SESSION['error'] = "Error técnico al eliminar médico";
            }
            $stmt->close();

            header("Location: ../pages/medicos.php");
            exit();
        }
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM medicos WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }

    public function obtenerTodos() {
        return $this->conn->query("SELECT * FROM medicos ORDER BY apellido, nombre");
    }
}