<?php

use App\Enums\RoleNames;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

function generateUuid()
{
    return Str::uuid()->toString();
}

function getRoleName()
{
    return Auth::user()?->getRoleNames()->first();
}

function applyRoleScope(
    Builder $query,
    array $allowed_roles = [],
    string $business_column = 'business_id',
    string $branch_column = 'branch_id'
) {

    $user = Auth::user();
    $role = getRoleName();
    if ($role == RoleNames::SUPERADMIN) {
        return $query; // no filters applied
    }
    /*
        |--------------------------------------------------------------------------
        | Role Validation
        |--------------------------------------------------------------------------
        */

    if (!empty($allowed_roles) && !in_array($role, $allowed_roles)) {

        abort(403, 'Unauthorized access.');
    }

    /*
        |--------------------------------------------------------------------------
        | Business Level Roles
        |--------------------------------------------------------------------------
        */

    $business_roles = [
        RoleNames::BUSINESSADMIN,
        RoleNames::GENERALMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::HRMANAGER,
        RoleNames::REPORTINGANALYST,
        RoleNames::MARKITINGMANAGER,
        RoleNames::INVENTORYMANAGER,
        RoleNames::PURCHASEMANAGER,
    ];

    /*
        |--------------------------------------------------------------------------
        | Branch Level Roles
        |--------------------------------------------------------------------------
        */

    $branch_roles = [
        RoleNames::BRANCHADMIN,
        RoleNames::POSMANAGER,
        RoleNames::ORDERTAKER,
        RoleNames::STAFF,
    ];

    /*
        |--------------------------------------------------------------------------
        | Mixed Roles
        |--------------------------------------------------------------------------
        */

    $mixed_roles = [
        RoleNames::SALEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::OPERATIONMANAGER,
    ];

    /*
        |--------------------------------------------------------------------------
        | Apply Filters
        |--------------------------------------------------------------------------
        */

    // Business Level Access
    if (in_array($role, $business_roles)) {

        $query->where($business_column, $user->business_id);
    }

    // Branch Level Access
    elseif (in_array($role, $branch_roles)) {

        $query->where($business_column, $user->business_id)
            ->where($branch_column, $user->branch_id);
    }

    // Mixed Access
    elseif (in_array($role, $mixed_roles)) {

        $query->where($business_column, $user->business_id);

        if (!empty($user->branch_id)) {

            $query->where($branch_column, $user->branch_id);
        }
    }

    return $query;
}

function checkPackageLimit($type, $count = null, $returnMessage = false)
{
    try {

        $user = Auth::user();

        // Super Admin Skip
        if (getRoleName() == RoleNames::SUPERADMIN) {

            return $returnMessage
                ? [
                    'status' => true,
                    'message' => 'Super admin bypass'
                ]
                : true;
        }

        if (!$user->business) {

            return $returnMessage
                ? [
                    'status' => false,
                    'message' => 'Business not found'
                ]
                : false;
        }

        $business = $user->business;

        if (!$business->package) {

            return $returnMessage
                ? [
                    'status' => false,
                    'message' => 'Package not found'
                ]
                : false;
        }

        $package = $business->package;

        // Allowed fields
        $allowedLimits = [

            'branches'          => 'max_branches',
            'users'             => 'max_users',
            'customers'         => 'max_customers',
            'warehouses'        => 'max_warehouses',
            'categories'        => 'max_categories',
            'products'          => 'max_products',
            'suppliers'         => 'max_suppliers',
            'purchase_orders'   => 'max_purchase_orders',
            'purchases'         => 'max_purchases',
            'sales'             => 'max_sales',
            'transfers'         => 'max_transfers',
            'expenses'          => 'max_expenses',
            'vouchers'          => 'max_vouchers',

        ];

        // Invalid type
        if (!isset($allowedLimits[$type])) {

            return $returnMessage
                ? [
                    'status' => false,
                    'message' => 'Invalid limit type'
                ]
                : false;
        }

        $column = $allowedLimits[$type];

        $limit = (int) $package->$column;

        // Unlimited
        if ($limit == -1) {

            return $returnMessage
                ? [
                    'status' => true,
                    'message' => 'Unlimited access'
                ]
                : true;
        }

        // If count not passed
        if ($count === null) {

            return $returnMessage
                ? [
                    'status' => false,
                    'message' => 'Count is required'
                ]
                : false;
        }

        // Limit exceeded
        if ($count >= $limit) {

            return $returnMessage
                ? [
                    'status' => false,
                    'message' => ucfirst($type) . ' limit exceeded'
                ]
                : false;
        }

        return $returnMessage
            ? [
                'status' => true,
                'message' => ucfirst($type) . ' limit available'
            ]
            : true;
    } catch (\Exception $e) {

        return $returnMessage
            ? [
                'status' => false,
                'message' => $e->getMessage()
            ]
            : false;
    }
}

function numberToWord($num = '')
{
    $num    = (string) ((int) $num);

    if (
        (int) ($num) && ctype_digit($num)
    ) {
        $words  = array();

        $num    = str_replace(
            array(',', ' '),
            '',
            trim($num)
        );

        $list1  = array(
            '',
            'one',
            'two',
            'three',
            'four',
            'five',
            'six',
            'seven',
            'eight',
            'nine',
            'ten',
            'eleven',
            'twelve',
            'thirteen',
            'fourteen',
            'fifteen',
            'sixteen',
            'seventeen',
            'eighteen',
            'nineteen'
        );

        $list2  = array(
            '',
            'ten',
            'twenty',
            'thirty',
            'forty',
            'fifty',
            'sixty',
            'seventy',
            'eighty',
            'ninety',
            'hundred'
        );

        $list3  = array(
            '',
            'thousand',
            'million',
            'billion',
            'trillion',
            'quadrillion',
            'quintillion',
            'sextillion',
            'septillion',
            'octillion',
            'nonillion',
            'decillion',
            'undecillion',
            'duodecillion',
            'tredecillion',
            'quattuordecillion',
            'quindecillion',
            'sexdecillion',
            'septendecillion',
            'octodecillion',
            'novemdecillion',
            'vigintillion'
        );

        $num_length = strlen($num);
        $levels = (int) (($num_length + 2) / 3);
        $max_length = $levels * 3;
        $num    = substr('00' . $num, -$max_length);
        $num_levels = str_split($num, 3);

        foreach ($num_levels as $num_part) {
            $levels--;
            $hundreds   = (int) ($num_part / 100);
            $hundreds   = ($hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ($hundreds == 1 ? '' : 's') . ' ' : '');
            $tens       = (int) ($num_part % 100);
            $singles    = '';

            if (
                $tens < 20
            ) {
                $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
            } else {
                $tens = (int) ($tens / 10);
                $tens = ' ' . $list2[$tens] . ' ';
                $singles = (int) ($num_part % 10);
                $singles = ' ' . $list1[$singles] . ' ';
            }
            $words[] = $hundreds . $tens . $singles . (($levels && (int) ($num_part)) ? ' ' . $list3[$levels] . ' ' : '');
        }
        $commas = count($words);
        if ($commas > 1) {
            $commas = $commas - 1;
        }

        $words  = implode(', ', $words);

        $words  = trim(str_replace(
            ' ,',
            ',',
            ucwords($words)
        ), ', ');
        if ($commas) {
            $words  = str_replace(
                ',',
                ' and',
                $words
            );
        }

        return $words;
    } else if (!((int) $num)) {
        return 'Zero';
    }
    return '';
}
