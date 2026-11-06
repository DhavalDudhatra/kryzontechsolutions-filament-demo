<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\EmailService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // Send the new administrator their welcome email.
    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;

        $sent = app(EmailService::class)->sendTemplate('welcome', $user->email, [
            'user' => ['name' => $user->name, 'email' => $user->email],
        ]);

        if (! $sent) {
            Notification::make()
                ->title('Welcome email not sent')
                ->body('The welcome email template is not active or could not be sent.')
                ->warning()
                ->send();
        }
    }

    // For Create and cancel button code changes
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
