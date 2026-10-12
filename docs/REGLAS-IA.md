---
description: Reglas durables de ImportnexCore para coding agents. Sistema de carga automática por glob. Sprint G.
---

# Reglas de Git Hooks

> Hooks pre-commit que validan automáticamente antes de cada commit.

## Instalación

Una sola vez por repo:

```bash
php scripts/install-hooks.php
```

## Hooks disponibles

### `pre-commit` (ya instalado en `.githooks/`)

Se ejecuta antes de cada `git commit`. Validaciones:

1. **PHP syntax check** — `php -l` en cada `.php` modificado.
2. **Pint dirty check** — detecta issues de formato sin modificarlos.
3. **i18n parity check** — verifica paridad es/en en claves de traducción.
4. **Tests** — solo si hay cambios en `tests/` o `Billing/StripeWebhookController`.
5. **Vite manifest** — alerta si hay cambios en `resources/` sin `manifest.json`.

## Configuración

El hook vive en `.githooks/pre-commit` (versionado en repo).

Para saltarse un hook en un commit específico (no recomendado):

```bash
git commit --no-verify -m "..."
```

## Añadir validación nueva

1. Editar `.githooks/pre-commit`.
2. Documentar aquí.
3. Re-instalar: `php scripts/install-hooks.php`.

## Desactivar hooks temporalmente

```bash
chmod -x .git/hooks/pre-commit
# Reactivar:
chmod +x .git/hooks/pre-commit
```
