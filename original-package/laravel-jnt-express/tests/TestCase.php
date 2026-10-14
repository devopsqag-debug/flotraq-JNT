<?php

namespace JntExpress\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use JntExpress\Concerns\HasJntShipping;
use JntExpress\Contracts\ShippableOrder;
use JntExpress\JntExpressServiceProvider;
use Orchestra\Testbench\TestCase as Base;

abstract class TestCase extends Base
{
    protected function getPackageProviders($app): array
    {
        return [JntExpressServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('jnt.order_model', TestOrder::class);
        $app['config']->set('jnt.routes.middleware', ['web']);
        $app['config']->set('jnt.settings', array_merge(config('jnt.settings'), [
            'vipcode' => 'VIP001', 'apikey' => 'pw', 'signkey' => 'signk', 'ratekey' => 'ratek',
            'name' => 'Shop', 'phone' => '0123456789', 'address' => '1 Jalan A', 'city' => 'KL',
            'postcode' => '50000', 'service' => '1', 'insurance' => true, 'markup' => 2,
        ]));
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function ($t) {
            $t->id();
            $t->string('shipping_name')->nullable();
            $t->string('shipping_phone')->nullable();
            $t->string('shipping_address_1')->nullable();
            $t->string('shipping_address_2')->nullable();
            $t->string('shipping_city')->nullable();
            $t->string('shipping_postcode')->nullable();
            $t->string('email')->nullable();
            $t->string('customer_note')->nullable();
            $t->decimal('subtotal', 10, 2)->default(0);
            $t->decimal('total', 10, 2)->default(0);
            $t->string('payment_method')->nullable();
            $t->string('status')->default('processing');
            $t->timestamps();
        });
        Schema::create('order_items', function ($t) {
            $t->id();
            $t->foreignId('order_id');
            $t->string('name');
            $t->integer('quantity');
            $t->decimal('weight', 8, 3)->nullable();
            $t->boolean('is_virtual')->default(false);
        });
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function makeOrder(array $attrs = [], array $items = [['Widget', 2, 0.5]]): TestOrder
    {
        $o = TestOrder::create(array_merge([
            'shipping_name' => 'Ali', 'shipping_phone' => '0111111111 / 0122222222',
            'shipping_address_1' => '12 Jalan B', 'shipping_city' => 'Penang', 'shipping_postcode' => '10000',
            'email' => 'ali@example.com', 'customer_note' => 'leave at gate',
            'subtotal' => 90, 'total' => 100, 'payment_method' => 'cod',
        ], $attrs));
        foreach ($items as [$n, $q, $w]) {
            $o->items()->create(['name' => $n, 'quantity' => $q, 'weight' => $w]);
        }

        return $o;
    }
}

class TestOrder extends Model implements ShippableOrder
{
    use HasJntShipping;

    protected $table = 'orders';
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(TestOrderItem::class, 'order_id');
    }
}

class TestOrderItem extends Model
{
    protected $table = 'order_items';
    public $timestamps = false;
    protected $guarded = [];
}
