<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions reserved for the super admin.
     *
     * Role and permission lifecycle, plus the ability to manage another
     * user's two-factor authentication, are deliberately kept away from
     * regular administrators.
     *
     * @return list<string>
     */
    private function superAdminOnlyPermissions(): array
    {
        return array_values(collect(PermissionEnum::cases())
            ->filter(fn (PermissionEnum $permission): bool => in_array($permission->group(), ['Roles', 'Permissions'], true))
            ->filter(fn (PermissionEnum $permission): bool => ! Str::endsWith($permission->value, '.view'))
            ->map(fn (PermissionEnum $permission): string => $permission->value)
            ->merge([
                PermissionEnum::UserTwoFactorManage->value,
            ])
            ->unique()
            ->values()
            ->all());
    }

    /**
     * Read-only permissions for regular users.
     *
     * @return list<string>
     */
    private function defaultUserPermissions(): array
    {
        return [
            PermissionEnum::ProductsView->value,
            PermissionEnum::CategoriesView->value,
            PermissionEnum::OrdersView->value,
        ];
    }

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();

        $guard = (string) config('auth.defaults.guard', 'web');

        // Create all application permissions before assigning them.
        $this->ensurePermissionsExist($guard);

        // Remove permission records that no longer exist in the enum; roles are re-synced below.
        $this->deleteRetiredPermissions($guard);

        // Repair the legacy role name.
        $this->renameLegacyUserRole($guard);

        // Move obsolete-guard roles when no target duplicate exists.
        $this->migrateStaleGuardRoles($guard);

        $permissions = $this->permissionNames($guard);

        // Super admin gets every registered permission.
        $this->syncRole('super_admin', $permissions, $guard);

        // Admin cannot manage role/permission lifecycle.
        $this->syncRole(
            'admin',
            array_values(array_diff(
                $permissions,
                $this->superAdminOnlyPermissions(),
            )),
            $guard,
        );

        // Regular users get read-only product access.
        $this->syncRole(
            'user',
            array_values(array_intersect(
                $permissions,
                $this->defaultUserPermissions(),
            )),
            $guard,
        );

        // Assign the default role to users who have no roles.
        $this->assignDefaultRoleToUnassignedUsers();

        $registrar->forgetCachedPermissions();
    }

    /**
     * Create permissions defined in the application enum.
     */
    private function ensurePermissionsExist(string $guard): void
    {
        foreach (PermissionEnum::values() as $permission) {
            Permission::findOrCreate((string) $permission, $guard);
        }
    }

    /**
     * Retired permission names (old CRUD abilities, plus widget/two-factor permissions no code checks); custom non-enum permissions are never touched.
     *
     * @return array<int, string>
     */
    private function retiredPermissionNames(): array
    {
        $resources = ['products', 'categories', 'orders', 'emailTemplates', 'users', 'roles', 'permissions'];
        $abilities = ['viewAny', 'deleteAny', 'restore', 'restoreAny', 'forceDelete', 'forceDeleteAny', 'replicate', 'reorder'];

        return collect($resources)
            ->crossJoin($abilities)
            ->map(fn (array $pair): string => "{$pair[0]}.{$pair[1]}")
            ->merge(['widgets.productStats', 'userTwoFactor.view'])
            ->values()
            ->all();
    }

    /** Delete retired permission records; abilities are squashed onto the four CRUD permissions in AuthorizesWithPermissions. */
    private function deleteRetiredPermissions(string $guard): void
    {
        Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $this->retiredPermissionNames())
            ->get()
            ->each(fn (Permission $permission) => $permission->delete());
    }

    /**
     * Create a role and synchronize only existing permission records.
     *
     * @param  list<string>  $permissions
     */
    private function syncRole(
        string $name,
        array $permissions,
        string $guard,
    ): void {
        $role = Role::findOrCreate($name, $guard);

        $validPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $permissions)
            ->get();

        $role->syncPermissions($validPermissions);
    }

    /**
     * Return permission names for the configured guard.
     *
     * @return list<string>
     */
    private function permissionNames(string $guard): array
    {
        return array_values(Permission::query()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->map(static fn ($name): string => (string) $name)
            ->values()
            ->all());
    }

    /**
     * Rename legacy User role without losing assignments.
     */
    private function renameLegacyUserRole(string $guard): void
    {
        $roles = Role::query()
            ->where('guard_name', $guard)
            ->get();

        $legacy = $roles->first(
            static fn (Role $role): bool => $role->name === 'User',
        );

        if (! $legacy instanceof Role) {
            return;
        }

        $existing = $roles->first(
            static fn (Role $role): bool => $role->name === 'user',
        );

        if ($existing instanceof Role) {
            User::query()
                ->role($legacy)
                ->each(static function (User $user) use (
                    $legacy,
                    $existing,
                ): void {
                    $user->removeRole($legacy);
                    $user->assignRole($existing);
                });

            $legacy->delete();

            return;
        }

        $legacy->update(['name' => 'user']);
    }

    /**
     * Repair obsolete role guard names.
     */
    private function migrateStaleGuardRoles(string $guard): void
    {
        $staleRoles = Role::query()
            ->where('guard_name', '!=', $guard)
            ->get();

        foreach ($staleRoles as $role) {
            $duplicateExists = Role::query()
                ->where('name', $role->name)
                ->where('guard_name', $guard)
                ->exists();

            if ($duplicateExists) {
                continue;
            }

            $role->update(['guard_name' => $guard]);
        }
    }

    /**
     * Assign the user role to users without any role.
     */
    private function assignDefaultRoleToUnassignedUsers(): void
    {
        User::query()
            ->whereDoesntHave('roles')
            ->each(
                static fn (User $user) => $user->assignRole('user'),
            );
    }
}
