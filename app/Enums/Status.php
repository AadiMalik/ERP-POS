<?php

namespace App\Enums;

class  Status
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const SUSPENDED = 'suspended';
    const EXPIRED = 'expired';
    const CANCELLED = 'cancelled';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const PENDING = 'pending';
    const POSTED = 'posted';
    const COMPLETED = 'completed';
    const QUOTATION_SENT = 'quotation sent';
    const QUOTATION_RECEIVED = 'quotation received';
    const CONVERTED = 'converted';
    const DELIVERED = 'delivered';
    const SHIPPED = 'shipped';
    const RECEIVED = 'received';
    const RETURNED = 'returned';
    const REFUNDED = 'refunded';
    const SENT='sent';
    const SELECTED='selected';
    const AVAILABLE='available';
    const OUT_OF_STOCK='out of stock';

    // Subscription lifecycle
    const TRIAL = 'trial';
    const PAYMENT_PENDING = 'payment_pending';
    const GRACE_PERIOD = 'grace_period';
    const EXPIRING_SOON = 'expiring_soon';
    const CHANGES_REQUESTED = 'changes_requested';

    // Subscription invoice / payment status
    const DRAFT = 'draft';
    const UNPAID = 'unpaid';
    const PARTIALLY_PAID = 'partially_paid';
    const PAID = 'paid';
    const VOID = 'void';
    const CONFIRMED = 'confirmed';
}