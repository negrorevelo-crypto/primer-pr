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
        // Demoras de la plataforma en la réplica de la subasta, en milisegundos:
        // [mínimo, típico, máximo]. Cada partida sortea un valor; casi siempre cae
        // cerca del típico y a veces más rápido o más lento. Valores medidos en la
        // grabación del 24 de noviembre: botón 0,7 s · Colocación 0,3 s ·
        // Soy humano 1,3 s · Atención 0,1–0,3 s.
        'tiempos_subasta' => [
            'boton_tras_apertura' => [400, 700, 1500],
            'ventana_colocacion'  => [150, 300, 1200],
            'captcha'             => [700, 1300, 4000],
            'ventana_atencion'    => [80, 250, 1500],
        ],
    ],

    // Carpeta para capturas de pantalla, fuera de la carpeta pública
    'almacenamiento' => dirname(__DIR__) . '/storage',

    // Permite crear el primer administrador en /instalar.php.
    // Después de crearlo, póngalo en false.
    'instalacion_habilitada' => true,
];
