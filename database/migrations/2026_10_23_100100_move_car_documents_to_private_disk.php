<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Los documentos de los coches (DNI del cliente, contratos, facturas, informes
 * internos) se guardaban en el disco `public`: cualquiera con la URL
 * `/storage/cars/{id}/documents/...` los abría sin iniciar sesión, y los
 * informes del ZIP tienen nombre fijo (fácil de adivinar). Se mueven al disco
 * privado `local`; la app los sirve solo con sesión y de la misma empresa.
 *
 * Idempotente: si el fichero ya está en `local`, solo borra la copia pública.
 */
return new class extends Migration
{
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        DB::table('car_documents')->whereNotNull('url')->where('url', '!=', '')->orderBy('id')
            ->chunk(200, function ($documents) use ($public, $private): void {
                foreach ($documents as $document) {
                    $path = $document->url;
                    try {
                        if ($public->exists($path)) {
                            if (! $private->exists($path)) {
                                $private->put($path, $public->get($path));
                            }
                            $public->delete($path);
                        }
                    } catch (Throwable $e) {
                        Log::warning('car_documents.move_private_failed', ['id' => $document->id, 'message' => $e->getMessage()]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Sin vuelta atrás: volver a exponer documentos personales no tiene sentido.
    }
};
