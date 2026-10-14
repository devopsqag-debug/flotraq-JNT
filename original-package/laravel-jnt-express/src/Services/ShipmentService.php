<?php

namespace JntExpress\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JntExpress\Contracts\ShippableOrder;
use JntExpress\Enums\ShipmentStatus;
use JntExpress\Events\ShipmentCancelled;
use JntExpress\Events\ShipmentCreated;
use JntExpress\Events\ShipmentStatusChanged;
use JntExpress\Models\JntShipment;
use JntExpress\Support\ErrorMessages;
use JntExpress\Support\Settings;

class ShipmentService
{
    public function __construct(
        protected JntClient $client,
        protected PayloadBuilder $builder,
        protected Settings $settings,
        protected OrderStatusSync $sync,
    ) {}

    /**
     * Create consignment notes ("Order to J&T"). Accepts order models or ids.
     * Orders that already have an active shipment are skipped.
     *
     * @return Collection<int, array{key:mixed, success:bool, shipment:?JntShipment, message:string}>
     */
    public function create(iterable $orders, bool $insurance = false): Collection
    {
        $this->assertConfigured(['vipcode', 'apikey', 'signkey', 'name', 'phone', 'postcode']);

        if ($insurance && !$this->settings->insuranceAllowed()) {
            throw new \DomainException('Insurance is not enabled in the J&T settings.');
        }

        $results = collect();

        foreach ($this->resolveOrders($orders) as $order) {
            $key = $order->jntOrderKey();

            if ($this->activeShipmentFor($order)) {
                $results->push(['key' => $key, 'success' => false, 'shipment' => null, 'message' => 'Already Order']);
                continue;
            }

            $fields = $this->builder->build($order, $insurance);
            $res = $this->client->createOrder($fields, (string) $this->settings->get('signkey'));

            if (!$res['ok']) {
                $results->push([
                    'key' => $key, 'success' => false, 'shipment' => null,
                    'message' => ErrorMessages::for($res['reason']),
                ]);
                continue;
            }

            $shipment = JntShipment::create([
                'shippable_type' => $order->getMorphClass(),
                'shippable_id' => $key,
                'awb' => $res['awb'],
                'jnt_order_id' => $res['order_id'],
                'code' => $res['code'],
                'status' => ShipmentStatus::Pending,
                'insurance' => $insurance,
                'cod' => $fields['cod'],
                'weight' => $fields['weight'],
                'request' => $res['request'],
                'response' => $res['raw'],
            ]);

            $shipment->addEvent('Tracking number: ' . $shipment->awb, ShipmentStatus::Pending);
            $this->sync->apply($shipment, ShipmentStatus::Pending);

            event(new ShipmentCreated($shipment));
            event(new ShipmentStatusChanged($shipment, null, ShipmentStatus::Pending));

            $results->push(['key' => $key, 'success' => true, 'shipment' => $shipment, 'message' => 'Tracking number: ' . $shipment->awb]);
        }

        return $results;
    }

    /**
     * Cancel shipments (models, ids, or waybill numbers).
     *
     * @return Collection<int, array{key:mixed, success:bool, shipment:JntShipment, message:string}>
     */
    public function cancel(iterable $shipments): Collection
    {
        $this->assertConfigured(['signkey']);
        $results = collect();

        foreach ($this->resolveShipments($shipments) as $shipment) {
            if (!$shipment->awb || $shipment->status === ShipmentStatus::Cancelled) {
                $results->push(['key' => $shipment->shippable_id, 'success' => false, 'shipment' => $shipment, 'message' => 'Not yet Order']);
                continue;
            }

            $res = $this->client->cancelOrder($shipment->awb, (string) $shipment->jnt_order_id, (string) $this->settings->get('signkey'));

            if (!$res['ok']) {
                $results->push([
                    'key' => $shipment->shippable_id, 'success' => false, 'shipment' => $shipment,
                    'message' => ErrorMessages::for($res['reason'] ?? $res['status']),
                ]);
                continue;
            }

            $from = $shipment->status;
            $shipment->update(['status' => ShipmentStatus::Cancelled, 'cancelled_at' => now()]);
            $shipment->addEvent('J&T order cancelled: ' . $shipment->awb, ShipmentStatus::Cancelled);
            $this->sync->apply($shipment, ShipmentStatus::Cancelled);

            event(new ShipmentCancelled($shipment));
            event(new ShipmentStatusChanged($shipment, $from, ShipmentStatus::Cancelled));

            $results->push(['key' => $shipment->shippable_id, 'success' => true, 'shipment' => $shipment, 'message' => 'Cancelled ' . $shipment->awb]);
        }

        return $results;
    }

    /**
     * Download the consignment note PDF for shipments / waybills.
     *
     * @param string $format consignment | thermal | a4
     */
    public function print(iterable $shipments, string $format = 'consignment'): string
    {
        $this->assertConfigured(['vipcode']);

        $awbs = $this->resolveShipments($shipments)
            ->filter(fn ($s) => $s->awb && $s->status !== ShipmentStatus::Cancelled)
            ->pluck('awb')->all();

        if (!$awbs) {
            throw new \RuntimeException('Not yet Order');
        }

        $pdf = $this->client->print((string) $this->settings->get('vipcode'), implode(',', $awbs), $format);

        if (!str_starts_with(ltrim($pdf), '%PDF')) {
            throw new \RuntimeException('J&T did not return a PDF. Check your VIP code and waybill numbers.');
        }

        return $pdf;
    }

    public function activeShipmentFor(ShippableOrder $order): ?JntShipment
    {
        return JntShipment::active()
            ->where('shippable_type', $order->getMorphClass())
            ->where('shippable_id', $order->jntOrderKey())
            ->latest('id')->first();
    }

    /** @return Collection<int, ShippableOrder&Model> */
    protected function resolveOrders(iterable $orders): Collection
    {
        $orders = collect($orders);
        $class = config('jnt.order_model');

        $ids = $orders->reject(fn ($o) => $o instanceof Model)->values()->all();
        $models = $orders->filter(fn ($o) => $o instanceof Model);

        if ($ids) {
            if (!class_exists($class)) {
                throw new \DomainException("jnt.order_model [{$class}] does not exist.");
            }
            $models = $models->concat($class::query()->findMany($ids));
        }

        foreach ($models as $m) {
            if (!$m instanceof ShippableOrder) {
                throw new \DomainException(get_class($m) . ' must implement ' . ShippableOrder::class);
            }
        }

        return $models->values();
    }

    /** @return Collection<int, JntShipment> */
    protected function resolveShipments(iterable $items): Collection
    {
        $items = collect($items);

        $models = $items->filter(fn ($i) => $i instanceof JntShipment);
        $rest = $items->reject(fn ($i) => $i instanceof JntShipment)->values();

        if ($rest->isNotEmpty()) {
            $ids = [];
            $awbs = [];
            foreach ($rest as $v) {
                // short numeric values are shipment ids, anything else is a waybill number
                if (is_int($v) || (ctype_digit((string) $v) && strlen((string) $v) < 10)) {
                    $ids[] = (int) $v;
                } else {
                    $awbs[] = (string) $v;
                }
            }
            $models = $models->concat(
                JntShipment::query()->where(fn ($q) => $q->whereIn('id', $ids)->orWhereIn('awb', $awbs))->get()
            );
        }

        return $models->unique('id')->values();
    }

    protected function assertConfigured(array $keys): void
    {
        $missing = array_filter($keys, fn ($k) => !filled($this->settings->get($k)));

        if ($missing) {
            throw new \DomainException('J&T settings incomplete, missing: ' . implode(', ', $missing));
        }
    }
}
