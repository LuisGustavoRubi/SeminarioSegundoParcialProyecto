</main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    
    <script>
        // token CSRF disponible globalmente para los scripts que necesiten enviar POST
        const CSRF_TOKEN = '<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>';

        function enviarPost(datos) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            // suponemos que el script se ejecuta en la misma página donde se usan las acciones
            form.action = window.location.pathname;
            for (const [k, v] of Object.entries(datos)) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = k;
                inp.value = v;
                form.appendChild(inp);
            }
            document.body.appendChild(form);
            form.submit();
        }

        function confirmarEliminacion(id, nombre, tipo) {
            const nombreEscapado = nombre.replace(/"/g, '\\"').replace(/'/g, "\\'");
            const idEscapado = parseInt(id, 10);
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar ${tipo} "${nombreEscapado}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarPost({ action: 'delete', id: idEscapado, csrf_token: CSRF_TOKEN });
                }
            });
        }

        function confirmarCancelacion(id) {
            const idEscapado = parseInt(id, 10);
            
            Swal.fire({
                title: '¿Cancelar cita?',
                text: '¿Estás seguro de cancelar esta cita?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarPost({ action: 'cancel', id: idEscapado, csrf_token: CSRF_TOKEN });
                }
            });
        }

        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: <?php echo json_encode(htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8')); ?>,
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: <?php echo json_encode(htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8')); ?>
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>