<?php

namespace JntExpress\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Pickup = 'pickup';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Returning = 'returning';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'J&T Pending Pickup',
            self::Pickup => 'J&T Pickup',
            self::InTransit => 'J&T In Transit',
            self::OutForDelivery => 'J&T Out For Delivery',
            self::Returning => 'J&T Return',
            self::Delivered => 'Delivered',
            self::Returned => 'Returned',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Note stored in the shipment history when J&T pushes this status. */
    public function note(): string
    {
        return match ($this) {
            self::Pending => 'J&T: Order is pending pickup',
            self::Pickup => 'J&T: Order has been Pickuped',
            self::InTransit => 'J&T: Order is In Transit',
            self::OutForDelivery => 'J&T: Order is out for Delivery and will be Delivered shortly',
            self::Returning => 'J&T: Return Order',
            self::Delivered => 'J&T: Order has been Delivered',
            self::Returned => 'J&T: Order has been Returned',
            self::Cancelled => 'J&T: Order has been Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#b45309',
            self::Pickup, self::InTransit => '#1d4ed8',
            self::OutForDelivery => '#6d28d9',
            self::Delivered => '#15803d',
            self::Returning, self::Returned => '#be123c',
            self::Cancelled => '#6b7280',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Cancelled], true);
    }

    /** Map a J&T webhook scanTypeCode to a status (null = ignored code). */
    public static function fromScanCode(int|string|null $code): ?self
    {
        return match ((string) $code) {
            '10', '200' => self::Pickup,
            '20', '30' => self::InTransit,
            '94' => self::OutForDelivery,
            '100' => self::Delivered,
            '172' => self::Returning,
            '173' => self::Returned,
            default => null,
        };
    }
}
