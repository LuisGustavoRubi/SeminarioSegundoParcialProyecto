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

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
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
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO pacientes (nombre, apellido, cedula, telefono, email, fecha_nacimiento) VALUES
('María', 'González', '0801-1990-12345', '+504 9876-5432', 'maria.gonzalez@email.com', '1990-05-15'),
('Carlos', 'Martínez', '0801-1985-54321', '+504 9876-5433', 'carlos.martinez@email.com', '1985-08-20'),
('Ana', 'López', '0801-1992-11111', '+504 9876-5434', 'ana.lopez@email.com', '1992-03-10'),
('José', 'Hernández', '0801-1988-22222', '+504 9876-5435', 'jose.hernandez@email.com', '1988-11-25'),
('Laura', 'Rodríguez', '0801-1995-33333', '+504 9876-5436', 'laura.rodriguez@email.com', '1995-07-05');

INSERT INTO medicos (nombre, apellido, especialidad, telefono, email) VALUES
('Roberto', 'Flores', 'Medicina General', '+504 2234-5678', 'dr.flores@hospital.com'),
('Patricia', 'Sánchez', 'Pediatría', '+504 2234-5679', 'dra.sanchez@hospital.com'),
('Miguel', 'Ramírez', 'Cardiología', '+504 2234-5680', 'dr.ramirez@hospital.com'),
('Elena', 'Torres', 'Dermatología', '+504 2234-5681', 'dra.torres@hospital.com');

INSERT INTO citas (paciente_id, medico_id, fecha, hora, motivo, estado) VALUES
(1, 1, CURDATE(), '09:00:00', 'Control de rutina', 'pendiente'),
(2, 2, CURDATE(), '10:00:00', 'Vacunación infantil', 'pendiente'),
(3, 3, CURDATE(), '11:00:00', 'Dolor en el pecho', 'completada'),
(4, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Seguimiento de tratamiento', 'pendiente'),
(5, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:30:00', 'Consulta dermatológica', 'pendiente'),
(1, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'Consulta general', 'completada'),
(3, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '16:00:00', 'Control médico', 'cancelada');
