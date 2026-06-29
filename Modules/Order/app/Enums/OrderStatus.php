<?php

declare(strict_types=1);

namespace Modules\Order\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    /**
     * All values as a plain array (handy for validation rules).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }

    /**
     * Only a confirmed order may be paid.
     */
    public function isPayable(): bool
    {
        return $this === self::Confirmed;
    }
}
