<?php

namespace App\Enums;

/** Tipos de entrada en la bitácora del encargo (order_events). */
enum OrderEventType: string
{
    case Created = 'created';
    case StateChanged = 'state_changed';
    case ContractIssued = 'contract_issued';
    case ContractViewed = 'contract_viewed';
    case ContractSigned = 'contract_signed';
    case ContractRejected = 'contract_rejected';
    case ContractVoided = 'contract_voided';
    case ContractExpired = 'contract_expired';
    case PaymentScheduled = 'payment_scheduled';
    case PaymentRecorded = 'payment_recorded';
    case ApprovalRequested = 'approval_requested';
    case ApprovalResolved = 'approval_resolved';
    case MediaPublished = 'media_published';
    case MilestoneCompleted = 'milestone_completed';
    case VehicleReplaced = 'vehicle_replaced';
    case CancellationRequested = 'cancellation_requested';
    case PortalLinkRotated = 'portal_link_rotated';
    case NotificationSent = 'notification_sent';
    case Incident = 'incident';
    case IncidentResolved = 'incident_resolved';
    case Note = 'note';
}
