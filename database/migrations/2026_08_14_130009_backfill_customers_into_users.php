<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        $roleId = $this->ensureCustomerRole();

        $customers = DB::table('customers')->get();

        foreach ($customers as $customer) {
            $email = trim((string) $customer->email);

            if ($email === '') {
                $email = 'legacy+' . $customer->customer_id . '@placeholder.local';
            }

            $user = DB::table('users')->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            if ($user) {
                $userId = $user->id;
            } else {
                $userId = DB::table('users')->insertGetId([
                    'name' => $customer->name ?: $email,
                    'email' => $email,
                    'phone' => $customer->phone,
                    'password' => null,
                    'status' => trim((string) $customer->email) === '' ? 'inactive' : ($customer->status ?: 'active'),
                    'email_verified_at' => null,
                    'is_deleted' => $customer->is_deleted,
                    'createdby_id' => $customer->createdby_id,
                    'updatedby_id' => $customer->updatedby_id,
                    'deletedby_id' => $customer->deletedby_id,
                    'date_created' => $customer->date_created,
                    'date_updated' => $customer->date_updated,
                    'date_deleted' => $customer->date_deleted,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $hasRole = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $userId)
                ->exists();

            if (!$hasRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }

            DB::table('customer_profiles')->insert([
                'customer_profile_id' => Str::uuid()->toString(),
                'user_id' => $userId,
                'business_id' => $customer->business_id,
                'branch_id' => $customer->branch_id,
                'account_id' => $customer->account_id,
                'legacy_customer_id' => $customer->customer_id,
                'code' => $customer->code,
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'address' => $customer->address,
                'city' => $customer->city,
                'state' => $customer->state,
                'country' => $customer->country,
                'credit_limit' => $customer->credit_limit,
                'credit_days' => $customer->credit_days,
                'is_walkin' => $customer->is_walkin,
                'loyalty_points' => 0,
                'status' => $customer->status ?: 'active',
                'is_deleted' => $customer->is_deleted,
                'createdby_id' => $customer->createdby_id,
                'updatedby_id' => $customer->updatedby_id,
                'deletedby_id' => $customer->deletedby_id,
                'date_created' => $customer->date_created,
                'date_updated' => $customer->date_updated,
                'date_deleted' => $customer->date_deleted,
            ]);
        }

        // Repoint every table that referenced customers.customer_id at the
        // matching users.id via the legacy_customer_id trail just recorded.
        DB::statement('
            UPDATE orders o
            JOIN customer_profiles cp ON cp.legacy_customer_id = o.customer_id
            SET o.user_id = cp.user_id
            WHERE o.customer_id IS NOT NULL
        ');

        DB::statement('
            UPDATE voucher_customers vc
            JOIN customer_profiles cp ON cp.legacy_customer_id = vc.customer_id
            SET vc.user_id = cp.user_id
            WHERE vc.customer_id IS NOT NULL
        ');

        DB::statement('
            UPDATE voucher_redemptions vr
            JOIN customer_profiles cp ON cp.legacy_customer_id = vr.customer_id
            SET vr.user_id = cp.user_id
            WHERE vr.customer_id IS NOT NULL
        ');

        DB::statement('
            UPDATE journal_entry_details jed
            JOIN customer_profiles cp ON cp.legacy_customer_id = jed.customer_id
            SET jed.user_id = cp.user_id
            WHERE jed.customer_id IS NOT NULL
        ');

        DB::statement('
            UPDATE pos_settings ps
            JOIN customer_profiles cp ON cp.legacy_customer_id = ps.default_customer_id
            SET ps.default_customer_user_id = cp.user_id
            WHERE ps.default_customer_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Data backfill - not reversible. The drop-customers-table migration's
        // down() recreates the customers table structure but not this data.
    }

    private function ensureCustomerRole(): int
    {
        $role = DB::table('roles')
            ->where('name', 'User')
            ->where('guard_name', 'web')
            ->whereNull('business_id')
            ->first();

        if ($role) {
            return $role->id;
        }

        return DB::table('roles')->insertGetId([
            'name' => 'User',
            'guard_name' => 'web',
            'business_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
