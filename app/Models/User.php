<?php

namespace App\Models;

use App\Support\TwoFactorSettings;
use App\Traits\ManagesStorage;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

/**
 * User model with admin authentication and Filament 2FA support.
 *
 * @property int|string|null $created_by
 */
#[Fillable(['name', 'email', 'password', 'is_2fa_enabled', 'avatar_url'])]
#[Hidden(['password', 'remember_token', 'google2fa_secret', 'google2fa_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar, HasEmailAuthentication, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithEmailAuthentication, ManagesStorage, MustVerifyEmailTrait, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            // Record the acting admin, only when someone is actually authenticated.
            $user->created_by = $user->created_by ?? (filled($authId = Auth::id()) ? (int) $authId : null);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    // Determine admin panel access based on assigned roles and permissions.
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->roles()->exists();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getImageUrl($this->avatar_url);
    }

    // 2FA: return encrypted secret (auto-decrypted by cast)
    public function getAppAuthenticationSecret(): ?string
    {
        return $this->google2fa_secret;
    }

    // 2FA: save new secret, clear recovery codes if secret removed
    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->google2fa_secret = $secret;

        if (blank($secret)) {
            $this->google2fa_recovery_codes = null;
        }

        $this->save();
    }

    // 2FA: holder name shown in authenticator app
    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    // 2FA: return recovery codes array (or null)
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        $codes = $this->getAttribute('google2fa_recovery_codes');

        return is_array($codes) ? $codes : null;
    }

    // 2FA: save recovery codes, throw on failure
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->setAttribute('google2fa_recovery_codes', $codes);

        if (! $this->save()) {
            throw new RuntimeException('Failed to save app authentication recovery codes.');
        }
    }

    // Determine Google Authenticator enrolment status from the stored secret.
    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->is_2fa_enabled && filled($this->google2fa_secret);
    }

    // Determine whether mandatory two-factor enrolment is required by global settings.
    public function mustEnrollInTwoFactor(): bool
    {
        return TwoFactorSettings::isGloballyEnabled()
            && $this->is_2fa_enabled
            && ! $this->hasTwoFactorEnabled();
    }

    // Revoke the current authenticator secret and any unused recovery codes.
    public function resetTwoFactorAuthentication(): void
    {
        $this->google2fa_secret = null;
        $this->google2fa_recovery_codes = null;
        $this->save();
    }

    // Turn two-factor authentication off for the account and revoke its credentials.
    public function disableTwoFactorAuthentication(): void
    {
        $this->is_2fa_enabled = false;
        $this->google2fa_secret = null;
        $this->google2fa_recovery_codes = null;
        $this->save();
    }

    // Determine whether this is the last protected super administrator to prevent administrative lockout.
    public function isLastSuperAdmin(): bool
    {
        $role = (string) config('two-factor.protected_role', 'super_admin');
        if (! $this->hasRole($role)) {
            return false;
        }

        return static::query()->role($role)->count() <= 1;
    }

    // enrolment status for badges and infolists.
    public function twoFactorStatus(): string
    {
        if ($this->hasTwoFactorEnabled()) {
            return 'enabled';
        }

        if ($this->mustEnrollInTwoFactor()) {
            return 'pending';
        }

        return 'disabled';
    }

    // Get the attributes that should be cast.
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google2fa_secret' => 'encrypted',
            'google2fa_recovery_codes' => 'encrypted:array',
            'is_2fa_enabled' => 'boolean',
            'has_email_authentication' => 'boolean',
        ];
    }
}
