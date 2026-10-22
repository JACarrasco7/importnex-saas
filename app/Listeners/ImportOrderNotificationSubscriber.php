<?php

namespace App\Listeners;

use App\Enums\ApprovalKind;
use App\Enums\ApprovalStatus;
use App\Enums\ImportOrderState;
use App\Events\ImportOrderStateChanged;
use App\Events\OrderApprovalRequested;
use App\Events\OrderApprovalResolved;
use App\Events\OrderCancellationRequested;
use App\Events\OrderContractIssued;
use App\Events\OrderContractRejected;
use App\Events\OrderContractSigned;
use App\Events\OrderContractVoided;
use App\Events\OrderMessagePosted;
use App\Events\OrderPaymentRecorded;
use App\Models\ImportOrder;
use App\Models\OrderMessage;
use App\Services\ImportOrders\OrderNotifier;
use App\Support\ContractDocument;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Traduce los eventos del encargo en avisos al cliente y al equipo.
 *
 * Síncrono a propósito (sin worker de colas en Forge) y cada handler envuelto
 * en try/catch: un fallo de correo nunca rompe la acción que lo disparó.
 */
class ImportOrderNotificationSubscriber
{
    public function __construct(private OrderNotifier $notifier) {}

    public function onContractIssued(OrderContractIssued $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $order = $event->order;
            $params = $this->params($order) + ['date' => $event->contract->expires_at?->format('d/m/Y')];

            $this->notifier->toClient(
                $order,
                __('orders.mail.contract_issued_subject', $params),
                __('orders.mail.contract_issued_heading'),
                [
                    __('orders.mail.contract_issued_line1', $params),
                    __('orders.mail.contract_issued_line2'),
                    __('orders.mail.contract_issued_line3', $params),
                ],
                __('orders.mail.contract_issued_button'),
            );
        });
    }

    public function onContractSigned(OrderContractSigned $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $order = $event->order;
            $contract = $event->contract;
            $params = $this->params($order) + [
                'amount' => number_format($order->depositAmount(), 2, ',', '.'),
                'hash' => $contract->contract_hash,
            ];

            $files = [];
            if ($contract->pdf_path) {
                $isPdf = str_ends_with($contract->pdf_path, '.pdf');
                $files[] = [
                    'path' => $contract->pdf_path,
                    'name' => ContractDocument::downloadName($contract, $isPdf ? 'pdf' : 'html'),
                    'mime' => $isPdf ? 'application/pdf' : 'text/html',
                ];
            }

            $this->notifier->toClient(
                $order,
                __('orders.mail.contract_signed_subject', $params),
                __('orders.mail.contract_signed_heading'),
                [
                    __('orders.mail.contract_signed_line1', $params),
                    (float) $order->fee_amount > 0
                        ? __('orders.mail.contract_signed_line2', $params)
                        : __('orders.mail.contract_signed_line2_nofee'),
                    __('orders.mail.contract_signed_line3', $params),
                ],
                files: $files,
            );

            $this->notifier->toTeam($order, __('orders.mail.team_contract_signed_subject', $params), [
                __('orders.mail.team_contract_signed_line', $params),
            ]);
        });
    }

    public function onContractRejected(OrderContractRejected $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $params = $this->params($event->order) + ['reason' => (string) $event->contract->rejection_reason];
            $this->notifier->toTeam($event->order, __('orders.mail.team_contract_rejected_subject', $params), [
                __('orders.mail.team_contract_rejected_line', $params),
            ]);
        });
    }

    public function onContractVoided(OrderContractVoided $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $params = $this->params($event->order) + ['reason' => $event->reason];
            $this->notifier->toTeam($event->order, __('orders.mail.team_contract_voided_subject', $params), [
                __('orders.mail.team_contract_voided_line', $params),
            ]);
        });
    }

    public function onStateChanged(ImportOrderStateChanged $event): void
    {
        $from = $event->from;
        $to = $event->to;

        // Solo avisamos al cliente de cambios de fase visibles. Firma, señal y
        // decisiones ya tienen su propio email; evitar duplicados.
        $silent = [
            ImportOrderState::Draft, ImportOrderState::AwaitingSignature, ImportOrderState::ContractRejected,
            ImportOrderState::AwaitingDeposit, ImportOrderState::AwaitingClientDecision,
        ];
        if (in_array($to, $silent, true) || ($from->clientPhase() === $to->clientPhase() && ! $to->isTerminal() && $to !== ImportOrderState::SearchingReplacement)) {
            return;
        }

        $this->safely($event->order, function () use ($event, $to) {
            $order = $event->order;
            $params = $this->params($order) + ['state' => $to->clientLabel()];
            $lines = [__('orders.mail.phase_changed_line1', $params), $to->clientPhase()->description()];
            if ($event->reason && $to === ImportOrderState::Cancelled) {
                $lines[] = $event->reason;
            }

            $this->notifier->toClient(
                $order,
                __('orders.mail.phase_changed_subject', $params),
                __('orders.mail.phase_changed_heading', $params),
                $lines,
            );
        });
    }

    public function onPaymentRecorded(OrderPaymentRecorded $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $payment = $event->payment;
            $params = $this->params($event->order) + [
                'amount' => number_format((float) $payment->amount, 2, ',', '.'),
                'concept' => $payment->concept,
                'method' => $payment->method?->label() ?? '—',
            ];

            $this->notifier->toClient(
                $event->order,
                __('orders.mail.payment_subject', $params),
                __('orders.mail.payment_heading'),
                [__('orders.mail.payment_line1', $params), __('orders.mail.payment_line2')],
            );
        });
    }

    public function onApprovalRequested(OrderApprovalRequested $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $approval = $event->approval;
            $params = $this->params($event->order) + [
                'title' => $approval->title,
                'amount' => number_format((float) $approval->amount, 2, ',', '.'),
                'date' => $approval->due_at?->format('d/m/Y H:i'),
            ];

            $lines = [__('orders.mail.approval_line1', $params)];
            if ($approval->description) {
                $lines[] = $approval->description;
            }
            if ($approval->kind === ApprovalKind::ExtraCost) {
                $lines[] = __('orders.mail.approval_amount', $params);
            }
            $lines[] = __('orders.mail.approval_line2', $params);

            $this->notifier->toClient(
                $event->order,
                __('orders.mail.approval_subject', $params),
                __('orders.mail.approval_heading'),
                $lines,
                __('orders.mail.approval_button'),
            );
        });
    }

    public function onApprovalResolved(OrderApprovalResolved $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $approval = $event->approval;
            $params = $this->params($event->order) + ['title' => $approval->title];
            $expired = $approval->status === ApprovalStatus::Expired;

            $lines = [$approval->status->label().($approval->decision ? ' · '.$approval->decision->label() : '')];
            if ($approval->client_comment) {
                $lines[] = '«'.$approval->client_comment.'»';
            }

            $this->notifier->toTeam(
                $event->order,
                __($expired ? 'orders.mail.team_approval_expired_subject' : 'orders.mail.team_approval_resolved_subject', $params),
                $lines,
            );
        });
    }

    public function onMessagePosted(OrderMessagePosted $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $order = $event->order;
            $message = $event->message;
            $params = $this->params($order);

            if ($message->author_type === OrderMessage::AUTHOR_CLIENT) {
                $this->notifier->toTeam($order, __('orders.mail.team_message_subject', $params), [$message->body]);

                return;
            }

            $this->notifier->toClient(
                $order,
                __('orders.mail.message_subject', $params),
                __('orders.mail.message_heading'),
                [$message->body],
                __('orders.mail.message_button'),
            );
        });
    }

    public function onCancellationRequested(OrderCancellationRequested $event): void
    {
        $this->safely($event->order, function () use ($event) {
            $params = $this->params($event->order) + ['reason' => $event->reason];
            $this->notifier->toTeam($event->order, __('orders.mail.team_cancellation_subject', $params), [
                __('orders.mail.team_cancellation_line', $params),
            ]);
        });
    }

    /** @return array<string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            OrderContractIssued::class => 'onContractIssued',
            OrderContractSigned::class => 'onContractSigned',
            OrderContractRejected::class => 'onContractRejected',
            OrderContractVoided::class => 'onContractVoided',
            ImportOrderStateChanged::class => 'onStateChanged',
            OrderPaymentRecorded::class => 'onPaymentRecorded',
            OrderApprovalRequested::class => 'onApprovalRequested',
            OrderApprovalResolved::class => 'onApprovalResolved',
            OrderMessagePosted::class => 'onMessagePosted',
            OrderCancellationRequested::class => 'onCancellationRequested',
        ];
    }

    /** @return array<string, string> */
    private function params(ImportOrder $order): array
    {
        return [
            'reference' => $order->reference,
            'vehicle' => $order->vehicleLabel(),
            'client' => (string) $order->client?->name,
        ];
    }

    private function safely(ImportOrder $order, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('orders.subscriber_failed', ['order_id' => $order->id, 'message' => $e->getMessage()]);
        }
    }
}
