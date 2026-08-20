<?php

namespace App\Services\ImportExport\Modules\User;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * User is the one simple module whose primary key is an auto-incrementing
 * int (not a UUID) and whose creation carries real side effects (password,
 * must_change_password, Spatie role assignment) - see UserController::store()
 * and UserService::save(). save() is therefore fully overridden instead of
 * relying on AbstractImportExportDefinition's generic UUID-PK create/update.
 */
class UserImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'user';
    }

    public function label(): string
    {
        return 'Admin Users';
    }

    public function modelClass(): string
    {
        return User::class;
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Jane Doe', 'John Smith'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Email',
                attribute: 'email',
                type: 'string',
                required: true,
                sampleValues: ['jane.doe@example.com', 'john.smith@example.com'],
                exportAccessor: 'email',
                notes: 'Must be unique across the whole system (not just this business).',
            ),
            new ColumnDefinition(
                key: 'Branch',
                attribute: 'branch_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Branch::class, 'branch', 'name', scopeToBusiness: true),
                sampleValues: ['Main Branch', ''],
                exportAccessor: fn ($m) => $m->branch->name ?? '',
                notes: 'Leave blank for a business-level (non branch-specific) user. Copy the exact Branch Name from the Branches list.',
            ),
            new ColumnDefinition(
                key: 'Role',
                attribute: 'role_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Role::class, 'role', 'name', scopeToBusiness: true, relatedLabel: 'Role'),
                sampleValues: ['Staff', 'Branch Admin'],
                exportAccessor: fn ($m) => $m->roles->first()->name ?? '',
                notes: 'Copy the exact Role Name from the Roles list. A random password is generated for new users and they are required to change it on first login.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['email'];
    }

    public function uniqueKeyIsGlobal(): bool
    {
        // Mirrors the real DB unique constraint on users.email, which is not
        // scoped by business_id.
        return true;
    }

    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $role = !empty($row->attributes['role_id']) ? Role::find($row->attributes['role_id']) : null;

        if ($row->action === 'update') {
            $model = User::findOrFail($row->matchedId);
            $model->update([
                'name' => $row->attributes['name'],
                'email' => $row->attributes['email'],
                'branch_id' => $row->attributes['branch_id'] ?? $model->branch_id,
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
            ]);

            if ($role) {
                $model->syncRoles([$role->name]);
            }

            return ['model' => $model, 'created' => false];
        }

        $model = User::create([
            'name' => $row->attributes['name'],
            'email' => $row->attributes['email'],
            'business_id' => $ctx->businessId,
            'branch_id' => $row->attributes['branch_id'] ?? null,
            'password' => Hash::make(Str::random(12)),
            'must_change_password' => true,
            'status' => 'active',
            'createdby_id' => $ctx->userId,
            'date_created' => now(),
        ]);

        if ($role) {
            $model->assignRole($role->name);
        }

        return ['model' => $model, 'created' => true];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = User::query();

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        } elseif ($ctx->businessId) {
            $query->where('business_id', $ctx->businessId);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'roles'];
    }
}
