<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending    => 'warning',
            self::Processing => 'info',
            self::Completed  => 'success',
            self::Cancelled  => 'danger',
        };
    }

    /**
     * Whether this status affects available stock.
     */
    public function commitsStock(): bool
    {
        return match ($this) {
            self::Processing, self::Completed => true,
            default => false,
        };
    }

    /**
     * Get all statuses that can be transitioned to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::Processing,
                self::Completed,
                self::Cancelled,
            ],

            self::Processing => [
                self::Pending,
                self::Completed,
                self::Cancelled,
            ],

            self::Completed => [
                self::Cancelled,
            ],

            self::Cancelled => [
                self::Pending,
                self::Processing,
            ],
        };
    }

    /**
     * Check whether a transition to another status is allowed.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Check whether this status commits stock after a transition.
     */
    public function changesStockOnTransitionTo(self $status): bool
    {
        return $this->commitsStock() !== $status->commitsStock();
    }
}