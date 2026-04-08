<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Verifica que el usuario esté logueado.
 * Si no lo está, muestra un SweetAlert en login y corta la ejecución.
 */
function requireLogin(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['usuario'])) {
        $_SESSION['auth_message'] = 'Es necesario que inicies sesión para poder ver esta página.';
        $inPages = str_contains($_SERVER['PHP_SELF'], '/pages/');
        header('Location: ' . ($inPages ? 'login.php' : 'pages/login.php'));
        exit();
    }
}

/**
 * Verifica que el usuario esté logueado Y tenga el rol requerido (por ID).
 * Si no tiene el rol, redirige al inicio con mensaje de error.
 */
function requireRol(int $rolRequerido): void
{
    requireLogin();
    if (($_SESSION['rol_id'] ?? 0) !== $rolRequerido) {
        $_SESSION['error'] = 'No tienes permiso para acceder a esta sección.';
        $inPages = str_contains($_SERVER['PHP_SELF'], '/pages/');
        header('Location: ' . ($inPages ? '../index.php' : 'index.php'));
        exit();
    }
}
