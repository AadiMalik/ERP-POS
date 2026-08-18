<?php

namespace App\Console\Commands;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Business;
use App\Models\CustomerProfile;
use App\Models\CustomerSetting;
use App\Models\InventorySetting;
use App\Models\NotificationSetting;
use App\Models\Order;
use App\Models\ProductVariationStock;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionSetting;
use App\Services\Concrete\Admin\NotificationDispatchService;
use App\Services\Concrete\Admin\Reports\AccountsPayableInvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckNotificationAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-alerts {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluates low-stock, payment-due, credit-limit, supplier-payment-reminder and subscription-expiry conditions and dispatches in-app alerts. Safe to re-run (duplicate-send is enforced at the database level via NotificationDispatchService).';

    protected NotificationDispatchService $dispatcher;
    protected AccountsPayableInvoiceService $accounts_payable_service;

    public function __construct(NotificationDispatchService $dispatcher, AccountsPayableInvoiceService $accounts_payable_service)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
        $this->accounts_payable_service = $accounts_payable_service;
    }

    public function handle()
    {
        $dry_run = (bool) $this->option('dry-run');

        $this->checkLowStock($dry_run);
        $this->checkPaymentDue($dry_run);
        $this->checkCreditLimit($dry_run);
        $this->checkSupplierPaymentReminder($dry_run);
        $this->checkSubscriptionExpiry($dry_run);

        $this->info('Notification alert check complete.');

        return 0;
    }

    protected function getNotificationSetting(?string $business_id): NotificationSetting
    {
        return NotificationSetting::where('business_id', $business_id)->first() ?? new NotificationSetting([
            'payment_due_alert_enabled' => true,
            'payment_due_days_before' => 3,
            'credit_limit_alert_enabled' => true,
            'credit_limit_threshold_percent' => 100,
            'supplier_payment_reminder_enabled' => true,
            'supplier_payment_reminder_days_before' => 3,
            'order_status_alert_enabled' => true,
            'sound_enabled' => true,
        ]);
    }

    protected function checkLowStock(bool $dry_run): void
    {
        $business_ids = InventorySetting::where('low_stock_alert', 1)->pluck('business_id')->filter();

        foreach ($business_ids as $business_id) {
            $inventory_setting = InventorySetting::where('business_id', $business_id)->first();
            $fallback_threshold = (float) ($inventory_setting->low_stock_quantity ?? 0);

            $stocks = ProductVariationStock::with(['productVariation', 'warehouse', 'product'])
                ->where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->get();

            foreach ($stocks as $stock) {
                $variation = $stock->productVariation;
                if (!$variation) {
                    continue;
                }

                $threshold = (float) ($variation->minimum_stock > 0 ? $variation->minimum_stock : $fallback_threshold);

                if ($threshold <= 0 || (float) $stock->quantity > $threshold) {
                    continue;
                }

                if ($dry_run) {
                    $this->line("Would alert low stock: {$variation->sku} ({$stock->quantity} <= {$threshold})");
                    continue;
                }

                $this->dispatcher->dispatch(
                    'low_stock',
                    $business_id,
                    $stock->warehouse->branch_id ?? null,
                    'Low Stock Alert',
                    'Stock for "' . ($variation->product->name ?? $variation->sku) . '" (' . $variation->sku . ') is low: ' . $stock->quantity . ' remaining at ' . ($stock->warehouse->name ?? 'warehouse') . '.',
                    'product_variation_stock',
                    $stock->product_variation_stock_id,
                    route('product.show', $variation->product_id),
                    ['quantity' => $stock->quantity, 'threshold' => $threshold, 'warehouse_id' => $stock->warehouse_id],
                    now()->toDateString()
                );
            }
        }
    }

    protected function checkPaymentDue(bool $dry_run): void
    {
        $today = Carbon::today();

        $orders = Order::where('is_deleted', 0)
            ->where('status', Status::POSTED)
            ->whereRaw('(total - paid_amount) > 0')
            ->get();

        foreach ($orders as $order) {
            $setting = $this->getNotificationSetting($order->business_id);
            if (!$setting->payment_due_alert_enabled) {
                continue;
            }

            $customer = CustomerProfile::where('user_id', $order->user_id)
                ->where('business_id', $order->business_id)
                ->first();

            if (!$customer || (int) $customer->credit_days <= 0 || empty($order->order_date)) {
                continue;
            }

            $due_date = Carbon::parse($order->order_date)->addDays((int) $customer->credit_days);
            $alert_from = $due_date->copy()->subDays((int) $setting->payment_due_days_before);

            if ($today->lt($alert_from)) {
                continue;
            }

            if ($dry_run) {
                $this->line("Would alert payment due: order {$order->order_id} (due {$due_date->toDateString()})");
                continue;
            }

            $due_amount = max((float) $order->total - (float) $order->paid_amount, 0);

            $this->dispatcher->dispatch(
                'payment_due',
                $order->business_id,
                $order->branch_id,
                'Payment Due Alert',
                'Order #' . $order->daily_order_id . ' has an outstanding balance of ' . number_format($due_amount, 2) . ' due on ' . $due_date->toDateString() . '.',
                'order',
                $order->order_id,
                route('order.show', $order->order_id),
                ['due_amount' => $due_amount, 'due_date' => $due_date->toDateString()],
                $today->toDateString()
            );
        }
    }

    protected function checkCreditLimit(bool $dry_run): void
    {
        $customers = CustomerProfile::where('is_deleted', 0)
            ->where('credit_limit', '>', 0)
            ->get();

        foreach ($customers as $customer) {
            $setting = $this->getNotificationSetting($customer->business_id);
            if (!$setting->credit_limit_alert_enabled) {
                continue;
            }

            $customer_setting = CustomerSetting::where('business_id', $customer->business_id)->first();
            if ($customer_setting && !$customer_setting->enable_credit_limit) {
                continue;
            }

            $outstanding = (float) Order::where('user_id', $customer->user_id)
                ->where('business_id', $customer->business_id)
                ->where('is_deleted', 0)
                ->where('status', Status::POSTED)
                ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as outstanding')
                ->value('outstanding');

            $threshold_amount = (float) $customer->credit_limit * ((int) $setting->credit_limit_threshold_percent / 100);

            if ($threshold_amount <= 0 || $outstanding < $threshold_amount) {
                continue;
            }

            if ($dry_run) {
                $this->line("Would alert credit limit: customer {$customer->customer_profile_id} (outstanding {$outstanding} >= {$threshold_amount})");
                continue;
            }

            $this->dispatcher->dispatch(
                'credit_limit',
                $customer->business_id,
                $customer->branch_id,
                'Customer Credit Limit Alert',
                'Customer "' . ($customer->company_name ?? $customer->code) . '" has an outstanding balance of ' . number_format($outstanding, 2) . ', at or above their credit limit of ' . number_format((float) $customer->credit_limit, 2) . '.',
                'customer_profile',
                $customer->customer_profile_id,
                route('users.show', $customer->user_id),
                ['outstanding' => $outstanding, 'credit_limit' => (float) $customer->credit_limit],
                Carbon::today()->toDateString()
            );
        }
    }

    protected function checkSupplierPaymentReminder(bool $dry_run): void
    {
        $business_ids = NotificationSetting::where('supplier_payment_reminder_enabled', 1)->pluck('business_id')
            ->merge(Business::where('is_deleted', 0)->whereDoesntHave('notificationSetting')->pluck('business_id'))
            ->unique()
            ->filter();

        $today = Carbon::today();

        foreach ($business_ids as $business_id) {
            $setting = $this->getNotificationSetting($business_id);
            if (!$setting->supplier_payment_reminder_enabled) {
                continue;
            }

            $invoices = $this->accounts_payable_service->getInvoices(['business_id' => $business_id]);

            foreach ($invoices as $invoice) {
                if (($invoice->outstanding_amount ?? 0) <= 0 || empty($invoice->due_date)) {
                    continue;
                }

                $alert_from = Carbon::parse($invoice->due_date)->subDays((int) $setting->supplier_payment_reminder_days_before);

                if ($today->lt($alert_from)) {
                    continue;
                }

                if ($dry_run) {
                    $this->line("Would alert supplier payment reminder: purchase {$invoice->purchase_id} (due {$invoice->due_date})");
                    continue;
                }

                $this->dispatcher->dispatch(
                    'supplier_payment_reminder',
                    $invoice->business_id,
                    $invoice->branch_id ?? null,
                    'Supplier Payment Reminder',
                    'Purchase "' . ($invoice->invoice_number ?? $invoice->purchase_no) . '" from ' . ($invoice->supplier_name ?? 'supplier') . ' has an outstanding balance of ' . number_format((float) $invoice->outstanding_amount, 2) . ' due on ' . Carbon::parse($invoice->due_date)->toDateString() . '.',
                    'purchase',
                    $invoice->purchase_id,
                    $invoice->purchase_id ? route('purchase.show', $invoice->purchase_id) : null,
                    ['outstanding_amount' => (float) $invoice->outstanding_amount, 'due_date' => Carbon::parse($invoice->due_date)->toDateString()],
                    $today->toDateString()
                );
            }
        }
    }

    protected function checkSubscriptionExpiry(bool $dry_run): void
    {
        $settings = SubscriptionSetting::current();
        $threshold = (int) $settings->expiry_alert_days_before;
        $now = now();

        $subscriptions = BusinessSubscription::whereIn('status', [Status::TRIAL, Status::ACTIVE])
            ->whereNotNull('end_at')
            ->where('end_at', '>=', $now)
            ->where('is_deleted', 0)
            ->get();

        foreach ($subscriptions as $subscription) {
            $remaining_days = (int) floor($now->diffInDays($subscription->end_at, false));

            if ($remaining_days > $threshold) {
                continue;
            }

            if ($dry_run) {
                $this->line("Would alert subscription expiry: {$subscription->business_subscription_id} ({$remaining_days} day(s) left)");
                continue;
            }

            $message = 'Subscription for business expires in ' . $remaining_days . ' day(s) (on ' . Carbon::parse($subscription->end_at)->toDateString() . ').';

            // Two separate dispatches - one per audience - so each gets its
            // own recipient-appropriate url and neither read state affects
            // the other (see NotificationDispatchService::defaultRoles()).
            $this->dispatcher->dispatch(
                'subscription_expiry',
                $subscription->business_id,
                null,
                'Subscription Expiry Alert',
                $message,
                'business_subscription',
                $subscription->business_subscription_id,
                route('my-subscription.index'),
                ['remaining_days' => $remaining_days, 'end_at' => $subscription->end_at],
                'expiry_alert_business',
                [RoleNames::BUSINESSADMIN]
            );

            $this->dispatcher->dispatch(
                'subscription_expiry',
                $subscription->business_id,
                null,
                'Subscription Expiry Alert',
                $message,
                'business_subscription',
                $subscription->business_subscription_id,
                route('subscriptions.show', $subscription->business_id),
                ['remaining_days' => $remaining_days, 'end_at' => $subscription->end_at],
                'expiry_alert_superadmin',
                [RoleNames::SUPERADMIN]
            );
        }
    }
}
