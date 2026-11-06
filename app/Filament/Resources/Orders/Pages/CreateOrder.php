<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static bool $canCreateAnother = false;

    // Hide Create / Create & create another / Cancel; the Wizard's submitAction saves.
    protected function getFormActions(): array
    {
        return [];
    }

    // Single persistence path: the service (validation, locks, stock).
    protected function handleRecordCreation(array $data): Model
    {
        return app(OrderService::class)->create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Order created';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
