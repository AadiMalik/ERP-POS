<?php

namespace App\Console\Commands;

use App\Enums\RoleNames;
use App\Models\AccountingSetting;
use App\Models\Business;
use App\Models\User;
use App\Services\Concrete\Admin\FixedAssetService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily fixed-asset depreciation runner (00:15 server time).
 * Idempotent per asset + period_key / JV source_type+source_id.
 */
class ProcessFixedAssetDepreciationCommand extends Command
{
    protected $signature = 'fixed-assets:post-depreciation {--dry-run} {--business=} {--date=}';

    protected $description = 'Posts due fixed-asset depreciation (straight-line) and related JVs. Safe to re-run.';

    public function __construct(protected FixedAssetService $fixed_asset_service)
    {
        parent::__construct();
    }

    public function handle()
    {
        $dry_run = (bool) $this->option('dry-run');
        $only_business_id = $this->option('business');
        $asOf = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::today()->startOfDay();

        $businesses = Business::whereHas('accountingSetting', function ($q) {
                $q->where('enable_accounting', 1);
            })
            ->when($only_business_id, fn ($q) => $q->where('business_id', $only_business_id))
            ->get();

        $this->info("Fixed asset depreciation for {$asOf->toDateString()} — {$businesses->count()} business(es).");

        $total = ['processed' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($businesses as $business) {
            try {
                $setting = AccountingSetting::where('business_id', $business->business_id)->first();
                if (!$setting
                    || empty($setting->default_fixed_asset_account_id)
                    || empty($setting->default_accumulated_depreciation_account_id)
                    || empty($setting->default_depreciation_expense_account_id)
                ) {
                    $this->line("[{$business->name}] skipped — Fixed Asset COA mappings not configured.");
                    continue;
                }

                if ($dry_run) {
                    $due = \App\Models\FixedAsset::where('business_id', $business->business_id)
                        ->where('is_deleted', 0)
                        ->where('depreciation_status', \App\Enums\FixedAssetStatuses::ACTIVE)
                        ->whereDate('next_depreciation_date', '<=', $asOf->toDateString())
                        ->count();
                    $this->line("[{$business->name}] would process {$due} due asset(s).");
                    continue;
                }

                $actor_id = $this->resolveActorUserId($business);
                if ($actor_id) {
                    Auth::onceUsingId($actor_id);
                }

                $stats = $this->fixed_asset_service->processDueDepreciations($asOf, $business->business_id);
                $total['processed'] += $stats['processed'];
                $total['skipped'] += $stats['skipped'];
                $total['errors'] += $stats['errors'];

                $this->line("[{$business->name}] processed={$stats['processed']} skipped={$stats['skipped']} errors={$stats['errors']}");
            } catch (Throwable $e) {
                Log::error('fixed-assets:post-depreciation failed for ' . $business->business_id . ': ' . $e->getMessage());
                $this->error("[{$business->name}] failed: " . $e->getMessage());
                $total['errors']++;
            }
        }

        $this->info("Done. processed={$total['processed']} skipped={$total['skipped']} errors={$total['errors']}");

        return $total['errors'] > 0 ? 1 : 0;
    }

    protected function resolveActorUserId(Business $business): ?int
    {
        return User::role(RoleNames::BUSINESSADMIN)
            ->where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->value('id')
            ?? User::role(RoleNames::ACCOUNTANT)
                ->where('business_id', $business->business_id)
                ->where('is_deleted', 0)
                ->value('id');
    }
}
