<?php

declare(strict_types=1);

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Checks Filament resource access using permission names like "products.view".
 *
 * @property string|null $model
 */
trait AuthorizesWithPermissions
{
    // Some Filament abilities share a permission, e.g. "restore" needs "delete".
    protected static array $abilityMap = [
        'viewAny' => 'view',
        'deleteAny' => 'delete',
        'restore' => 'delete',
        'restoreAny' => 'delete',
        'forceDelete' => 'delete',
        'forceDeleteAny' => 'delete',
        'replicate' => 'create',
        'reorder' => 'update',
    ];

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return Response::deny();
        }

        // Ability name, e.g. "viewAny" -> "view".
        $ability = $action instanceof UnitEnum ? $action->name : $action;
        $ability = static::$abilityMap[$ability] ?? $ability;

        // Resource name from the model, e.g. Product -> "products".
        $model = $record ? $record::class : static::$model;
        $resource = Str::plural(Str::camel(class_basename($model)));

        // Final permission, e.g. "products.view".
        $permission = "{$resource}.{$ability}";

        return Gate::forUser($user)->allows($permission)
            ? Response::allow()
            : Response::deny();
    }
}
