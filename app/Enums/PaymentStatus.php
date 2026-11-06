<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unpaid => 'warning',
            self::Paid => 'success',
            self::Refunded => 'gray',
        };
    }

    /**
     * Check whether payment has been successfully received.
     */
    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Check whether payment has been refunded.
     */
    public function isRefunded(): bool
    {
        return $this === self::Refunded;
    }

    /**
     * Check whether payment is still outstanding.
     */
    public function isUnpaid(): bool
    {
        return $this === self::Unpaid;
    }

    /**
     * Check whether payment has been settled.
     */
    public function isSettled(): bool
    {
        return $this === self::Paid || $this === self::Refunded;
    }
}
