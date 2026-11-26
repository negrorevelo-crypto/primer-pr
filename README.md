# primer-pr

Repositorio de práctica para aprender a trabajar con ramas y pull requests en GitHub.

## Cómo empezar

1. Clona el repositorio:

   ```bash
   gh repo clone negrorevelo-crypto/primer-pr
   ```

2. Crea una rama para tu cambio:

   ```bash
   git switch -c mi-cambio
   ```

3. Haz tus cambios, guárdalos y súbelos:

   ```bash
   git add .
   git commit -m "Describe tu cambio"
   git push -u origin mi-cambio
   ```

4. Abre un pull request:

   ```bash
   gh pr create
   ```

---

# Sitio Chelart — entorno local

El sitio de Chelart (PHP 8 + MariaDB, alojado en Plesk de Revelco) está en `chelart/`, con la misma estructura del hosting:

| Carpeta | Qué es |
|---|---|
| `chelart/public/` | Raíz web (Document root en Plesk): páginas, `admin/`, `api/`, `assets/` |
| `chelart/config/` | Arranque PHP, conexión a la base y correo. Queda **fuera** de la raíz web |
| `chelart/sql/schema.sql` | Tablas de cotizaciones |

> Las credenciales reales (`config/plesk-secrets.php`) **no** están en el repo y nunca deben subirse (`.gitignore` las bloquea). En local se usan valores de prueba.

## Opción A — Docker (recomendada)

Requiere [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
docker compose up -d --build
```

| Servicio | URL |
|---|---|
| Sitio | http://localhost:8080 |
| Historial privado | http://localhost:8080/admin/ — usuario `admin`, contraseña `chelart-local-2026` |
| Correos de cotización (Mailpit) | http://localhost:8025 |
| phpMyAdmin | http://localhost:8081 |

La base de datos se crea sola con `schema.sql` la primera vez. Los cambios que hagas en `chelart/` se ven al recargar el navegador (no hay que reconstruir). Para detener:

```bash
docker compose down
```

Para borrar también la base local y empezar de cero:

```bash
docker compose down -v
```

## Opción B — Sin Docker (PHP instalado)

Requiere PHP 8.2+ con `pdo_mysql`. Las páginas se ven sin base de datos; para enviar cotizaciones y usar `/admin/` necesitas MariaDB local con la base `chelart_local` (usuario y clave en `dev/local.env.example`) y ejecutar `chelart/sql/schema.sql`.

```bash
./dev/servidor-local.sh
```

En Windows (PowerShell):

```bash
powershell -ExecutionPolicy Bypass -File dev/servidor-local.ps1
```

Abre http://localhost:8080. Para cambiar valores, copia `dev/local.env.example` como `.env` en la raíz.

## Campaña de Navidad (Black Friday 2026)

Página `/christmas-decoration-services/` y módulo `/admin/christmas.php`. Las tablas nuevas, los archivos modificados, los pasos para publicar en Plesk y el interruptor de activación están en [`chelart/CAMPANA-NAVIDAD.md`](chelart/CAMPANA-NAVIDAD.md). Antes de publicar, ejecuta `chelart/sql/christmas_campaign.sql` en la base, previo respaldo.

## Flujo de trabajo

1. Crea una rama para cada cambio, edita en `chelart/` y revisa en http://localhost:8080.
2. Haz commit y abre un pull request.
3. Para publicar, sube a Plesk solo `chelart/public/` y `chelart/config/` (sin tocar el `plesk-secrets.php` que ya existe en el servidor). No subas `sql/`, `dev/` ni `docker-compose.yml`.
