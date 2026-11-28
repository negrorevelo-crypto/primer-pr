-- Portal de entrenamiento de corredores · Revelco
-- MySQL 5.7+ / MariaDB 10.3+. Importar desde Plesk › Bases de datos › phpMyAdmin › Importar.

SET NAMES utf8mb4;
SET time_zone = '-05:00';

-- Firmas comisionistas (empresas de los corredores)
CREATE TABLE IF NOT EXISTS firmas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(160) NOT NULL,
  nit VARCHAR(20) NULL,
  contacto_nombre VARCHAR(120) NULL,
  contacto_correo VARCHAR(160) NULL,
  contacto_telefono VARCHAR(40) NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_firmas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios: corredores, responsables de firma y administradores de Revelco
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firma_id INT UNSIGNED NULL,
  rol ENUM('corredor','admin_firma','admin') NOT NULL DEFAULT 'corredor',
  usuario VARCHAR(60) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(160) NULL,
  telefono VARCHAR(40) NULL,
  clave_hash VARCHAR(255) NOT NULL,
  debe_cambiar_clave TINYINT(1) NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  notas VARCHAR(500) NULL,
  ajustes_juego TEXT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY ix_usuarios_firma (firma_id),
  CONSTRAINT fk_usuarios_firma FOREIGN KEY (firma_id) REFERENCES firmas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paquetes de cupos que compra una firma para sus corredores
CREATE TABLE IF NOT EXISTS paquetes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firma_id INT UNSIGNED NOT NULL,
  cupos SMALLINT UNSIGNED NOT NULL,
  inicio DATE NOT NULL,
  fin DATE NOT NULL,
  valor DECIMAL(14,2) NULL,
  notas VARCHAR(500) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_paquetes_firma (firma_id),
  CONSTRAINT fk_paquetes_firma FOREIGN KEY (firma_id) REFERENCES firmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Licencias: derecho de un usuario a usar el portal entre dos fechas
CREATE TABLE IF NOT EXISTS licencias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  origen ENUM('individual','paquete','cortesia') NOT NULL DEFAULT 'individual',
  paquete_id INT UNSIGNED NULL,
  plan ENUM('mensual','trimestral','semestral','anual','otro') NOT NULL DEFAULT 'mensual',
  inicio DATE NOT NULL,
  fin DATE NOT NULL,
  estado ENUM('activa','suspendida','anulada') NOT NULL DEFAULT 'activa',
  creado_por INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_licencias_usuario (usuario_id, estado, fin),
  KEY ix_licencias_paquete (paquete_id),
  CONSTRAINT fk_licencias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_licencias_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pagos manuales (transferencia, Nequi...) registrados por el administrador
CREATE TABLE IF NOT EXISTS pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  firma_id INT UNSIGNED NULL,
  licencia_id INT UNSIGNED NULL,
  paquete_id INT UNSIGNED NULL,
  valor DECIMAL(14,2) NOT NULL,
  medio ENUM('transferencia','nequi','daviplata','efectivo','pasarela','otro') NOT NULL DEFAULT 'transferencia',
  referencia VARCHAR(120) NULL,
  fecha_pago DATE NOT NULL,
  notas VARCHAR(500) NULL,
  registrado_por INT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_pagos_usuario (usuario_id),
  KEY ix_pagos_firma (firma_id),
  KEY ix_pagos_fecha (fecha_pago),
  CONSTRAINT fk_pagos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_pagos_firma FOREIGN KEY (firma_id) REFERENCES firmas(id) ON DELETE SET NULL,
  CONSTRAINT fk_pagos_licencia FOREIGN KEY (licencia_id) REFERENCES licencias(id) ON DELETE SET NULL,
  CONSTRAINT fk_pagos_paquete FOREIGN KEY (paquete_id) REFERENCES paquetes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Calibraciones de pantalla (la más reciente es la que usa el juego)
CREATE TABLE IF NOT EXISTS calibraciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  resolucion VARCHAR(20) NULL,
  escala SMALLINT UNSIGNED NULL,
  monitores TINYINT UNSIGNED NULL,
  modo ENUM('grabacion','mi-puesto') NOT NULL DEFAULT 'grabacion',
  encaja TINYINT(1) NULL,
  perfil MEDIUMTEXT NOT NULL,
  capturas VARCHAR(1000) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_calibraciones_usuario (usuario_id, creado_en),
  CONSTRAINT fk_calibraciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partidas del juego
CREATE TABLE IF NOT EXISTS intentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  firma_id INT UNSIGNED NULL,
  escenario VARCHAR(40) NOT NULL DEFAULT 'lanzamiento-v1',
  resultado ENUM('lanzada','cancelada','agotada') NOT NULL,
  tiempo_reaccion_ms INT UNSIGNED NULL,
  tiempo_total_ms INT UNSIGNED NULL,
  tiempo_propio_ms INT UNSIGNED NULL,
  valor_inicial BIGINT UNSIGNED NULL,
  valor_lanzado BIGINT UNSIGNED NULL,
  errores SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  calibracion_id INT UNSIGNED NULL,
  detalle TEXT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_intentos_usuario (usuario_id, creado_en),
  KEY ix_intentos_ranking (firma_id, resultado, creado_en),
  CONSTRAINT fk_intentos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_intentos_firma FOREIGN KEY (firma_id) REFERENCES firmas(id) ON DELETE SET NULL,
  CONSTRAINT fk_intentos_calibracion FOREIGN KEY (calibracion_id) REFERENCES calibraciones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de acciones importantes
CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  accion VARCHAR(60) NOT NULL,
  detalle VARCHAR(1000) NULL,
  ip VARCHAR(45) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_auditoria_fecha (creado_en),
  KEY ix_auditoria_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
