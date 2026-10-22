<?php

namespace App\Http\Controllers\ImportOrders;

use App\Enums\ApprovalStatus;
use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Enums\OrderEventType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarRequest;
use App\Models\ImportOrder;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\ImportOrders\ImportOrderService;
use App\Services\ImportOrders\OrderFlowException;
use App\Services\ImportOrders\OrderMessageService;
use App\Services\ImportOrders\OrderPanelPresenter;
use App\Services\ImportOrders\OrderTimeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Panel de encargos: bandeja, ficha del encargo, estados y datos básicos. */
class ImportOrderController extends Controller
{
    use AuthorizesImportOrder;

    public function __construct(
        private ImportOrderService $orders,
        private OrderPanelPresenter $presenter,
        private OrderMessageService $messages,
        private OrderTimeline $timeline,
    ) {}

    public function index(Request $request): Response
    {
        $orgId = (int) auth()->user()->organization_id;
        $tab = in_array($request->input('tab'), ['attention', 'active', 'closed', 'all'], true) ? $request->input('tab') : 'attention';
        $search = trim((string) $request->input('search'));

        $base = fn (): Builder => ImportOrder::query()->forOrganization($orgId);

        $query = $base()
            ->with(['client:id,name,email,phone', 'car:id,brand,model,version,year'])
            ->withCount([
                'messages as unread_messages_count' => fn ($q) => $q->where('author_type', OrderMessage::AUTHOR_CLIENT)->whereNull('read_by_team_at'),
                'approvals as pending_approvals_count' => fn ($q) => $q->where('status', ApprovalStatus::Pending->value),
                'payments as pending_payments_count' => fn ($q) => $q->where('status', PaymentStatus::Pending->value),
            ])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $sub) => $sub
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('client', fn ($c) => $c->withoutGlobalScopes()->where('name', 'like', "%{$search}%"))
                ->orWhereHas('car', fn ($c) => $c->withoutGlobalScopes()->where('model', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%"))));

        $this->applyTab($query, $tab);

        $orders = $query->latest('updated_at')->paginate(20)->withQueryString()
            ->through(fn (ImportOrder $order) => $this->presenter->listItem($order));

        return Inertia::render('ImportOrders/Index', [
            'orders' => $orders,
            'filters' => ['tab' => $tab, 'search' => $search],
            'counts' => [
                'attention' => $this->applyTab($base(), 'attention')->count(),
                'active' => $this->applyTab($base(), 'active')->count(),
                'closed' => $this->applyTab($base(), 'closed')->count(),
                'all' => $base()->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) auth()->user()->organization_id;
        $data = $request->validate([
            'car_id' => ['required', 'integer', Rule::exists('cars', 'id')->where('organization_id', $orgId)],
            'car_request_id' => ['nullable', 'integer', Rule::exists('car_requests', 'id')->where('organization_id', $orgId)],
        ]);

        $car = Car::withoutGlobalScope('organization')->where('organization_id', $orgId)->findOrFail($data['car_id']);
        $carRequest = isset($data['car_request_id']) ? CarRequest::query()->find($data['car_request_id']) : null;

        try {
            $order = $this->orders->createForCar($car, $request->user(), $carRequest);
        } catch (OrderFlowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('import-orders.show', $order->id)
            ->with('success', "Encargo {$order->reference} creado. Siguiente paso: revisa los datos y envía el contrato.");
    }

    public function show(ImportOrder $importOrder): Response
    {
        $this->authorizeOrder($importOrder);
        $this->messages->markReadByTeam($importOrder);

        return Inertia::render('ImportOrders/Show', $this->presenter->present($importOrder));
    }

    public function update(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $orgId = (int) $importOrder->organization_id;

        $data = $request->validate([
            'fee_amount' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'expected_delivery_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('organization_id', $orgId)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $feeLocked = ! in_array($importOrder->state, [ImportOrderState::Draft, ImportOrderState::ContractRejected], true);
        if (array_key_exists('fee_amount', $data) && $feeLocked && (float) $data['fee_amount'] !== (float) $importOrder->fee_amount) {
            return back()->withErrors(['fee_amount' => 'Los honorarios no se pueden cambiar con un contrato enviado o firmado. Anula el contrato primero.']);
        }

        $importOrder->fill($data)->save();

        if (array_key_exists('expected_delivery_date', $data) && $importOrder->wasChanged('expected_delivery_date') && $importOrder->expected_delivery_date) {
            $this->timeline->record($importOrder, OrderEventType::Note, 'Fecha estimada de entrega: '.$importOrder->expected_delivery_date->format('d/m/Y'), [
                'user' => $request->user(),
                'visible' => true,
            ]);
            $importOrder->car?->forceFill(['expected_delivery_date' => $importOrder->expected_delivery_date])->save();
        }

        return back()->with('success', 'Encargo actualizado.');
    }

    public function transition(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $data = $request->validate([
            'to' => ['required', Rule::enum(ImportOrderState::class)],
            'reason' => ['nullable', 'string', 'max:2000'],
            'force' => ['nullable', 'boolean'],
        ]);

        $target = ImportOrderState::from($data['to']);

        return $this->flowAction(fn () => $this->orders->transition($importOrder, $target, [
            'user' => $request->user(),
            'reason' => $data['reason'] ?? null,
            'force' => (bool) ($data['force'] ?? false),
        ]), 'Estado actualizado: '.$target->label());
    }

    public function replaceVehicle(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $data = $request->validate([
            'car_id' => ['required', 'integer', Rule::exists('cars', 'id')->where('organization_id', $importOrder->organization_id)],
        ]);

        $car = Car::withoutGlobalScope('organization')->findOrFail($data['car_id']);

        return $this->flowAction(
            fn () => $this->orders->replaceVehicle($importOrder, $car, $request->user()),
            'Coche sustituido. El encargo vuelve a «Inspección».',
        );
    }

    public function rotatePortal(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $this->orders->rotatePortalToken($importOrder, $request->user());

        return back()->with('success', 'Enlace renovado. El anterior ya no funciona: envía el nuevo al cliente.');
    }

    /** Registra en la bitácora que se ha compartido algo por WhatsApp/email a mano. */
    public function logShare(Request $request, ImportOrder $importOrder): RedirectResponse
    {
        $this->authorizeOrder($importOrder);
        $data = $request->validate([
            'channel' => ['required', Rule::in(['whatsapp', 'email', 'copy'])],
            'what' => ['required', Rule::in(['portal', 'contract', 'payment', 'approval'])],
        ]);

        $labels = ['portal' => 'enlace del portal', 'contract' => 'contrato', 'payment' => 'datos de pago', 'approval' => 'petición de aprobación'];
        $channels = ['whatsapp' => 'WhatsApp', 'email' => 'email', 'copy' => 'enlace copiado'];

        $this->timeline->record($importOrder, OrderEventType::NotificationSent, "Enviado {$labels[$data['what']]} por {$channels[$data['channel']]}", [
            'user' => $request->user(),
            'meta' => $data,
        ]);

        return back();
    }

    private function applyTab(Builder $query, string $tab): Builder
    {
        $terminal = [ImportOrderState::Delivered->value, ImportOrderState::Cancelled->value];

        return match ($tab) {
            'attention' => $query->whereNotIn('state', $terminal)->where(fn (Builder $q) => $q
                ->whereIn('state', [ImportOrderState::Draft->value, ImportOrderState::ContractRejected->value, ImportOrderState::SearchingReplacement->value])
                ->orWhereNotNull('cancellation_requested_at')
                ->orWhereHas('messages', fn ($m) => $m->where('author_type', OrderMessage::AUTHOR_CLIENT)->whereNull('read_by_team_at'))
                ->orWhereHas('approvals', fn ($a) => $a->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value, ApprovalStatus::Expired->value])->where('resolved_at', '>=', now()->subDays(2)))
                ->orWhere(fn (Builder $unsent) => $unsent->where('state', ImportOrderState::AwaitingSignature->value)
                    ->whereDoesntHave('contracts', fn ($c) => $c->whereIn('status', [ContractStatus::Sent->value, ContractStatus::Viewed->value])->where('expires_at', '>', now())))
                ->orWhere(fn (Builder $deposit) => $deposit->where('state', ImportOrderState::AwaitingDeposit->value)->where('state_changed_at', '<=', now()->subDays(3)))),
            'active' => $query->whereNotIn('state', $terminal),
            'closed' => $query->whereIn('state', $terminal),
            default => $query,
        };
    }

    /** @return list<array{id: int, name: string}> */
    public static function teamMembers(int $organizationId): array
    {
        return User::query()->where('organization_id', $organizationId)->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])->all();
    }
}
