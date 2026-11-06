<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderEditForm;
use App\Models\OrderItem;
use App\Services\OrderService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function form(Schema $schema): Schema
    {
        return OrderEditForm::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(OrderService::class)->update($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Edit Order #'.$this->getRecord()->order_number;
    }

    public function getSubheading(): ?string
    {
        return 'Review and update the customer and products for this order.';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Order updated successfully';
    }

    protected function getFormActions(): array
    {
        if (OrderEditForm::isLocked($this->getRecord())) {
            return [$this->getCancelFormAction()->label('Back')];
        }

        return [
            $this->getSaveFormAction()->label('Save changes'),
            $this->getCancelFormAction(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->getRecord()->items
            ->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => (float) $item->unit_price * $item->quantity,
            ])
            ->all();

        return $data;
    }
}
