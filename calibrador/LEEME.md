# Calibrador de pantalla

Página web que cada corredor abre en su computador para registrar cómo está configurada su pantalla. El simulador de entrenamiento usa ese perfil para encajar en cada pantalla.

## Qué hace

1. **Detecta la pantalla:** resolución real, escala de Windows, área útil del navegador, zoom, navegador y versión de Windows.
2. **Detecta varios monitores:** en Chrome y Edge, con permiso del usuario. También se pueden agregar a mano.
3. **Prueba de ajuste:** abre en pantalla completa el lienzo del simulador. El corredor confirma que ve las 4 esquinas y que el círculo se ve redondo.
4. **Mi puesto:** el corredor arrastra y redimensiona las ventanas para que queden como en su computador.
5. **Envío:** guarda el perfil en el servidor con hasta 3 capturas de pantalla y le muestra un código al corredor.
6. **Panel de administración** (`admin.php`): tabla con todos los perfiles, capturas y exportación a Excel.

## Archivos

| Archivo | Para qué |
| --- | --- |
| `index.html` | La página que abren los corredores |
| `guardar.php` | Recibe y guarda los perfiles |
| `admin.php` | Panel para ver los perfiles (con contraseña) |
| `config.php` | Contraseña del panel y carpeta de datos |

## Instalación en Plesk

1. En Plesk, abra el dominio o subdominio (por ejemplo `entrenamiento.midominio.com`).
2. Verifique que el sitio tenga PHP 8.0 o superior: **Configuración de PHP**.
3. Abra el **Administrador de archivos**, entre a `httpdocs` y cree la carpeta `calibrador`.
4. Suba los 4 archivos a esa carpeta.
5. Edite `config.php` y cambie `ADMIN_PASSWORD` por una contraseña segura. Con la clave por defecto el panel no deja entrar.
6. Active el certificado SSL: **SSL/TLS Certificates › Let's Encrypt**. La detección de varios monitores solo funciona con HTTPS.
7. Si las capturas pesan más de 8 MB, suba en **Configuración de PHP** los valores `upload_max_filesize` a 12M y `post_max_size` a 40M.

Los datos se guardan en `calibrador_datos`, al lado de `httpdocs` y fuera de la carpeta pública. Esa carpeta se crea sola con el primer envío.

## Enlaces

- Para los corredores: `https://su-dominio/calibrador/`
- Con la resolución de la grabación: `https://su-dominio/calibrador/?lienzo=1920x1080`. Cambie `1920x1080` por la resolución del equipo grabado.
- Panel: `https://su-dominio/calibrador/admin.php`

## Datos que quedan en cada perfil

Cada envío es un archivo JSON en `calibrador_datos/perfiles/`. Ahí quedan:

- La detección de la pantalla.
- La escala que confirmó el corredor.
- Los monitores y su ubicación.
- El resultado de la prueba de ajuste (factor de escala y área útil).
- Las ventanas en píxeles de Windows y en proporción de su monitor.

Más adelante el simulador lee esos archivos para mostrar a cada corredor su distribución.

## Probar en su computador

```bash
cd carpeta-que-contiene-calibrador
php -S localhost:8000
```

Luego abra `http://localhost:8000/calibrador/`.
