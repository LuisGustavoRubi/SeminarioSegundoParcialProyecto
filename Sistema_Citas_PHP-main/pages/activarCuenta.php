<?php
require_once '../includes/config.php';
require_once '../controllers/activarCuentaController.php';

$controller = new ActivacionController($conn);
$controller->handleRequest();

// Si llegó aquí en GET sin token, el controller ya redirigió. Seguro mostrar.
$usuario_activo = $_SESSION['activacion_usuario'] ?? '';

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
                <div class="col-12 col-md-9 col-lg-7">
                    <div class="wrap">
                        <div class="login-wrap">

                            <div class="d-flex align-items-start mb-4">
                                <div class="w-100">
                                    <h3 class="mb-1">Activar Cuenta</h3>
                                    <p class="text-secondary mb-0">
                                        Establece tu contraseña para
                                        <strong><?php echo htmlspecialchars($usuario_activo, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </p>
                                </div>
                            </div>

                            <form class="signin-form" method="post" onsubmit="return validarFormulario()" novalidate>

                                <div class="form-group">
                                    <input id="password-field" name="contrasena" type="password" class="form-control" placeholder="Contraseña" title="Completar campo de contraseña" required>
                                    <label class="form-control-placeholder">Contraseña</label>
                                    <span class="bi bi-eye field-icon toggle-password" data-toggle="#password-field"></span>
                                </div>

                                <div class="form-group">
                                    <input id="confirmar-field" type="password" class="form-control" placeholder="Confirmar contraseña" title="Repetir contraseña" required>
                                    <label class="form-control-placeholder">Confirmar contraseña</label>
                                    <span class="bi bi-eye field-icon toggle-password" data-toggle="#confirmar-field"></span>
                                </div>

                                <!-- Requisitos de contraseña -->
                                <div id="req-panel" style="background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px; padding:0.75rem 1rem; margin-bottom:1.25rem; font-size:0.85rem;">
                                    <p class="mb-2 fw-semibold" style="color:#555;">Requisitos de contraseña:</p>
                                    <ul class="list-unstyled mb-0" style="display:grid; gap:0.25rem;">
                                        <li id="req-length">  <i class="bi bi-x-circle-fill" style="color:#ccc;"></i> Mínimo 8 caracteres</li>
                                        <li id="req-upper">   <i class="bi bi-x-circle-fill" style="color:#ccc;"></i> Al menos una mayúscula</li>
                                        <li id="req-lower">   <i class="bi bi-x-circle-fill" style="color:#ccc;"></i> Al menos una minúscula</li>
                                        <li id="req-number">  <i class="bi bi-x-circle-fill" style="color:#ccc;"></i> Al menos un número</li>
                                        <li id="req-special"> <i class="bi bi-x-circle-fill" style="color:#ccc;"></i> Al menos un carácter especial (@$!%*?&)</li>
                                        <li id="req-match">   <i class="bi bi-x-circle-fill" style="color:#ccc;"></i> Las contraseñas coinciden</li>
                                    </ul>
                                </div>

                                <div class="form-group d-flex gap-2">
                                    <a href="../pages/login.php" class="form-control btn btn-cancel text-center">Cancelar</a>
                                    <button type="submit" id="btn-activar" class="form-control btn btn-submit" disabled>Activar</button>
                                </div>
                            </form>

                            <p class="text-center mt-4 mb-0">Ya tienes cuenta activa? <a href="../pages/login.php">Iniciar sesión</a></p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle mostrar/ocultar contraseña
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

        // Requisitos
        const REQS = {
            length:  { el: document.getElementById('req-length'),  test: p => p.length >= 8 },
            upper:   { el: document.getElementById('req-upper'),   test: p => /[A-Z]/.test(p) },
            lower:   { el: document.getElementById('req-lower'),   test: p => /[a-z]/.test(p) },
            number:  { el: document.getElementById('req-number'),  test: p => /[0-9]/.test(p) },
            special: { el: document.getElementById('req-special'), test: p => /[@$!%*?&]/.test(p) },
        };

        const passField    = document.getElementById('password-field');
        const confirmField = document.getElementById('confirmar-field');
        const btnActivar   = document.getElementById('btn-activar');

        function setReq(el, ok) {
            const icon = el.querySelector('i');
            if (ok) {
                icon.className = 'bi bi-check-circle-fill';
                icon.style.color = '#01d28e';
            } else {
                icon.className = 'bi bi-x-circle-fill';
                icon.style.color = '#ccc';
            }
        }

        function evaluarRequisitos() {
            const pass    = passField.value;
            const confirm = confirmField.value;

            let allOk = true;
            for (const key in REQS) {
                const ok = REQS[key].test(pass);
                setReq(REQS[key].el, ok);
                if (!ok) allOk = false;
            }

            const matchOk = pass !== '' && pass === confirm;
            setReq(document.getElementById('req-match'), matchOk);
            if (!matchOk) allOk = false;

            btnActivar.disabled = !allOk;
        }

        passField.addEventListener('input', evaluarRequisitos);
        confirmField.addEventListener('input', evaluarRequisitos);

        // Validación antes de enviar (segunda línea de defensa)
        function validarFormulario() {
            const pass    = passField.value;
            const confirm = confirmField.value;

            if (pass.length < 8) {
                Swal.fire({ icon: 'error', title: 'Contraseña inválida', text: 'La contraseña debe tener mínimo 8 caracteres.' });
                return false;
            }
            if (!/[A-Z]/.test(pass)) {
                Swal.fire({ icon: 'error', title: 'Contraseña inválida', text: 'La contraseña debe contener al menos una mayúscula.' });
                return false;
            }
            if (!/[a-z]/.test(pass)) {
                Swal.fire({ icon: 'error', title: 'Contraseña inválida', text: 'La contraseña debe contener al menos una minúscula.' });
                return false;
            }
            if (!/[0-9]/.test(pass)) {
                Swal.fire({ icon: 'error', title: 'Contraseña inválida', text: 'La contraseña debe contener al menos un número.' });
                return false;
            }
            if (!/[@$!%*?&]/.test(pass)) {
                Swal.fire({ icon: 'error', title: 'Contraseña inválida', html: 'La contraseña debe contener al menos un carácter especial.<br><strong>(@$!%*?&)</strong>' });
                return false;
            }
            if (pass !== confirm) {
                Swal.fire({ icon: 'error', title: 'Contraseñas no coinciden', text: 'Verifica que ambas contraseñas sean iguales.' });
                return false;
            }

            return true;
        }
    </script>
</body>

</html>
<?php include '../includes/footer.php'; ?>
