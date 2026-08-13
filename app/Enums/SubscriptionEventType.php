<?php

namespace App\Enums;

class SubscriptionEventType
{
    const CREATED = 'created';
    const RENEWED = 'renewed';
    const PLAN_CHANGED = 'plan_changed';
    const SUSPENDED = 'suspended';
    const CANCELLED = 'cancelled';
    const EXPIRED = 'expired';
    const GRACE_PERIOD_ENTERED = 'grace_period_entered';
    const REACTIVATED = 'reactivated';
    const PAYMENT_RECORDED = 'payment_recorded';
    const PAYMENT_APPROVED = 'payment_approved';
    const PAYMENT_REJECTED = 'payment_rejected';
    const INVOICE_VOIDED = 'invoice_voided';
    const RENEWAL_REQUESTED = 'renewal_requested';
    const RENEWAL_APPROVED = 'renewal_approved';
    const RENEWAL_REJECTED = 'renewal_rejected';
    const RENEWAL_CHANGES_REQUESTED = 'renewal_changes_requested';
    const BACKFILLED = 'backfilled';
}
