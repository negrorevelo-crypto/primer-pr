# Chelart — primera maqueta

Sitio PHP responsive para revisar la nueva dirección visual de Chelart y recibir solicitudes guiadas de cotización. Preparado para alojarse en Plesk con MariaDB. El contenido de servicios “Home care” y “Project oversight” aparece como propuesta y no como disponibilidad confirmada.

## Estructura

- `public/`: raíz web recomendada; contiene el sitio, formulario y archivos estáticos.
- `config/`: inicio de sesión segura y conexión PDO; debe quedar fuera de la raíz web.
- `sql/schema.sql`: tablas para guardar las encuestas y los servicios elegidos.
- `.env.example`: nombres de las variables de entorno requeridas; no contiene credenciales reales.

## Vista local

Con Node.js instalado, ejecuta `node tools/preview-server.mjs` desde esta carpeta y abre `http://127.0.0.1:4173`. Esta vista sirve la página y el formulario para revisión de diseño; el envío se desactiva intencionalmente y no guarda ni transmite datos. El proceso debe seguir abierto mientras se revisa el sitio.

El idioma se elige automáticamente con la preferencia principal del navegador (español o inglés). El control `ES / EN` permite cambiarlo manualmente y conserva la elección en ese navegador. La interfaz incluye puntos de adaptación para escritorio, tableta y móvil.

## Publicación en Plesk

Subdominio de revisión creado: `chelartconstruction.revelco.com.co`.

1. En Plesk, abrir `ChelartConstruction.revelco.com.co` y configurar la raíz de documentos para que apunte a `ChelartConstruction.revelco.com.co/public` (la carpeta `public` dentro de la ruta que aparece actualmente en Hosting Settings). El botón **Preview** permite mostrar el sitio antes de cambiar DNS; los enlaces de la aplicación reconocen el prefijo temporal de Plesk.
2. Elegir PHP 8.2 o superior y habilitar `PDO`, `pdo_mysql` y sesiones. HTTPS debe estar activo.
3. Crear una base de datos MariaDB y un usuario separado para la aplicación, con permisos sobre esa base.
4. Seleccionar la base en phpMyAdmin y ejecutar `sql/schema.sql`.
5. En Windows/IIS, guardar la configuración privada en `config/plesk-secrets.php`, que debe estar en la carpeta del dominio, al mismo nivel que `public/` y fuera de la raíz web. Copiar `config/plesk-secrets.example.php` con ese nombre y completar los valores localmente. Se puede usar el editor de Plesk; nunca poner el archivo dentro de `public/` ni compartir su contenido. `DB_NAME` es el nombre que muestra Plesk (`admin_`) y `DB_USER` es `admin_chelart_quotes`. El remitente debe ser un buzón o remitente autorizado por el dominio.
6. Subir `config/` y el contenido de `public/` conservando esta estructura. No subir `sql/` al directorio público; conservarlo fuera de la raíz web o usarlo localmente. Visitar el subdominio.

El formulario tiene cuatro pasos y guarda la solicitud y los servicios seleccionados en una transacción MariaDB. Incluye validación del lado servidor, token CSRF, campo trampa y límite sencillo de envíos repetidos por sesión. Después del guardado, se pone en cola un aviso por el transporte de correo PHP del hosting hacia `QUOTE_NOTIFY_EMAIL` (por omisión `chelartusa@gmail.com`); el mensaje incluye los datos de la solicitud, un código de referencia y el enlace privado al historial. Las respuestas se dirigen al correo del cliente. Si el correo del hosting no está configurado, la solicitud sigue guardada en MariaDB y el fallo queda en el log de PHP.

El historial está en `public/admin/` y requiere HTTPS, usuario y contraseña. Configura `QUOTE_ADMIN_USERNAME` y `QUOTE_ADMIN_PASSWORD_HASH` en Plesk; el hash debe generarse con `password_hash('contraseña', PASSWORD_DEFAULT)` en PHP. La sesión vence tras 30 minutos de inactividad, el login tiene límite de intentos y se exige token CSRF para iniciar/cerrar sesión. El aviso por correo enlaza a `QUOTE_ADMIN_URL`, cuya dirección predeterminada es `https://chelartconstruction.revelco.com.co/admin/`. El historial no es público.

## Imágenes

`chelart-logo.png` conserva el archivo original. `chelart-logo-transparent.png` es la versión preparada para la web con transparencia exterior y mayor tamaño en el encabezado. `public/assets/img/projects/` conserva los siete proyectos y las diez fotos de proyecto del PDF de Chelart; `public/assets/img/clients/` conserva las fotos de Diane Delgado y Coy Ingram. Si existen las fotos originales en resolución superior, pueden reemplazar los recortes del PDF sin cambiar proyectos ni clientes.

`hero-home-placeholder.png` es una imagen conceptual provisional pendiente de una foto premium definitiva de Canva. Las cinco imágenes compartidas para servicios ya están en `public/assets/img/services/`. Ver `CANVA-ASSETS.md` para la búsqueda pendiente de portada; no usar imágenes de stock en Our Projects ni Our Satisfied Clients.

## Estado de esta copia

La vista local es una maqueta de revisión y no guarda ni transmite envíos. La base de datos y variables de correo/admin deben configurarse en Plesk para activar cotizaciones reales. En el equipo de edición no está instalado PHP, así que la ejecución del backend debe verificarse en el hosting después de configurarlo.
