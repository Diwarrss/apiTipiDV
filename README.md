# apiTipiDV — API de licencias TipiDV

API Laravel standalone para licencias por equipo de **TipiDV** (separada de apiGridPos).

## Flujo

```
Portal tipidv.gridsoft.co
  → POST /api/checkout (Wompi payment link)
  → Cliente paga en checkout.wompi.co
  → Wompi POST /api/webhook/wompi (evento transaction.updated APPROVED)
  → subscriptions + correo con clave TDV-XXXX-XXXX-XXXX
  → TipiDV en el PC: activar con correo + clave
  → POST /api/activate (vincula machine_fingerprint)
  → Validación periódica POST /api/validate
```

## Requisitos

- PHP 8.2+
- Composer
- MySQL (o SQLite para desarrollo)

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Variables de entorno

Ver `.env.example`. Variables propias de TipiDV:

```env
APP_URL=https://tipidv.gridsoft.co

# Wompi producción (dashboard del comercio)
WOMPI_API_URL=https://production.wompi.co/v1
WOMPI_PUBLIC_KEY=...
WOMPI_PRIVATE_KEY=...
WOMPI_EVENTS_SECRET=...
WOMPI_INTEGRITY_SECRET=...
PORTAL_URL=https://tipidv.gridsoft.co
OFFLINE_GRACE_DAYS=14

# Admin /admin
ADMIN_EMAIL=...
ADMIN_PASSWORD_HASH=...

# Sitio + instalador GitHub (ver .env.example para MARKETING_* y GITHUB_RELEASE_WEBHOOK_SECRET)

# Correo (clave de licencia al pagar)
MAIL_FROM_ADDRESS=licencias@tipidv.gridsoft.co
```

## Productos en GridPay

Crear productos en el CRM de GridPay con `detail` similar a:

```json
{
  "service_type": "TIPIDV",
  "billing_period": "annual",
  "billing_months": 12,
  "machine_slots": 1,
  "payment_type": "ANUAL"
}
```

Mensual: `billing_period: "monthly"`, `billing_months: 1`.

## Rutas API

| Método | Ruta | Uso |
|--------|------|-----|
| GET | `/api/plans` | Portal: listar planes |
| POST | `/api/checkout` | Iniciar transacción Wompi |
| POST | `/api/activate` | App escritorio: vincular PC |
| POST | `/api/validate` | App escritorio: revalidar |
| POST | `/api/webhook/gridpay` | Webhook GridPay (pago aprobado) |

## Despliegue

1. Configurar `.env` en el servidor (ver arriba).
2. `php artisan migrate --force`
3. Apuntar `tipidv.gridsoft.co` al `public/` de Laravel.
4. El checkout envía `webhook_url` = `{APP_URL}/api/webhook/gridpay` (GridPay reenvía el evento Wompi).
5. En la app Windows: `TIPIDV_API_BASE` o `tipidv-api.txt` = `https://tipidv.gridsoft.co/api`.

## Renovaciones

Si el mismo correo paga de nuevo, se extiende `expires_at` de la suscripción existente (misma `license_key`). Idempotencia por `transaction_uuid`.
