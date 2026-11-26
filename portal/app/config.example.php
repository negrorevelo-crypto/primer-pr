<?php
// Copie este archivo como config.php (en esta misma carpeta) y complete los datos.
// config.php nunca debe quedar dentro de la carpeta pública.

return [
    // Base de datos creada en Plesk › Bases de datos
    'db' => [
        'host' => 'localhost',
        'puerto' => 3306,
        'nombre' => 'entrenamiento',
        'usuario' => 'entrenamiento_app',
        'clave' => 'CAMBIAR',
    ],

    'app' => [
        'nombre' => 'Entrenamiento de ofertas',
        // Lienzo del simulador: resolución del equipo donde se grabó la sesión
        'lienzo' => '1920x1080',
        // Datos de contacto que ve un corredor sin licencia
        'contacto_nombre' => 'Carlos Revelo',
        'contacto_correo' => 'carlos.revelo@revelco.com.co',
        'contacto_whatsapp' => '573168318098',
    ],

    // Carpeta para capturas de pantalla, fuera de la carpeta pública
    'almacenamiento' => dirname(__DIR__) . '/storage',

    // Permite crear el primer administrador en /instalar.php.
    // Después de crearlo, póngalo en false.
    'instalacion_habilitada' => true,
];
