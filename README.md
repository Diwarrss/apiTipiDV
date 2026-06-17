# apiTipiDV

API Laravel para **licencias TipiDV por equipo**: cobro con Wompi (vía GridPay), emisión automática de clave, activación en Windows y revalidación periódica.

Proyecto **independiente** de `apiGridPos`. No usa tenants, Sanctum ni base de datos central de GridPOS.

**URL de producción:** `https://tipidv.gridsoft.co` (API en `/api`, sitio marketing en `/`)

---

## Flujo de compra (paso a paso)

```
1. Cliente entra a tipidv.gridsoft.co/comprar
2. Elige plan (mensual/anual) y tipo: nueva licencia | renovación | equipo adicional
3. POST /api/checkout → GridPay/Wompi → pago con tarjeta/PSE
4. Wompi aprueba → POST /api/webhook/gridpay (event APPROVED)
5. apiTipiDV crea/renueva Subscription + clave TDV-XXXX-XXXX-XXXX
6. Correo al cliente con clave + link de descarga del .exe (último release GitHub)
7. Cliente instala TipiDV-Setup.exe en el PC
8. Al abrir la app: correo + clave → POST /api/activate (vincula fingerprint del PC)
9. Cada arranque: POST /api/validate (gracia offline ~14 días sin internet)
```

| Paso | ¿Internet? |
|------|-------------|
| Comprar en web | Sí |
| Tipificar PDFs | No |
| Activar licencia (una vez por PC) | Sí |
| Validar licencia | Sí, periódico (con gracia offline) |

---

## Descarga del instalador (GitHub Releases)

El workflow de [appWindowsTipificadorPDF](https://github.com/Diwarrss/appWindowsTipificadorPDF) publica cada build como **GitHub Release** con `TipiDV-Setup.exe` (URL estable, no expira como los artifacts).

La API guarda la última URL en `storage/app/windows-release.json` y la usa en la web y en el correo de licencia.

**Prioridad de URL de descarga:**

1. Archivo guardado por webhook (`POST /api/webhook/github-release`)
2. Consulta a GitHub API `releases/latest` (caché 5 min)
3. `MARKETING_DOWNLOAD_URL` en `.env` (fallback manual)

### Configurar sync automático (GitHub Actions → apiTipiDV)

En el repo **appWindowsTipificadorPDF**, Settings → Secrets and variables:

| Nombre | Tipo | Valor |
|--------|------|--------|
| `TIPIDV_RELEASE_WEBHOOK_SECRET` | Secret | Clave larga (misma que en apiTipiDV `.env`) |
| `TIPIDV_API_URL` | Variable | `https://tipidv.gridsoft.co` |

En **apiTipiDV** `.env`:

```env
GITHUB_RELEASE_WEBHOOK_SECRET=tu-clave-secreta-larga
MARKETING_GITHUB_REPO=Diwarrss/appWindowsTipificadorPDF
```

Tras cada push a `main`, el workflow crea el release y llama al webhook. Manual:

```bash
php artisan tipidv:sync-release
```

### Webhook manual

```bash
curl -X POST https://tipidv.gridsoft.co/api/webhook/github-release \
  -H "Authorization: Bearer TU_SECRETO" \
  -H "Content-Type: application/json" \
  -d '{"tag":"build-42","setup_url":"https://github.com/Diwarrss/appWindowsTipificadorPDF/releases/download/build-42/TipiDV-Setup.exe"}'
```

---

## Requisitos

- PHP 8.2+
- Composer
- MySQL/MariaDB (recomendado en producción) o SQLite (desarrollo)
- Cuenta GridPay/Wompi con productos TipiDV
- SMTP para enviar correos de licencia

---

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Desarrollo local:

```bash
php artisan serve
# API en http://127.0.0.1:8000/api/plans
```

Producción:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Document root del virtual host: carpeta `public/`.

---

## Variables de entorno

### Aplicación

| Variable | Ejemplo | Descripción |
|----------|---------|-------------|
| `APP_URL` | `https://tipidv.gridsoft.co` | Dominio real. El webhook de checkout usa `{APP_URL}/api/webhook/gridpay` |
| `APP_TIMEZONE` | `America/Bogota` | Zona horaria para fechas de licencia |
| `APP_DEBUG` | `false` | Siempre `false` en producción |

### Base de datos

En producción usar MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tipidv_licenses
DB_USERNAME=...
DB_PASSWORD=...
```

### Correo

```env
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=licencias@tudominio.com
MAIL_FROM_NAME=TipiDV
```

### Licencias y pagos

| Variable | Descripción |
|----------|-------------|
| `GRIDPAY_URL` | URL base del API Gateway GridPay (sin barra final) |
| `GRIDPAY_API_KEY` | Clave `x-api-key` de GridPay |
| `GRIDPAY_SLUG` | Slug en transacciones (`tipidv` por defecto) |
| `PRODUCT_MONTHLY_UUID` | UUID del producto mensual en GridPay |
| `PRODUCT_ANNUAL_UUID` | UUID del producto anual en GridPay |
| `PORTAL_URL` | Página de compra/renovación (`https://tipidv.gridsoft.co`) |
| `OFFLINE_GRACE_DAYS` | Días sin internet antes de bloquear la app (default `14`) |

Tras cambiar `.env`:

```bash
php artisan config:clear
# o en prod: php artisan config:cache
```

**Qué puedes cambiar sin tocar código:** precios (en GridPay), UUID de planes, gracia offline, portal, SMTP, credenciales GridPay. Las licencias ya emitidas mantienen su `expires_at` hasta renovación o edición en BD.

### Sitio marketing (`tipidv.gridsoft.co`)

La landing y el checkout viven en este mismo proyecto (rutas web en `routes/web.php`, vistas en `resources/views/site/`).

| Ruta | Descripción |
|------|-------------|
| `/` | Página principal (SEO, precios, FAQ) |
| `/comprar` | Formulario → `POST /api/checkout` → Wompi |
| `/gracias` | Retorno tras pago |
| `/robots.txt` | SEO crawlers |
| `/sitemap.xml` | Mapa del sitio |
| `/admin` | Panel super admin (licencias) |

| Variable | Descripción |
|----------|-------------|
| `MARKETING_PRICE_ANNUAL_COP` | Precio mostrado si GridPay no responde (default `198000`) |
| `MARKETING_PRICE_MONTHLY_COP` | Precio mensual fallback (default `29000`) |
| `MARKETING_DOWNLOAD_URL` | Fallback manual si no hay release en GitHub |
| `MARKETING_GITHUB_REPO` | Repo con releases (`Diwarrss/appWindowsTipificadorPDF`) |
| `GITHUB_RELEASE_WEBHOOK_SECRET` | Token para `POST /api/webhook/github-release` |
| `MARKETING_SEO_*` | Título, descripción y keywords |
| `MARKETING_CONTACT_*` / `MARKETING_WHATSAPP` | Footer y CTA |

Si GridPay está configurado, los precios en la web salen de los productos reales. Si no, se usan los `MARKETING_PRICE_*`.

---

## Productos en GridPay

Crear uno o dos productos con `detail` similar a:

```json
{
  "service_type": "TIPIDV",
  "billing_period": "annual",
  "billing_months": 12,
  "machine_slots": 1
}
```

Mensual: `"billing_period": "monthly"`, `"billing_months": 1`.

Copiar los UUID a `PRODUCT_ANNUAL_UUID` y `PRODUCT_MONTHLY_UUID` en `.env`.

---

## Endpoints

Prefijo: `/api`

| Método | Ruta | Uso |
|--------|------|-----|
| `GET` | `/plans` | Listar planes (portal) |
| `POST` | `/checkout` | Iniciar pago Wompi |
| `POST` | `/webhook/gridpay` | Webhook pago aprobado (GridPay/Wompi) |
| `POST` | `/webhook/github-release` | Guardar URL del último `TipiDV-Setup.exe` (CI GitHub) |
| `POST` | `/activate` | App Windows: vincular equipo |
| `POST` | `/validate` | App Windows: revalidar licencia |

### Ejemplo `POST /api/activate`

```json
{
  "license_key": "TDV-A1B2-C3D4-E5F6",
  "email": "cliente@hospital.com",
  "machine_fingerprint": "abc123...",
  "machine_label": "PC-RECEPCION"
}
```

---

## Nginx (referencia)

```nginx
server {
    listen 443 ssl http2;
    server_name tipidv.gridsoft.co;
    root /var/www/apiTipiDV/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Health check de Laravel: `GET /up`

---

## Hospitales y usuarios particulares

El checkout es vía `POST /api/checkout` (cualquier front que consuma esta API: Postman, página propia, etc.).

| Campo | Hospital | Particular |
|-------|----------|------------|
| `customer.type_id` | `NIT` | `CC` |
| `organization_name` | Nombre del hospital | omitir o `null` |
| `customer.email` | Correo del responsable | Correo personal |
| `purchase_type` | ver tabla abajo | igual |

### Modelo mental (simple)

1. **Correo** = cuenta (renovaciones y varios equipos usan el mismo).
2. **Clave TDV-…** = llega por email tras pagar.
3. **Un PC por licencia** = cada compra `new_license` activa un computador.
4. **Varios PCs** = mismo correo, `purchase_type: new_equipment`.
5. **Renovar** = `purchase_type: renewal`.

### `purchase_type` en checkout

| Valor | Efecto |
|-------|--------|
| `new_license` | Primera compra o licencia nueva |
| `renewal` | Extiende vigencia; no agrega equipos |
| `new_equipment` | Suma un cupo de PC más a la misma clave |

---

## Cliente Windows (TipiDV.exe)

| Configuración | Valor por defecto |
|---------------|-------------------|
| API | `https://tipidv.gridsoft.co/api` — override: `tipidv-api.txt` o env `TIPIDV_API_BASE` |
| Portal compra | `https://tipidv.gridsoft.co` — override: `tipidv-portal.txt` o env `TIPIDV_PORTAL_URL` |

---

## Panel super administrador (solo tú)

URL: **`https://tipidv.gridsoft.co/admin`**

No hay registro público. Un único usuario definido en `.env`:

```env
ADMIN_EMAIL=tu-correo@ejemplo.com
ADMIN_PASSWORD_HASH=   # ver abajo
```

Generar el hash de contraseña:

```bash
php artisan admin:hash-password "tu-clave-segura"
```

Copia el resultado en `ADMIN_PASSWORD_HASH` del `.env`.

### Qué puedes hacer en el panel

- Ver todas las licencias (clave, hospital, correo, vencimiento)
- Buscar por clave, correo u hospital
- Ver equipos activados por licencia
- **Extender vigencia** (meses manuales)
- **Ajustar cupos** de PC
- **Desvincular** un equipo (libera cupo para otro PC)

---

## Checklist go-live

- [ ] `.env` con `APP_URL`, BD, mail y GridPay
- [ ] `php artisan migrate --force`
- [ ] Productos TIPIDV en GridPay con UUIDs en `.env`
- [ ] SSL en `tipidv.gridsoft.co`
- [ ] Probar `GET /api/plans`
- [ ] Pago de prueba → webhook → correo con clave
- [ ] Activación desde TipiDV en un PC de prueba
- [ ] `ADMIN_EMAIL` + `ADMIN_PASSWORD_HASH` y acceso a `/admin`

---

## Stack

- Laravel 11
- PHP 8.2+
- Tablas: `subscriptions`, `machine_activations`

Autor del producto TipiDV: Ing. Diego Vargas.
