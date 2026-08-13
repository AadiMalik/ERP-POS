<?php

namespace App\Policies;

use App\Enums\RoleNames;
use App\Models\BusinessSubscription;
use App\Models\User;

class BusinessSubscriptionPolicy
{
    public function view(User $user, BusinessSubscription $subscription): bool
    {
        return getRoleName() == RoleNames::SUPERADMIN || $subscription->business_id === $user->business_id;
    }

    public function manage(User $user, BusinessSubscription $subscription): bool
    {
        return getRoleName() == RoleNames::SUPERADMIN;
    }
}
