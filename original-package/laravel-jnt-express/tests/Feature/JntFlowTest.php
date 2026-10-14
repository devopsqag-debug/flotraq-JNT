<?php

namespace JntExpress\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use JntExpress\Enums\ShipmentStatus;
use JntExpress\Facades\Jnt;
use JntExpress\Mail\ShipmentStatusMail;
use JntExpress\Models\JntShipment;
use JntExpress\Tests\TestCase;

class JntFlowTest extends TestCase
{
    protected function fakeCreateOk(string $awb = '630000000001'): void
    {
        Http::fake([
            '*createOrder' => Http::response(['responseitems' => [[
                'awb_no' => $awb, 'orderid' => 'X1', 'status' => 'success', 'reason' => '', 'data' => ['code' => 'KUL-01'],
            ]]]),
            '*cancelOrder' => Http::response(['details' => [['awb_no' => $awb, 'status' => 'success', 'reason' => '']]]),
            '*facelistAction*' => Http::response('%PDF-1.4 fake'),
            '*getQuotedPriceByCustomer' => Http::response(['data' => ['totalFreight' => 8.5]]),
        ]);
    }

    public function test_create_builds_correct_payload_and_stores_shipment(): void
    {
        Mail::fake();
        $this->fakeCreateOk();
        $order = $this->makeOrder();

        $r = Jnt::create([$order->id], true)->first();

        $this->assertTrue($r['success']);
        $s = JntShipment::first();
        $this->assertSame('630000000001', $s->awb);
        $this->assertSame(ShipmentStatus::Pending, $s->status);
        $this->assertTrue($s->insurance);
        $this->assertEquals(100, $s->cod);          // COD payment method -> total
        $this->assertEquals(1.0, (float) $s->weight); // 2 x 0.5kg

        Http::assertSent(function ($req) {
            if (!str_contains($req->url(), 'createOrder')) {
                return false;
            }
            $d = $req['data_param'];
            $detail = json_decode($d, true)['detail'][0];

            return $req['data_sign'] === base64_encode(md5($d . 'signk'))
                && $detail['receiver_phone'] === '0111111111'          // first of "a / b"
                && $detail['item_name'] === '2 X Widget'
                && $detail['offerFeeFlag'] === 1
                && $detail['goodsdesc'] === '#' . 1 . ' - leave at gate'
                && $detail['shipper_addr'] === '1 Jalan A KL 50000';
        });
        Mail::assertSent(ShipmentStatusMail::class);
    }

    public function test_duplicate_order_is_skipped_and_api_errors_are_translated(): void
    {
        $this->fakeCreateOk();
        $order = $this->makeOrder();
        Jnt::create([$order]);
        $this->assertSame('Already Order', Jnt::create([$order])->first()['message']);

        Http::fake(['*createOrder' => Http::response(['x' => [['status' => 'fail', 'reason' => 'S32']]])]);
        $o2 = $this->makeOrder();
        $this->assertSame('Receiver Postcode not Exist', Jnt::create([$o2])->first()['message']);
        $this->assertSame(1, JntShipment::count());
    }

    public function test_cancel_then_reorder(): void
    {
        $this->fakeCreateOk();
        $order = $this->makeOrder();
        Jnt::create([$order]);

        $r = Jnt::cancel([JntShipment::first()])->first();
        $this->assertTrue($r['success']);
        $this->assertSame(ShipmentStatus::Cancelled, JntShipment::first()->status);

        $this->assertTrue(Jnt::create([$order])->first()['success']);
    }

    public function test_print_returns_pdf_and_rejects_non_pdf(): void
    {
        $this->fakeCreateOk();
        Jnt::create([$this->makeOrder()]);
        $this->assertStringStartsWith('%PDF', Jnt::print([JntShipment::first()], 'thermal'));

        Http::fake(['*facelistAction*' => Http::response('<html>error</html>')]);
        $this->expectException(\RuntimeException::class);
        Jnt::print([JntShipment::first()]);
    }

    public function test_quote_adds_markup_and_returns_null_on_failure(): void
    {
        $this->fakeCreateOk();
        $this->assertSame(10.5, Jnt::quote(1.2, '10000'));

        Http::fake(['*getQuotedPriceByCustomer' => Http::response(['data' => []])]);
        $this->assertNull(Jnt::quote(1.2, '10000'));
    }

    public function test_rate_endpoint(): void
    {
        $this->fakeCreateOk();
        $this->postJson('/jnt/rates', ['postcode' => '10000', 'items' => [['weight' => 0.5, 'quantity' => 2]]])
            ->assertOk()->assertJson(['available' => true, 'cost' => 10.5, 'weight_kg' => 1]);
        $this->postJson('/jnt/rates', ['postcode' => '10000', 'weight' => 1, 'country' => 'SG'])->assertStatus(422);
    }

    public function test_webhook_flow(): void
    {
        Mail::fake();
        $this->fakeCreateOk();
        Jnt::create([$this->makeOrder()]);
        $payload = fn ($code, $awb = '630000000001') => ['bizContent' => json_encode(['billCode' => $awb, 'details' => [['scanTypeCode' => $code]]])];

        // missing apiAccount header
        $this->post('/jnt-webhook', $payload(10))->assertJson(['code' => '0', 'data' => 'Invalid Account!']);
        // missing bizContent
        $this->post('/jnt-webhook', [], ['apiAccount' => 'x'])->assertJson(['data' => 'Invalid Request!']);
        // unknown awb
        $this->post('/jnt-webhook', $payload(10, '999'), ['apiAccount' => 'x'])->assertJson(['data' => 'Order Not Found!']);
        // test awb always succeeds
        $this->post('/jnt-webhook', $payload(10, '630002864925'), ['apiAccount' => 'x'])->assertJson(['code' => '1']);

        foreach ([[10, 'pickup'], [30, 'in_transit'], [94, 'out_for_delivery'], [100, 'delivered']] as [$code, $status]) {
            $this->post('/jnt-webhook', $payload($code), ['apiAccount' => 'x'])->assertJson(['code' => '1']);
            $this->assertSame($status, JntShipment::first()->status->value);
        }

        // pending + pickup + out_for_delivery e-mails (create sent the first one)
        Mail::assertSent(ShipmentStatusMail::class, 3);
        // retry of same status is idempotent
        $count = JntShipment::first()->events()->count();
        $this->post('/jnt-webhook', $payload(100), ['apiAccount' => 'x']);
        $this->assertSame($count, JntShipment::first()->events()->count());
    }

    public function test_order_status_column_sync(): void
    {
        config(['jnt.order_status.column' => 'status']);
        Mail::fake();
        $this->fakeCreateOk();
        $order = $this->makeOrder();
        Jnt::create([$order]);
        $this->assertSame('jnt-pending', $order->fresh()->status);
        $this->post('/jnt-webhook', ['bizContent' => json_encode(['billCode' => '630000000001', 'details' => [['scanTypeCode' => 100]]])], ['apiAccount' => 'x']);
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_settings_secrets_encrypted_and_kept_when_blank(): void
    {
        $this->actingAs(new \Illuminate\Foundation\Auth\User)->put('/jnt/settings', [
            'name' => 'N', 'phone' => '1', 'postcode' => '50000', 'service' => '6', 'signkey' => 'newsecret',
        ])->assertSessionHasNoErrors();

        $raw = \JntExpress\Models\JntSetting::where('key', 'signkey')->value('value');
        $this->assertNotSame('newsecret', $raw);
        $this->assertSame('newsecret', app(\JntExpress\Support\Settings::class)->get('signkey'));

        $this->put('/jnt/settings', ['name' => 'N', 'phone' => '1', 'postcode' => '50000', 'service' => '6']);
        $this->assertSame('newsecret', app(\JntExpress\Support\Settings::class)->get('signkey'));
        $this->assertSame('pw', app(\JntExpress\Support\Settings::class)->get('apikey')); // config fallback
    }

    public function test_admin_pages_and_bulk_routes(): void
    {
        Mail::fake();
        $this->fakeCreateOk();
        $order = $this->makeOrder();

        $this->post('/jnt/shipments', ['orders' => (string) $order->id])->assertSessionHas('jnt.print_url');
        $this->get('/jnt/shipments?q=630000000001')->assertOk()->assertSee('630000000001');
        $this->get('/jnt/settings')->assertOk();
        $s = JntShipment::first();
        $this->get("/jnt/shipments/{$s->id}")->assertOk();
        $this->get("/jnt/shipments/{$s->id}/print/thermal")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->post('/jnt/shipments/cancel', ['ids' => [$s->id]]);
        $this->assertSame(ShipmentStatus::Cancelled, $s->fresh()->status);
    }
}
