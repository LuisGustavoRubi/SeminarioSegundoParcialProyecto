CREATE DATABASE IF NOT EXISTS citas_medicas
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE citas_medicas;

CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_nacimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS enfermedades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS localidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS localidad_medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    localidad_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    stock INT DEFAULT 0,

    FOREIGN KEY (localidad_id) REFERENCES localidades(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cita_medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    cantidad INT DEFAULT 1,

    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    enfermedad_id INT NULL,
    localidad_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo TEXT,
    estado ENUM('pendiente', 'completada', 'cancelada') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_citas_paciente
        FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_citas_medico
        FOREIGN KEY (medico_id) REFERENCES medicos(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_citas_enfermedad
        FOREIGN KEY (enfermedad_id) REFERENCES enfermedades(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_citas_localidad
        FOREIGN KEY (localidad_id) REFERENCES localidades(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citas_historial (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    cita_id             INT NOT NULL,
    tipo_cambio         ENUM('modificacion', 'cancelacion', 'reprogramacion') NOT NULL,
    observacion         TEXT,
    anterior_paciente_id INT,
    anterior_medico_id   INT,
    anterior_fecha       DATE NOT NULL,
    anterior_hora        TIME NOT NULL,
    anterior_motivo      TEXT,
    anterior_estado      ENUM('pendiente', 'completada', 'cancelada') NOT NULL,
    nuevo_paciente_id    INT,
    nuevo_medico_id      INT,
    nuevo_fecha          DATE NOT NULL,
    nuevo_hora           TIME NOT NULL,
    nuevo_motivo         TEXT,
    nuevo_estado         ENUM('pendiente', 'completada', 'cancelada') NOT NULL,
    fecha_cambio         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historial_cita
        FOREIGN KEY (cita_id) REFERENCES citas(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_historial_ant_paciente
        FOREIGN KEY (anterior_paciente_id) REFERENCES pacientes(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_historial_ant_medico
        FOREIGN KEY (anterior_medico_id) REFERENCES medicos(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_historial_nvo_paciente
        FOREIGN KEY (nuevo_paciente_id) REFERENCES pacientes(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_historial_nvo_medico
        FOREIGN KEY (nuevo_medico_id) REFERENCES medicos(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255),
    rol_id INT NOT NULL,
    medico_id INT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'inactivo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (rol_id) REFERENCES roles(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_usuario_medico
        FOREIGN KEY (medico_id) REFERENCES medicos(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos de ejemplo
INSERT IGNORE INTO enfermedades (nombre) VALUES
('Hipertensión arterial'),
('Diabetes mellitus tipo 2'),
('Gripe / Influenza'),
('Infección respiratoria aguda'),
('Gastritis'),
('Dermatitis'),
('Anemia'),
('Asma');

INSERT IGNORE INTO roles (nombre) VALUES
('jefe'),
('empleado');

INSERT IGNORE INTO medicos (nombre, apellido, especialidad, telefono, email) VALUES
('Roberto', 'Flores', 'Medicina General', '+504 2234-5678', 'dr.flores@hospital.com'),
('Patricia', 'Sánchez', 'Pediatría', '+504 2234-5679', 'dra.sanchez@hospital.com'),
('Miguel', 'Ramírez', 'Cardiología', '+504 2234-5680', 'dr.ramirez@hospital.com'),
('Elena', 'Torres', 'Dermatología', '+504 2234-5681', 'dra.torres@hospital.com');

INSERT IGNORE INTO usuarios (usuario, contrasena, rol_id, medico_id, estado) VALUES
('jefe', '1234', 1, 1, 'activo'),
('empleado', '1234', 2, NULL, 'activo');

INSERT IGNORE INTO pacientes (nombre, apellido, cedula, telefono, email, fecha_nacimiento) VALUES
('María', 'González', '0801-1990-12345', '+504 9876-5432', 'maria.gonzalez@email.com', '1990-05-15'),
('Carlos', 'Martínez', '0801-1985-54321', '+504 9876-5433', 'carlos.martinez@email.com', '1985-08-20'),
('Ana', 'López', '0801-1992-11111', '+504 9876-5434', 'ana.lopez@email.com', '1992-03-10'),
('José', 'Hernández', '0801-1988-22222', '+504 9876-5435', 'jose.hernandez@email.com', '1988-11-25'),
('Laura', 'Rodríguez', '0801-1995-33333', '+504 9876-5436', 'laura.rodriguez@email.com', '1995-07-05');

INSERT IGNORE INTO localidades (nombre) VALUES
('Centro A'),
('Centro B'),
('Clínica Sur'),
('Hospital Norte');

INSERT IGNORE INTO medicamentos (nombre, precio) VALUES
('Paracetamol 500mg', 5.00),
('Ibuprofeno 400mg', 6.50),
('Amoxicilina 500mg', 12.00),
('Azitromicina 500mg', 18.00),
('Omeprazol 20mg', 7.00),
('Loratadina 10mg', 4.50),
('Salbutamol Inhalador', 25.00),
('Metformina 850mg', 9.00),
('Losartán 50mg', 11.00),
('Ácido Fólico', 3.50),
('Vitamina C 500mg', 4.00),
('Diclofenaco 50mg', 6.00);

INSERT INTO localidad_medicamentos (localidad_id, medicamento_id, stock) VALUES
-- Centro A
(1, 1, 50),
(1, 2, 40),
(1, 3, 30),
(1, 5, 25),
(1, 6, 60),

-- Centro b
(2, 1, 30),
(2, 4, 20),
(2, 7, 15),
(2, 8, 25),
(2, 9, 20),

-- Clinica sur
(3, 2, 35),
(3, 3, 20),
(3, 6, 40),
(3, 10, 50),
(3, 11, 45),

-- Hospital norte
(4, 1, 60),
(4, 3, 40),
(4, 4, 35),
(4, 7, 25),
(4, 12, 30);


INSERT IGNORE INTO citas (paciente_id, medico_id, fecha, hora, motivo, estado, localidad_id) VALUES
(1, 1, CURDATE(), '09:00:00', 'Control de rutina', 'pendiente', 1),
(2, 2, CURDATE(), '10:00:00', 'Vacunación infantil', 'pendiente', 2),
(3, 3, CURDATE(), '11:00:00', 'Dolor en el pecho', 'completada', 3),
(4, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Seguimiento de tratamiento', 'pendiente', 4),
(5, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:30:00', 'Consulta dermatológica', 'pendiente', 1),
(1, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'Consulta general', 'completada', 2),
(3, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '16:00:00', 'Control médico', 'cancelada', 3);


-- Correcciones para las citas_historial
-- Usuario que hizo el cambio
ALTER TABLE citas_historial
    ADD COLUMN usuario_id INT NULL AFTER cita_id,
    ADD CONSTRAINT fk_historial_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE SET NULL;

-- Enfermedad al completar la cita
ALTER TABLE citas_historial
    ADD COLUMN enfermedad_id INT NULL AFTER observacion,
    ADD CONSTRAINT fk_historial_enfermedad
        FOREIGN KEY (enfermedad_id) REFERENCES enfermedades(id)
        ON DELETE SET NULL;

-- Medicamentos de cada cita del historial
CREATE TABLE IF NOT EXISTS citas_historial_medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    CONSTRAINT fk_histmed_historial
        FOREIGN KEY (historial_id) REFERENCES citas_historial(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_histmed_medicamento
        FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Localidad en el historial de citas
ALTER TABLE citas_historial
    ADD COLUMN localidad_id INT NULL AFTER enfermedad_id,
    ADD CONSTRAINT fk_historial_localidad
        FOREIGN KEY (localidad_id) REFERENCES localidades(id)
        ON DELETE SET NULL;