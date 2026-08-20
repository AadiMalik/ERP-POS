<?php

namespace App\Services\Concrete\Admin;

use App\Models\AccountingPeriod;
use App\Models\AccountingSetting;
use App\Models\Business;
use App\Models\FiscalYear;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Mode Fiscal Year CRUD, plus the two pieces of automatic
 * bootstrapping that make the Simple-mode "Monthly/Yearly" setting work
 * without an accountant ever touching this screen:
 *  - ensureCurrentFiscalYear(): finds or creates the fiscal year covering
 *    today, based on accounting_settings.fiscal_year_start_month.
 *  - ensureFuturePeriods(): tops up 'upcoming' AccountingPeriod rows far
 *    enough ahead that AccountingPeriodService::openNext() always has a next
 *    period to roll into.
 */
class FiscalYearService
{
    use Auditable;

    protected $model_fiscal_year;

    public function __construct()
    {
        $this->model_fiscal_year = new Repository(new FiscalYear());
    }

    public function getData($obj)
    {
        $wh = [];

        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }

        return $this->model_fiscal_year->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderByDesc('start_date')
            ->get();
    }

    public function getById($fiscal_year_id)
    {
        return $this->model_fiscal_year->getModel()::with('accountingPeriods')->findOrFail($fiscal_year_id);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            if (!empty($obj['fiscal_year_id'])) {
                $fiscal_year = $this->model_fiscal_year->getModel()::findOrFail($obj['fiscal_year_id']);

                $fiscal_year->update([
                    'name'         => $obj['name'],
                    'start_date'   => $obj['start_date'],
                    'end_date'     => $obj['end_date'],
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]);

                $this->logActivity('fiscal-year', $fiscal_year->fiscal_year_id, 'updated', null, $obj, null, $fiscal_year->business_id);
            } else {
                $fiscal_year = $this->model_fiscal_year->create([
                    'fiscal_year_id' => generateUuid(),
                    'business_id'    => $obj['business_id'],
                    'name'           => $obj['name'],
                    'start_date'     => $obj['start_date'],
                    'end_date'       => $obj['end_date'],
                    'status'         => 'upcoming',
                    'is_current'     => false,
                    'createdby_id'   => Auth::id(),
                    'date_created'   => now(),
                ]);

                $this->logActivity('fiscal-year', $fiscal_year->fiscal_year_id, 'created', null, $obj, null, $fiscal_year->business_id);
            }

            DB::commit();

            return $fiscal_year;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function delete($fiscal_year_id)
    {
        $result = $this->model_fiscal_year->update([
            'is_deleted'   => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $fiscal_year_id);

        $this->logActivity('fiscal-year', $fiscal_year_id, 'deleted');

        return $result;
    }

    /**
     * Finds the fiscal year covering today for $business, promoting it to
     * is_current if it isn't already; creates one (based on
     * fiscal_year_start_month) if none exists at all.
     */
    public function ensureCurrentFiscalYear(Business $business): FiscalYear
    {
        $today = today();

        $covering = FiscalYear::where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if ($covering) {
            if (!$covering->is_current) {
                FiscalYear::where('business_id', $business->business_id)->where('is_current', 1)->update(['is_current' => 0]);
                $covering->update(['is_current' => 1, 'status' => 'open']);
            }

            return $covering;
        }

        $setting = AccountingSetting::where('business_id', $business->business_id)->first();
        $start_month = $setting->fiscal_year_start_month ?? 1;

        $year = $today->month >= $start_month ? $today->year : $today->year - 1;
        $start_date = Carbon::create($year, $start_month, 1)->startOfDay();
        $end_date = $start_date->copy()->addYear()->subDay()->endOfDay();

        FiscalYear::where('business_id', $business->business_id)->where('is_current', 1)->update(['is_current' => 0]);

        return FiscalYear::firstOrCreate(
            [
                'business_id' => $business->business_id,
                'start_date'  => $start_date->toDateString(),
                'end_date'    => $end_date->toDateString(),
            ],
            [
                'fiscal_year_id' => generateUuid(),
                'name'           => 'FY ' . $start_date->format('Y') . '-' . $end_date->format('y'),
                'status'         => 'open',
                'is_current'     => true,
                'date_created'   => now(),
            ]
        );
    }

    /**
     * Creates the fiscal year immediately following $fiscal_year (same
     * length), status 'upcoming' - it is only promoted to current later, by
     * ensureCurrentFiscalYear(), once today's date actually enters it.
     */
    protected function createNextFiscalYear(FiscalYear $fiscal_year): FiscalYear
    {
        $start_date = Carbon::parse($fiscal_year->end_date)->addDay()->startOfDay();
        $end_date = Carbon::parse($fiscal_year->start_date)->addYear()->subDay()->endOfDay();
        // If the source FY wasn't exactly a year (shouldn't normally happen),
        // fall back to a straightforward one-year span from start_date.
        if ($end_date->lte($start_date)) {
            $end_date = $start_date->copy()->addYear()->subDay()->endOfDay();
        }

        return FiscalYear::firstOrCreate(
            [
                'business_id' => $fiscal_year->business_id,
                'start_date'  => $start_date->toDateString(),
                'end_date'    => $end_date->toDateString(),
            ],
            [
                'fiscal_year_id' => generateUuid(),
                'name'           => 'FY ' . $start_date->format('Y') . '-' . $end_date->format('y'),
                'status'         => 'upcoming',
                'is_current'     => false,
                'date_created'   => now(),
            ]
        );
    }

    /**
     * Tops up 'upcoming' AccountingPeriod rows for $business per its
     * configured cadence, far enough ahead ($monthsAhead) that the scheduler
     * always has a next period to roll into. No-op if accounting is
     * disabled or period_closing_mode = 'manual' (manual-cadence businesses
     * only get periods an accountant creates by hand in Advanced Mode).
     */
    public function ensureFuturePeriods(Business $business, int $monthsAhead = 3): void
    {
        $setting = AccountingSetting::where('business_id', $business->business_id)->first();

        if (!$setting || !$setting->enable_accounting || $setting->period_closing_mode === 'manual') {
            return;
        }

        $cadence = $setting->period_closing_mode; // monthly | yearly
        $fiscal_year = $this->ensureCurrentFiscalYear($business);
        $horizon = today()->addMonths($monthsAhead);

        $last_period = AccountingPeriod::where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->orderByDesc('end_date')
            ->first();

        // On the very first bootstrap for a business (no periods at all yet),
        // start at the CURRENT period, not the fiscal year's start - a
        // business that turns on monthly closing mid-year must never get
        // months already in the past silently backfilled as 'upcoming'
        // periods that nothing will ever auto-process. Yearly cadence's
        // "current period" already IS the fiscal year, so that one starts at
        // the fiscal year's own start_date.
        if ($last_period) {
            $cursor = Carbon::parse($last_period->end_date)->addDay()->startOfDay();
        } elseif ($cadence === 'monthly') {
            $cursor = today()->startOfMonth();
        } else {
            $cursor = Carbon::parse($fiscal_year->start_date)->startOfDay();
        }

        $guard = 0; // hard stop against any unforeseen infinite-loop condition

        while ($cursor->lte($horizon) && $guard < 60) {
            $guard++;

            if ($cursor->gt(Carbon::parse($fiscal_year->end_date))) {
                $fiscal_year = $this->createNextFiscalYear($fiscal_year);
            }

            if ($cadence === 'monthly') {
                $period_start = $cursor->copy()->startOfMonth();
                $period_end = $cursor->copy()->endOfMonth();
                $name = $period_start->format('F Y');
            } else {
                $period_start = Carbon::parse($fiscal_year->start_date);
                $period_end = Carbon::parse($fiscal_year->end_date);
                $name = $fiscal_year->name;
            }

            if ($period_end->gt(Carbon::parse($fiscal_year->end_date))) {
                $period_end = Carbon::parse($fiscal_year->end_date)->copy();
            }

            AccountingPeriod::firstOrCreate(
                [
                    'business_id' => $business->business_id,
                    'start_date'  => $period_start->toDateString(),
                    'end_date'    => $period_end->toDateString(),
                ],
                [
                    'accounting_period_id' => generateUuid(),
                    'fiscal_year_id'       => $fiscal_year->fiscal_year_id,
                    'name'                 => $name,
                    'cadence'              => $cadence,
                    'status'               => 'upcoming',
                    'date_created'         => now(),
                ]
            );

            $cursor = $period_end->copy()->addDay()->startOfDay();
        }

        $this->openEarliestIfNoneOpen($business);
    }

    /**
     * Bootstraps the very first 'open' period for a business that has just
     * turned on automatic period closing and has no open period yet.
     */
    protected function openEarliestIfNoneOpen(Business $business): void
    {
        $has_open = AccountingPeriod::where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->where('status', 'open')
            ->exists();

        if ($has_open) {
            return;
        }

        // Prefer the period that actually contains today; fall back to the
        // most recent eligible one if there's a gap (e.g. a manually
        // created period range that doesn't line up with today).
        $current = AccountingPeriod::where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->where('status', 'upcoming')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderBy('start_date')
            ->first();

        $current ??= AccountingPeriod::where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->where('status', 'upcoming')
            ->whereDate('start_date', '<=', today())
            ->orderByDesc('start_date')
            ->first();

        if ($current) {
            $current->update([
                'status'       => 'open',
                'opened_at'    => now(),
                'opened_by_id' => Auth::id(),
                'date_updated' => now(),
            ]);
        }
    }
}
