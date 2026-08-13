<?php

use App\Enums\RoleNames;
use App\Models\Branch;
use App\Models\Category;
use App\Models\GoodReceiptNote;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\OpeningStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestQuotation;
use App\Models\StockTaking;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\TransferNote;
use App\Models\User;
use App\Models\Warehouse;
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

function localDateTime($date)
{
    if (blank($date)) {
        return $date;
    }
    $setting = session('business_setting');
    $date_format = $setting['date_format'] ?? 'd-m-Y';
    $time_format = $setting['time_format'] ?? 'H:i:s';
    $timezone = $setting['timezone'] ?? 'UTC';

    return Carbon::parse($date, 'UTC')
        ->setTimezone($timezone)
        ->format($date_format . ' ' . $time_format);
}

function localDate($date)
{
    if (blank($date)) {
        return $date;
    }

    $setting = session('business_setting', []);

    $date_format = $setting['date_format'] ?? 'd-m-Y';
    $timezone   = $setting['timezone'] ?? 'UTC';

    return Carbon::parse($date, 'UTC')
        ->setTimezone($timezone)
        ->format($date_format);
}

function utcDateTime($datetime)
{
    if (blank($datetime)) {
        return $datetime;
    }

    $setting = session('business_setting', []);

    $timezone = $setting['timezone'] ?? 'UTC';
    $dateFormat = $setting['date_format'] ?? 'd-m-Y';
    $timeFormat = $setting['time_format'] ?? 'H:i';

    return Carbon::createFromFormat(
        $dateFormat . ' ' . $timeFormat,
        $datetime,
        $timezone
    )->utc()->format('Y-m-d H:i:s');
}

function utcDate($datetime, $fromUtc = false)
{
    if (blank($datetime)) {
        return $datetime;
    }

    $setting = session('business_setting', []);

    $timezone = $setting['timezone'] ?? 'UTC';
    $dateFormat = $setting['date_format'] ?? 'd-m-Y';
    if ($fromUtc) {
        return Carbon::parse($datetime)->format('Y-m-d');
    }
    return Carbon::createFromFormat(
        $dateFormat,
        $datetime,
        $timezone
    )->utc()->format('Y-m-d');
}

function currency($amount = 0, $showSymbol = true)
{
    $setting = session('accounting_setting', []);

    $symbol   = $setting['currency_symbol'] ?? 'Rs';
    $position = strtolower($setting['currency_position'] ?? 'left');
    $decimal  = (int) ($setting['decimal_points'] ?? 2);

    // Keep numeric value
    $amount = round((float) $amount, $decimal);

    if (!$showSymbol) {
        return $amount;
    }

    return $position === 'after'
        ? $amount . ' ' . $symbol
        : $symbol . ' ' . $amount;
}

function decimal($value = 0)
{
    $decimal = (int) (session('accounting_setting.decimal_points') ?? 2);

    return sprintf('%.' . $decimal . 'f', (float) $value);
}

function generateJVNum($journal_id)
{
    $business_id = Auth::user()->business_id;

    $journal = Journal::findOrFail($journal_id);

    $journal_entry = JournalEntry::where('business_id', $business_id)
        ->where('journal_id', $journal_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($journal_entry) {
        $next_number = (int) substr($journal_entry->entry_no, strrpos($journal_entry->entry_no, '-') + 1) + 1;
    }

    return sprintf(
        '%s-%s-%04d',
        $journal->short,
        now()->format('dmY'),
        $next_number
    );
}

function generatePRNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $purchase_request = PurchaseRequest::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($purchase_request) {
        $next_number = (int) substr($purchase_request->purchase_request_no, strrpos($purchase_request->purchase_request_no, '-') + 1) + 1;
    }

    return sprintf(
        'PR-%04d',
        $next_number
    );
}

function generateQuotationNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $purchase_request_quaotation = PurchaseRequestQuotation::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($purchase_request_quaotation) {
        $next_number = (int) substr($purchase_request_quaotation->purchase_request_quotation_no, strrpos($purchase_request_quaotation->purchase_request_quotation_no, '-') + 1) + 1;
    }
    return sprintf(
        'PRQ-%04d',
        $next_number
    );
}

function generatePONo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $purchase = Purchase::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($purchase) {
        $next_number = (int) substr($purchase->purchase_no, strrpos($purchase->purchase_no, '-') + 1) + 1;
    }

    return sprintf(
        'PO-%04d',
        $next_number
    );
}

function generateGRNNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $grn = GoodReceiptNote::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($grn) {
        $next_number = (int) substr($grn->good_receipt_note_no, strrpos($grn->good_receipt_note_no, '-') + 1) + 1;
    }

    return sprintf(
        'GRN-%04d',
        $next_number
    );
}

function generatePurchaseReturnNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $purchase_return = PurchaseReturn::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($purchase_return) {
        $next_number = (int) substr($purchase_return->purchase_return_no, strrpos($purchase_return->purchase_return_no, '-') + 1) + 1;
    }

    return sprintf(
        'PRTN-%04d',
        $next_number
    );
}

function generateOpeningStockNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $opening_stock = OpeningStock::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($opening_stock) {
        $next_number = (int) substr($opening_stock->opening_stock_no, strrpos($opening_stock->opening_stock_no, '-') + 1) + 1;
    }

    return sprintf(
        'OS-%04d',
        $next_number
    );
}

function generateStockTakingNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $stock_taking = StockTaking::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($stock_taking) {
        $next_number = (int) substr($stock_taking->stock_taking_no, strrpos($stock_taking->stock_taking_no, '-') + 1) + 1;
    }

    return sprintf(
        'STK-%04d',
        $next_number
    );
}

function generateTransferNoteNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $transfer_note = TransferNote::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($transfer_note) {
        $next_number = (int) substr($transfer_note->transfer_note_no, strrpos($transfer_note->transfer_note_no, '-') + 1) + 1;
    }

    return sprintf(
        'TRF-%04d',
        $next_number
    );
}

function generateSupplierPaymentNo($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $supplier_payment = SupplierPayment::where('business_id', $business_id)
        ->where('is_deleted', 0)
        ->latest('date_created')
        ->first();

    $next_number = 1;

    if ($supplier_payment) {
        $next_number = (int) substr($supplier_payment->payment_no, strrpos($supplier_payment->payment_no, '-') + 1) + 1;
    }

    return sprintf(
        'SP-%04d',
        $next_number
    );
}

function generateSupplierCode($business_id = null)
{
    $business_id = $business_id ?? Auth::user()->business_id;

    $prefix = session('supplier_setting.supplier_code_prefix') ?? 'SUP-';

    $last_supplier = Supplier::where('business_id', $business_id)
        ->where('code', 'like', $prefix . '%')
        ->orderByDesc('supplier_id')
        ->first();

    $next_number = 1;

    if ($last_supplier && $last_supplier->code) {
        $number = str_replace($prefix, '', $last_supplier->code);
        $next_number = (int) $number + 1;
    }

    return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
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

/**
 * Compatibility wrapper - the real logic now lives in FeatureLimitService,
 * which centralizes every subscription limit/module check in one place
 * instead of duplicating it across module services. Kept as a free function
 * with the same signature/return shape so none of the existing call sites
 * (BranchService, CategoryService, ProductService, SubCategoryService,
 * SupplierService, WarehouseService) need to change.
 */
function checkPackageLimit($type)
{
    return app(\App\Services\Concrete\Admin\FeatureLimitService::class)->check($type);
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
