<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Elimina una empresa y todos sus datos (derecho de supresión, RGPD, y baja
 * del SaaS): primero cancela la suscripción de Stripe para que no se le siga
 * cobrando; después borra las filas de todas las tablas que cuelgan de la
 * empresa y sus ficheros (fotos, documentos, contratos, adjuntos del portal).
 *
 * Las tablas se descubren por columna (`organization_id`, `car_id`, `user_id`…)
 * para que una tabla nueva no se quede fuera.
 */
class OrganizationDeletion
{
    /** Tablas que se recorren por la clave de su padre, además de `organization_id`. */
    private const PARENT_KEYS = [
        'car_id' => 'cars',
        'user_id' => 'users',
        'client_id' => 'clients',
        'import_order_id' => 'import_orders',
        'subscription_id' => 'subscriptions',
        'market_model_id' => 'market_models',
    ];

    public function delete(Organization $organization): void
    {
        if ($organization->isOwner()) {
            throw new RuntimeException('La organización operadora de la plataforma no se puede eliminar.');
        }

        $this->cancelSubscription($organization);

        $ids = $this->collectIds($organization);
        $emails = DB::table('users')->where('organization_id', $organization->id)->pluck('email')->all();

        DB::transaction(function () use ($organization, $ids, $emails): void {
            // Las tablas van de hijas a padres (según sus claves foráneas), así
            // no hace falta desactivar las restricciones.
            foreach ($this->tables() as $table => $columns) {
                if (in_array('organization_id', $columns, true)) {
                    DB::table($table)->where('organization_id', $organization->id)->delete();
                }
                foreach (self::PARENT_KEYS as $column => $parent) {
                    if ($table !== $parent && in_array($column, $columns, true) && ($ids[$parent] ?? []) !== []) {
                        DB::table($table)->whereIn($column, $ids[$parent])->delete();
                    }
                }
            }
            if ($emails !== []) {
                DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
            }
            DB::table('organizations')->where('id', $organization->id)->delete();
        });

        $this->deleteFiles($organization, $ids['cars'] ?? []);

        Log::info('organization.deleted', ['organization_id' => $organization->id, 'cars' => count($ids['cars'] ?? [])]);
    }

    /**
     * Si Stripe no responde, no se borra nada: borrar la empresa dejando la
     * suscripción viva seguiría cobrándole.
     */
    private function cancelSubscription(Organization $organization): void
    {
        $subscription = $organization->subscription(Organization::SUBSCRIPTION_TYPE);
        if ($subscription && ! $subscription->ended()) {
            $subscription->cancelNow();
        }
    }

    /** @return array<string, array<int, int>> */
    private function collectIds(Organization $organization): array
    {
        $ids = [];
        foreach (array_unique(self::PARENT_KEYS) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id')) {
                $ids[$table] = DB::table($table)->where('organization_id', $organization->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
            }
        }

        return $ids;
    }

    /**
     * Tablas con sus columnas, ordenadas para borrar primero las que apuntan a
     * otras (hijas) y al final las referenciadas (padres).
     *
     * @return array<string, array<int, string>>
     */
    private function tables(): array
    {
        $columns = [];
        $references = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if ($table === 'organizations' || str_starts_with($table, 'telescope_')) {
                continue;
            }
            $columns[$table] = Schema::getColumnListing($table);
            $references[$table] = collect(Schema::getForeignKeys($table))
                ->pluck('foreign_table')
                ->reject(fn ($parent) => $parent === $table || $parent === 'organizations')
                ->values()
                ->all();
        }

        // Orden topológico: una tabla sale cuando ya han salido todas las que la referencian.
        $ordered = [];
        $pending = array_keys($columns);
        while ($pending !== []) {
            $progress = false;
            foreach ($pending as $index => $table) {
                $referencedByPending = collect($pending)->contains(
                    fn ($other) => $other !== $table && in_array($table, $references[$other] ?? [], true)
                );
                if (! $referencedByPending) {
                    $ordered[] = $table;
                    unset($pending[$index]);
                    $progress = true;
                }
            }
            if (! $progress) {
                // Ciclo de claves foráneas: se borra en el orden que quede.
                array_push($ordered, ...array_values($pending));
                break;
            }
            $pending = array_values($pending);
        }

        return array_combine($ordered, array_map(fn ($table) => $columns[$table], $ordered));
    }

    /** @param  array<int, int>  $carIds */
    private function deleteFiles(Organization $organization, array $carIds): void
    {
        $directories = [
            ['local', "orders/{$organization->id}"],
            ['local', "contracts/{$organization->id}"],
        ];
        foreach ($carIds as $carId) {
            $directories[] = ['public', "cars/{$carId}"];
            $directories[] = ['local', "cars/{$carId}"];
        }

        foreach ($directories as [$disk, $directory]) {
            try {
                Storage::disk($disk)->deleteDirectory($directory);
            } catch (\Throwable $e) {
                Log::warning('organization.delete_files_failed', ['disk' => $disk, 'directory' => $directory, 'message' => $e->getMessage()]);
            }
        }

        if ($organization->logo && ! str_starts_with((string) $organization->logo, 'images/')) {
            Storage::disk('public')->delete($organization->logo);
        }
    }
}
