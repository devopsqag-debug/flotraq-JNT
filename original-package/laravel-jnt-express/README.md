# J&T Express Malaysia for Laravel

Laravel port of the official **J&T Express Malaysia** WooCommerce plugin (v2.0.24). Everything the plugin did is here, decoupled from WooCommerce so it works with any Laravel order model.

Requires PHP 8.1+, Laravel 10 / 11 / 12.

## Install

```bash
composer require jtexpress/laravel-jnt-express        # or add as a path repository
php artisan migrate
php artisan vendor:publish --tag=jnt-config           # optional
```

Add the trait + interface to your order model and map your column names in `config/jnt.php` → `order_map`:

```php
use JntExpress\Concerns\HasJntShipping;
use JntExpress\Contracts\ShippableOrder;

class Order extends Model implements ShippableOrder
{
    use HasJntShipping;   // override any jnt*() method for custom logic
}
```

Set `JNT_ORDER_MODEL=App\Models\Order` in `.env`, open **/jnt/settings** and fill in VIP code, API key, sign key, rate key and sender details (or set the `JNT_*` env vars).

Give J&T your webhook URL: `https://your-app.test/jnt-webhook`.

## Feature map (plugin → package)

| WordPress plugin | Laravel package |
|---|---|
| Shipping method settings (VIP code, keys, sender, service, insurance, markup) | `/jnt/settings` page, secrets encrypted in `jnt_settings`; `config/jnt.php` defaults |
| Bulk action **Order to J&T** / **with Insurance** | `POST /jnt/shipments`, `Jnt::create($orders, $insurance)`, `php artisan jnt:create 1 2 3 --insurance`, `<x-jnt::order-actions/>` |
| Skip orders already sent ("Already Order") | Same; one active shipment per order |
| Auto-print thermal label after create | `jnt.auto_print` (opens label in new tab) |
| Bulk **Print Consignment Note** / **(Thermal)** / A4 | `POST /jnt/shipments/print` (`format=consignment\|thermal\|a4`), `GET /jnt/shipments/{id}/print/{format}`, `Jnt::print()`, `jnt:print` |
| Bulk **Cancel J&T Order** | `POST /jnt/shipments/cancel`, `Jnt::cancel()`, `jnt:cancel` |
| Live shipping rate + markup at checkout | `POST /jnt/rates`, `Jnt::quote($kg, $postcode)`, `jnt:quote`; optional cache |
| MY-only availability | `jnt.countries` |
| Waybill column + search on orders list | `/jnt/shipments` list with waybill/order search + status filter, `<x-jnt::tracking-link>` |
| My Account tracking number column / order totals row | `<x-jnt::tracking-link :order="$order"/>`, `<x-jnt::status-badge>` |
| Custom order statuses (Pending Pickup, Pickup, In Transit, Out For Delivery, Return) | `ShipmentStatus` enum + history in `jnt_shipment_events`; optional mirroring into your own status column (`jnt.order_status`) |
| `/jnt-webhook` callback (scan codes 10, 20/30, 94, 100, 172, 173, 200) | `POST /jnt-webhook` – same codes, same `code/message/data` responses, `apiAccount` header check, J&T test waybill |
| Customer e-mails (pending / pickup / out for delivery) | `ShipmentStatusMail`, queueable, `jnt.notifications` |
| S10…S43 error translations | `ErrorMessages` |
| Order meta `jtawb`, `jtorder`, `jtcode` | `jnt_shipments.awb`, `jnt_order_id`, `code` |
| Admin notices (WooCommerce missing / outdated) | n/a in Laravel |

Events you can listen to: `ShipmentCreated`, `ShipmentCancelled`, `ShipmentStatusChanged`.

```php
Jnt::create([$order]);                       // Collection of ['key','success','shipment','message']
Jnt::quote(1.2, '50000');                    // float|null (markup included)
file_put_contents('label.pdf', Jnt::print([$shipment], 'thermal'));
```

## Deliberate differences from the plugin

- **Rate failures return `null`, not 0.** The plugin turned an API failure into a RM 0 (free) shipping rate. The `/jnt/rates` endpoint now answers 422 so you can hide the option.
- **TLS verification is on** (`JNT_VERIFY_SSL=false` restores the old behaviour if J&T's chain fails for you).
- **Webhook retries are idempotent** – the same status twice doesn't duplicate history or e-mails.
- Non-PDF responses from the print API are reported as an error instead of being served as a broken file.
- Weight units kg, g, lb, oz are converted (the plugin handled only kg/g).
- The integration identifiers `WORDPRESS` / `WORD12` from the plugin are kept as defaults and are configurable (`JNT_API_USERNAME`, `JNT_API_KEY`). Confirm with J&T whether they want a Laravel-specific pair.

## Tests

```bash
composer install && vendor/bin/phpunit
```

## License

GPL-2.0-or-later, as the original plugin.
