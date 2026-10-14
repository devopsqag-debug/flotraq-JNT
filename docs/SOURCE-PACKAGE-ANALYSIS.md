# Source package analysis

The supplied archive was validated with `unzip -t` and contains 77 files under `laravel-jnt-express/`. It is preserved unchanged under `original-package/laravel-jnt-express/`.

## Actual Laravel functionality

- `JntManager` exposes create, cancel, print, quote, order quote, webhook handling, and settings operations.
- `ShipmentService` builds and persists shipment records, calls the client, supports bulk create/cancel/print, and resolves shippable orders.
- `RateService` and `RateController` provide configured rate lookups; the source README notes that API failures return `null` and the HTTP endpoint returns 422.
- `JntClient` contains configured HTTP calls for order creation, cancellation, printing, and quotations. The demo intentionally does not call these endpoints.
- Shipment and event models use `jnt_shipments`, `jnt_shipment_events`, and `jnt_settings`, with a supplied migration.
- `ShipmentStatus` maps scan codes to states and the webhook processor records status events. Optional order-status mirroring and queued status e-mail listeners are included.
- Web routes cover settings, shipment listing/show/create/cancel/print, rates, and a protected webhook route. Console commands cover create, cancel, print, and quote.
- Blade views provide shipment lists/details, settings, status badges, tracking links, order actions, and mail output.

## Demo boundary

The React application implements equivalent reviewable workflows with fictional seed data and localStorage. It simulates rates, tracking events, bulk import, status updates, label printing, customer records, and reports. It does not include Laravel runtime, credentials, a database, a queue, or real J&T requests.

## License observations

The package `composer.json` declares `GPL-2.0-or-later`; that original source and its notices remain in `original-package/`. No additional package license file was present in the archive. The static demo is a separate portfolio implementation and is labeled as an independent demonstration.
