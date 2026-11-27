# Portal de entrenamiento de ofertas

Portal web donde cada corredor inicia sesión, calibra su pantalla, juega la simulación de lanzamiento de ofertas y ve sus resultados. El administrador (Revelco) crea las cuentas, registra los pagos y controla los vencimientos.

## Cómo funciona el acceso

```
Pago (transferencia / Nequi)  →  Administrador activa la licencia  →  Corredor recibe usuario y contraseña
```

- **No hay registro abierto.** Solo el administrador crea cuentas.
- **Contraseña temporal:** al crear un usuario el sistema genera una contraseña temporal y arma el mensaje para enviarlo por WhatsApp o correo. En su primer ingreso el corredor debe crear su propia contraseña.
- **Licencia por periodo:** mensual, trimestral, semestral, anual u otro. Sin licencia vigente el corredor entra, pero solo ve su panel con el botón para pedir la activación. No puede calibrar ni jugar.
- **Dos formas de venta:**
  - **Individual:** el corredor paga su licencia. Al activarla se registra el pago en el mismo formulario.
  - **Paquete de la firma:** la firma compra N cupos con fechas. El botón **Asignar** reparte un cupo a cada corredor de la firma sin licencia vigente.
- **Ranking solo dentro de cada firma.** Un corredor sin firma solo ve sus propios tiempos.
- **Roles:**
  - **Corredor:** juega y ve el ranking de su firma.
  - **Responsable de firma:** además ve la tabla de todos los corredores de su firma.
  - **Administrador:** todo, incluida la administración.
- **Seguridad:**
  - Contraseñas cifradas (bcrypt).
  - Bloqueo de 15 minutos tras 5 intentos fallidos.
  - Protección CSRF en todos los formularios.
  - La sesión se cierra tras 8 horas de inactividad.
  - Registro de acciones en la tabla `auditoria`.
  - Cada partida usa un token de un solo uso, para que no se puedan reportar tiempos imposibles.

## Escenarios del juego

| Escenario | Qué entrena |
| --- | --- |
| **Subasta (réplica de la rueda)** | La pantalla de subasta descendente de la grabación del 24 de noviembre: reloj hasta la apertura, aparece **Lanzar Oferta**, ventana **Colocación** con precio base y **Soy humano**, **Lanzar** y **Aceptar**. Un operador simulado compite por el calce (normal 4,5 s, exigente 3,5 s, experto 2,8 s). |
| **Lanzamiento básico** | El juego original, con las ventanas del puesto calibrado. |

**Tiempos de la plataforma simulada:** en **Administración › Simulador** se ajusta cuánto tarda cada momento de la réplica: el botón tras la apertura, la ventana Colocación, la verificación Soy humano y la ventana Atención. Cada partida sortea un valor entre el mínimo y el máximo, casi siempre cerca del típico. Hay tres perfiles listos: *Como la grabación*, *Rueda congestionada* y *Rueda ágil*. El ajuste se guarda en `storage/ajustes-simulador.json`.

Cada escenario tiene sus propias marcas y su propio ranking. La réplica no usa el logo ni la dirección de la plataforma real, y se identifica como simulación.

## Base de datos

| Tabla | Contenido |
| --- | --- |
| `firmas` | Firmas comisionistas |
| `usuarios` | Cuentas: rol, firma, contraseña cifrada, bloqueo, último acceso |
| `paquetes` | Cupos comprados por una firma, con fechas |
| `licencias` | Derecho de un usuario a entrar entre dos fechas (individual, de paquete o cortesía) |
| `pagos` | Pagos manuales registrados por el administrador |
| `calibraciones` | Perfil de pantalla de cada corredor (la más reciente es la que usa el juego) |
| `intentos` | Cada partida: resultado, reacción, tiempo total, valor, errores |
| `auditoria` | Ingresos, cambios de contraseña, licencias creadas, etc. |

El esquema completo está en `database/schema.sql`.

## Estructura de carpetas

```
portal/
├── app/          ← código y config.php (NO público)
├── database/     ← schema.sql (NO público)
├── storage/      ← capturas de pantalla subidas (NO público)
└── public/       ← lo único que se publica (raíz del subdominio)
    ├── login.php, inicio.php, jugar.php, resultados.php, calibrar.php
    ├── admin/    ← usuarios, firmas y cupos, pagos
    ├── api/      ← guardado de partidas y calibraciones
    └── assets/   ← estilos y scripts
```

## Instalación en Plesk (subdominio)

> **Servidor Windows (IIS):** la raíz del documento se escribe con barra invertida: `entrenamiento.tudominio.com\public`. Las carpetas `app`, `database` y `storage` traen un `web.config` que bloquea su contenido, porque IIS no lee los archivos `.htaccess`. Si `instalar.php` responde **404**, la raíz del documento no apunta a `public`.

1. **Crear el subdominio.** En Plesk entre a **Sitios web y dominios → Agregar subdominio**, por ejemplo `entrenamiento.tudominio.com`. En **Raíz del documento** escriba `entrenamiento.tudominio.com/public`. Así solo la carpeta `public` queda expuesta a internet.
2. **Versión de PHP.** En **Configuración de PHP** del subdominio elija PHP 8.1 o superior.
3. **Subir los archivos.** En el **Administrador de archivos**, entre a la carpeta `entrenamiento.tudominio.com` y suba ahí el contenido de `portal/`. Deben quedar las carpetas `app`, `database`, `storage` y `public` una al lado de la otra.
4. **Crear la base de datos.**
   1. Vaya a **Bases de datos → Agregar base de datos**. Use por ejemplo el nombre `entrenamiento` y cree un usuario con contraseña.
   2. Abra **phpMyAdmin**, pestaña **Importar**, y seleccione `database/schema.sql`.
5. **Configurar.** En `app/`, copie `config.example.php` como `config.php` y complete el nombre de la base de datos, el usuario y la contraseña. También puede cambiar ahí el correo y el WhatsApp de contacto, y el lienzo del simulador (`1920x1080`, o la resolución del equipo donde se haga la grabación).
6. **Permisos.** La carpeta `storage` debe poder escribirse. Plesk lo permite por defecto.
7. **HTTPS.** En **SSL/TLS Certificates**, active **Let's Encrypt** y marque **Redirigir de HTTP a HTTPS**.
8. **Crear el administrador.**
   1. Abra `https://entrenamiento.tudominio.com/instalar.php` y cree su usuario.
   2. Después ponga `'instalacion_habilitada' => false` en `config.php` y borre `public/instalar.php`.
9. **Copias de seguridad.** En **Administrador de copias de seguridad**, programe una copia diaria que incluya la base de datos.

## Uso diario del administrador

1. **Firmas y cupos → Nueva firma**, por ejemplo Mercado y Bolsa S.A.
2. **Usuarios → Nuevo usuario.** Escriba nombre, usuario, celular y firma. El sistema muestra la contraseña temporal una sola vez, con los botones **Copiar mensaje** y **Enviar por WhatsApp**.
3. En la ficha del usuario, en **Agregar licencia**:
   - Si el pago es individual: elija el plan y registre el valor, el medio y la referencia.
   - Si paga la firma: elija el origen **Cupo de un paquete de la firma**.
4. **Usuarios → filtro "Vence en 7 días"** para ver a quién hay que renovar.
5. **Pagos:** pagos del mes, con descarga a Excel (CSV).

Si alguien olvida su contraseña, entre a su ficha y use **Generar contraseña temporal**.

## Probar en un computador

Necesita PHP 8.1+ y MySQL o MariaDB:

```bash
mysql -u root -e "CREATE DATABASE entrenamiento CHARACTER SET utf8mb4"
mysql -u root entrenamiento < database/schema.sql
cp app/config.example.php app/config.php   # y complete los datos
php -S localhost:8000 -t public
```

Abra `http://localhost:8000/instalar.php`.

## Pendiente

- **Escenario real:** cuando llegue la grabación de la sesión, se reemplaza el escenario `lanzamiento-v1` del juego por la réplica.
- **Pagos y competencia:** la pasarela de pago en línea y la competencia en vivo quedan para una siguiente fase. La tabla `pagos` ya acepta el medio `pasarela`.
