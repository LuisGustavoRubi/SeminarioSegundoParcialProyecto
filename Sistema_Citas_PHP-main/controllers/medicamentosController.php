<?php
session_start();
require_once '../includes/config.php';

class MedicamentosController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function handleRequest()
    {
        // Crear egisto
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {

            $localidad_id = intval($_POST['localidad_id'] ?? 0);
            $modo = $_POST['modo'] ?? 'nuevo';

            if ($localidad_id <= 0) {
                $_SESSION['error'] = 'Se deleccionar una localidad.';
                $_SESSION['form_data'] = $_POST;
                header('Location: ../pages/medicamentos.php?action=new');
                exit();
            }

            // modo para ingresar nuevo medicamento
            if ($modo === 'nuevo') {

                $nombre = trim($_POST['nombre_medicamento'] ?? '');
                $precio = $_POST['precio'] ?? '';
                $stock = $_POST['stock_nuevo'] ?? '';

                $faltantes = [];
                if ($nombre === '') $faltantes[] = 'Nombre de medicamento';
                if ($precio === '') $faltantes[] = 'Precio';
                if ($stock === '') $faltantes[] = 'Stock inicial';

                if (!empty($faltantes)) {
                    $_SESSION['error'] = 'Completar los campos: ' . implode(', ', $faltantes);
                    $_SESSION['form_data'] = $_POST;
                    header('Location: ../pages/medicamentos.php?action=new');
                    exit();
                }

                $precio = floatval($precio);
                $stock  = intval($stock);

                if ($precio < 0) {
                    $_SESSION['error'] = 'El precio no puede ser negativo.';
                    $_SESSION['form_data'] = $_POST;
                    header('Location: ../pages/medicamentos.php?action=new');
                    exit();
                }
                if ($stock < 0) {
                    $_SESSION['error'] = 'El stock no puede ser negativo.';
                    $_SESSION['form_data'] = $_POST;
                    header('Location: ../pages/medicamentos.php?action=new');
                    exit();
                }

                // Ingresar nuevo medicamento o usar el mismo si el nombre ya existe (osea actualizar registro existente)
                $stmtBuscar = $this->conn->prepare("SELECT id FROM medicamentos WHERE nombre = ?");
                $stmtBuscar->bind_param("s", $nombre);
                $stmtBuscar->execute();
                $filaMed = $stmtBuscar->get_result()->fetch_assoc();
                $stmtBuscar->close();

                if ($filaMed) {
                    $medicamento_id = $filaMed['id'];
                    // Actualizar precio si hubo cambio
                    $upPrecio = $this->conn->prepare("UPDATE medicamentos SET precio = ? WHERE id = ?");
                    $upPrecio->bind_param("di", $precio, $medicamento_id);
                    $upPrecio->execute();
                    $upPrecio->close();
                } else {
                    $insMed = $this->conn->prepare("INSERT INTO medicamentos (nombre, precio) VALUES (?, ?)");
                    $insMed->bind_param("sd", $nombre, $precio);
                    if (!$insMed->execute()) {
                        $_SESSION['error'] = 'Error al crear el medicamento.';
                        header('Location: ../pages/medicamentos.php?action=new');
                        exit();
                    }
                    $medicamento_id = $this->conn->insert_id;
                    $insMed->close();
                }

                // Si ya existe stock en la localidad
                $stmtExiste = $this->conn->prepare("SELECT id, stock FROM localidad_medicamentos WHERE localidad_id = ? AND medicamento_id = ?");
                $stmtExiste->bind_param("ii", $localidad_id, $medicamento_id);
                $stmtExiste->execute();
                $filaStock = $stmtExiste->get_result()->fetch_assoc();
                $stmtExiste->close();

                if ($filaStock) {
                    $nuevoStock = $filaStock['stock'] + $stock;
                    $upd = $this->conn->prepare("UPDATE localidad_medicamentos SET stock = ? WHERE id = ?");
                    $upd->bind_param("ii", $nuevoStock, $filaStock['id']);
                    $upd->execute();
                    $upd->close();
                    $_SESSION['success'] = "El medicamento ya existe en esa localidad. Se sumaron $stock unidades (total: $nuevoStock).";
                } else {
                    $ins = $this->conn->prepare("INSERT INTO localidad_medicamentos (localidad_id, medicamento_id, stock) VALUES (?, ?, ?)");
                    $ins->bind_param("iii", $localidad_id, $medicamento_id, $stock);
                    if ($ins->execute()) {
                        $_SESSION['success'] = 'Medicamento registrado en la localidad con existo.';
                    } else {
                        $_SESSION['error'] = 'Error al registrar el medicamento en la localidad.';
                    }
                    $ins->close();
                }
            } else {
                // Modo para actualizar stock de medicamento existente
                $medicamento_id = intval($_POST['medicamento_id'] ?? 0);
                $stock_sumar = intval($_POST['stock_sumar'] ?? 0);

                if ($medicamento_id <= 0) {
                    $_SESSION['error'] = 'Seleccionar un medicamento existente.';
                    $_SESSION['form_data'] = $_POST;
                    header('Location: ../pages/medicamentos.php?action=new');
                    exit();
                }
                if ($stock_sumar <= 0) {
                    $_SESSION['error'] = 'La cantidad a sumar debe ser positiva o mayor a 0.';
                    $_SESSION['form_data'] = $_POST;
                    header('Location: ../pages/medicamentos.php?action=new');
                    exit();
                }

                // Si ya existe la localidad con el medicamento
                $stmtExiste = $this->conn->prepare("SELECT id, stock FROM localidad_medicamentos WHERE localidad_id = ? AND medicamento_id = ?");
                $stmtExiste->bind_param("ii", $localidad_id, $medicamento_id);
                $stmtExiste->execute();
                $filaStock = $stmtExiste->get_result()->fetch_assoc();
                $stmtExiste->close();

                if ($filaStock) {
                    $nuevoStock = $filaStock['stock'] + $stock_sumar;
                    $upd = $this->conn->prepare("UPDATE localidad_medicamentos SET stock = ? WHERE id = ?");
                    $upd->bind_param("ii", $nuevoStock, $filaStock['id']);
                    if ($upd->execute()) {
                        $_SESSION['success'] = "Se sumaron $stock_sumar unidades al stock. Actual total: $nuevoStock.";
                    } else {
                        $_SESSION['error'] = 'Error al actualizar el stock.';
                    }
                    $upd->close();
                } else {
                    // Si noexiste en la localidad, crear el registro con el stock indicado
                    $ins = $this->conn->prepare("INSERT INTO localidad_medicamentos (localidad_id, medicamento_id, stock) VALUES (?, ?, ?)");
                    $ins->bind_param("iii", $localidad_id, $medicamento_id, $stock_sumar);
                    if ($ins->execute()) {
                        $_SESSION['success'] = "Medicamento añadido a localidad con $stock_sumar unidades de stock.";
                    } else {
                        $_SESSION['error'] = 'Error al registrar en localidad.';
                    }
                    $ins->close();
                }
            }

            header('Location: ../pages/medicamentos.php');
            exit();
        }

        // Actualizar
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre_medicamento'] ?? '');
            $precio = $_POST['precio'] ?? '';
            $stock = $_POST['stock'] ?? '';
            $localidad_id = intval($_POST['localidad_id'] ?? 0);

            $faltantes = [];
            if (!$nombre) $faltantes[] = 'Nombre del medicamento';
            if ($precio === '') $faltantes[] = 'Precio';
            if ($stock === '') $faltantes[] = 'Stock';
            if ($localidad_id <= 0) $faltantes[] = 'Localidad';

            if (!empty($faltantes)) {
                $_SESSION['error'] = 'Completar los campos: ' . implode(', ', $faltantes);
                $_SESSION['form_data'] = $_POST;
                header("Location: ../pages/medicamentos.php?action=edit&id=$id");
                exit();
            }

            $precio = floatval($precio);
            $stock = intval($stock);

            if ($precio < 0) {
                $_SESSION['error'] = 'El precio no puede ser negativo.';
                $_SESSION['form_data'] = $_POST;
                header("Location: ../pages/medicamentos.php?action=edit&id=$id");
                exit();
            }
            if ($stock < 0) {
                $_SESSION['error'] = 'El stock no puede ser negativo.';
                $_SESSION['form_data'] = $_POST;
                header("Location: ../pages/medicamentos.php?action=edit&id=$id");
                exit();
            }

            // Obtener medicamento_id del registro actual
            $stmtGet = $this->conn->prepare("SELECT medicamento_id FROM localidad_medicamentos WHERE id = ?");
            $stmtGet->bind_param("i", $id);
            $stmtGet->execute();
            $filaReg = $stmtGet->get_result()->fetch_assoc();
            $stmtGet->close();

            if (!$filaReg) {
                $_SESSION['error'] = 'Registro no encontrado.';
                header('Location: ../pages/medicamentos.php');
                exit();
            }

            $medicamento_id = $filaReg['medicamento_id'];

            // Actualizar nombre y precio del medicamento
            $updMed = $this->conn->prepare("UPDATE medicamentos SET nombre = ?, precio = ? WHERE id = ?");
            $updMed->bind_param("sdi", $nombre, $precio, $medicamento_id);
            $updMed->execute();
            $updMed->close();

            // Actualizar stock y localidad del registro de localidad_medicamentos
            $updStock = $this->conn->prepare("UPDATE localidad_medicamentos SET stock = ?, localidad_id = ? WHERE id = ?");
            $updStock->bind_param("iii", $stock, $localidad_id, $id);
            if ($updStock->execute()) {
                $_SESSION['success'] = 'Registro actualizado con exito.';
            } else {
                $_SESSION['error'] = 'Error al actualizar el registro.';
            }
            $updStock->close();

            header('Location: ../pages/medicamentos.php');
            exit();
        }

        // Delete (este puede no ir y que sea cambiar estado, depende de logica, por ahora esta solo termporal)
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $id = intval($_GET['id']);

            $del = $this->conn->prepare("DELETE FROM localidad_medicamentos WHERE id = ?");
            $del->bind_param("i", $id);
            if ($del->execute()) {
                $_SESSION['success'] = 'Registro eliminado con exito.';
            } else {
                $_SESSION['error'] = 'Error al eliminar el registro.';
            }
            $del->close();

            header('Location: ../pages/medicamentos.php');
            exit();
        }
    }

    // Funciones con querys SQL
    public function obtenerPorId($id)
    {
        $id = intval($id);
        $stmt = $this->conn->prepare(
            "SELECT lm.id, lm.localidad_id, lm.medicamento_id, lm.stock,
            m.nombre AS medicamento_nombre, m.precio,
            l.nombre AS localidad_nombre
            FROM localidad_medicamentos lm
            INNER JOIN medicamentos m ON lm.medicamento_id = m.id
            INNER JOIN localidades l ON lm.localidad_id = l.id
            WHERE lm.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function obtenerStockCompleto()
    {
        return $this->conn->query(
            "SELECT lm.id,
            m.nombre AS medicamento,
            m.precio,
            l.nombre AS localidad,
            lm.stock
            FROM localidad_medicamentos lm
            INNER JOIN medicamentos m ON lm.medicamento_id = m.id
            INNER JOIN localidades l ON lm.localidad_id = l.id
            ORDER BY l.nombre ASC, m.nombre ASC"
        );
    }

    public function obtenerMedicamentos()
    {
        return $this->conn->query("SELECT id, nombre, precio FROM medicamentos ORDER BY nombre ASC");
    }

    public function obtenerLocalidades()
    {
        return $this->conn->query("SELECT id, nombre FROM localidades ORDER BY nombre ASC");
    }
}
