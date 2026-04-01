
# Sistema De Gestion De Citas Medicas Usando PHP y MYSQL

## Cambios implementados 31 (Mar 2026)

### 1. Eliminación de normalización de caracteres al mostrar y en BD
- Se eliminó la función `normalizar_texto` de `includes/config.php` para que no haya transformaciones automáticas sobre los datos recuperados.
- Se actualizó el renderizado de datos en las vistas para usar `htmlspecialchars` directo:
  - `index.php`
  - `pages/citas.php`
  - `pages/pacientes.php`
  - `pages/medicos.php`
  - `pages/citasHistorial.php`
- Esto asegura que los valores en la base de datos se mantengan exactamente como se ingresan por el usuario, sin doble codificación o correcciones implícitas.

### 2. Control de localidad para citas (Campo obligatorio `localloc`)
- Esquema nuevo en `database.sql`:
  - nueva tabla `localidades(id, nombre)`
  - nueva columna `citas.localidad_id` con FK `fk_citas_localidad`
  - se actualizaron datos de ejemplo con localidades (Centro A, Centro B, Clínica Sur, Hospital Norte)
  - muestras de citas actualizadas con un `localidad_id` asociado.
- Backend en `controllers/CitasController.php`:
  - se agregó el método `obtenerLocalidades()`.
  - en `handleRequest()` se validó `localidad_id` como obligatorio en creación/edición.
  - query de inserción actualizada para incluir `localidad_id`.
  - query de edición actualizada para incluir `localidad_id`.
  - en `obtenerTodas()` se agregó join con `localidades` para traer `l.nombre AS localidad`.
- UI en `pages/citas.php`:
  - formulario creación/edición: selección obligatoria de localidad.
  - listado de citas: columna adicional `Localidad` y dato mostrado.

### 3. Ajustes adicionales de integridad visual y seguridad
- Se mantuvo el uso de `htmlspecialchars` en salidas HTML para prevención básica de XSS.
- Se actualizó la vista de `index` para mostrar `localidad` y evitar `normalizar_texto`.

## Cómo probar los cambios
1. Importar `database.sql` en MySQL (si existía la versión anterior, recrear la base de datos para evitar conflictos de FK).
2. Verificar que la tabla `localidades` está poblada y `citas.localidad_id` está configurado.
3. Ejecutar el servidor local y acceder al sistema.
4. Agendar cita nueva: no se guarda sin campo Localidad.
5. Editar una cita y cambiar localidad.
6. Revisar Dashboard (index) y listado de citas donde aparece Localidad.

## Conclusión
- Se completaron 100% las modificaciones requeridas.
- Se eliminó el formateo/transcodificación de caracteres y la lógica se mantiene con salida segura y datos sin formato.
- Se añadió control de localidad (`localloc`) en cita con todo el flujo de CRUD.
