<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$controllerName = app_config('PRIVACY_CONTROLLER_NAME', 'REVELCO S.A.S. (Aturitmo)') ?? 'REVELCO S.A.S. (Aturitmo)';
$controllerNit = app_config('PRIVACY_CONTROLLER_NIT', '900.454.411') ?? '900.454.411';
$controllerAddress = app_config('PRIVACY_CONTROLLER_ADDRESS', 'Cra 34 #47-20, Villavicencio, Meta, Colombia') ?? 'Cra 34 #47-20, Villavicencio, Meta, Colombia';
$controllerEmail = app_config('PRIVACY_CONTACT_EMAIL', 'info@revelco.com.co') ?? 'info@revelco.com.co';
$controllerPhone = app_config('PRIVACY_CONTACT_PHONE', '316 831 8098') ?? '316 831 8098';
$hostingProvider = app_config('PRIVACY_HOSTING_PROVIDER', 'Boom Host') ?? 'Boom Host';
$hostingCountry = app_config('PRIVACY_HOSTING_COUNTRY', 'Canadá') ?? 'Canadá';
$policyVersion = '1.5';
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#C5BAAA">
  <meta name="description" content="Política de Tratamiento de Datos Personales de SumaRitmo, de REVELCO S.A.S. (Aturitmo).">
  <title>Política de datos — SumaRitmo</title>
  <link rel="stylesheet" href="assets/privacy.css?v=20261122-1">
</head>
<body>
  <header class="policy-header"><a class="policy-brand" href="index.php"><span>∿</span> SumaRitmo</a><a class="policy-back" href="index.php">Volver a SumaRitmo</a></header>
  <main class="policy-main">
    <div class="eyebrow">PRIVACIDAD Y CUIDADO</div>
    <h1>Política de Tratamiento de Datos Personales</h1>
    <p class="policy-lead">Te contamos qué datos utiliza SumaRitmo, para qué los usa y cómo puedes ejercer tus derechos.</p>
    <p class="policy-meta">Versión <?= e($policyVersion) ?> · Vigente desde el 22 de noviembre de 2026</p>

    <section class="policy-section">
      <h2>1. Responsable del tratamiento</h2>
      <p><strong><?= e($controllerName) ?></strong>, NIT <?= e($controllerNit) ?>, es responsable del tratamiento de los datos personales usados en SumaRitmo.</p>
      <dl class="controller-details">
        <div><dt>Domicilio y dirección</dt><dd><?= e($controllerAddress) ?></dd></div>
        <div><dt>Correo para solicitudes de privacidad</dt><dd><a href="mailto:<?= e($controllerEmail) ?>"><?= e($controllerEmail) ?></a></dd></div>
        <div><dt>Teléfono</dt><dd><a href="tel:+573168318098"><?= e($controllerPhone) ?></a></dd></div>
      </dl>
    </section>

    <section class="policy-section">
      <h2>2. Datos que podemos tratar</h2>
      <p>Para crear y proteger una cuenta tratamos nombre, correo electrónico, contraseña cifrada, rango de edad, objetivo personal, deportes que practicas y tiempo disponible. Si decides usar los registros, también tratamos la información que ingreses sobre actividad, duración, distancia, frecuencia cardiaca, calorías, esfuerzo, sensaciones del cuerpo, sueño, puntaje de sueño, HRV, alimentación, ánimo y peso.</p>
      <p>Si eliges leer una captura del reloj, la imagen se procesa en tu navegador para proponer valores. La imagen no se envía a SumaRitmo ni se conserva; solo guardamos los datos que confirmes en la pantalla de revisión.</p>
      <p>Si importas un CSV de Garmin Connect, el archivo completo se lee en tu navegador y no se guarda ni se envía. Solo después de pulsar “Importar actividades” se envían a tu cuenta los datos de actividad de los últimos 60 días. Las filas anteriores se dejan fuera y las actividades ya sincronizadas no se duplican.</p>
      <p>Los registros de peso, sueño y sensaciones físicas pueden revelar información relacionada con la salud, por lo que los tratamos con especial cuidado. No ingreses información que no quieras compartir.</p>
      <p>SumaRitmo está dirigido a personas de 18 años o más. No guardamos tu fecha de nacimiento. Garmin y Strava no están conectados en esta versión.</p>
    </section>

    <section class="policy-section">
      <h2>3. Para qué usamos los datos</h2>
      <ul>
        <li>Crear la cuenta, iniciar sesión y mantener la seguridad del servicio.</li>
        <li>Guardar y mostrar los registros personales que decidas ingresar o confirmar después de leer una captura.</li>
        <li>Importar, si lo solicitas, resúmenes de tus actividades Garmin de los últimos 60 días; el CSV original no se conserva.</li>
        <li>Mostrar resúmenes y comparaciones de tus propios registros y ofrecer orientación general dentro de la app. Los datos de frecuencia cardiaca, sueño y HRV se usan para mostrar tu historial y contexto personal, no para diagnóstico.</li>
        <li>Permitir la descarga o eliminación de tu cuenta y responder solicitudes relacionadas con tus datos.</li>
      </ul>
      <p>Las sugerencias no diagnostican ni sustituyen atención médica. Las comparaciones describen coincidencias entre tus registros y no establecen que un hábito haya causado un resultado.</p>
    </section>

    <section class="policy-section">
      <h2>4. Autorización y datos opcionales</h2>
      <p>Al crear una cuenta, aceptas el acuerdo de uso y autorizas el tratamiento descrito en esta política. Los registros de hábitos y pesajes son voluntarios y puedes dejar de ingresarlos cuando quieras.</p>
      <p>Cuando eliges una captura, la app solicita una confirmación puntual antes de leerla y guardar los valores que revisaste. Puedes usar el registro manual en vez de una captura.</p>
      <p>La importación de un CSV es voluntaria: la app te muestra una vista previa y solo guarda las actividades recientes después de que pulsas “Importar actividades”. Si vuelves a cargar el mismo archivo, las sesiones reconocidas como ya sincronizadas se omiten.</p>
      <p>La versión inicial no ofrece el registro de alcohol u otras sustancias. Si ya existen registros guardados en una versión anterior, siguen disponibles para descargarlos o eliminarlos desde las opciones de tu cuenta.</p>
    </section>

    <section class="policy-section">
      <h2>5. Con quién se comparten</h2>
      <p>No vendemos tus datos ni los usamos para publicidad. El proveedor de alojamiento informado para SumaRitmo es <strong><?= e($hostingProvider) ?></strong>. Boom Host declara que su infraestructura está en <?= e($hostingCountry) ?>, en el área metropolitana de Toronto. La ubicación asignada a esta cuenta concreta y las condiciones de respaldo aplicables al servicio Plesk deben confirmarse con el proveedor. Si activas la lectura de capturas, el navegador descarga el motor OCR y sus archivos de idioma desde jsDelivr y tessdata.projectnaptha.com; estos servicios reciben datos técnicos de conexión del navegador. El contenido de la captura se procesa localmente y no se les envía. Otros proveedores técnicos solo podrán tratar datos en lo necesario para operar, respaldar y proteger el servicio; también podremos atender requerimientos de autoridades cuando exista una obligación legal.</p>
      <p>El alojamiento puede implicar tratamiento de datos desde fuera de Colombia. REVELCO S.A.S. debe documentar con el proveedor las condiciones y garantías aplicables a ese tratamiento internacional antes de abrir el servicio al público.</p>
    </section>

    <section class="policy-section">
      <h2>6. Conservación y eliminación</h2>
      <p>Conservamos los datos mientras mantengas tu cuenta y sean necesarios para prestar el servicio. Puedes descargar tus registros o eliminar tu cuenta desde “Mi espacio”; al eliminarla se borran el perfil y sus registros de la base de datos principal. Las copias de respaldo se eliminan conforme al ciclo de retención del alojamiento. El plazo exacto aplicable a esta cuenta Plesk está pendiente de confirmación con Boom Host; hasta entonces no prometemos un plazo específico para la desaparición de copias de respaldo.</p>
    </section>

    <section class="policy-section">
      <h2>7. Tus derechos y cómo ejercerlos</h2>
      <p>Puedes solicitar conocer, actualizar, rectificar o suprimir tus datos, pedir prueba de tu autorización, revocarla cuando proceda y presentar consultas o reclamos sobre su tratamiento. Para hacerlo, escribe a <a href="mailto:<?= e($controllerEmail) ?>"><?= e($controllerEmail) ?></a> desde el correo asociado a tu cuenta e indica “Datos personales — SumaRitmo” en el asunto. Puedes descargar o borrar tu cuenta directamente desde la app.</p>
      <p>Atenderemos las consultas y reclamos dentro de los plazos establecidos por la normativa colombiana. Si consideras que tu solicitud no fue atendida, puedes acudir a la Superintendencia de Industria y Comercio, una vez agotado el trámite correspondiente ante el responsable.</p>
    </section>

    <section class="policy-section">
      <h2>8. Seguridad y cookies</h2>
      <p>La aplicación usa medidas técnicas como contraseñas almacenadas mediante funciones de hash, sesiones protegidas, solicitudes con token de seguridad y acceso a registros limitado a cada cuenta. También usa una cookie de sesión y una cookie necesaria para cambiar la imagen de fondo entre inicios de sesión; no son cookies publicitarias.</p>
    </section>

    <section class="policy-section">
      <h2>9. Cambios a esta política</h2>
      <p>Publicaremos aquí las versiones actualizadas. Si un cambio sustancial modifica la identidad del responsable o las finalidades del tratamiento, informaremos a las personas usuarias antes de aplicarlo.</p>
    </section>

    <div class="policy-footer"><a href="index.php?crear=1">Crear cuenta</a><a href="index.php">Inicio</a></div>
  </main>
</body>
</html>
