<?php
declare(strict_types=1);

/** Copy to plesk-secrets.php in this private config directory and fill locally. */
return [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'admin_',
    'DB_USER' => 'admin_chelart_quotes',
    'DB_PASSWORD' => 'PASTE_THE_DATABASE_PASSWORD_HERE',

    'QUOTE_NOTIFY_EMAIL' => 'chelartusa@gmail.com',
    'QUOTE_MAIL_FROM' => 'website@chelartconstruction.revelco.com.co',
    'QUOTE_ADMIN_USERNAME' => 'chelart-admin',
    'QUOTE_ADMIN_PASSWORD_HASH' => 'REPLACE_WITH_A_PHP_PASSWORD_HASH',
    'QUOTE_ADMIN_URL' => 'https://chelartconstruction.revelco.com.co/admin/',
];
