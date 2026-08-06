# Backend Rules — Laravel 13 / PHP 8.5

> Activar por glob: `app/Http/Controllers/**/*.php`, `app/Services/**/*.php`, `app/Jobs/**/*.php`

---

## PHP 8.5+

- **Constructor promotion:** `public function __construct(public Foo $foo) {}`. NO `__construct()` vacío.
- **Type hints:** TODOS los parámetros + return types explícitos.
- **Enums TitleCase:** keys con `FavoritePerson`, no `favorite_person`.
- **readonly properties** para value objects.
- **PHPDoc blocks** > inline comments.

## Laravel 11/13

- **Bootstrap/app.php** para middleware, routes, exceptions.
- **bootstrap/providers.php** para service providers.
- **Comandos** auto-register (no map manual).
- **Casts** en método `casts()`, NO propiedad `$casts`.
- **Form Requests** para validación compleja (>3 reglas).
- **Policies** > Gate::define inline.
- **Resources** para APIs > array manual.
- **Named routes** para TODOS los `route()`.

## Queries

- **Eager load SIEMPRE** (`with()`) en relaciones usadas en blade.
- **No `Model::all()`** en queries grandes.
- **Pagination** en listados >20 items.
- **Índices** en FK + columnas de `WHERE` frecuente.
- **Select específico** (`->get(['id', 'name'])`) si no necesitas todo.

## Errores

- **JSON errors** si `Accept: application/json`.
- **abort(403, msg)` para auth checks.
- **try/catch** en operaciones externas (HTTP, FS, DB).
- **Logger::error()** antes de retornar error al usuario.
- **NUNCA** echo de variables en respuestas de API.

## NO HACER

- ❌ `dd()` / `dump()` / `var_dump()`.
- ❌ `app()->environment('production')` para lógica de negocio.
- ❌ `config()` desde controller (inyectar en constructor).
- ❌ SQL crudo (usar query builder o Eloquent).
- ❌ `Auth::user()` en lugares no-context (usar `auth()->user()`).
- ❌ Comentarios obvios tipo `// increment i`.
