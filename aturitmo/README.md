# SumaRitmo — segunda revisión

Aplicación web en español para registrar deporte multideporte, sueño, alimentación y ánimo, y recibir una sugerencia diaria sencilla. Usa PHP, MariaDB y JavaScript. La versión se diseñó para alojarse en un hosting PHP como los otros sitios del espacio.

La filosofía de producto es mejorar frente al propio punto de partida, sin competir contra otras personas. La orientación cruza actividad, recuperación, objetivo y sensaciones; funciona sin reloj y deja claro qué señales tomó en cuenta. Landing y app usan la paleta Concrete `#C5BAAA`, Craft `#806D61`, Bright Brick `#EE7828` y Graphite `#2C2C2C`.

## Funciones incluidas

- Crear cuenta e iniciar/cerrar sesión.
- Confirmar mayoría de edad y elegir una franja de edad sin guardar la fecha de nacimiento; al elegirla, la casilla de mayoría de edad se marca automáticamente y sigue siendo editable.
- Aceptar el acuerdo de uso, con fecha y versión guardadas al crear la cuenta.
- Consultar la Política de Tratamiento de Datos Personales desde el landing, el registro y la app.
- Mostrar de forma difuminada una imagen deportiva distinta en cada nuevo inicio de sesión.
- Elegir un objetivo personal: bienestar, constancia, fuerza o rendimiento.
- Registrar varias disciplinas: fútbol, pádel, tenis, natación, correr, ciclismo, fuerza, caminata, baloncesto, baile, yoga y otra actividad.
- Guardar duración, esfuerzo percibido y cómo se siente el cuerpo después de una sesión, todos opcionales salvo el deporte y la duración.
- Registrar fecha, distancia, frecuencia cardiaca promedio y calorías de una actividad cuando el usuario las tenga disponibles.
- Leer una captura seleccionada en el navegador con OCR opcional; la persona confirma los campos antes de guardar. La imagen no se sube ni se almacena. El motor OCR y los archivos de idioma se descargan desde jsDelivr y tessdata.projectnaptha.com cuando la persona pulsa “Leer datos de la captura”; si esos servicios no están disponibles, puede completar el registro manualmente.
- Importar desde Garmin Connect un CSV de actividades, con vista previa y filtro de los últimos 60 días. El CSV se lee localmente; solo se envían los registros recientes al confirmar, y la clave de origen evita duplicados al sincronizar de nuevo.
- Anotar horas de sueño, puntaje del dispositivo y HRV como valores opcionales, junto con la sensación al despertar.
- Configurar deportes preferidos, días disponibles y tiempo habitual por sesión para dar contexto a las sugerencias.
- Mostrar “Nivel 1 · A tu ritmo” como acceso piloto sin cobros; se guardan estado, plan y referencias de facturación futura, pero no hay cobros activos.
- Revisar un resumen de cada registro antes de guardarlo y conservar la fuente declarada (manual, captura o CSV de Garmin).
- Mostrar el periodo inicial de 7 días de referencia para dejar claro cuándo las sugerencias todavía usan pocos datos.
- Registrar pesajes opcionales con su fecha y consultar el peso más reciente y el historial.
- Registrar comida y bebidas.
- Registrar movilidad y equilibrio, sueño, ánimo y notas cortas.
- El registro de alcohol u otras sustancias está desarrollado, pero queda fuera de la versión inicial.
- Ver resumen diario, historial del día y actividad de la semana.
- Configurar una meta semanal de actividad.
- Recibir orientación basada en reglas sencillas y consultar por qué aparece.
- Descargar registros propios en CSV o eliminar la cuenta y los registros asociados.
- **Comparte tu logro:** crear una publicación vertical 1080×1920 (video animado de unos 6 s y también imagen PNG) con el día, la semana o cualquier sesión del último año. La persona elige qué datos se ven (distancia, tiempo, ritmo o velocidad, pulso medio y máximo, calorías, esfuerzo, sensación), el lugar, una frase y uno de tres estilos (Ladrillo, Foto, Grafito). Puede cargar el GPX de Garmin o Strava para dibujar el recorrido; se lee en el navegador, no se guarda, y por defecto oculta los primeros y últimos 300 m. El video se graba en el dispositivo (MP4 en Safari y Chrome recientes; WebM en navegadores que no graban MP4) y se comparte con el menú del teléfono hacia TikTok, Instagram o WhatsApp, o se descarga.
- Diseño adaptable a teléfono (barra inferior, zona segura del notch, campos sin zoom en iOS), tablet y escritorio (barra lateral), con tipografía Archivo alojada en el propio sitio.

## Requisitos

- PHP 8.2 o superior.
- Extensiones PHP: `PDO`, `pdo_mysql` y `mbstring`.
- MariaDB o MySQL compatible.
- HTTPS para despliegue público.

## Preparar la base de datos

1. Crea una base de datos y un usuario de aplicación con permisos solo sobre esa base.
2. Para una instalación nueva, ejecuta `sql/schema.sql`. Si ya tienes una versión anterior, haz una copia de seguridad y ejecuta las migraciones pendientes una sola vez y en orden, hasta `migration-v7.sql`. Desde v6 ejecuta únicamente `migration-v7.sql`.
3. Copia `config/plesk-secrets.example.php` como `config/plesk-secrets.php` y completa las credenciales reales. Ese archivo privado está excluido de Git y debe permanecer fuera de la raíz web.
4. Configura la raíz del sitio para que apunte a la carpeta `public/`.
5. Activa HTTPS y visita `index.php`.

Para ver la interfaz en local, desde la carpeta del proyecto ejecuta `php -S 127.0.0.1:8000 -t public` y abre `http://127.0.0.1:8000`. La base de datos debe estar configurada para crear cuentas y guardar datos.

## Protección de datos

Las contraseñas se guardan con `password_hash()`. Las consultas usan parámetros preparados; las rutas de escritura requieren sesión y token CSRF; el acceso a los registros se filtra por usuario. Las credenciales viven fuera de `public/`. Cada persona puede descargar o eliminar los registros de su cuenta. La aceptación del acuerdo de uso y la Política de Tratamiento de Datos se registra con su fecha y versión. La lógica desarrollada para registros sensibles solicita autorización explícita por envío; esta función no está expuesta en la versión inicial.

La política está en `public/privacy.php`. El responsable, su domicilio en Villavicencio, Meta, y el proveedor informado (Boom Host) ya están identificados. Boom Host declara que su infraestructura está en Canadá; antes de abrir el servicio al público, confirma con el proveedor la ubicación asignada a esta cuenta Plesk, el plazo de retención de sus copias de respaldo y las condiciones/garantías para el tratamiento internacional de datos.

La app registra la información que la persona decide proporcionar. La franja de edad se usa como contexto de orientación; no se guarda la fecha de nacimiento. No comparte datos con proveedores externos en esta versión. Antes de abrir el servicio al público, configura recuperación y verificación de correo, revisa la política de privacidad y los procedimientos de respaldo y eliminación del hosting.

## Alcance de esta primera versión

- Las conexiones Garmin y Strava se muestran como próximas; no hay OAuth activo ni se leen datos externos.
- El plan Nivel 1 está en estado piloto. No hay cobros ni suscripciones activas con pasarela de pago, y las recomendaciones no se generan con IA.
- Las recomendaciones son reglas iniciales basadas en los registros disponibles, el objetivo y algunas señales de esfuerzo y recuperación. No diagnostican, no garantizan prevenir lesiones y no son planes técnicos de entrenamiento ni consejo médico.
- El perfil y los rangos de edad aportan contexto. Las recomendaciones por edad son generales y no sustituyen una valoración profesional.
- La meta semanal suma minutos de distintas disciplinas. La app conserva detalles específicos por sesión, pero aún no calcula métricas distintas para natación, pádel, fútbol u otros deportes.
- Garmin y Strava siguen sin conexión automática. En esta versión, se pueden importar resúmenes de actividad desde CSV (solo 60 días, con deduplicación) o leer capturas en el navegador. FIT/ZIP y la API empresarial de Open Wearables quedan para la siguiente etapa.
- Los archivos `index.html`, `styles.css` y `app.js` de la raíz conservan el prototipo visual anterior como referencia. La app con cuentas empieza en `public/index.php`.
