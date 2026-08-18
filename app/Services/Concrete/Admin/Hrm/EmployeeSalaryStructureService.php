<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\EmployeeSalaryStructureItem;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeSalaryStructureService
{
    use Auditable;

    public function getCurrent($employee_id)
    {
        return EmployeeSalaryStructure::with(['items.component'])
            ->where('employee_id', $employee_id)
            ->where('status', 'active')
            ->where('is_deleted', 0)
            ->first();
    }

    public function getHistory($employee_id)
    {
        return EmployeeSalaryStructure::with(['items.component'])
            ->where('employee_id', $employee_id)
            ->where('is_deleted', 0)
            ->orderBy('effective_from', 'desc')
            ->get();
    }

    /**
     * Assigning a new structure supersedes (never deletes) the previous
     * active one - the version history IS the audit trail, no separate log
     * table needed.
     */
    public function save($obj)
    {
        $employee = Employee::findOrFail($obj['employee_id']);

        DB::beginTransaction();
        try {
            EmployeeSalaryStructure::where('employee_id', $obj['employee_id'])
                ->where('status', 'active')
                ->update([
                    'status' => 'superseded',
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]);

            $structure = EmployeeSalaryStructure::create([
                'employee_salary_structure_id' => generateUuid(),
                'employee_id' => $obj['employee_id'],
                'effective_from' => $obj['effective_from'],
                'basic_salary' => $obj['basic_salary'],
                'overtime_rate_per_hour' => $obj['overtime_rate_per_hour'] ?? null,
                'status' => 'active',
                'business_id' => $employee->business_id,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $component_ids = $obj['salary_component_id'] ?? [];
            $amounts = $obj['amount_or_percentage'] ?? [];

            foreach ($component_ids as $i => $component_id) {
                if (empty($component_id) || !isset($amounts[$i]) || $amounts[$i] === '') {
                    continue;
                }
                EmployeeSalaryStructureItem::create([
                    'employee_salary_structure_item_id' => generateUuid(),
                    'employee_salary_structure_id' => $structure->employee_salary_structure_id,
                    'salary_component_id' => $component_id,
                    'amount_or_percentage' => $amounts[$i],
                ]);
            }

            $this->logActivity('salary-structure', $structure->employee_salary_structure_id, 'created', null, ['employee_id' => $obj['employee_id'], 'basic_salary' => $obj['basic_salary']], null, $employee->business_id, $employee->branch_id);

            DB::commit();
            return $structure;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Fully resolved earning/deduction amounts for one structure (percentage
     * components resolved against basic_salary) - consumed by PayrollService.
     */
    public function resolveComponents(EmployeeSalaryStructure $structure)
    {
        $earnings = [];
        $deductions = [];

        foreach ($structure->items as $item) {
            if (!$item->component) {
                continue;
            }
            $amount = $item->component->calculation_type == 'percentage_of_basic'
                ? round($structure->basic_salary * ($item->amount_or_percentage / 100), 2)
                : (float) $item->amount_or_percentage;

            $row = ['name' => $item->component->name, 'amount' => $amount];

            if ($item->component->type == 'earning') {
                $earnings[] = $row;
            } else {
                $deductions[] = $row;
            }
        }

        return ['earnings' => $earnings, 'deductions' => $deductions];
    }
}
