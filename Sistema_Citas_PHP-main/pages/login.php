<?php
require_once '../includes/config.php';
require_once '../controllers/LoginController.php';

$controller = new LoginController($conn);
$controller->login();

$usuario = '';
$password = '';

include '../includes/headerLogin.php';
?>

<body>
    <section class="ftco-section">
        <div class="login-shell">
            <div class="row justify-content-center mb-4">
                <div class="col-md-7 text-center">
                    <h2 class="heading-section">Acceso al Sistema</h2>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-9">
                    <div class="wrap">
                        <div class="row g-0">
                            <div class="col-lg-6">
                                <div class="login-hero d-flex flex-column justify-content-between p-4 p-md-5">
                                    <div class="login-brand text-center">
                                        <i class="bi bi-hospital"></i> Centro de Salud
                                        <small>Sistema de Citas Medicas</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="login-wrap">
                                    <div class="d-flex align-items-start mb-4">
                                        <div class="w-100">
                                            <h3 class="mb-1">Iniciar Sesión</h3>
                                            <p class="text-secondary mb-0">Ingresar credenciales</p>
                                        </div>
                                    </div>

                                    <form method="POST" class="signin-form">
                                        <div class="form-group mt-3">
                                            <input type="text" class="form-control" id="usuario" name="usuario"  placeholder="Usuario"
                                                value="<?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?>" title="Completar campo de usuario" required>
                                            <label class="form-control-placeholder" for="usuario">Usuario</label>
                                        </div>

                                        <div class="form-group">
                                            <input id="password-field"type="password" name="password" class="form-control" placeholder="Contraseña" title="Completar campo de contraseña" required>
                                            <label class="form-control-placeholder" for="password-field">Contraseña</label>
                                            <span class="bi bi-eye field-icon toggle-password" data-toggle="#password-field" role="button" tabindex="0" aria-label="Mostrar u ocultar contraseña"></span>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="form-control btn btn-submit rounded submit px-3">Acceder</button>
                                        </div>

                                        <div class="form-group d-md-flex justify-content-between align-items-center">
                                            <div class="w-100 text-center">
                                                <a href="https://www.memegenerator.es/meme/31583445">Olvidaste tu contraseña?</a>
                                            </div>
                                        </div>
                                    </form>

                                    <p class="text-center mt-4 mb-0 text-muted" style="font-size:0.85rem;">Cuenta inactiva? Inicia sesión con tu contraseña temporal para activarla.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(function(toggle) {
            function togglePasswordVisibility() {
                var input = document.querySelector(toggle.getAttribute('data-toggle'));
                if (!input) {
                    return;
                }

                var isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                toggle.classList.toggle('bi-eye', !isPassword);
                toggle.classList.toggle('bi-eye-slash', isPassword);
            }

            toggle.addEventListener('click', togglePasswordVisibility);
            toggle.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    togglePasswordVisibility();
                }
            });
        });
    </script>
</body>

</html>
<?php include '../includes/footer.php'; ?>