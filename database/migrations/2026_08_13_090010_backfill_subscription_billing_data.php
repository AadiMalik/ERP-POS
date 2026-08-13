<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfills every existing business_subscriptions row into the new
     * subscription_invoices / subscription_payments / subscription_history
     * tables, and points businesses.current_business_subscription_id at the
     * latest subscription per business. No existing column value on
     * business_subscriptions or businesses is modified or deleted.
     *
     * @return void
     */
    public function up()
    {
        $subscriptions = DB::table('business_subscriptions')->orderBy('date_created')->get();

        $sequence = 1;
        $latestByBusiness = [];

        foreach ($subscriptions as $subscription) {
            $package = $subscription->package_id
                ? DB::table('packages')->where('package_id', $subscription->package_id)->first()
                : null;

            $billingCycle = $package->duration_type ?? 'monthly';

            DB::table('business_subscriptions')
                ->where('business_subscription_id', $subscription->business_subscription_id)
                ->update(['billing_cycle' => $billingCycle]);

            $invoiceId = (string) Str::uuid();
            $invoiceNo = 'SUB-INV-' . str_pad($sequence++, 6, '0', STR_PAD_LEFT);

            DB::table('subscription_invoices')->insert([
                'subscription_invoice_id' => $invoiceId,
                'business_id' => $subscription->business_id,
                'business_subscription_id' => $subscription->business_subscription_id,
                'package_id' => $subscription->package_id,
                'invoice_no' => $invoiceNo,
                'billing_cycle' => $billingCycle,
                'period_start' => $subscription->start_at,
                'period_end' => $subscription->end_at,
                'subtotal' => $subscription->subtotal,
                'discount' => $subscription->discount,
                'discount_type' => $subscription->discount_type,
                'discount_amount' => $subscription->discount_amount,
                'tax' => $subscription->tax,
                'tax_amount' => $subscription->tax_amount,
                'total' => $subscription->total,
                'status' => $subscription->payment_status === 'paid' ? 'paid' : 'unpaid',
                'is_deleted' => 0,
                'createdby_id' => $subscription->createdby_id,
                'date_created' => $subscription->date_created,
            ]);

            if ($subscription->payment_status === 'paid') {
                $paymentMethod = $subscription->payment_method === 'bank transfer'
                    ? 'bank_transfer'
                    : $subscription->payment_method;

                DB::table('subscription_payments')->insert([
                    'subscription_payment_id' => (string) Str::uuid(),
                    'subscription_invoice_id' => $invoiceId,
                    'business_id' => $subscription->business_id,
                    'amount' => $subscription->total,
                    'payment_method' => $paymentMethod ?: 'cash',
                    'payment_reference' => $subscription->payment_reference,
                    'status' => 'confirmed',
                    'paid_at' => $subscription->date_created,
                    'is_deleted' => 0,
                    'createdby_id' => $subscription->createdby_id,
                    'date_created' => $subscription->date_created,
                ]);
            }

            DB::table('subscription_history')->insert([
                'subscription_history_id' => (string) Str::uuid(),
                'business_id' => $subscription->business_id,
                'business_subscription_id' => $subscription->business_subscription_id,
                'event_type' => 'backfilled',
                'to_status' => $subscription->status,
                'to_package_id' => $subscription->package_id,
                'notes' => 'Backfilled from pre-existing business_subscriptions row during subscription/billing module upgrade.',
                'createdby_id' => $subscription->createdby_id,
                'date_created' => now(),
            ]);

            $latestByBusiness[$subscription->business_id] = $subscription->business_subscription_id;
        }

        foreach ($latestByBusiness as $businessId => $subscriptionId) {
            DB::table('businesses')
                ->where('business_id', $businessId)
                ->update(['current_business_subscription_id' => $subscriptionId]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('subscription_history')->where('event_type', 'backfilled')->delete();
        DB::table('subscription_payments')->whereIn('subscription_invoice_id', function ($query) {
            $query->select('subscription_invoice_id')->from('subscription_invoices')->where('invoice_no', 'like', 'SUB-INV-%');
        })->delete();
        DB::table('subscription_invoices')->where('invoice_no', 'like', 'SUB-INV-%')->delete();
        DB::table('businesses')->update(['current_business_subscription_id' => null]);
        DB::table('business_subscriptions')->update(['billing_cycle' => null]);
    }
};
