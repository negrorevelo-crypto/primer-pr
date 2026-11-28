<?php
declare(strict_types=1);
// Dirección corta para redes y material impreso: chelart.com/navidad -> página de la campaña en español.
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
header('Location: ' . app_base_path() . 'christmas-decoration-services/?lang=es', true, 301);
exit;
