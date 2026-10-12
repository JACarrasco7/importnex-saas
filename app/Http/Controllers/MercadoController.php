<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\MarketLead;
use App\Models\MarketModel;
use App\Models\MarketModelHistory;
use App\Services\PushNotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MercadoController extends Controller
{
    /**
     * Catálogo público "bajo pedido" — tarjetas por modelo, sin enlaces públicos.
     * GET /mercado
     */
    public function index(Request $request): Response
    {
        $models = MarketModel::query()
            ->where(function ($q) {
                // Modelos globales (sin org) o de organizaciones públicas
                $q->whereNull('organization_id')
                    ->orWhereHas('organization', fn ($org) => $org->where('is_public', true));
            })
            ->where('publicar_en_catalogo', true) // #5 — solo lo publicado en el catálogo
            ->when($request->input('categoria'), fn ($q, $c) => $q->where('categoria', $c))
            ->when($request->input('segmento'), fn ($q, $s) => $q->where('segmento', $s))
            ->when($request->input('tipo_cliente'), fn ($q, $t) => $q->porTipoCliente($t))
            ->when($request->input('veredicto'), fn ($q, $v) => $q->where('veredicto', $v))
            ->when($request->boolean('con_negocio'), fn ($q) => $q->where('hueco_neto_pct', '>', 0))
            ->orderByDesc('hueco_pct')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Public/MercadoIndex', [
            'models' => $models,
            'categorias' => MarketModel::CATEGORIAS,
            'segmentos' => MarketModel::SEGMENTOS,
            'tiposCliente' => MarketModel::TIPOS_CLIENTE,
            'filters' => $request->only(['categoria', 'segmento', 'tipo_cliente', 'veredicto', 'con_negocio']),
        ]);
    }

    /**
     * Panel admin del mapa de mercado.
     * GET /mercado/admin
     */
    public function admin(Request $request): Response
    {
        $orgId = $this->orgId($request);

        $models = MarketModel::query()
            ->deOrganizacion($orgId)
            ->when($request->input('caducados'), fn ($q) => $q->caducados())
            ->when($request->input('categoria'), fn ($q, $c) => $q->where('categoria', $c))
            ->when($request->input('segmento'), fn ($q, $s) => $q->where('segmento', $s))
            ->when($request->input('tipo_cliente'), fn ($q, $t) => $q->porTipoCliente($t))
            ->withCount('leads')
            ->with('history')
            ->orderByDesc('hueco_pct')
            ->paginate(50)
            ->withQueryString();

        // #4 — tendencia vs medición anterior (serializable para la vista)
        $models->getCollection()->each(fn ($m) => $m->setAttribute('tendencia', $m->tendencia()));

        // #9 — KPIs del mapa (solo de esta organización)
        $kpis = [
            'total' => MarketModel::query()->deOrganizacion($orgId)->count(),
            'verdes' => MarketModel::query()->deOrganizacion($orgId)->verdes()->count(),
            'amarillos' => MarketModel::query()->deOrganizacion($orgId)->where('veredicto', 'amarillo')->count(),
            'rojos' => MarketModel::query()->deOrganizacion($orgId)->where('veredicto', 'rojo')->count(),
            'oportunidades' => MarketModel::query()->deOrganizacion($orgId)->oportunidades()->count(),
            'caducados' => MarketModel::query()->deOrganizacion($orgId)->caducados()->count(),
            'hueco_medio' => round((float) MarketModel::query()->deOrganizacion($orgId)->whereNotNull('hueco_pct')->avg('hueco_pct'), 1),
            'leads' => MarketLead::query()->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))->count(),
            'leads_nuevos' => MarketLead::query()->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))->where('estado', 'nuevo')->count(),
        ];

        return Inertia::render('Mercado/Admin', [
            'models' => $models,
            'kpis' => $kpis,
            'categorias' => MarketModel::CATEGORIAS,
            'segmentos' => MarketModel::SEGMENTOS,
            'tiposCliente' => MarketModel::TIPOS_CLIENTE,
            'veredictos' => MarketModel::VEREDICTOS,
            'filters' => $request->only(['caducados', 'categoria', 'segmento', 'tipo_cliente']),
        ]);
    }

    /**
     * Edición manual de nota / veredicto / oportunidad desde el admin.
     * PATCH /mercado/admin/{marketModel}
     */
    public function update(Request $request, MarketModel $marketModel): RedirectResponse
    {
        $this->authorizeAccess($request, $marketModel->organization_id);

        $validated = $request->validate([
            'veredicto' => ['sometimes', Rule::in(MarketModel::VEREDICTOS)],
            'oportunidad' => ['sometimes', 'boolean'],
            'publicar_en_catalogo' => ['sometimes', 'boolean'],
            'nota' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        // #1 — bucle skill↔SaaS: si el humano toca el veredicto, se marca como corregido
        // y la skill estudio-mercado NO lo sobrescribirá en la próxima pasada.
        if (array_key_exists('veredicto', $validated) && $validated['veredicto'] !== $marketModel->veredicto) {
            $validated['veredicto_fuente'] = 'humano';
        }

        $marketModel->update($validated);

        return back()->with('success', 'Modelo de mercado actualizado.');
    }

    /**
     * #2 — Captura de lead desde el catálogo público /mercado.
     * POST /mercado/{marketModel}/interes
     */
    public function storeLead(Request $request, MarketModel $marketModel): RedirectResponse
    {
        $this->abortIfOculto($marketModel);

        $validated = $request->validate([
            'nombre' => ['nullable', 'string', 'max:120'],
            'contacto' => ['required', 'string', 'max:180'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'mensaje' => ['nullable', 'string', 'max:1000'],
        ]);

        MarketLead::create([
            'market_model_id' => $marketModel->id,
            'organization_id' => $marketModel->organization_id,
            'nombre' => $validated['nombre'] ?? null,
            'contacto' => $validated['contacto'],
            'presupuesto' => $validated['presupuesto'] ?? null,
            'mensaje' => $validated['mensaje'] ?? null,
            'estado' => 'nuevo',
            'origen' => 'mercado',
        ]);

        // Notificación de lead nuevo (push OneSignal si la org lo tiene configurado)
        try {
            if ($marketModel->organization_id) {
                $alert = Alert::create([
                    'organization_id' => $marketModel->organization_id,
                    'alert_type' => 'market_lead',
                    'reference_type' => 'market_model',
                    'reference_id' => $marketModel->id,
                    'message' => 'Nuevo lead: '.($validated['nombre'] ?? 'anónimo').' interesado en '.$marketModel->modelo,
                ]);
                PushNotificationDispatcher::dispatch($alert);
            }
        } catch (\Throwable $e) {
            // no romper el flujo público si falla la notificación
        }

        return back()->with('success', 'Gracias. Te contactamos con la información de esta referencia.');
    }

    /**
     * #2 — Listado de leads del catálogo (admin).
     * GET /mercado/admin/leads
     */
    public function leads(Request $request): Response
    {
        $orgId = $this->orgId($request);

        $leads = MarketLead::query()
            ->with('marketModel')
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->when($request->input('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Mercado/Leads', [
            'leads' => $leads,
            'estados' => MarketLead::ESTADOS,
            'filters' => $request->only(['estado']),
        ]);
    }

    /**
     * #3 — Desglose de coste puesto en Huelva para una referencia.
     * GET /mercado/{marketModel}/coste?precio=xxx
     */
    public function coste(Request $request, MarketModel $marketModel): JsonResponse
    {
        $this->abortIfOculto($marketModel);

        $precio = $request->float('precio') ?: (float) $marketModel->precio_desde_de;

        return response()->json([
            'modelo' => $marketModel->modelo,
            'coste' => $marketModel->costePuestoEnHuelva($precio),
        ]);
    }

    /**
     * #1 — Pipeline de leads: cambiar estado + nota interna.
     * PATCH /mercado/admin/leads/{marketLead}
     */
    public function updateLead(Request $request, MarketLead $marketLead): RedirectResponse
    {
        $this->authorizeAccess($request, $marketLead->organization_id);

        $validated = $request->validate([
            'estado' => ['sometimes', Rule::in(MarketLead::ESTADOS)],
            'nota' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $marketLead->update($validated);

        return back()->with('success', 'Lead actualizado.');
    }

    /**
     * #3 — Panel de reportes del mapa.
     * GET /mercado/admin/reportes
     */
    public function reportes(Request $request): Response
    {
        $orgId = $this->orgId($request);
        $orgFilter = fn () => MarketModel::query()->deOrganizacion($orgId);

        $porCategoria = $orgFilter()
            ->whereNotNull('hueco_pct')
            ->groupBy('categoria')
            ->selectRaw('categoria, round(avg(hueco_pct),1) as hueco_medio, count(*) as n, sum(case when veredicto="verde" then 1 else 0 end) as verdes')
            ->orderByDesc('hueco_medio')
            ->get();

        $porSegmento = $orgFilter()
            ->whereNotNull('segmento')
            ->groupBy('segmento')
            ->selectRaw('segmento, round(avg(hueco_pct),1) as hueco_medio, count(*) as n')
            ->orderByDesc('hueco_medio')
            ->get();

        $topOportunidades = MarketModel::query()
            ->deOrganizacion($orgId)
            ->oportunidades()
            ->orderByDesc('vendibilidad')
            ->orderByDesc('hueco_neto_pct')
            ->limit(10)
            ->get();

        $evolucion = MarketModelHistory::query()
            ->whereHas('marketModel', fn ($q) => $q->where(fn ($x) => $x->where('organization_id', $orgId)->orWhereNull('organization_id')))
            ->with('marketModel')
            ->orderByDesc('medido_el')
            ->limit(200)
            ->get()
            ->groupBy('medido_el')
            ->map(fn ($grupo) => [
                'fecha' => $grupo->first()->medido_el?->toDateString(),
                'hueco_medio' => round((float) $grupo->avg('hueco_pct'), 1),
                'n' => $grupo->count(),
            ])
            ->values();

        return Inertia::render('Mercado/Reportes', [
            'porCategoria' => $porCategoria,
            'porSegmento' => $porSegmento,
            'topOportunidades' => $topOportunidades,
            'evolucion' => $evolucion,
        ]);
    }

    private function orgId(Request $request): ?int
    {
        return $request->user()?->organization_id;
    }

    private function authorizeAccess(Request $request, ?int $ownerOrgId): void
    {
        $orgId = $this->orgId($request);
        abort_unless($ownerOrgId === null || $ownerOrgId === $orgId, 403);
    }

    /**
     * Blindaje público: solo modelos publicados y visibles (globales o de org pública)
     * pueden recibir leads o exponer su coste. En otro caso → 404 (no revelar existencia).
     */
    private function abortIfOculto(MarketModel $marketModel): void
    {
        $visible = $marketModel->publicar_en_catalogo
            && ($marketModel->organization_id === null
                || $marketModel->organization?->is_public === true);

        abort_unless($visible, 404);
    }
}
