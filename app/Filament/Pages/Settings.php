<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Filament\Support\FileUploadConfig;
use App\Models\Setting;
use App\Support\SettingsStore;
use App\Support\TwoFactorSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Filament\Support\Icons\Heroicon;


/**
 * Admin settings page: company profile, branding, localization, contact details and two-factor auth.
 *
 * @property-read Schema $form
 */
class Settings extends Page
{
    // Settings keys that hold uploaded branding files.
    private const BRANDING_KEYS = ['light_logo', 'dark_logo', 'favicon'];

    private const LOGO_HINT = 'Recommended: 400×120px, transparent PNG/WebP, max 2 MB. Min shortest side 120px, max longest side 2000px, aspect ratio 1:4 to 4:1.';

    protected static ?string $title = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function getSubheading(): ?string
    {
        return 'Manage your company information, branding, and security settings.';
    }

    // Who can open the page: users who can view or manage settings.
    public static function canAccess(): bool
    {
        return Gate::allows(Permission::SettingsView->value)
            || Gate::allows(Permission::SettingsManage->value);
    }

    // Who can change settings: users who can update or manage settings.
    public static function canUpdateSettings(): bool
    {
        return Gate::allows(Permission::SettingsUpdate->value)
            || Gate::allows(Permission::SettingsManage->value);
    }

    // Fill the form with saved settings
    public function mount(): void
    {
        $saved = Setting::query()->pluck('value', 'key')->toArray();

        $this->form->fill(array_replace([
            'app_name' => config('app.name'),
            'contact_email' => '',
            'contact_phone' => '',
            'company_address' => '',
            'primary_color' => '#4f46e5',
            'light_logo' => null,
            'dark_logo' => null,
            'favicon' => null,
            'date_format' => config('app.date_format', 'd M Y'),
            'date_time_format' => config('app.date_time_format', 'd M Y, h:i A'),
            'timezone' => config('app.timezone', 'UTC'),
            'email_verification_enabled' => false,
            TwoFactorSettings::KeyEnabled => false,
            TwoFactorSettings::KeyRecoveryCodes => false,
        ], $saved));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->companySection(),
                $this->localizationSection(),
                $this->twoFactorSection(),
            ])
            ->statePath('data');
    }

    // Company name, theme color, logos and contact details.
    private function companySection(): Section
    {
        return Section::make('Company profile')
            ->description('Manage company details, logos and appearance.')
            ->disabled(fn (): bool => ! static::canUpdateSettings())
            ->collapsible()
            ->columns(['default' => 1, 'md' => 2])
            ->columnSpanFull()
            ->schema([
                TextInput::make('app_name')
                    ->label('Application Name')
                    ->required()
                    ->maxLength(120)
                    ->placeholder('Enter application name')
                    ->helperText('This name appears in the browser title and navigation.'),

                ColorPicker::make('primary_color')
                    ->label('Primary Theme Color')
                    ->helperText('Choose the primary color used throughout the admin panel.')
                    ->default('#4f46e5')
                    ->required()
                    ->hex()
                    ->live(),

                FileUploadConfig::logo(
                    'light_logo',
                    'Light Theme Logo',
                    'Use a dark-colored logo for light backgrounds. '.self::LOGO_HINT,
                ),

                FileUploadConfig::logo(
                    'dark_logo',
                    'Dark Theme Logo',
                    'Use a light-colored logo for dark backgrounds. '.self::LOGO_HINT,
                ),

                FileUploadConfig::favicon(),

                $this->contactSection(),
            ]);
    }

    // Email, phone and address shown to customers.
    private function contactSection(): Section
    {
        return Section::make('Contact Information')
            ->description('Configure how customers can reach your business.')
            ->extraAttributes(['class' => '-ml-4 pt-4 border-t border-gray-200 dark:border-gray-700'])
            ->columns(['default' => 1, 'md' => 2])
            ->columnSpanFull()
            ->schema([
                TextInput::make('contact_email')
                    ->label('Email Address')
                    ->email()
                    ->nullable()
                    ->maxLength(255)
                    ->placeholder('contact@company.com')
                    ->prefixIcon(Heroicon::OutlinedEnvelope),

                TextInput::make('contact_phone')
                    ->label('Phone Number')
                    ->tel()
                    ->nullable()
                    ->maxLength(30)
                    ->placeholder('+1 (555) 000-0000')
                    ->prefixIcon(Heroicon::OutlinedPhone),

                Textarea::make('company_address')
                    ->label('Company Address')
                    ->nullable()
                    ->rows(3)
                    ->placeholder('Enter your company address')
                    ->columnSpanFull(),
            ]);
    }

    // Date/time display formats and timezone used across the application.
    private function localizationSection(): Section
    {
        return Section::make('Localization')
            ->description('Configure date and time display formats and the application timezone.')
            ->icon(Heroicon::OutlinedLanguage)
            ->disabled(fn (): bool => ! static::canUpdateSettings())
            ->collapsible()
            ->columns(['default' => 1, 'md' => 2])
            ->columnSpanFull()
            ->schema([
                Select::make('date_format')
                    ->label('Date Format')
                    ->required()
                    ->maxLength(30)
                    ->options([
                        'd M Y' => 'd M Y (01 Jan 2024)',
                        'm/d/Y' => 'm/d/Y (01/01/2024)',
                        'Y-m-d' => 'Y-m-d (2024-01-01)',
                        'd/m/Y' => 'd/m/Y (01/01/2024)',
                        'F j, Y' => 'F j, Y (January 1, 2024)',
                        'l, F j, Y' => 'l, F j, Y (Wednesday, January 1, 2024)',
                    ])
                    ->placeholder('d M Y'),

                Select::make('date_time_format')
                    ->label('Date & Time Format')
                    ->required()
                    ->maxLength(30)
                    ->options([
                        'd M Y, h:i A' => 'd M Y, h:i A (01 Jan 2024, 1:00 PM)',
                        'm/d/Y, h:i A' => 'm/d/Y, h:i A (01/01/2024, 1:00 PM)',
                        'Y-m-d h:i A' => 'Y-m-d h:i A (2024-01-01 1:00 PM)',
                        'd/m/Y, h:i A' => 'd/m/Y, h:i A (01/01/2024, 1:00 PM)',
                        'F j, Y, h:i A' => 'F j, Y, h:i A (January 1, 2024, 1:00 PM)',
                    ])
                    ->placeholder('d M Y, h:i A'),

                Select::make('timezone')
                    ->label('Timezone')
                    ->searchable()
                    ->required()
                    ->options(collect(timezone_identifiers_list())
                        ->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])
                        ->all()),
            ]);
    }

    // Turn Google Authenticator 2FA on or off for the whole admin panel.
    private function twoFactorSection(): Section
    {
        return Section::make('Two-Factor Authentication')
            ->description('Configure Google Authenticator 2FA for enhanced security across the admin panel.')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->visible(fn (): bool => static::canUpdateSettings())
            ->collapsible()
            ->columns(['default' => 1, 'md' => 2])
            ->columnSpanFull()
            ->schema([
                Toggle::make(TwoFactorSettings::KeyEnabled)
                    ->label('Enable Google Authenticator 2FA')
                    ->helperText('Require two-factor authentication for all admin users.')
                    ->live()
                    ->inline(false),

                // Recovery codes only make sense while 2FA is switched on.
                Toggle::make(TwoFactorSettings::KeyRecoveryCodes)
                    ->label('Allow Recovery Codes')
                    ->helperText('Allow users to generate backup sign-in codes for account recovery.')
                    ->visible(fn (Get $get): bool => (bool) $get(TwoFactorSettings::KeyEnabled))
                    ->inline(false),

                Toggle::make('email_verification_enabled')
                    ->label('Require Email Verification')
                    ->helperText('Require admin users to verify their email before signing in.')
                    ->inline(false),
            ]);
    }

    // The "Save Changes" button in the page header.
    protected function getHeaderActions(): array
    {
        if (! static::canUpdateSettings()) {
            return [];
        }

        return [
            Action::make('save')
                ->label('Save Changes')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->keyBindings(['mod+s'])
                ->action('save'),
        ];
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(static::canUpdateSettings(), 403);

        $data = $this->form->getState();
        $oldBranding = Setting::query()->whereIn('key', self::BRANDING_KEYS)->pluck('value', 'key')->all();

        try {
            // Save every setting together: all of them, or none.
            DB::transaction(function () use ($data): void {
                foreach ($data as $key => $value) {
                    // An empty branding field means "keep the current file".
                    if (in_array($key, self::BRANDING_KEYS, true) && blank($value)) {
                        continue;
                    }

                    Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                }
            });
        } catch (QueryException $exception) {
            report($exception);

            Notification::make()->title('Settings could not be saved')->body('Please try again.')->danger()->send();

            return;
        }

        $this->deleteReplacedBranding($oldBranding, $data);

        // Clear cached values so the latest settings are used.
        SettingsStore::clearCache();
        TwoFactorSettings::flush();

        Notification::make()->title('Settings saved')->success()->send();

        // Branding and colors are drawn by the panel layout, so a fresh page load is needed.
        $this->redirect(static::getUrl());
    }

    // Delete the old branding file when a new one replaced it.
    private function deleteReplacedBranding(array $oldBranding, array $data): void
    {
        foreach ($oldBranding as $key => $oldFile) {
            $newFile = $data[$key] ?? null;

            if (filled($oldFile) && filled($newFile) && $oldFile !== $newFile) {
                (new Setting)->deleteStorageFile($oldFile);
            }
        }
    }
}
