<?php

namespace App\Filament\Auth\Actions;

use App\Mail\EmailTemplateMailable;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Sets up Google Authenticator 2FA without asking for the current password. */
class SetUpGoogleTwoFactorAction
{
    private const LANG = 'filament-panels::auth/multi-factor/app/actions/set-up';

    public static function make(AppAuthentication $appAuthentication): Action
    {
        $isRecoverable = $appAuthentication->isRecoverable();

        return Action::make('setUpAppAuthentication')
            ->label(__(self::LANG.'.label'))
            ->icon(Heroicon::LockClosed)
            ->link()
            ->modalIcon(Heroicon::OutlinedLockClosed)
            ->modalHeading(__(self::LANG.'.modal.heading'))
            ->modalWidth(Width::Large)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modifyWizardUsing(fn (Wizard $wizard) => $wizard->hiddenHeader())

            // When the modal opens: create a secret (and recovery codes) and keep them encrypted.
            ->mountUsing(function (HasActions $livewire, Schema $schema) use ($appAuthentication, $isRecoverable): void {
                $schema->fill();

                $livewire->mergeMountedActionArguments([
                    'encrypted' => encrypt([
                        'secret' => $appAuthentication->generateSecret(),
                        'recoveryCodes' => $isRecoverable ? $appAuthentication->generateRecoveryCodes() : [],
                        'userKey' => Filament::getUserScopedAuthIdentifier(),
                    ]),
                ]);
            })

            // The modal steps: scan the QR code, then (optionally) save recovery codes.
            ->steps(fn (Action $action): array => [
                self::qrCodeStep($action, $appAuthentication),
                self::recoveryCodesStep($action)->visible($isRecoverable),
            ])

            // When the user submits: save everything and tell them.
            ->action(function (array $arguments) use ($appAuthentication, $isRecoverable): void {
                self::saveSetup($arguments, $appAuthentication, $isRecoverable);
            });
    }

    /** Step 1: show the QR code and ask for the 6-digit code from the app. */
    private static function qrCodeStep(Action $action, AppAuthentication $appAuthentication): Step
    {
        return Step::make('app')->schema([
            Group::make([
                Text::make(__(self::LANG.'.modal.content.qr_code.instruction')),
                Image::make(
                    url: fn (): string => $appAuthentication->generateQrCodeDataUri(self::data($action)['secret']),
                    alt: __(self::LANG.'.modal.content.qr_code.alt'),
                )
                    ->imageHeight('12rem')
                    ->alignCenter(),
                Text::make(fn (): string => self::data($action)['secret'])
                    ->fontFamily(FontFamily::Mono)
                    ->copyable(),
            ])->dense(),

            OneTimeCodeInput::make('code')
                ->label(__(self::LANG.'.modal.form.code.label'))
                ->required()
                ->rule(fn () => function (string $attribute, $value, $fail) use ($action, $appAuthentication): void {
                    if (! $appAuthentication->verifyCode($value, self::data($action)['secret'])) {
                        $fail(__(self::LANG.'.modal.form.code.messages.invalid'));
                    }
                }),
        ]);
    }

    /** Step 2: show the recovery codes so the user can store them safely. */
    private static function recoveryCodesStep(Action $action): Step
    {
        return Step::make('recovery')->schema([
            Text::make(__(self::LANG.'.modal.content.recovery_codes.instruction')),
            Text::make(fn (): string => implode('   ', self::data($action)['recoveryCodes'] ?? []))
                ->fontFamily(FontFamily::Mono)
                ->copyable(),
        ]);
    }

    /** Read the encrypted setup data stored on the action. */
    private static function data(Action $action): array
    {
        $encrypted = $action->getArguments()['encrypted'] ?? null;

        return $encrypted ? decrypt($encrypted) : [];
    }

    /** Save the secret and recovery codes, then notify the user. */
    private static function saveSetup(array $arguments, AppAuthentication $appAuthentication, bool $isRecoverable): void
    {
        /** @var Authenticatable&HasAppAuthentication&HasAppAuthenticationRecovery $user */
        $user = Filament::auth()->user();
        $data = decrypt($arguments['encrypted']);

        // Safety check: the setup data must belong to the logged-in user.
        if (Filament::getUserScopedAuthIdentifier($user) !== $data['userKey']) {
            return;
        }

        DB::transaction(function () use ($appAuthentication, $isRecoverable, $user, $data): void {
            $appAuthentication->saveSecret($user, $data['secret']);

            if ($isRecoverable) {
                $appAuthentication->saveRecoveryCodes($user, $data['recoveryCodes']);
            }
        });

        Notification::make()
            ->title(__(self::LANG.'.notifications.enabled.title'))
            ->success()
            ->send();

        self::sendAlertEmail($user);
    }

    /** Tell the user by email that 2FA was turned on. A mail error must not break the setup. */
    private static function sendAlertEmail(mixed $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        try {
            EmailTemplateMailable::sendTo('security_alert', $user->email, [
                'user' => ['name' => $user->name, 'email' => $user->email],
                'event' => 'Two-factor authentication was enabled',
            ], $user);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
