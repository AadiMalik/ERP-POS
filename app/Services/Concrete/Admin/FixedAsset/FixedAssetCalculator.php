<?php

namespace App\Services\Concrete\Admin\FixedAsset;

use App\Enums\DepreciationAdjustmentModes;
use App\Enums\DepreciationFrequencies;
use App\Models\FixedAsset;
use Carbon\Carbon;

/**
 * Straight-line depreciation math for Fixed Assets.
 * Purchase cost is never mutated; book value floors at residual / min %.
 */
class FixedAssetCalculator
{
    public function floorValue(FixedAsset $asset): float
    {
        $purchaseCost = (float) $asset->purchase_cost;
        $residual = (float) $asset->residual_value;
        $minPercentFloor = round($purchaseCost * ((float) $asset->min_book_value_percent / 100), 2);

        return max($residual, $minPercentFloor, 0);
    }

    public function depreciableBase(FixedAsset $asset): float
    {
        return max(round((float) $asset->purchase_cost - $this->floorValue($asset), 2), 0);
    }

    public function basePeriodAmount(FixedAsset $asset): float
    {
        $years = max((int) $asset->useful_life_years, 1);
        $annual = $this->depreciableBase($asset) / $years;
        $periods = DepreciationFrequencies::periodsPerYear($asset->depreciation_frequency);

        return round($annual / max($periods, 1), 2);
    }

    public function periodAmount(FixedAsset $asset, int $postedCount): float
    {
        $base = $this->basePeriodAmount($asset);
        $rate = (float) $asset->depreciation_adjustment_rate;
        $mode = $asset->depreciation_adjustment_mode ?: DepreciationAdjustmentModes::NONE;

        if ($mode === DepreciationAdjustmentModes::INCREASE && $rate > 0) {
            return round($base * pow(1 + ($rate / 100), $postedCount), 2);
        }

        if ($mode === DepreciationAdjustmentModes::DECREASE && $rate > 0) {
            $factor = pow(1 - ($rate / 100), $postedCount);
            return round(max($base * $factor, 0), 2);
        }

        return $base;
    }

    public function remainingDepreciable(FixedAsset $asset): float
    {
        return max(round((float) $asset->current_book_value - $this->floorValue($asset), 2), 0);
    }

    /**
     * @return array{amount: float, previous: float, new: float, accumulated: float, fully_depreciated: bool}|null
     */
    public function computeDepreciation(FixedAsset $asset, int $postedCount): ?array
    {
        $remaining = $this->remainingDepreciable($asset);
        if ($remaining <= 0) {
            return null;
        }

        $amount = $this->periodAmount($asset, $postedCount);
        if ($amount <= 0) {
            return null;
        }

        $amount = min($amount, $remaining);
        $previous = (float) $asset->current_book_value;
        $new = round($previous - $amount, 2);
        $accumulated = round((float) $asset->accumulated_depreciation + $amount, 2);
        $fully = $new <= $this->floorValue($asset) + 0.00001;

        return [
            'amount' => $amount,
            'previous' => $previous,
            'new' => max($new, $this->floorValue($asset)),
            'accumulated' => $accumulated,
            'fully_depreciated' => $fully,
        ];
    }

    public function periodKey(FixedAsset $asset, Carbon $date): string
    {
        return match ($asset->depreciation_frequency) {
            DepreciationFrequencies::DAILY => $date->format('Y-m-d'),
            DepreciationFrequencies::WEEKLY => $date->format('o-\WW'),
            DepreciationFrequencies::MONTHLY => $date->format('Y-m'),
            DepreciationFrequencies::YEARLY => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    public function nextDepreciationDate(FixedAsset $asset, ?Carbon $from = null): Carbon
    {
        $from = ($from ?: Carbon::parse($asset->next_depreciation_date ?: $asset->purchase_date))->copy()->startOfDay();

        return match ($asset->depreciation_frequency) {
            DepreciationFrequencies::DAILY => $from->copy()->addDay(),
            DepreciationFrequencies::WEEKLY => $from->copy()->addWeek(),
            DepreciationFrequencies::MONTHLY => $from->copy()->addMonthNoOverflow(),
            DepreciationFrequencies::YEARLY => $from->copy()->addYear(),
            default => $from->copy()->addMonthNoOverflow(),
        };
    }

    public function firstDepreciationDate(FixedAsset $asset): Carbon
    {
        $purchase = Carbon::parse($asset->purchase_date)->startOfDay();

        return match ($asset->depreciation_frequency) {
            DepreciationFrequencies::DAILY => $purchase->copy()->addDay(),
            DepreciationFrequencies::WEEKLY => $purchase->copy()->addWeek(),
            DepreciationFrequencies::MONTHLY => $purchase->copy()->addMonthNoOverflow(),
            DepreciationFrequencies::YEARLY => $purchase->copy()->addYear(),
            default => $purchase->copy()->addMonthNoOverflow(),
        };
    }

    public function resolveResidualValue(float $purchaseCost, ?float $residualValue, ?float $residualPercent): float
    {
        if ($residualValue !== null && $residualValue > 0) {
            return round($residualValue, 2);
        }

        if ($residualPercent !== null && $residualPercent > 0) {
            return round($purchaseCost * ($residualPercent / 100), 2);
        }

        return 0;
    }
}
