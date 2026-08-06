# Migrations Rules — Schema + Factories

> Activar por glob: `database/migrations/**`, `database/factories/**`, `database/seeders/**`.

---

## Reglas inquebrantables

1. **BACKUP** antes de UPDATE/DELETE en producción. Sin excepción.
2. **Toda migración con `down()`** (rollback posible).
3. **Todo FK con `constrained()`** (no manual FK sin nombre).
4. **Multi-tenancy**: `organization_id` en TODA tabla de negocio.
5. **SoftDeletes** en tablas de negocio.

## Patrón seguro

```php
public function up(): void
{
    Schema::table('cars', function (Blueprint $table) {
        // 1. Nullable column
        $table->foreignId('owner_user_id')
            ->nullable()
            ->after('organization_id')
            ->constrained('users')
            ->nullOnDelete();

        // 2. Backfill en chunks (NO cargar todo en memoria)
        DB::table('cars')
            ->whereNull('owner_user_id')
            ->chunkById(1000, function ($cars) {
                foreach ($cars as $car) {
                    DB::table('cars')
                        ->where('id', $car->id)
                        ->update(['owner_user_id' => $car->created_by]);
                }
            });

        // 3. NO endurecer aun (nullable -> required en 2 release)
    });
}

public function down(): void
{
    Schema::table('cars', function (Blueprint $table) {
        $table->dropConstrainedForeignId('owner_user_id');
    });
}
```

## Backup/restore

```bash
# Backup
ssh forge@... 'mysqldump jjimportmotors_prod > /tmp/backup-20260806.sql'

# Migrar
ssh forge@... 'cd current && php artisan migrate --force'

# Si falla, restore
ssh forge@... 'mysql jjimportmotors_prod < /tmp/backup-20260806.sql'
```

## Dry-run antes de migrate

```bash
ssh forge@... 'cd current && php artisan migrate --pretend --no-interaction | tail -10'
```

## Índices

- **FK** → `constrained()` crea índice automáticamente.
- **`WHERE` frecuente** → `$table->index('columna')`.
- **Unique** → `$table->unique(['col1', 'col2'])`.

## Factories

```php
// BIEN — con org
$car = Car::factory()->for($org)->create();

// MAL — sin org (rompe tenancy)
$car = Car::factory()->create();

// State
$car = Car::factory()->marketplace()->create();
```

## Seeders

```php
class CarsSeeder extends Seeder
{
    public function run(): void
    {
        $orgs = Organization::factory()->count(3)->create();

        foreach ($orgs as $org) {
            User::factory()
                ->for($org)
                ->count(5)
                ->create()
                ->each(function ($user) use ($org) {
                    Car::factory()
                        ->for($org)
                        ->count(10)
                        ->create(['created_by' => $user->id]);
                });
        }
    }
}
```

## Comandos

```bash
php artisan make:migration add_field_to_table --table=...
php artisan migrate:status
php artisan migrate:rollback --step=1
php artisan db:seed --class=CarsSeeder
```

## NO HACER

- ❌ Migración sin `down()`.
- ❌ `dropColumn` sin backup.
- ❌ Foreign key sin `constrained()`.
- ❌ Cambiar `down()` después de ejecutar en prod.
- ❌ Seeds con `truncate` directo (rompe FKs).
- ❌ Tests con datos hardcoded (usar factories).
- ❌ Tabla de negocio sin `organization_id`.
