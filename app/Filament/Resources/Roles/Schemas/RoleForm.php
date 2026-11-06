<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Enums\Permission as PermissionEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Filament\Support\Icons\Heroicon;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role Information')
                    ->description('Create a role and configure its access permissions.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->placeholder('Enter role name (e.g. admin)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->minLength(2)
                            ->helperText('Enter a unique name for this role.')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Section::make('Role Permissions')
                    ->description('Select the permissions this role is allowed to perform.')
                    ->icon(Heroicon::OutlinedKey)
                    ->schema(self::permissionSections())
                    ->columnSpanFull(),
            ]);
    }

    /** @return list<Section> */
    private static function permissionSections(): array
    {
        $groups = collect(PermissionEnum::cases())
            ->groupBy(fn (PermissionEnum $permission): string => $permission->group())
            ->map(fn ($permissions): array => $permissions
                ->map(fn (PermissionEnum $permission): string => $permission->value)
                ->all());

        $otherPermissions = Permission::query()
            ->where('guard_name', config('auth.defaults.guard', 'web'))
            ->whereNotIn('name', PermissionEnum::values())
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if ($otherPermissions !== []) {
            $groups->put('Other', $otherPermissions);
        }

        return array_values($groups
            ->sortKeys()
            ->map(function (array $permissionNames, string $group): Section {
                return Section::make($group)
                    ->schema([
                        CheckboxList::make('permissions_'.Str::snake($group))
                            ->hiddenLabel()
                            ->relationship(
                                name: 'permissions',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('guard_name', config('auth.defaults.guard', 'web'))
                                    ->whereIn('name', $permissionNames),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Permission $permission): string => PermissionEnum::tryFrom($permission->name)?->label()
                                    ?? Str::headline($permission->name),
                            )
                            ->bulkToggleable()
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 4,
                            ])
                            ->gridDirection('row')
                            ->columnSpanFull(),
                    ])
                    ->compact()
                    ->columnSpanFull();
            })
            ->all());
    }
}
