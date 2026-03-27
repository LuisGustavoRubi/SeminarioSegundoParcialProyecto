<?php
require_once '../includes/config.php';
require_once '../controllers/activarCuentaController.php';

$controller = new ActivacionController($conn);
$controller->handleRequest();

$page_title = "Activacion";
include '../includes/headerLogin.php';
?>

<body>
    <section class="ftco-section">
        <div class="login-shell">
            <div class="row justify-content-center mb-4">
                <div class="col-md-7 text-center">
                    <h2 class="heading-section">Activación de Cuenta</h2>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="wrap">
                        <div class="login-wrap">

                            <div class="d-flex align-items-start mb-4">
                                <div class="w-100">
                                    <h3 class="mb-1">Activar Cuenta</h3>
                                    <p class="text-secondary mb-0">Ingresar credenciales</p>
                                </div>
                            </div>

                            <form class="signin-form" method="post">
                                <div class="form-group mt-3">
                                    <input type="text" name="usuario" class="form-control" placeholder="Usuario" title="Completar campo de usuario" required>
                                    <label class="form-control-placeholder">Usuario</label>
                                </div>

                                <div class="form-group">
                                    <input id="password-field" name="contrasena" type="password" class="form-control" placeholder="Contraseña" title="Completar campo de contraseña" required>
                                    <label class="form-control-placeholder">Contraseña</label>
                                    <span class="bi bi-eye field-icon toggle-password" data-toggle="#password-field"></span>
                                </div>

                                <div class="form-group d-flex gap-2">
                                    <a href="../pages/login.php" class="form-control btn btn-cancel text-center">Cancelar</a>
                                    <button type="submit" class="form-control btn btn-submit">Activar</button>
                                </div>
                            </form>

                            <p class="text-center mt-4 mb-0">Ya tienes cuenta activa? <a href="../pages/login.php">Iniciar sesión</a></p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function(toggle) {
            function togglePasswordVisibility() {
                var input = document.querySelector(toggle.getAttribute('data-toggle'));
                if (!input) return;

                var isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                toggle.classList.toggle('bi-eye', !isPassword);
                toggle.classList.toggle('bi-eye-slash', isPassword);
            }

            toggle.addEventListener('click', togglePasswordVisibility);
        });
    </script>
</body>

</html>
<?php include '../includes/footer.php'; ?>