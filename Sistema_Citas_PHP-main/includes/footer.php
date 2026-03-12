</main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    
    <script>
        function confirmarEliminacion(id, nombre, tipo) {
            // escapar para prevenir inyección en JavaScript
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
                    // usar solo el id numérico en la URL
                    window.location.href = `?action=delete&id=${idEscapado}`;
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
                    window.location.href = `?action=cancel&id=${idEscapado}`;
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