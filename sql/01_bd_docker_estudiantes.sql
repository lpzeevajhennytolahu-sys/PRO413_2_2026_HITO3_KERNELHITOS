CREATE TABLE IF NOT EXISTS estudiantes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_estudiante VARCHAR(30) NOT NULL UNIQUE,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(120) NOT NULL,
    semestre INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS asignaturas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sigla VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    creditos INT NOT NULL DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inscripciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    estudiante_id BIGINT UNSIGNED NOT NULL,
    asignatura_id BIGINT UNSIGNED NOT NULL,
    gestion_codigo VARCHAR(30) NOT NULL,
    fecha_inscripcion DATE NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'Inscrito',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_insc_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_insc_asignatura FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS calificaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inscripcion_id BIGINT UNSIGNED NOT NULL,
    nota_parcial DECIMAL(5,2) NOT NULL DEFAULT 0,
    nota_final DECIMAL(5,2) NOT NULL DEFAULT 0,
    observacion VARCHAR(50) NOT NULL DEFAULT 'En curso',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calif_inscripcion FOREIGN KEY (inscripcion_id) REFERENCES inscripciones(id) ON DELETE CASCADE
);

INSERT IGNORE INTO estudiantes (id, codigo_estudiante, nombre_completo, correo, semestre) VALUES
(1, 'EST-2026-01', 'Carlos Mendoza Quispe', 'carlos.mendoza@unifranz.edu.bo', 4),
(2, 'EST-2026-02', 'Lucia Flores Mamani', 'lucia.flores@unifranz.edu.bo', 4);

INSERT IGNORE INTO asignaturas (id, sigla, nombre, creditos) VALUES
(1, 'PRO-413', 'Programación III', 5),
(2, 'BDD-412', 'Base de Datos II', 4),
(3, 'RED-415', 'Redes de Computadoras I', 4);

INSERT IGNORE INTO inscripciones (id, estudiante_id, asignatura_id, gestion_codigo, fecha_inscripcion, estado) VALUES
(1, 1, 1, 'II-2026', '2026-08-01', 'Inscrito'),
(2, 2, 2, 'II-2026', '2026-08-02', 'Inscrito');

INSERT IGNORE INTO calificaciones (id, inscripcion_id, nota_parcial, nota_final, observacion) VALUES
(1, 1, 85.00, 90.00, 'Aprobado'),
(2, 2, 45.00, 48.00, 'Reprobado');