# Subir SumaRitmo Nivel 1 Piloto a Plesk

Paquete: `sumaritmo-nivel1-piloto-v1.11.2.zip`

Esta actualización renueva la apariencia de la app y de la página pública para que se lean bien en teléfono, tablet y escritorio, y agrega **Comparte tu logro**: un video vertical (y una imagen) para TikTok, Instagram o WhatsApp. No cambia la base de datos.

## Qué cambió

- Tipografía propia (Archivo, en `public/assets/fonts/`) y tamaños de letra legibles sin el `zoom` anterior.
- Mi día: la foto deportiva de cada sesión pasa a una portada con buen contraste; resumen del día en una sola franja; los registros (actividad, sueño, ánimo, comida) en pestañas.
- Teléfono: barra inferior con botón **Compartir**, respeto del notch y de la barra de inicio, campos de 16 px que no hacen zoom en iPhone.
- Escritorio: barra lateral con acceso directo a **Comparte tu logro**.
- Historial: botón **Compartir** en cada sesión.
- `api.php?share_history=1`: lista de solo lectura con las sesiones del último año del usuario que inició sesión.
- `config/bootstrap.php`: la política de seguridad permite reproducir el video generado en el navegador (`media-src 'self' blob:`).

## Antes de reemplazar archivos

1. En Plesk, crea un respaldo del sitio y exporta la base de datos `admin_aturitmo`.
2. Confirma que `config/plesk-secrets.php` existe. Ese archivo contiene las credenciales privadas y **no viene dentro del ZIP**; consérvalo sin cambios.
3. El sitio usa `public/` como raíz web. Mantén esa configuración.

## Base de datos

No requiere migraciones. Si aún no aplicaste `migration-v7.sql`, hazlo primero.

## Actualizar los archivos

1. En **Archivos**, entra a la carpeta raíz del subdominio `aturitmo.revelco.com.co`.
2. Sube `sumaritmo-nivel1-piloto-v1.11.2.zip` y extráelo allí. El ZIP contiene `config/`, `public/` y `sql/`.
3. Cuando Plesk pregunte si reemplazar, reemplaza los archivos de la app. Conserva `config/plesk-secrets.php`.
4. La página pública ahora es `public/landing.html`. Puedes borrar el `public/landing.php` antiguo del servidor; ya no se usa.
5. Abre `https://aturitmo.revelco.com.co` y actualiza con **Ctrl+F5** (en el teléfono, cierra y vuelve a abrir la pestaña).

## Recorrido de prueba

- En el teléfono: entra, revisa que no haya desplazamiento lateral y que la barra inferior no tape el contenido.
- Registra una actividad y un sueño desde las pestañas de **Registra tu día**.
- Pulsa **Crear publicación**: debe verse la vista previa animada. Cambia la fuente a una sesión del historial, marca y desmarca datos, escribe un lugar y prueba los tres estilos.
- Carga un GPX exportado de Garmin Connect o Strava (en la actividad: engranaje o tres puntos → Exportar a GPX). Debe dibujarse el recorrido.
- Pulsa **Crear video**, espera unos 7 s con la pantalla abierta y luego **Compartir video**. En el teléfono debe abrirse el menú para enviarlo a Instagram, TikTok o WhatsApp; en computador se descarga.
- Pulsa **Compartir imagen** y confirma que se comparte o descarga un PNG vertical.

## Notas

- Windows Defender marcaba `landing.php` como `Trojan:Script/Ulthar.A!ml`, un falso positivo de su detección automática (el archivo era HTML puro, sin código PHP). Desde v1.11.2 esa página es `landing.html`.

- El video se crea en el dispositivo. Safari (iPhone) y Chrome recientes graban MP4; si un navegador solo graba WebM, la app lo avisa y se puede usar la imagen.
- La grabación necesita que la pantalla quede abierta durante esos segundos; si la persona cambia de app, el video puede salir incompleto.
- El GPX nunca se envía al servidor ni se guarda.
