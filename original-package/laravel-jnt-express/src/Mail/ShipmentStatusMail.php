<?php

namespace JntExpress\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use JntExpress\Enums\ShipmentStatus;
use JntExpress\Models\JntShipment;

class ShipmentStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(public JntShipment $shipment, public ShipmentStatus $status) {}

    public static function copy(ShipmentStatus $s): array
    {
        return match ($s) {
            ShipmentStatus::Pending => ['Your Order is Pending to Pickup', 'Your order is waiting for the courier to pick up.'],
            ShipmentStatus::Pickup => ['Your Order has been Picked Up', 'Your order has been picked up by the courier.'],
            ShipmentStatus::OutForDelivery => ['Your Order is out for Delivery', 'Your order is out for delivery and will be delivered shortly.'],
            default => [$s->label(), $s->note()],
        };
    }

    public function build(): static
    {
        [$subject, $line] = self::copy($this->status);

        return $this->subject($subject)->view('jnt::mail.status', [
            'heading' => $subject,
            'line' => $line,
            'awb' => $this->shipment->awb,
            'trackingUrl' => $this->shipment->trackingUrl(),
        ]);
    }
}
