# Campaña CHRISTMAS DECORATION SERVICE — Black Friday 2026

Página: **https://chelart.com/christmas-decoration-services/**
Administración: **https://chelart.com/admin/christmas.php** (mismo usuario y contraseña del historial actual)

## 1. Arquitectura encontrada y cómo se integró

| Aspecto | Sitio actual | Cómo lo usa la campaña |
|---|---|---|
| Lenguaje / framework | PHP 8 sin framework, Plesk (Windows/IIS) | Mismas páginas PHP; URL amigable con una carpeta (`christmas-decoration-services/index.php`), sin reglas de reescritura |
| Base de datos | MariaDB por PDO (`config/database.php`) | Misma conexión; consultas preparadas (parametrizadas) |
| Cotizaciones | `quote.php` → `api/quote.php` → `quote_requests` | Formulario y tablas propios; **el formulario general no se tocó** |
| Autenticación admin | Sesión de `/admin/` (HTTPS, hash de contraseña, 30 min, CSRF, límite de intentos) | Se reutiliza la misma sesión; no hay un login nuevo |
| Correo | `mail()` de PHP con el transporte del hosting, remitente `QUOTE_MAIL_FROM` | Mismo transporte y remitente. **No se cambió ninguna credencial ni configuración SMTP** |
| Idiomas | `i18n.js` traduce los textos en el navegador; inglés por defecto | Diccionario adicional `christmas-i18n.js`; el selector EN/ES controla página, formulario, validaciones, confirmación y el idioma del correo al cliente |

## 2. Tablas nuevas (`sql/christmas_campaign.sql`)

No se reutilizó `quote_requests` porque exige campos que este formulario no tiene (plazo y descripción del proyecto) y le faltan dirección, fechas, paquete, cotización y notas. Ampliarla habría afectado el formulario general.

**`christmas_quote_requests`**: una fila por solicitud.
- `request_number`: identificador único `XMAS-XXXXXXXX` (índice único).
- Datos del cliente y del inmueble: `full_name`, `email`, `phone`, `contact_method` (phone/whatsapp/email), `property_address`, `city`, `zip_code`, `property_type`, `property_size`.
- Servicio: `package_type` (essential/premium/luxury/custom), `decoration_areas` (claves separadas por coma), `decoration_supplier`, `installation_date`, `removal_date` (DATE, opcionales), `additional_details`, `uploaded_photos` (cantidad).
- Autorización: `privacy_consent`, `consent_at` (UTC), `language` (en/es).
- Campaña: `campaign_source` (= `BLACK_FRIDAY_2026` y `discount_percentage` = 15 hasta el 30 de noviembre; después `CHRISTMAS_2026` y 0).
- Gestión interna: `estimated_price`, `final_quote` (DECIMAL, nunca públicos), `request_status` (`NEW_REQUEST` por defecto), `internal_notes`, `created_at`, `updated_at`.
- Índices: estado+fecha, paquete, fecha de instalación, correo, ciudad, campaña.

**`christmas_quote_photos`**: fotos adjuntas (relación 1‑N con borrado en cascada): `request_id`, `stored_name` (nombre aleatorio), `original_name`, `mime_type`, `size_bytes`, `width`, `height`.

Estados: NEW REQUEST, CONTACTED, SITE VISIT SCHEDULED, QUOTE SENT, APPROVED, SCHEDULED, COMPLETED, CANCELLED.

## 3. Archivos

Nuevos:
- `config/christmas.php`: interruptor, fechas de la promoción, catálogos EN/ES, estados.
- `config/christmas_notifications.php`: correo al equipo (con fotos adjuntas) y confirmación al cliente.
- `config/christmas_admin.php`: verificación de la sesión del área privada.
- `public/christmas-decoration-services/index.php` (página y formulario) y `thanks.php` (confirmación).
- `public/api/christmas-quote.php`: validación, guardado, fotos y correos.
- `public/admin/christmas.php` (CHRISTMAS SERVICE LEADS) y `admin/christmas-photo.php` (fotos solo con sesión).
- `public/assets/css/christmas.css`, `assets/js/christmas.js`, `assets/js/christmas-i18n.js`.
- `sql/christmas_campaign.sql`, `storage/` (carpeta privada de fotos con bloqueo `.htaccess` y `web.config`).

Modificados, con el motivo:
- `public/index.php`: franja de temporada debajo del hero (solo con la campaña activa) y carga del diccionario de Navidad.
- `public/assets/js/i18n.js`: suma diccionarios adicionales. Además, las meta descripciones ahora se traducen por página; antes cada página recibía la de la portada.
- `public/assets/css/site.css`: estilos de la franja de la portada.
- `public/admin/index.php`: enlace a Christmas Service Leads y regreso al módulo después de iniciar sesión.
- `public/quote.php`, `quote-thanks.php`, `api/quote.php`: solo la versión `?v=20261126` de CSS/JS, para que el navegador cargue los archivos nuevos.
- `public/sitemap.xml`: nueva URL. `docker-compose.yml`: crea las tablas nuevas en local. `.gitignore`: excluye las fotos de clientes.

## 4. Publicación en Plesk (en este orden)

1. **Respaldo**: Plesk → Databases → la base del sitio → *Export Dump* (o phpMyAdmin → Exportar → SQL). Guarda el archivo.
2. phpMyAdmin → selecciona la base → pestaña SQL → pega y ejecuta `sql/christmas_campaign.sql`. Solo crea tablas; se puede repetir sin riesgo.
3. Sube `config/` y `public/` como siempre (sin tocar el `plesk-secrets.php` del servidor). Sube también `storage/` al mismo nivel que `public/` y `config/`.
4. En el Administrador de archivos de Plesk, da permiso de **escritura** sobre `storage/christmas-uploads` al usuario de la aplicación (IIS_IUSRS / usuario del pool del sitio).
5. Configuración PHP del dominio: `upload_max_filesize` ≥ 8M y `post_max_size` ≥ 50M. El navegador reduce las fotos antes de enviarlas (normalmente < 1 MB), así que funciona incluso con valores menores.
6. Prueba con una solicitud real tuya: revisa el correo del equipo, la confirmación y el registro en `/admin/christmas.php`. Después márcala como CANCELLED.
7. Opcional: sube la foto definitiva de temporada como `public/assets/img/christmas/christmas-home-hero.jpg` junto con las versiones `-480`, `-800`, `-1200` y `-1600` `.jpg`. La página la usa automáticamente; mientras no exista, muestra la foto actual de la portada con una guirnalda gráfica.

## 5. Activar, desactivar y ajustar (sin borrar datos)

En `config/plesk-secrets.php` (o como variables de entorno):

| Ajuste | Valor por omisión | Efecto |
|---|---|---|
| `CHRISTMAS_CAMPAIGN_ENABLED` | `1` | `0` oculta la página (redirige 302 a la portada), quita la franja y cierra el formulario. El admin y los datos siguen disponibles |
| `CHRISTMAS_PROMO_DEADLINE` | `2026-12-01` | Desde esa fecha (hora de Florida) desaparecen las etiquetas del 15% y las solicitudes se guardan sin descuento |
| `CHRISTMAS_DISCOUNT_PERCENT` | `15` | Porcentaje mostrado y guardado |
| `CHRISTMAS_CAMPAIGN_CODE` | `BLACK_FRIDAY_2026` | Valor de `campaign_source` durante la promoción |
| `CHRISTMAS_NOTIFY_EMAIL` | (vacío = `QUOTE_NOTIFY_EMAIL`) | Buzón que recibe los avisos |
| `CHRISTMAS_UPLOAD_DIR` | `storage/christmas-uploads` | Carpeta privada de fotos |

## 6. Seguridad

- Consultas preparadas en todo el código; valores de listas validados contra catálogos cerrados.
- Escape HTML de todo lo que se muestra (XSS); token CSRF; campo trampa; límite de 1 envío cada 20 s por sesión y de 3 por correo cada 24 h.
- Fotos: máximo 6, de 8 MB cada una, solo JPG/PNG/WebP. El tipo se verifica por contenido (no por extensión) y GD las vuelve a codificar, lo que elimina archivos disfrazados y metadatos como el GPS. Se guardan fuera de la raíz web con nombre aleatorio y solo se ven con sesión.
- El CSV exportado neutraliza fórmulas de Excel.
- Sin precios públicos, sin cobros y sin envío automático de cotizaciones. Los correos aclaran que no son un precio final.

## 7. Pendientes que debe definir Chelart

- Foto de temporada definitiva (ver paso 7). No se publicó ninguna imagen inventada.
- Revisar el texto del aviso de privacidad del formulario o enlazar una política oficial.
- Precios de los paquetes: no se publican hasta que Chelart los autorice.
