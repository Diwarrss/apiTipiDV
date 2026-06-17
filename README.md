# apiTipiDV

API Laravel para **licencias TipiDV por equipo**: cobro con Wompi (vía GridPay), emisión automática de clave, activación en Windows y revalidación periódica.

Proyecto **independiente** de `apiGridPos`. No usa tenants, Sanctum ni base de datos central de GridPOS.

**URL de producción:** `https://tipidv.gridpos.co/api`

---

## Flujo

```
Portal (gridpos.co/tipidv)
  → POST /api/checkout
  → Cliente paga en Wompi
  → POST /api/webhook/gridpay (evento APPROVED)
  → Se crea/renueva suscripción + correo con clave TDV-XXXX-XXXX-XXXX
  → TipiDV en el PC: POST /api/activate (vincula huella del equipo)
  → Cada arranque: POST /api/validate
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
| `APP_URL` | `https://tipidv.gridpos.co` | Dominio real. El webhook de checkout usa `{APP_URL}/api/webhook/gridpay` |
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
| `PORTAL_URL` | Página de compra/renovación (`https://gridpos.co/tipidv`) |
| `OFFLINE_GRACE_DAYS` | Días sin internet antes de bloquear la app (default `14`) |

Tras cambiar `.env`:

```bash
php artisan config:clear
# o en prod: php artisan config:cache
```

**Qué puedes cambiar sin tocar código:** precios (en GridPay), UUID de planes, gracia offline, portal, SMTP, credenciales GridPay. Las licencias ya emitidas mantienen su `expires_at` hasta renovación o edición en BD.

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
    server_name tipidv.gridpos.co;
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

## Clientes que consumen esta API

| Cliente | Configuración |
|---------|---------------|
| **TipiDV.exe** | Por defecto `https://tipidv.gridpos.co/api`. Override: archivo `tipidv-api.txt` junto al exe o env `TIPIDV_API_BASE` |
| **Portal** (GridPOS-Page) | `NUXT_TIPIDV_API_BASE=https://tipidv.gridpos.co/api` |

---

## Checklist go-live

- [ ] `.env` con `APP_URL`, BD, mail y GridPay
- [ ] `php artisan migrate --force`
- [ ] Productos TIPIDV en GridPay con UUIDs en `.env`
- [ ] SSL en `tipidv.gridpos.co`
- [ ] Probar `GET /api/plans`
- [ ] Pago de prueba → webhook → correo con clave
- [ ] Activación desde TipiDV en un PC de prueba

---

## Stack

- Laravel 11
- PHP 8.2+
- Tablas: `subscriptions`, `machine_activations`

Autor del producto TipiDV: Ing. Diego Vargas.
