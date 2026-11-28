<?php
// Configuración del Calibrador de pantalla.
// Este archivo no muestra nada en el navegador, pero no lo comparta.

// 1. Cambie esta contraseña antes de publicar. Con la clave por defecto
//    el panel de administración no deja entrar.
const ADMIN_PASSWORD = 'cambiar-esta-clave';

// 2. Carpeta donde se guardan los perfiles y las capturas.
//    Por defecto queda FUERA de httpdocs (no se puede abrir desde internet).
//    Si el calibrador está en  /var/www/vhosts/midominio.com/httpdocs/calibrador
//    los datos quedan en       /var/www/vhosts/midominio.com/calibrador_datos
define('DATA_DIR', dirname(__DIR__, 2) . '/calibrador_datos');

// 3. Tamaño máximo de cada captura de pantalla, en MB.
const MAX_CAPTURA_MB = 12;

// 4. Número máximo de capturas por envío.
const MAX_CAPTURAS = 3;
