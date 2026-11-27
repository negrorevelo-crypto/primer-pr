<?php
declare(strict_types=1);
// Dirección corta para redes y material impreso: chelart.com/christmas -> página de la campaña en inglés.
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
header('Location: ' . app_base_path() . 'christmas-decoration-services/?lang=en', true, 301);
exit;
