<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/** Defines application permissions using the {resource}.{ability} convention. */
enum Permission: string
{
    // Products (view authorizes both the table and record viewing)
    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';

    // Categories
    case CategoriesView = 'categories.view';
    case CategoriesCreate = 'categories.create';
    case CategoriesUpdate = 'categories.update';
    case CategoriesDelete = 'categories.delete';

    // Orders
    case OrdersView = 'orders.view';
    case OrdersCreate = 'orders.create';
    case OrdersUpdate = 'orders.update';
    case OrdersDelete = 'orders.delete';

    // Email templates
    case EmailTemplatesView = 'emailTemplates.view';
    case EmailTemplatesCreate = 'emailTemplates.create';
    case EmailTemplatesUpdate = 'emailTemplates.update';
    case EmailTemplatesDelete = 'emailTemplates.delete';

    // Users
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    // Roles
    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';

    // Permissions
    case PermissionsView = 'permissions.view';
    case PermissionsCreate = 'permissions.create';
    case PermissionsUpdate = 'permissions.update';
    case PermissionsDelete = 'permissions.delete';

    // Dashboard widgets
    case WidgetsProductsChart = 'widgets.productsChart';

    // Application settings
    case SettingsManage = 'settings.manage';
    case SettingsView = 'settings.view';
    case SettingsUpdate = 'settings.update';

    // User two-factor authentication
    case UserTwoFactorManage = 'userTwoFactor.manage';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Return the resource group for this permission. */
    public function group(): string
    {
        return Str::headline(Str::before($this->value, '.'));
    }

    /** Return a Permission label. */
    public function label(): string
    {
        return Str::headline(Str::after($this->value, '.'));
    }
}
