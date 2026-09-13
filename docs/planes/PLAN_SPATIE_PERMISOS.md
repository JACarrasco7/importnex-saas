# Plan: `spatie/laravel-permission` — implementar o desinstalar

> **Estado: APARCADO** (13-sep-2026, decisión del usuario). No ejecutar sin su OK.
> Regla asociada: [`.ai/rules/auth-roles.md`](../../.ai/rules/auth-roles.md)

Este documento existe porque la decisión estaba bloqueada desde hace semanas y no había
plan escrito. Aquí quedan los hechos medidos, las dos trampas que hay que esquivar y las
dos salidas posibles.

---

## 1. Situación de partida (medido el 13-sep-2026, no supuesto)

| Comprobación | Resultado |
|---|---|
| `composer.json` | `spatie/laravel-permission: ^8.3` declarado |
| `config/permission.php` | **NO existe** (nunca se publicó) |
| Migraciones `*permission*` / `*roles*` | **Ninguna** |
| Tablas `roles`, `permissions`, `model_has_roles`, `role_has_permissions` | **Ninguna existe** (BD local) |
| Usos de la API de Spatie (`Role::`, `hasRole()`, `givePermissionTo()`…) | **0** |

Es decir: la dependencia está **declarada y sin configurar**. No hay nada a medias que
pudiera funcionar por accidente.

### El sistema de permisos que SÍ funciona hoy

Columna `users.role` (`varchar(255)`, sin constraint) con valores string:
`'owner'` · `'operator'` · `'Super Admin'`.

Se consulta desde:

- `app/Models/User.php` → `scopeOwner()`, `scopeOperator()`, `isOwner()`, `isOperator()`
- `app/Http/Middleware/TelescopeAccess.php:31` → `$user->role !== 'Super Admin'`

---

## 2. ⚠️ Trampa nº 1 — hay **dos** `isOwner()` distintos y NO significan lo mismo

| Método | Archivo | Qué mira | ¿Es un rol? |
|---|---|---|---|
| `Organization::isOwner()` | `app/Models/Organization.php:63` | La **columna** `organizations.is_owner` | **NO.** Significa "esta organización ES la plataforma (JJ Import Motors)" |
| `User::isOwner()` | `app/Models/User.php:85` | `$this->role === 'owner'` | **SÍ** |

`$org->isOwner()` se llama en **12 sitios** (billing/Stripe, límites de plan, alertas,
`HandleInertiaRequests`…). **Eso NO se toca.** Si alguien "migra todos los `isOwner()` a
`hasRole()`", rompe el billing y los límites de plan. La migración afecta **solo** al rol
de usuario.

### ⚠️ Trampa nº 2 — la regla cita un método que no existe

`.ai/rules/auth-roles.md` dice que existe `$user->isSuperAdmin()` para acceso total.
**Ese método no está en `app/`** (`grep` no lo encuentra). El valor `'Super Admin'` sí se
usa, pero como string suelto en `TelescopeAccess`. Hay que corregir la regla.

---

## 3. Opción A — implementar Spatie de verdad

Coste estimado **2-3 días** · riesgo **alto** (permisos: un fallo abre o cierra de más).

### Decisión previa obligatoria (bloquea todo el diseño)

**¿Los roles son globales o por organización?** Es un SaaS multi-tenant: Spatie soporta
"teams" (roles por organización) pero cambia el esquema y todas las llamadas. Si esto no
se decide antes de la fase 3, hay que rehacerla.

### Fases

| # | Fase | Qué implica | Verificación |
|---|---|---|---|
| 1 | Config + migraciones | Publicar `config/permission.php` y las migraciones; `php artisan migrate` (5 tablas) | Tablas creadas; `php artisan test --compact` sigue verde |
| 2 | Roles | Definir los 3 roles reales: `owner`, `operator`, `super-admin` (ojo: hoy es `'Super Admin'`, con espacio y mayúsculas) | — |
| 3 | **Migración de datos** | Seeder/command que crea roles y asigna a **cada usuario existente** según `users.role`. **Debe ser idempotente y reversible** | Contar: mismo nº de owners/operators/Super Admin antes y después |
| 4 | Modelo | `use HasRoles` en `User` | — |
| 5 | **Compatibilidad** | Mantener `User::isOwner()` / `isOperator()` como *wrappers* de `hasRole()` — NO borrarlos de golpe: los llaman controladores y tests | `OrganizationOwnerTest` y el resto siguen pasando |
| 6 | Middleware | `TelescopeAccess` → `hasRole('super-admin')` | Test de acceso a Telescope con y sin rol |
| 7 | Policies/Gates | Permisos finos (si hacen falta de verdad) | Tests por permiso |
| 8 | Limpieza | Solo cuando 5-7 estén verdes: quitar wrappers y la columna (¡o dejarla como caché!) | Suite completa |

### Rollback

Antes de la fase 3: `mysqldump` de `users`. La fase 3 es la única que escribe datos
masivamente. Las fases 1-2 son aditivas y se deshacen con `migrate:rollback`.

### Señal de alerta

Si en la fase 5 alguien tiene que tocar **más de 20 sitios**, la abstracción no encaja con
cómo está montado el proyecto y conviene replantear antes de seguir.

---

## 4. Opción B — desinstalar (la barata)

```powershell
composer remove spatie/laravel-permission
```

- No hay nada que limpiar: ni config, ni migraciones, ni tablas, ni usos.
- Libera espacio en `vendor/` y quita una dependencia que hoy **da una falsa sensación de
  que hay un sistema de roles** cuando el real es `users.role`.
- Riesgo: **casi nulo**.

> ⚠️ Requiere OK del usuario: es un cambio de dependencias, y la convención del repo es no
> tocar dependencias sin aprobación.

---

## 5. Recomendación

**Opción B ahora, Opción A cuando haya una necesidad concreta** (p. ej. permisos por
módulo, invitaciones con roles, o auditoría de quién puede hacer qué). Hoy el sistema de
`users.role` cubre lo que la app hace, y la dependencia no aporta nada: solo confunde.

---

## 6. Aparcados en el mismo cajón (no son Spatie)

- **Rotar la password de la BD de Forge.** El 13-sep-2026 se encontró en texto plano en
  `forge-mysql-tunnel.bat`, que está en un **repo público** (historial incluido). Ya se
  quitó del archivo, pero **hasta que se rote la credencial sigue siendo válida para
  cualquiera que la lea del historial de GitHub.** Usuario: aparcado a su petición.
- **`IMPORTNEX_TOKEN` + implementar la API de subida de informes.** La API
  (`POST /api/import-valuation`) ya existe desde el 12-sep; el token se define con
  `scripts/set-importnex-token.ps1`. Usuario: para más adelante.
