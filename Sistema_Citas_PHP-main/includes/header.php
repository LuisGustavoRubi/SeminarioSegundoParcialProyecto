<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Sistema de Citas Médicas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --primary-color: #0d6efd;
            --login-active-primary: #dc3545;
            --sidebar-bg: #f8f9fa;
        }

        body {
            min-height: 100vh;
        }

        .sidebar {
            min-height: 100vh;
            background-color: var(--sidebar-bg);
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background-color: #e9ecef;
            color: var(--primary-color);
        }

        .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-link i {
            margin-right: 0.5rem;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .stat-card {
            border-left: 4px solid var(--primary-color);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.025);
        }

        /* Estilo de header de tabla historia */
        thead.bg-primary th,
        tr.bg-primary th {
            background-color: var(--bs-primary) !important;
            color: white;
        }

        /* Estilo de boton de cerrar sesion */
        .btn-logout {
            background: var(--login-active-primary);
            border: 1px solid var(--login-active-primary);
            color: #fff;
            font-size: 15px;
            padding: 10px 20px;
            box-shadow: none;
            transition: all 0.3s ease;
        }

        .btn-logout:hover,
        .btn-logout:focus {
            background: transparent;
            color: var(--login-active-primary);
            border-color: var(--login-active-primary);
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5 class="text-primary">
                            <i class="bi bi-hospital"></i>
                            Centro de Salud
                        </h5>
                    </div>

                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?> rounded"
                                href="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? '#' : '../index.php'; ?>">
                                <i class="bi bi-house-door"></i>
                                Inicio
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($current_page) && $current_page == 'pacientes' ? 'active' : ''; ?> rounded"
                                href="<?php echo basename($_SERVER['PHP_SELF']) == 'pacientes.php' ? '#' : (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'pages/pacientes.php' : 'pacientes.php'); ?>">
                                <i class="bi bi-person"></i>
                                Pacientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($current_page) && $current_page == 'medicos' ? 'active' : ''; ?> rounded"
                                href="<?php echo basename($_SERVER['PHP_SELF']) == 'medicos.php' ? '#' : (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'pages/medicos.php' : 'medicos.php'); ?>">
                                <i class="bi bi-person-badge"></i>
                                Médicos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($current_page) && $current_page == 'medicamentos' ? 'active' : ''; ?> rounded"
                                href="<?php echo basename($_SERVER['PHP_SELF']) == 'medicamentos.php' ? '#' : (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'pages/medicamentos.php' : 'medicamentos.php'); ?>">
                                <i class="bi bi-capsule"></i>
                                Medicamentos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($current_page) && $current_page == 'citas' ? 'active' : ''; ?> rounded"
                                href="<?php echo basename($_SERVER['PHP_SELF']) == 'citas.php' ? '#' : (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'pages/citas.php' : 'citas.php'); ?>">
                                <i class="bi bi-calendar-check"></i>
                                Citas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($current_page) && $current_page == 'citasHistorial' ? 'active' : ''; ?> rounded"
                                href="<?php echo basename($_SERVER['PHP_SELF']) == 'cistasHistorial.php' ? '#' : (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'pages/citasHistorial.php' : 'citasHistorial.php'); ?>">
                                <i class="bi bi-journal-text"></i>
                                Historial Citas
                            </a>
                        </li>
                        <?php if (($_SESSION['rol'] ?? '') === 'jefe'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo isset($current_page) && $current_page == 'usuarios' ? 'active' : ''; ?> rounded"
                                    href="<?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? '#' : (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'pages/usuarios.php' : 'usuarios.php'); ?>">
                                    <i class="bi bi-people"></i>
                                    Usuarios
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <div class="px-3 text-muted small">
                        <p class="mb-1">Sistema de Gestión</p>
                        <p class="mb-0">Versión 1.1</p>
                    </div>

                    <hr>

                    <div class="px-3 small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-circle text-primary" style="font-size:1.4rem;"></i>
                            <div>
                                <div class="fw-semibold text-dark" style="line-height:1.2;">
                                    <?php echo htmlspecialchars($_SESSION['usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    <?php echo htmlspecialchars($_SESSION['rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        $logout_url = basename($_SERVER['PHP_SELF']) == 'index.php'
                            ? 'pages/logout.php'
                            : 'logout.php';
                        ?>
                        <a href="<?php echo $logout_url; ?>"
                            class="btn btn-outline-danger btn-sm w-100"
                            onclick="return confirm('¿Cerrar sesión?')">
                            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                        </a>
                    </div>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
                </div>