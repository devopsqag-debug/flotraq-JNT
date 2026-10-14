<?php

namespace JntExpress\Listeners;

use Illuminate\Support\Facades\Mail;
use JntExpress\Events\ShipmentStatusChanged;
use JntExpress\Mail\ShipmentStatusMail;

/** Customer e-mails for pending pickup / picked up / out for delivery. */
class SendShipmentStatusEmail
{
    public function handle(ShipmentStatusChanged $e): void
    {
        if (!config('jnt.notifications.enabled')
            || !in_array($e->to->value, (array) config('jnt.notifications.on', []), true)) {
            return;
        }

        $order = $e->shipment->shippable;
        $email = $order && method_exists($order, 'jntCustomerEmail') ? $order->jntCustomerEmail() : null;

        if (!$email) {
            return;
        }

        $mail = new ShipmentStatusMail($e->shipment, $e->to);
        $pending = Mail::to($email);

        config('jnt.notifications.queue') ? $pending->queue($mail) : $pending->send($mail);
    }
}
