<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API connection
    |--------------------------------------------------------------------------
    | The username / api_key pair identifies the integration (the original
    | WordPress plugin ships with WORDPRESS / WORD12). Your own VIP code,
    | API key, sign key and rate key live in "settings" below (or are saved
    | through the settings page, encrypted, in the database).
    */
    'api' => [
        'base_url'   => env('JNT_API_BASE_URL', 'https://ylstandard.jtexpress.my'),
        'username'   => env('JNT_API_USERNAME', 'WORDPRESS'),
        'api_key'    => env('JNT_API_KEY', 'WORD12'),
        'verify_ssl' => env('JNT_VERIFY_SSL', true), // original plugin disabled verification; keep true unless J&T's chain fails
        'timeout'    => env('JNT_TIMEOUT', 30),
        'endpoints'  => [
            'create'   => '/blibli/order/createOrder',
            'cancel'   => '/blibli/order/cancelOrder',
            'rate'     => '/open/api/express/getQuotedPriceByCustomer',
            'print'    => '/jandt_report_web/print/facelistAction!print.action',
            'print_a4' => '/jandt_report_web/print/A4facelistAction!print.action',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Merchant settings (defaults; the settings page overrides these)
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'enabled'   => env('JNT_ENABLED', true),
        'title'     => env('JNT_TITLE', 'J&T Express'),
        'vipcode'   => env('JNT_VIP_CODE'),
        'apikey'    => env('JNT_PASSWORD'),      // "API Key" in the plugin (sent as password)
        'signkey'   => env('JNT_SIGN_KEY'),      // signs create / cancel requests
        'ratekey'   => env('JNT_RATE_KEY'),      // signs shipping rate requests
        'name'      => env('JNT_SENDER_NAME'),
        'phone'     => env('JNT_SENDER_PHONE'),
        'address'   => env('JNT_SENDER_ADDRESS'),
        'address_2' => env('JNT_SENDER_ADDRESS_2'),
        'city'      => env('JNT_SENDER_CITY'),
        'postcode'  => env('JNT_SENDER_POSTCODE'),
        'service'   => env('JNT_SERVICE', '1'),  // 1 = PICKUP, 6 = DROPOFF
        'insurance' => env('JNT_INSURANCE', false),
        'markup'    => env('JNT_MARKUP', 0),     // added to every quoted rate
    ],

    'countries'        => ['MY'],
    'rate_cache_ttl'   => env('JNT_RATE_CACHE_TTL', 0), // seconds, 0 = no cache
    'tracking_url'     => 'https://www.jtexpress.my/tracking/',
    'auto_print'       => env('JNT_AUTO_PRINT', true), // open thermal label after bulk create
    'print_disposition'=> 'attachment',                 // or "inline"

    /*
    |--------------------------------------------------------------------------
    | Your order model
    |--------------------------------------------------------------------------
    | The model must implement JntExpress\Contracts\ShippableOrder. The easiest
    | way is `use JntExpress\Concerns\HasJntShipping;` and map your column names
    | below. Anything dot-notated works (data_get).
    */
    'order_model' => env('JNT_ORDER_MODEL', 'App\\Models\\Order'),

    'order_map' => [
        'shipping' => [
            'name' => 'shipping_name', 'phone' => 'shipping_phone',
            'address_1' => 'shipping_address_1', 'address_2' => 'shipping_address_2',
            'city' => 'shipping_city', 'postcode' => 'shipping_postcode',
        ],
        'billing' => [
            'name' => 'billing_name', 'phone' => 'billing_phone',
            'address_1' => 'billing_address_1', 'address_2' => 'billing_address_2',
            'city' => 'billing_city', 'postcode' => 'billing_postcode',
        ],
        'email'               => 'email',
        'customer_note'       => 'customer_note',
        'subtotal'            => 'subtotal',
        'total'               => 'total',
        'payment_method'      => 'payment_method',
        'cod_payment_methods' => ['cod'],      // total is collected as COD for these
        'weight_unit'         => 'kg',         // kg | g | lb | oz  (unit of item weights)
        'items' => [
            'relation' => 'items',
            'name' => 'name', 'quantity' => 'quantity',
            'weight' => 'weight', 'virtual' => 'is_virtual',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Keep your own order status column in sync (optional)
    |--------------------------------------------------------------------------
    | Set "column" to e.g. "status" and map shipment statuses to your values.
    | Leave null to only use the shipment status / events.
    */
    'order_status' => [
        'column' => env('JNT_ORDER_STATUS_COLUMN'),
        'map' => [
            'pending'          => 'jnt-pending',
            'pickup'           => 'jnt-pickup',
            'in_transit'       => 'jnt-in-transit',
            'out_for_delivery' => 'jnt-out-delivery',
            'returning'        => 'jnt-return',
            'delivered'        => 'completed',
            'returned'         => 'completed',
            'cancelled'        => 'cancelled',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer e-mails (pending pickup / picked up / out for delivery)
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => env('JNT_NOTIFICATIONS', true),
        'queue'   => env('JNT_NOTIFICATIONS_QUEUE', false),
        'on'      => ['pending', 'pickup', 'out_for_delivery'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook (J&T -> you).  POST /jnt-webhook
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'path'        => 'jnt-webhook',
        'middleware'  => [],                        // deliberately no CSRF / session
        'api_account' => env('JNT_WEBHOOK_ACCOUNT'), // if set, the apiAccount header must match
        'test_awb'    => '630002864925',             // J&T's connectivity test waybill -> always "success"
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes & UI
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'jnt',
        'name'       => 'jnt.',
        'middleware' => ['web', 'auth'],      // admin pages: shipments + settings
        'rate_middleware' => ['throttle:60,1'], // public checkout rate quote
    ],
];
