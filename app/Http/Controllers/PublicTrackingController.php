<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\User;
use App\Support\CarChecklistDefinitions;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página pública de seguimiento del proceso de importación.
 *
 * GET /tracking/{token}
 *
 * Solo expone información presentable al cliente final:
 * - Marca, modelo, versión, año, color, ciudad, fotos.
 * - Hitos del proceso con fecha completada.
 * - Resumen de inspecciones (% global, % por sección — SIN items individuales críticos).
 * - Próximo paso (descripción humana del hito pendiente).
 * - Fecha estimada de entrega.
 * - Contacto del gestor (assigned_to del coche, fallback al owner de la organización).
 *
 * NO expone NUNCA: purchase_price, IEDMT, comparables, research_gaps, costes, VIN, margen.
 */
class PublicTrackingController extends Controller
{
    /** Descripción humana del próximo paso por hito (presentable al cliente). */
    private const NEXT_STEPS = [
        'deposit_paid' => [
            'es' => 'Hemos confirmado el encargo. Estamos preparando la reserva con el vendedor.',
            'en' => 'Your order is confirmed. We are arranging the reservation with the seller.',
        ],
        'transport_contracted' => [
            'es' => 'Buscando transportista para traer el coche desde Alemania.',
            'en' => 'Booking transport to bring the car from Germany.',
        ],
        'coc_ordered' => [
            'es' => 'Pidiendo el Certificado de Conformidad (COC) al fabricante.',
            'en' => 'Requesting the Certificate of Conformity (COC) from the manufacturer.',
        ],
        'itv_passed' => [
            'es' => 'Pasando la ITV de importación en una estación autorizada.',
            'en' => 'Passing the import ITV at an authorized inspection centre.',
        ],
        'iedmt_paid' => [
            'es' => 'Liquidando el impuesto de matriculación (IEDMT) en Hacienda.',
            'en' => 'Paying the registration tax (IEDMT) at the tax office.',
        ],
        'registered' => [
            'es' => 'Matriculando el coche en Tráfico a tu nombre.',
            'en' => 'Registering the car under your name at Tráfico.',
        ],
    ];

    public function show(string $token): Response
    {
        // Rate limit por IP para evitar enumeration si los tokens no son perfectos.
        $key = 'tracking:'.request()->ip().':'.substr($token, 0, 8);
        if (RateLimiter::tooManyAttempts($key, 60)) {
            abort(429);
        }
        RateLimiter::hit($key, 60);

        // withoutGlobalScope('organization') — NO withoutGlobalScopes() sin args,
        // que también desactivaría SoftDeletes y expondría coches borrados.
        $car = Car::withoutGlobalScope('organization')
            ->publicTracking()
            ->where('tracking_token', $token)
            ->firstOrFail();

        // No inflar views con bots (M4).
        $isBot = $this->looksLikeBot(request()->userAgent());
        if (! $isBot) {
            $car->increment('tracking_views');
        }

        $car->load(['organization']);

        // Hitos del proceso con fecha completada (si aplica).
        $milestoneDefs = CarChecklistDefinitions::milestones();
        $milestoneRows = $car->checklists()->milestones()->get()->keyBy('item_key');
        $milestones = collect($milestoneDefs)->map(function ($def) use ($milestoneRows) {
            $row = $milestoneRows->get($def['key']);

            return [
                'key' => $def['key'],
                'name' => $def['name'],
                'completed' => (bool) ($row ? $row->completed : false),
                'completed_at' => $row && $row->completed_at ? $row->completed_at->toDateString() : null,
            ];
        })->all();

        // Próximo hito pendiente (o null si todo completado).
        $nextMilestone = collect($milestones)->firstWhere('completed', false);

        $locale = app()->getLocale();
        $locale = in_array($locale, ['es', 'en'], true) ? $locale : 'es';

        $nextStepDescription = null;
        if ($nextMilestone && isset(self::NEXT_STEPS[$nextMilestone['key']])) {
            $nextStepDescription = self::NEXT_STEPS[$nextMilestone['key']][$locale];
        }

        // Resumen de inspecciones: solo % global + % por sección (sin items críticos).
        $inspectionRows = $car->checklists()->inspections()->get();
        $sections = [];
        foreach (CarChecklistDefinitions::inspectionGroups() as $section) {
            // CarChecklist.section guarda el `name` del grupo (legacy).
            $sectionRows = $inspectionRows->where('section', $section['match_name']);
            $completed = $sectionRows->where('completed', true)->count();
            $total = $sectionRows->count();
            if ($total === 0) {
                continue;
            }
            $sections[] = [
                'key' => $section['key'],
                'name' => $section['name'],
                'completed' => $completed,
                'total' => $total,
                'percent' => (int) round($completed / $total * 100),
            ];
        }
        $inspTotal = array_sum(array_column($sections, 'total'));
        $inspCompleted = array_sum(array_column($sections, 'completed'));
        $inspPercent = $inspTotal > 0 ? (int) round($inspCompleted / $inspTotal * 100) : 0;

        // Gestor asignado: owner de la organización (v1 simplificado; en futuro,
        // cuando exista `cars.assigned_to`, se prefiere ese colaborador).
        $manager = null;
        if ($car->organization) {
            $manager = User::withoutGlobalScopes()
                ->where('organization_id', $car->organization_id)
                ->where('role', 'owner')
                ->orderBy('id')
                ->first();
        }

        $managerData = null;
        if ($manager) {
            $managerData = [
                'name' => $manager->name,
                'role' => $manager->role,
                'email' => $manager->email,
                'schedule' => 'Lun-Vie 9:00–19:00 · Sáb 10:00–13:00',
            ];
        }

        // Solo fotos de marketing (nunca defectos/documentos internos).
        // Base64 en línea para que el navegador no dependa de auth en storage.
        $photos = $car->photos()
            ->whereIn('photo_type', ['exterior', 'interior', 'engine'])
            ->orderBy('sort_order')
            ->limit(8)
            ->get()
            ->map(function ($photo) {
                $abs = str_starts_with($photo->url, '/storage/')
                    ? public_path($photo->url)
                    : storage_path('app/public/'.ltrim($photo->url, '/'));
                if (! file_exists($abs)) {
                    return null;
                }
                $mime = mime_content_type($abs) ?: 'image/jpeg';

                return [
                    'url' => 'data:'.$mime.';base64,'.base64_encode(file_get_contents($abs)),
                    'is_cover' => (bool) $photo->is_cover,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return Inertia::render('Public/Tracking', [
            'car' => [
                'brand' => $car->brand,
                'model' => $car->model,
                'version' => $car->version,
                'year' => $car->year,
                'color' => $car->color,
                'city' => $car->city,
                'pais_origen' => $car->pais_origen,
                'status' => $car->status,
                'status_label' => $car->status,
                'milestones' => $milestones,
                'next_milestone' => $nextMilestone,
                'next_step_description' => $nextStepDescription,
                'inspections_percent' => $inspPercent,
                'inspections_completed' => $inspCompleted,
                'inspections_total' => $inspTotal,
                'inspections_sections' => $sections,
                'expected_delivery_date' => $car->expected_delivery_date?->toDateString(),
                'photos' => $photos,
            ],
            'manager' => $managerData,
            'contact' => [
                'phone' => '+34 675 70 14 39',
                'email' => 'jjimportmotors@gmail.com',
            ],
        ]);
    }

    /** Detección básica de bots para no inflar contadores. */
    private function looksLikeBot(?string $ua): bool
    {
        if (! $ua) {
            return false;
        }

        return (bool) preg_match('/bot|crawl|spider|slurp|curl|wget|python-requests|headless/i', $ua);
    }
}
