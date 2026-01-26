# vohrad-api

Laravel API backend for an inventory management system.

## Overview

- Framework: Laravel ^12.0 (PHP ^8.2)
- Auth: Laravel Sanctum (token-based auth on most routes)
- Primary resources: organizations, users, roles, locations, items, batches, item locations, movements, maintenance, statuses, categories, suppliers, attachments, printers, print jobs, labels, licenses, API keys, translations, events, check-in/out
- Webhooks: Stripe invoice webhook handler
- Docs UI: Vite-built static site served by Laravel

## API

- Base path: `/api/v1`

### Auth

- `POST /api/v1/login`
- `POST /api/v1/admin/login`
- `POST /api/v1/logout`
- `GET  /api/v1/user`
- `POST /api/v1/admin/logout`
- `GET  /api/v1/admin/user`

### Organization + user management

- `/api/v1/organizations` (CRUD + `/active`)
- `/api/v1/users` (CRUD)
- `/api/v1/roles` (CRUD + `/all`, `/organization`)

### Items, locations, and movements

- `/api/v1/items`, `/api/v1/locations`, `/api/v1/batches`, `/api/v1/item-locations`
- `/api/v1/movements` (move, initial placement, adjust quantity, history, integrity validation)
- `/api/v1/checks` (check in/out, history, availability)
- `/api/v1/events` (system + per-item event streams)

### Maintenance

- `/api/v1/maintenances` (CRUD + `/complete`, `/item`, `/condition`)
- `/api/v1/maintenances/categories`, `/conditions`, `/details`

### Reference data

- `/api/v1/statuses`, `/api/v1/categories`, `/api/v1/suppliers`, `/api/v1/uom`

### Media and printing

- `/api/v1/attachments` (CRUD + `/type-options`, `/{id}/stats`)
- `/api/v1/printers`, `/api/v1/printjobs`
- `/api/v1/labels/generate`

### Admin

- `/api/v1/admin/api-keys` (CRUD + revoke/regenerate/usage/overview)
- `/api/v1/admin/security/threats`

### Licensing

- `/api/v1/licenses` (CRUD + `/{id}/invoice`)

### Translations

- `/api/v1/translations/{locale?}`
- `/api/v1/translations/{locale}/{key}`

### Utility

- `GET /test` (health check)
- `GET /get-env-key` (returns `OPENROUTER_API_KEY` if set)

### Webhooks

- `POST /webhooks/stripe`

## Documentation

- UI: `GET /` and `GET /docs` serve the built docs UI (`public/dist/docs.html`)
- OpenAPI JSON: `GET /swagger/openapi.json`

OpenAPI build pipeline:

- Template: `public/swagger/openapi-template.json`
- Path fragments: `public/swagger/paths/*.json`
- Schema fragments: `public/swagger/schemas/*.json`
- Build script: `public/swagger/build-openapi.js`

## Authentication and authorization

- Sanctum (`auth:sanctum`) for API authentication
- Route-level permission checks via `PermissionMiddleware`
- System roles (`App\Services\AuthorizationHelper`): `super_admin`, `admin`, `manager`, `employee`
- Permission keys: `App\Constants\Permissions`
- More detail: `scripts/permissions.md`

## Multi-tenancy and RLS

- Tenant context: `TenancyContextMiddleware` + `TenancyPolicyService` (sets `app.org_id` in PostgreSQL)
- RLS policy tables: `config/tenancy.php`
- Production RLS guidance: `scripts/PRODUCTION_RLS_SETUP.md`

## Configuration

Notable environment keys:

- Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Stripe: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_CURRENCY`, `STRIPE_DAYS_UNTIL_DUE`
- Docs assistant: `OPENROUTER_API_KEY` (exposed via `/get-env-key`)

## Development

Install dependencies:

```bash
composer install
npm install
```

Run local dev stack (API server, queue listener, log tail, Vite):

```bash
composer run dev
```

Build docs UI + OpenAPI spec:

```bash
npm run build
npm run build:openapi
```

## Testing

Run tests:

```bash
composer run test
```

`phpunit.xml` test DB defaults to MySQL (`DB_HOST=db`, `DB_DATABASE=db`, `DB_USERNAME=root`, `DB_PASSWORD=root`).

## Docker

- Root `Dockerfile`: builds `php:8.2-apache`, exposes `8080`, serves the Laravel app
- `docker/8.2/`: Ubuntu-based PHP 8.2 image with Node tooling and Supervisor config
