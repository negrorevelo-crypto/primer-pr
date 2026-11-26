<?php
require dirname(__DIR__) . '/app/bootstrap.php';
redirigir(url(usuario() ? 'inicio.php' : 'login.php'));
