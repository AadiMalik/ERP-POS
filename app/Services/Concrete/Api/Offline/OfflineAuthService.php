<?php

namespace App\Services\Concrete\Api\Offline;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\BusinessSetting;
use App\Models\InventorySetting;
use App\Models\PosDevice;
use App\Models\PosRegister;
use App\Models\PosSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CategoryService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DiscountService;
use App\Services\Concrete\Admin\ExpenseCategoryService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Services\Concrete\Admin\PosRegisterService;
use App\Services\Concrete\Admin\SaleTypeService;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use App\Services\Concrete\Admin\UserService;
use App\Services\Concrete\Admin\WarehouseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OfflineAuthService
{
  protected $business_service;
  protected $branch_service;

  public function __construct(BusinessService $business_service, BranchService $branch_service)
  {
    $this->business_service = $business_service;
    $this->branch_service = $branch_service;
  }

  /**
   * Authenticate a staff user for desktop POS. Returns Sanctum token plus
   * permissions needed for offline operation.
   */
  public function login(string $email, string $password, ?string $business_id = null): array
  {
    $user = User::where('email', $email)->where('is_deleted', 0)->first();

    if (!$user || !Hash::check($password, $user->password)) {
      throw new \Exception('Invalid email or password.');
    }

    if ($user->status !== Status::ACTIVE) {
      throw new \Exception('Your account is not active.');
    }

    if (!$user->can('pos.access')) {
      throw new \Exception('You do not have permission to access POS.');
    }

    if (!empty($business_id)) {
      app(OfflineSetupService::class)->assertUserCanAccessBusiness($user, $business_id);
    }

    $token = $user->createToken('desktop-pos', ['pos:offline'])->plainTextToken;

    return [
      'token' => $token,
      'user' => $this->formatUser($user),
      'permissions' => $this->collectPermissions($user),
      'password_hash' => $user->password,
    ];
  }

  public function formatUser(User $user): array
  {
    $role = $user->roles()->first();

    return [
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email,
      'phone' => $user->phone,
      'business_id' => $user->business_id,
      'branch_id' => $user->branch_id,
      'role_name' => $role?->name,
      'is_fixed_context' => in_array($role?->name, [RoleNames::ORDERTAKER, RoleNames::POSMANAGER], true),
    ];
  }

  public function collectPermissions(User $user): array
  {
    $keys = [
      'pos.access',
      'pos.register.close',
      'pos.register.report.view',
      'pos.register.cash-movement.manage',
      'order.create',
      'order.edit',
      'order.complete',
      'order.discount.apply',
      'order.coupon.apply',
      'order.price.change',
      'order.price.override-minimum',
      'order.hold',
      'order.cancel',
      'order.void',
      'order.reopen',
      'order.refund.process',
      'order.payment.credit',
      'order.customer.change',
      'expense.access',
    ];

    $permissions = [];
    foreach ($keys as $key) {
      $permissions[$key] = $user->can($key);
    }

    return $permissions;
  }
}
